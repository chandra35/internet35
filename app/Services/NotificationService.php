<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Models\PopSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class NotificationService
{
    // ─── Main Public API ───────────────────────────────────────

    /**
     * Send notification to customer using templates.
     * Automatically determines which channels are enabled and sends accordingly.
     */
    public function sendToCustomer(
        Customer $customer,
        string $templateCode,
        array $extraVariables = [],
        ?array $forceChannels = null
    ): array {
        $results = [];
        $popSetting = PopSetting::where('user_id', $customer->pop_id)->first();
        $notifSetting = NotificationSetting::where('user_id', $customer->pop_id)->first();

        if (!$popSetting) {
            return ['success' => false, 'message' => 'POP settings not found'];
        }

        // Determine active channels from settings
        $channels = $forceChannels ?? $this->getActiveChannels($popSetting, $notifSetting);

        // Check if the event is enabled
        if ($notifSetting && !$this->isEventEnabled($notifSetting, $templateCode)) {
            Log::info("Notification [{$templateCode}] skipped — event disabled for POP {$customer->pop_id}");
            return ['success' => true, 'message' => 'Event notification disabled', 'skipped' => true];
        }

        // Build variables
        $variables = $this->buildCustomerVariables($customer, $popSetting);
        $variables = array_merge($variables, $extraVariables);

        foreach ($channels as $channel) {
            $template = MessageTemplate::getTemplate($templateCode, $channel, $customer->pop_id);

            if (!$template) {
                $results[$channel] = ['success' => false, 'message' => 'Template not found'];
                continue;
            }

            $parsed = $template->parse($variables);

            if ($channel === 'email' && $customer->email) {
                $results['email'] = $this->sendEmail(
                    $customer->email,
                    $parsed['subject'],
                    $this->wrapEmailHtml($parsed['email_body'], $popSetting),
                    $popSetting,
                    $customer->pop_id,
                    $customer->id,
                    $templateCode
                );
            } elseif ($channel === 'whatsapp' && $customer->phone) {
                $results['whatsapp'] = $this->sendWhatsApp(
                    $this->formatPhoneNumber($customer->phone),
                    $parsed['wa_body'],
                    $popSetting,
                    $customer->pop_id,
                    $customer->id,
                    $templateCode
                );
            }
        }

        return $results;
    }

    /**
     * Get active channels based on settings
     */
    protected function getActiveChannels(PopSetting $popSetting, ?NotificationSetting $notifSetting): array
    {
        $channels = [];

        // Email enabled check
        $emailEnabled = $notifSetting ? $notifSetting->email_enabled : ($popSetting->smtp_enabled ?? false);
        if ($emailEnabled && $popSetting->smtp_host) {
            $channels[] = 'email';
        }

        // WhatsApp enabled check (toggle on/off)
        $waEnabled = $notifSetting ? $notifSetting->whatsapp_enabled : ($popSetting->wa_enabled ?? false);
        if ($waEnabled && ($popSetting->wa_api_url || $popSetting->wa_api_key)) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    /**
     * Check if a specific event is enabled for notifications
     */
    protected function isEventEnabled(NotificationSetting $setting, string $templateCode): bool
    {
        $enabledEvents = $setting->enabled_events ?? [];

        $eventMap = [
            'customer_welcome' => 'customer_registered',
            'user_created' => 'customer_registered',
            'invoice_created' => 'invoice_created',
            'invoice_reminder' => 'invoice_due_reminder',
            'invoice_overdue' => 'invoice_overdue',
            'payment_success' => 'payment_received',
            'service_isolated' => 'customer_suspended',
            'service_activated' => 'customer_unsuspended',
            'service_expired' => 'subscription_expired',
        ];

        $eventName = $eventMap[$templateCode] ?? $templateCode;

        if (empty($enabledEvents)) {
            return true;
        }

        return in_array($eventName, $enabledEvents);
    }

    /**
     * Build customer variables for template
     */
    protected function buildCustomerVariables(Customer $customer, PopSetting $popSetting): array
    {
        $package = $customer->package;

        return [
            'customer_name' => $customer->name,
            'customer_id' => $customer->customer_id,
            'email' => $customer->email ?? '',
            'phone' => $customer->phone ?? '',
            'package_name' => $package->name ?? '',
            'package_price' => $package ? 'Rp ' . number_format($package->price, 0, ',', '.') : '',
            'pppoe_username' => $customer->pppoe_username ?? '',
            'isp_name' => $popSetting->isp_name ?? '',
            'isp_phone' => $popSetting->isp_phone ?? '',
            'isp_email' => $popSetting->isp_email ?? '',
            'isp_address' => $popSetting->isp_address ?? '',
            'login_url' => url('/login'),
            'active_until' => $customer->active_until ? $customer->active_until->format('d F Y') : '-',
            'current_date' => now()->format('d F Y'),
            'current_time' => now()->format('H:i'),
        ];
    }

    // ─── Email Sending ─────────────────────────────────────────

    /**
     * Send email using POP's SMTP settings, with logging
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlBody,
        PopSetting $popSetting,
        ?string $popId = null,
        ?string $customerId = null,
        ?string $templateCode = null
    ): array {
        if (!$popSetting->smtp_host) {
            return $this->logAndReturn('email', $popId, $customerId, $templateCode, $to, $subject, $htmlBody, false, 'SMTP not configured');
        }

        try {
            $encryption = $popSetting->smtp_encryption ?? 'tls';
            $port = $popSetting->smtp_port ?? 587;

            $transport = new EsmtpTransport(
                $popSetting->smtp_host,
                $port,
                $encryption === 'tls'
            );

            $transport->setUsername($popSetting->smtp_username);
            $transport->setPassword($popSetting->decrypted_smtp_password);

            $mailer = new Mailer($transport);

            $fromAddress = $popSetting->smtp_from_address ?? $popSetting->smtp_username;
            $fromName = $popSetting->smtp_from_name ?? $popSetting->isp_name ?? 'Noreply';

            $email = (new Email())
                ->from(new Address($fromAddress, $fromName))
                ->to($to)
                ->subject($subject)
                ->html($htmlBody);

            $mailer->send($email);

            Log::info("Email sent to {$to}: {$subject}");

            return $this->logAndReturn('email', $popId, $customerId, $templateCode, $to, $subject, $htmlBody, true, null);

        } catch (\Exception $e) {
            Log::error("Failed to send email to {$to}: " . $e->getMessage());
            return $this->logAndReturn('email', $popId, $customerId, $templateCode, $to, $subject, $htmlBody, false, $e->getMessage());
        }
    }

    // ─── WhatsApp Sending ──────────────────────────────────────

    /**
     * Send WhatsApp message using configured API, with logging
     */
    public function sendWhatsApp(
        string $to,
        string $message,
        PopSetting $popSetting,
        ?string $popId = null,
        ?string $customerId = null,
        ?string $templateCode = null
    ): array {
        if (!$popSetting->wa_api_url && !$popSetting->wa_api_key) {
            return $this->logAndReturn('whatsapp', $popId, $customerId, $templateCode, $to, null, $message, false, 'WhatsApp API not configured');
        }

        try {
            $provider = $popSetting->wa_provider ?? 'fonnte';

            $result = match ($provider) {
                'fonnte' => $this->sendViaFonnte($to, $message, $popSetting),
                'wablas' => $this->sendViaWablas($to, $message, $popSetting),
                'dripsender' => $this->sendViaDripsender($to, $message, $popSetting),
                'custom' => $this->sendViaCustomApi($to, $message, $popSetting),
                default => $this->sendViaGenericApi($to, $message, $popSetting),
            };

            return $this->logAndReturn('whatsapp', $popId, $customerId, $templateCode, $to, null, $message, $result['success'], $result['success'] ? null : ($result['message'] ?? 'Failed'));

        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp to {$to}: " . $e->getMessage());
            return $this->logAndReturn('whatsapp', $popId, $customerId, $templateCode, $to, null, $message, false, $e->getMessage());
        }
    }

    // ─── WA Provider Methods ───────────────────────────────────

    protected function sendViaFonnte(string $to, string $message, PopSetting $popSetting): array
    {
        $response = Http::withHeaders([
            'Authorization' => $popSetting->wa_api_key,
        ])->post('https://api.fonnte.com/send', [
            'target' => $to,
            'message' => $message,
        ]);

        if ($response->successful() && $response->json('status')) {
            return ['success' => true, 'message' => 'WhatsApp sent via Fonnte'];
        }

        return ['success' => false, 'message' => $response->json('reason') ?? 'Failed to send'];
    }

    protected function sendViaWablas(string $to, string $message, PopSetting $popSetting): array
    {
        $response = Http::withHeaders([
            'Authorization' => $popSetting->wa_api_key,
        ])->post($popSetting->wa_api_url . '/api/send-message', [
            'phone' => $to,
            'message' => $message,
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp sent via Wablas'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? 'Failed to send'];
    }

    protected function sendViaDripsender(string $to, string $message, PopSetting $popSetting): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $popSetting->wa_api_key,
        ])->post($popSetting->wa_api_url . '/send', [
            'phone' => $to,
            'text' => $message,
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp sent via Dripsender'];
        }

        return ['success' => false, 'message' => $response->json('error') ?? 'Failed to send'];
    }

    protected function sendViaCustomApi(string $to, string $message, PopSetting $popSetting): array
    {
        $headers = [];
        if ($popSetting->wa_api_key) {
            $headers['Authorization'] = $popSetting->wa_api_key;
        }

        $url = str_replace(
            ['{{phone}}', '{{message}}'],
            [urlencode($to), urlencode($message)],
            $popSetting->wa_api_url
        );

        $body = [
            $popSetting->wa_phone_field ?? 'phone' => $to,
            $popSetting->wa_message_field ?? 'message' => $message,
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp sent via Custom API'];
        }

        return ['success' => false, 'message' => 'Failed to send: ' . $response->body()];
    }

    protected function sendViaGenericApi(string $to, string $message, PopSetting $popSetting): array
    {
        $headers = [];
        if ($popSetting->wa_api_key) {
            $headers['Authorization'] = $popSetting->wa_api_key;
        }

        $response = Http::withHeaders($headers)->post($popSetting->wa_api_url, [
            'phone' => $to,
            'message' => $message,
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp sent'];
        }

        return ['success' => false, 'message' => 'Failed to send: ' . $response->body()];
    }

    // ─── Logging ───────────────────────────────────────────────

    /**
     * Log notification and return result
     */
    protected function logAndReturn(
        string $channel,
        ?string $popId,
        ?string $customerId,
        ?string $templateCode,
        string $recipient,
        ?string $subject,
        ?string $body,
        bool $success,
        ?string $errorMessage
    ): array {
        if ($popId) {
            try {
                NotificationLog::create([
                    'pop_id' => $popId,
                    'customer_id' => $customerId,
                    'channel' => $channel,
                    'template_code' => $templateCode,
                    'recipient' => $recipient,
                    'subject' => $subject,
                    'body' => mb_substr($body ?? '', 0, 65535),
                    'status' => $success ? 'sent' : 'failed',
                    'error_message' => $errorMessage,
                    'sent_at' => $success ? now() : null,
                    'metadata' => [
                        'channel' => $channel,
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to log notification: " . $e->getMessage());
            }
        }

        return [
            'success' => $success,
            'message' => $success
                ? ($channel === 'email' ? 'Email terkirim' : 'WhatsApp terkirim')
                : ($errorMessage ?? 'Gagal mengirim'),
        ];
    }

    // ─── Email HTML Wrapper ────────────────────────────────────

    /**
     * Wrap plain email body with a nice HTML template
     */
    public function wrapEmailHtml(string $body, ?PopSetting $popSetting = null): string
    {
        $ispName = $popSetting->isp_name ?? 'ISP';
        $primaryColor = '#4e73df';

        if (str_contains($body, '<html') || str_contains($body, '<!DOCTYPE')) {
            return $body;
        }

        if (!str_contains($body, '<p>') && !str_contains($body, '<div>') && !str_contains($body, '<table')) {
            $body = nl2br(e($body));
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
  .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
  .header { background: {$primaryColor}; color: white; padding: 25px 30px; border-radius: 8px 8px 0 0; text-align: center; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
  .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; line-height: 1.7; color: #333; font-size: 14px; }
  .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; }
  .footer a { color: {$primaryColor}; text-decoration: none; }
  code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
  .btn { display: inline-block; padding: 12px 28px; background: {$primaryColor}; color: white !important; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 10px 0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{$ispName}</h1>
  </div>
  <div class="content">
    {$body}
  </div>
  <div class="footer">
    <p>&copy; {$ispName} &mdash; Email ini dikirim secara otomatis.</p>
    <p>Jika Anda merasa tidak seharusnya menerima email ini, silakan abaikan.</p>
  </div>
</div>
</body>
</html>
HTML;
    }

    // ─── Utility Methods ───────────────────────────────────────

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    // ─── Convenience Methods ───────────────────────────────────

    public function sendWelcome(Customer $customer, array $extra = []): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_CUSTOMER_WELCOME, $extra);
    }

    public function sendInvoiceCreated(Customer $customer, array $invoiceData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_INVOICE_CREATED, $invoiceData);
    }

    public function sendInvoiceReminder(Customer $customer, array $invoiceData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_INVOICE_REMINDER, $invoiceData);
    }

    public function sendOverdue(Customer $customer, array $invoiceData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_INVOICE_OVERDUE, $invoiceData);
    }

    public function sendPaymentSuccess(Customer $customer, array $paymentData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_PAYMENT_SUCCESS, $paymentData);
    }

    public function sendIsolated(Customer $customer, array $data = []): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_SERVICE_ISOLATED, $data);
    }

    public function sendActivated(Customer $customer, array $data = []): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_SERVICE_ACTIVATED, $data);
    }

    public function sendExpired(Customer $customer, array $data = []): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_SERVICE_EXPIRED, $data);
    }
}
