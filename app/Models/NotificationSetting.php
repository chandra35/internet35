<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class NotificationSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        // Email
        'email_enabled',
        'email_from_name',
        'email_from_address',
        'email_reply_to',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        // WhatsApp
        'whatsapp_enabled',
        'whatsapp_provider',
        'whatsapp_api_key',
        'whatsapp_sender',
        'whatsapp_device_id',
        // Telegram
        'telegram_enabled',
        'telegram_bot_token',
        'telegram_chat_id',
        // Templates & Events
        'templates',
        'enabled_events',
        // Schedule
        'reminder_time',
        'reminder_days_before',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'telegram_enabled' => 'boolean',
        'templates' => 'array',
        'enabled_events' => 'array',
        'reminder_days_before' => 'array',
        'smtp_port' => 'integer',
    ];

    protected $hidden = [
        'smtp_password',
        'whatsapp_api_key',
        'telegram_bot_token',
    ];

    /**
     * Get the user (admin-pop) that owns this setting
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set SMTP password with encryption
     */
    public function setSmtpPasswordAttribute($value): void
    {
        $this->attributes['smtp_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted SMTP password
     */
    public function getDecryptedSmtpPasswordAttribute(): ?string
    {
        if (!$this->attributes['smtp_password']) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->attributes['smtp_password']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set WhatsApp API key with encryption
     */
    public function setWhatsappApiKeyAttribute($value): void
    {
        $this->attributes['whatsapp_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted WhatsApp API key
     */
    public function getDecryptedWhatsappApiKeyAttribute(): ?string
    {
        if (!$this->attributes['whatsapp_api_key']) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->attributes['whatsapp_api_key']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set Telegram bot token with encryption
     */
    public function setTelegramBotTokenAttribute($value): void
    {
        $this->attributes['telegram_bot_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted Telegram bot token
     */
    public function getDecryptedTelegramBotTokenAttribute(): ?string
    {
        if (!$this->attributes['telegram_bot_token']) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->attributes['telegram_bot_token']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if event is enabled
     */
    public function isEventEnabled(string $event): bool
    {
        return in_array($event, $this->enabled_events ?? []);
    }

    /**
     * Get template for event
     */
    public function getTemplate(string $event, string $channel = 'email'): ?string
    {
        return $this->templates[$channel][$event] ?? null;
    }

    /**
     * Get or create setting for a user
     */
    public static function getOrCreateForUser(string $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_enabled' => true,
                'whatsapp_enabled' => false,
                'telegram_enabled' => false,
                'reminder_time' => '09:00',
                'reminder_days_before' => [1, 3, 7],
                'enabled_events' => self::defaultEnabledEvents(),
            ]
        );
    }

    /**
     * Default enabled events
     */
    public static function defaultEnabledEvents(): array
    {
        return [
            'invoice_created',
            'invoice_due_reminder',
            'payment_received',
            'subscription_activated',
            'subscription_expiring_soon',
        ];
    }

    /**
     * All available events
     */
    public static function availableEvents(): array
    {
        return [
            'invoice_created' => [
                'name' => 'Invoice Dibuat',
                'description' => 'Notifikasi saat invoice baru dibuat untuk pelanggan',
                'channels' => ['email', 'whatsapp', 'telegram'],
            ],
            'invoice_due_reminder' => [
                'name' => 'Reminder Jatuh Tempo',
                'description' => 'Pengingat sebelum invoice jatuh tempo',
                'channels' => ['email', 'whatsapp', 'telegram'],
            ],
            'invoice_overdue' => [
                'name' => 'Invoice Melewati Jatuh Tempo',
                'description' => 'Notifikasi saat invoice sudah melewati tanggal jatuh tempo',
                'channels' => ['email', 'whatsapp', 'telegram'],
            ],
            'payment_received' => [
                'name' => 'Pembayaran Diterima',
                'description' => 'Konfirmasi saat pembayaran berhasil diterima',
                'channels' => ['email', 'whatsapp', 'telegram'],
            ],
            'payment_failed' => [
                'name' => 'Pembayaran Gagal',
                'description' => 'Notifikasi saat pembayaran gagal diproses',
                'channels' => ['email', 'whatsapp'],
            ],
            'subscription_activated' => [
                'name' => 'Langganan Diaktifkan',
                'description' => 'Notifikasi saat langganan pelanggan diaktifkan',
                'channels' => ['email', 'whatsapp'],
            ],
            'subscription_expired' => [
                'name' => 'Langganan Berakhir',
                'description' => 'Notifikasi saat masa langganan berakhir',
                'channels' => ['email', 'whatsapp', 'telegram'],
            ],
            'subscription_expiring_soon' => [
                'name' => 'Langganan Akan Berakhir',
                'description' => 'Pengingat beberapa hari sebelum langganan berakhir',
                'channels' => ['email', 'whatsapp'],
            ],
            'customer_registered' => [
                'name' => 'Pelanggan Baru Terdaftar',
                'description' => 'Notifikasi selamat datang untuk pelanggan baru',
                'channels' => ['email', 'whatsapp'],
            ],
            'customer_suspended' => [
                'name' => 'Pelanggan Di-suspend',
                'description' => 'Notifikasi saat akun pelanggan di-suspend',
                'channels' => ['email', 'whatsapp'],
            ],
            'customer_unsuspended' => [
                'name' => 'Pelanggan Di-unsuspend',
                'description' => 'Notifikasi saat akun pelanggan diaktifkan kembali',
                'channels' => ['email', 'whatsapp'],
            ],
        ];
    }

    /**
     * Available WhatsApp providers
     */
    public static function whatsappProviders(): array
    {
        return [
            'fonnte' => [
                'name' => 'Fonnte',
                'description' => 'WhatsApp Gateway Indonesia dengan harga terjangkau',
                'url' => 'https://fonnte.com',
            ],
            'wablas' => [
                'name' => 'Wablas',
                'description' => 'WhatsApp API Gateway dengan fitur lengkap',
                'url' => 'https://wablas.com',
            ],
            'woowa' => [
                'name' => 'WooWa',
                'description' => 'WhatsApp Gateway mudah dan cepat',
                'url' => 'https://woowa.id',
            ],
            'waapi' => [
                'name' => 'WhatsApp API (Official)',
                'description' => 'Official WhatsApp Business API',
                'url' => 'https://business.whatsapp.com',
            ],
            'zenziva' => [
                'name' => 'Zenziva',
                'description' => 'SMS & WhatsApp Gateway terpercaya',
                'url' => 'https://zenziva.net',
            ],
            'dripsender' => [
                'name' => 'Dripsender',
                'description' => 'WhatsApp Marketing Automation',
                'url' => 'https://dripsender.id',
            ],
        ];
    }

    /**
     * Default templates
     */
    public static function defaultTemplates(): array
    {
        return [
            'email' => [
                'invoice_created' => "Yth. {customer_name},\n\nInvoice baru telah dibuat:\n\nNo. Invoice: {invoice_number}\nTotal: {total}\nJatuh Tempo: {due_date}\n\nSilakan lakukan pembayaran sebelum tanggal jatuh tempo.\n\nTerima kasih,\n{isp_name}",
                'payment_received' => "Yth. {customer_name},\n\nPembayaran Anda telah kami terima:\n\nNo. Invoice: {invoice_number}\nJumlah: {amount}\nMetode: {payment_method}\n\nTerima kasih atas pembayaran Anda.\n\n{isp_name}",
            ],
            'whatsapp' => [
                'invoice_created' => "Halo {customer_name}! 👋\n\nInvoice baru telah dibuat:\n📄 No: {invoice_number}\n💰 Total: {total}\n📅 Jatuh Tempo: {due_date}\n\nSilakan lakukan pembayaran sebelum jatuh tempo.\n\nTerima kasih! 🙏\n_{isp_name}_",
                'payment_received' => "Halo {customer_name}! 👋\n\n✅ Pembayaran diterima!\n\n📄 Invoice: {invoice_number}\n💰 Jumlah: {amount}\n\nTerima kasih! 🙏\n_{isp_name}_",
            ],
            'telegram' => [
                'invoice_created' => "📄 *Invoice Baru*\n\nPelanggan: {customer_name}\nNo: `{invoice_number}`\nTotal: *{total}*\nJatuh Tempo: {due_date}",
                'payment_received' => "✅ *Pembayaran Diterima*\n\nPelanggan: {customer_name}\nInvoice: `{invoice_number}`\nJumlah: *{amount}*",
            ],
        ];
    }
}
