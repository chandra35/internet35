<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationSettingController extends Controller
{
    protected ActivityLogger $activityLog;

    public function __construct(ActivityLogger $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get target user ID
     */
    protected function getTargetUserId(?string $userId = null): string
    {
        $user = auth()->user();
        
        if ($userId && $user->hasRole('superadmin')) {
            return $userId;
        }
        
        return $user->id;
    }

    /**
     * Check if SuperAdmin is trying to access without user_id
     */
    protected function requireUserIdForSuperAdmin(Request $request): bool
    {
        $user = auth()->user();
        $userId = $request->query('user_id');
        
        return $user->hasRole('superadmin') && !$userId;
    }

    /**
     * Show notification settings
     */
    public function index(Request $request)
    {
        // SuperAdmin without user_id should go to monitoring
        if ($this->requireUserIdForSuperAdmin($request)) {
            return redirect()->route('admin.pop-settings.monitoring')
                ->with('info', 'Silakan pilih Admin POP yang ingin Anda kelola.');
        }
        
        $userId = $request->query('user_id');
        $targetUserId = $this->getTargetUserId($userId);
        
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);
        $availableEvents = NotificationSetting::availableEvents();
        $whatsappProviders = NotificationSetting::whatsappProviders();
        $defaultTemplates = NotificationSetting::defaultTemplates();
        
        // For superadmin
        $popUsers = null;
        if (auth()->user()->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        return view('admin.pop-settings.notification-settings', compact(
            'setting',
            'availableEvents',
            'whatsappProviders',
            'defaultTemplates',
            'popUsers',
            'userId'
        ));
    }

    /**
     * Update email settings
     */
    public function updateEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'email_enabled' => 'boolean',
            'email_from_name' => 'nullable|string|max:100',
            'email_from_address' => 'nullable|email|max:255',
            'email_reply_to' => 'nullable|email|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);
        
        $oldData = $setting->toArray();

        $setting->email_enabled = $request->boolean('email_enabled');
        $setting->email_from_name = $request->email_from_name;
        $setting->email_from_address = $request->email_from_address;
        $setting->email_reply_to = $request->email_reply_to;
        $setting->smtp_host = $request->smtp_host;
        $setting->smtp_port = $request->smtp_port;
        $setting->smtp_username = $request->smtp_username;
        
        if ($request->filled('smtp_password')) {
            $setting->smtp_password = $request->smtp_password;
        }
        
        $setting->smtp_encryption = $request->smtp_encryption;
        $setting->save();

        $this->activityLog->logUpdate('notification_settings', "Updated email settings");

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan email berhasil disimpan!',
        ]);
    }

    /**
     * Update WhatsApp settings
     */
    public function updateWhatsapp(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'whatsapp_enabled' => 'boolean',
            'whatsapp_provider' => 'nullable|string|in:' . implode(',', array_keys(NotificationSetting::whatsappProviders())),
            'whatsapp_api_key' => 'nullable|string|max:255',
            'whatsapp_sender' => 'nullable|string|max:20',
            'whatsapp_device_id' => 'nullable|string|max:100',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        $setting->whatsapp_enabled = $request->boolean('whatsapp_enabled');
        $setting->whatsapp_provider = $request->whatsapp_provider;
        $setting->whatsapp_sender = $request->whatsapp_sender;
        $setting->whatsapp_device_id = $request->whatsapp_device_id;
        
        if ($request->filled('whatsapp_api_key')) {
            $setting->whatsapp_api_key = $request->whatsapp_api_key;
        }
        
        $setting->save();

        $this->activityLog->logUpdate('notification_settings', "Updated WhatsApp settings");

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan WhatsApp berhasil disimpan!',
        ]);
    }

    /**
     * Update Telegram settings
     */
    public function updateTelegram(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'telegram_enabled' => 'boolean',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:100',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        $setting->telegram_enabled = $request->boolean('telegram_enabled');
        $setting->telegram_chat_id = $request->telegram_chat_id;
        
        if ($request->filled('telegram_bot_token')) {
            $setting->telegram_bot_token = $request->telegram_bot_token;
        }
        
        $setting->save();

        $this->activityLog->logUpdate('notification_settings', "Updated Telegram settings");

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan Telegram berhasil disimpan!',
        ]);
    }

    /**
     * Update enabled events
     */
    public function updateEvents(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'enabled_events' => 'array',
            'enabled_events.*' => 'string|in:' . implode(',', array_keys(NotificationSetting::availableEvents())),
            'reminder_time' => 'nullable|date_format:H:i',
            'reminder_days_before' => 'array',
            'reminder_days_before.*' => 'integer|min:1|max:30',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        $setting->enabled_events = $request->enabled_events ?? [];
        $setting->reminder_time = $request->reminder_time ?? '09:00';
        $setting->reminder_days_before = $request->reminder_days_before ?? [1, 3, 7];
        $setting->save();

        $this->activityLog->logUpdate('notification_settings', "Updated notification events");

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan event notifikasi berhasil disimpan!',
        ]);
    }

    /**
     * Update templates
     */
    public function updateTemplates(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'templates' => 'array',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        $setting->templates = $request->templates ?? [];
        $setting->save();

        $this->activityLog->logUpdate('notification_settings', "Updated notification templates");

        return response()->json([
            'success' => true,
            'message' => 'Template notifikasi berhasil disimpan!',
        ]);
    }

    /**
     * Reset templates to default
     */
    public function resetTemplates(Request $request)
    {
        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        $setting->templates = NotificationSetting::defaultTemplates();
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil direset ke default!',
            'templates' => $setting->templates,
        ]);
    }

    /**
     * Test email connection
     */
    public function testEmail(Request $request)
    {
        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        if (!$setting->smtp_host || !$setting->smtp_username) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi SMTP belum lengkap!',
            ]);
        }

        try {
            // Create temporary mailer config
            config([
                'mail.mailers.test_smtp' => [
                    'transport' => 'smtp',
                    'host' => $setting->smtp_host,
                    'port' => $setting->smtp_port,
                    'encryption' => $setting->smtp_encryption,
                    'username' => $setting->smtp_username,
                    'password' => $setting->decrypted_smtp_password,
                ],
            ]);

            \Illuminate\Support\Facades\Mail::mailer('test_smtp')
                ->raw('Test email dari Internet35 - Konfigurasi email berhasil!', function ($message) use ($setting) {
                    $message->to($setting->email_from_address ?? auth()->user()->email)
                        ->subject('Test Email - Internet35');
                });

            return response()->json([
                'success' => true,
                'message' => 'Email test berhasil dikirim!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Test WhatsApp connection
     */
    public function testWhatsapp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        if (!$setting->whatsapp_provider || !$setting->decrypted_whatsapp_api_key) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi WhatsApp belum lengkap!',
            ]);
        }

        try {
            $result = $this->sendWhatsappTest($setting, $request->phone);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim WhatsApp: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Send WhatsApp test based on provider
     */
    protected function sendWhatsappTest(NotificationSetting $setting, string $phone): array
    {
        $message = "Test WhatsApp dari Internet35 - Konfigurasi berhasil! 🎉";
        $apiKey = $setting->decrypted_whatsapp_api_key;

        return match ($setting->whatsapp_provider) {
            'fonnte' => $this->sendViaFonnte($apiKey, $phone, $message),
            'wablas' => $this->sendViaWablas($apiKey, $setting->whatsapp_sender, $phone, $message),
            'woowa' => $this->sendViaWoowa($apiKey, $phone, $message),
            'dripsender' => $this->sendViaDripsender($apiKey, $phone, $message),
            default => ['success' => false, 'message' => 'Provider tidak didukung'],
        };
    }

    protected function sendViaFonnte(string $apiKey, string $phone, string $message): array
    {
        $response = Http::withHeaders(['Authorization' => $apiKey])
            ->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
            ]);

        if ($response->successful() && $response->json('status')) {
            return ['success' => true, 'message' => 'WhatsApp test berhasil dikirim!'];
        }

        return ['success' => false, 'message' => $response->json('reason') ?? 'Gagal mengirim'];
    }

    protected function sendViaWablas(string $apiKey, ?string $sender, string $phone, string $message): array
    {
        $response = Http::withHeaders(['Authorization' => $apiKey])
            ->post('https://pati.wablas.com/api/send-message', [
                'phone' => $phone,
                'message' => $message,
            ]);

        if ($response->successful() && $response->json('status')) {
            return ['success' => true, 'message' => 'WhatsApp test berhasil dikirim!'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? 'Gagal mengirim'];
    }

    protected function sendViaWoowa(string $apiKey, string $phone, string $message): array
    {
        $response = Http::post("https://api.wa.my.id/api/{$apiKey}/text", [
            'number' => $phone,
            'text' => $message,
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp test berhasil dikirim!'];
        }

        return ['success' => false, 'message' => 'Gagal mengirim'];
    }

    protected function sendViaDripsender(string $apiKey, string $phone, string $message): array
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->post('https://api.dripsender.id/send', [
                'phone' => $phone,
                'text' => $message,
            ]);

        if ($response->successful() && $response->json('success')) {
            return ['success' => true, 'message' => 'WhatsApp test berhasil dikirim!'];
        }

        return ['success' => false, 'message' => $response->json('message') ?? 'Gagal mengirim'];
    }

    /**
     * Test Telegram connection
     */
    public function testTelegram(Request $request)
    {
        $targetUserId = $this->getTargetUserId($request->user_id);
        $setting = NotificationSetting::getOrCreateForUser($targetUserId);

        if (!$setting->decrypted_telegram_bot_token || !$setting->telegram_chat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Telegram belum lengkap!',
            ]);
        }

        try {
            $botToken = $setting->decrypted_telegram_bot_token;
            $chatId = $setting->telegram_chat_id;
            $message = "✅ *Test Telegram dari Internet35*\n\nKonfigurasi berhasil! 🎉";

            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful() && $response->json('ok')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Telegram test berhasil dikirim!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description') ?? 'Gagal mengirim',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim Telegram: ' . $e->getMessage(),
            ]);
        }
    }
}
