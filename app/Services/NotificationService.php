<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\PopSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class NotificationService
{
    /**
     * Send notification to customer
     */
    public function sendToCustomer(
        Customer $customer,
        string $templateCode,
        array $extraVariables = [],
        array $channels = ['email', 'whatsapp']
    ): array {
        $results = [];
        $popSetting = PopSetting::where('user_id', $customer->pop_id)->first();

        if (!$popSetting) {
            return ['success' => false, 'message' => 'POP settings not found'];
        }

        // Build base variables
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
                    $parsed['email_body'],
                    $popSetting
                );
            } elseif ($channel === 'whatsapp' && $customer->phone) {
                $results['whatsapp'] = $this->sendWhatsApp(
                    $this->formatPhoneNumber($customer->phone),
                    $parsed['wa_body'],
                    $popSetting
                );
            }
        }

        return $results;
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
            'pppoe_password' => $customer->decrypted_pppoe_password ?? '',
            'isp_name' => $popSetting->isp_name ?? '',
            'isp_phone' => $popSetting->isp_phone ?? '',
            'isp_email' => $popSetting->isp_email ?? '',
            'isp_address' => $popSetting->isp_address ?? '',
            'login_url' => url('/login'),
            'active_until' => $customer->active_until ? $customer->active_until->format('d F Y') : '-',
        ];
    }

    /**
     * Send email using POP's SMTP settings
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlBody,
        PopSetting $popSetting
    ): array {
        if (!$popSetting->smtp_host) {
            return ['success' => false, 'message' => 'SMTP not configured'];
        }

        try {
            // Create transport with POP's SMTP settings
            $transport = new EsmtpTransport(
                $popSetting->smtp_host,
                $popSetting->smtp_port ?? 587,
                $popSetting->smtp_encryption === 'tls'
            );
            
            $transport->setUsername($popSetting->smtp_username);
            $transport->setPassword($popSetting->decrypted_smtp_password);

            $mailer = new Mailer($transport);

            // Create email
            $email = (new Email())
                ->from($popSetting->smtp_from_address ?? $popSetting->smtp_username)
                ->to($to)
                ->subject($subject)
                ->html($htmlBody);

            // Add from name if set
            if ($popSetting->smtp_from_name) {
                $email->from(new \Symfony\Component\Mime\Address(
                    $popSetting->smtp_from_address ?? $popSetting->smtp_username,
                    $popSetting->smtp_from_name
                ));
            }

            $mailer->send($email);

            Log::info("Email sent to {$to}: {$subject}");

            return ['success' => true, 'message' => 'Email sent successfully'];

        } catch (\Exception $e) {
            Log::error("Failed to send email to {$to}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp message using configured API
     */
    public function sendWhatsApp(
        string $to,
        string $message,
        PopSetting $popSetting
    ): array {
        if (!$popSetting->wa_api_url) {
            return ['success' => false, 'message' => 'WhatsApp API not configured'];
        }

        try {
            // Build request based on WA provider
            $provider = $popSetting->wa_provider ?? 'fonnte';
            
            switch ($provider) {
                case 'fonnte':
                    return $this->sendViaFonnte($to, $message, $popSetting);
                case 'wablas':
                    return $this->sendViaWablas($to, $message, $popSetting);
                case 'dripsender':
                    return $this->sendViaDripsender($to, $message, $popSetting);
                case 'custom':
                    return $this->sendViaCustomApi($to, $message, $popSetting);
                default:
                    return $this->sendViaGenericApi($to, $message, $popSetting);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp to {$to}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send via Fonnte API
     */
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

    /**
     * Send via Wablas API
     */
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

    /**
     * Send via Dripsender API
     */
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

    /**
     * Send via custom API (user configurable)
     */
    protected function sendViaCustomApi(string $to, string $message, PopSetting $popSetting): array
    {
        $headers = [];
        if ($popSetting->wa_api_key) {
            $headers['Authorization'] = $popSetting->wa_api_key;
        }

        // Replace placeholders in URL if any
        $url = str_replace(
            ['{{phone}}', '{{message}}'],
            [urlencode($to), urlencode($message)],
            $popSetting->wa_api_url
        );

        // Build body based on configured field names
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

    /**
     * Send via generic API (fallback)
     */
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

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 08xx to 628xx
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Add 62 if not present
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Send welcome notification to new customer
     */
    public function sendWelcome(Customer $customer): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_CUSTOMER_WELCOME);
    }

    /**
     * Send invoice created notification
     */
    public function sendInvoiceCreated(Customer $customer, array $invoiceData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_INVOICE_CREATED, $invoiceData);
    }

    /**
     * Send invoice reminder notification
     */
    public function sendInvoiceReminder(Customer $customer, array $invoiceData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_INVOICE_REMINDER, $invoiceData);
    }

    /**
     * Send overdue notification
     */
    public function sendOverdue(Customer $customer, array $invoiceData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_INVOICE_OVERDUE, $invoiceData);
    }

    /**
     * Send payment success notification
     */
    public function sendPaymentSuccess(Customer $customer, array $paymentData): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_PAYMENT_SUCCESS, $paymentData);
    }

    /**
     * Send isolation notification
     */
    public function sendIsolated(Customer $customer, array $data = []): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_SERVICE_ISOLATED, $data);
    }

    /**
     * Send activation notification
     */
    public function sendActivated(Customer $customer): array
    {
        return $this->sendToCustomer($customer, MessageTemplate::CODE_SERVICE_ACTIVATED);
    }
}
