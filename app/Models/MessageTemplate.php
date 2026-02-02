<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pop_id',
        'code',
        'name',
        'description',
        'channel',
        'email_subject',
        'email_body',
        'wa_body',
        'is_active',
        'is_default',
        'available_variables',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'available_variables' => 'array',
    ];

    /**
     * Template codes
     */
    public const CODE_CUSTOMER_WELCOME = 'customer_welcome';
    public const CODE_USER_CREATED = 'user_created';
    public const CODE_FORGOT_PASSWORD = 'forgot_password';
    public const CODE_INVOICE_CREATED = 'invoice_created';
    public const CODE_INVOICE_REMINDER = 'invoice_reminder';
    public const CODE_INVOICE_OVERDUE = 'invoice_overdue';
    public const CODE_PAYMENT_SUCCESS = 'payment_success';
    public const CODE_SERVICE_ISOLATED = 'service_isolated';
    public const CODE_SERVICE_ACTIVATED = 'service_activated';
    public const CODE_SERVICE_EXPIRED = 'service_expired';

    /**
     * Get all available template codes with info
     */
    public static function templateCodes(): array
    {
        return [
            self::CODE_CUSTOMER_WELCOME => [
                'name' => 'Selamat Datang Pelanggan',
                'description' => 'Dikirim saat pelanggan baru didaftarkan',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan (untuk login portal)',
                    'email' => 'Email pelanggan',
                    'phone' => 'No. telepon',
                    'package_name' => 'Nama paket',
                    'package_price' => 'Harga paket',
                    'password' => 'Password (untuk login portal)',
                    'isp_name' => 'Nama ISP',
                    'isp_phone' => 'Telepon ISP',
                    'isp_email' => 'Email ISP',
                    'isp_address' => 'Alamat ISP',
                    'login_url' => 'URL Login Portal',
                ],
            ],
            self::CODE_USER_CREATED => [
                'name' => 'Akun Portal Dibuat',
                'description' => 'Dikirim saat akun portal pelanggan dibuat',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'email' => 'Email pelanggan',
                    'password' => 'Password sementara',
                    'login_url' => 'URL Login Portal',
                    'isp_name' => 'Nama ISP',
                ],
            ],
            self::CODE_FORGOT_PASSWORD => [
                'name' => 'Reset Password',
                'description' => 'Dikirim saat pelanggan request reset password',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'reset_url' => 'URL Reset Password',
                    'expire_minutes' => 'Waktu kadaluarsa (menit)',
                    'isp_name' => 'Nama ISP',
                ],
            ],
            self::CODE_INVOICE_CREATED => [
                'name' => 'Invoice Dibuat',
                'description' => 'Dikirim saat invoice baru dibuat',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'invoice_number' => 'Nomor invoice',
                    'invoice_date' => 'Tanggal invoice',
                    'due_date' => 'Tanggal jatuh tempo',
                    'amount' => 'Jumlah tagihan',
                    'package_name' => 'Nama paket',
                    'period' => 'Periode tagihan',
                    'payment_url' => 'URL Pembayaran',
                    'isp_name' => 'Nama ISP',
                    'isp_phone' => 'Telepon ISP',
                    'bank_accounts' => 'Daftar rekening bank',
                ],
            ],
            self::CODE_INVOICE_REMINDER => [
                'name' => 'Pengingat Jatuh Tempo',
                'description' => 'Dikirim H-3, H-1 sebelum jatuh tempo',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'invoice_number' => 'Nomor invoice',
                    'due_date' => 'Tanggal jatuh tempo',
                    'days_left' => 'Sisa hari',
                    'amount' => 'Jumlah tagihan',
                    'payment_url' => 'URL Pembayaran',
                    'isp_name' => 'Nama ISP',
                    'isp_phone' => 'Telepon ISP',
                ],
            ],
            self::CODE_INVOICE_OVERDUE => [
                'name' => 'Tagihan Terlambat',
                'description' => 'Dikirim saat tagihan melewati jatuh tempo',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'invoice_number' => 'Nomor invoice',
                    'due_date' => 'Tanggal jatuh tempo',
                    'days_overdue' => 'Hari keterlambatan',
                    'amount' => 'Jumlah tagihan',
                    'late_fee' => 'Denda keterlambatan',
                    'total_amount' => 'Total yang harus dibayar',
                    'payment_url' => 'URL Pembayaran',
                    'isp_name' => 'Nama ISP',
                    'isp_phone' => 'Telepon ISP',
                ],
            ],
            self::CODE_PAYMENT_SUCCESS => [
                'name' => 'Pembayaran Berhasil',
                'description' => 'Dikirim setelah pembayaran dikonfirmasi',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'invoice_number' => 'Nomor invoice',
                    'payment_date' => 'Tanggal pembayaran',
                    'amount' => 'Jumlah pembayaran',
                    'payment_method' => 'Metode pembayaran',
                    'active_until' => 'Aktif hingga',
                    'isp_name' => 'Nama ISP',
                ],
            ],
            self::CODE_SERVICE_ISOLATED => [
                'name' => 'Layanan Diisolir',
                'description' => 'Dikirim saat layanan pelanggan diisolir',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'isolate_reason' => 'Alasan isolir',
                    'invoice_number' => 'Nomor invoice tertunggak',
                    'amount' => 'Jumlah tagihan',
                    'payment_url' => 'URL Pembayaran',
                    'isp_name' => 'Nama ISP',
                    'isp_phone' => 'Telepon ISP',
                ],
            ],
            self::CODE_SERVICE_ACTIVATED => [
                'name' => 'Layanan Diaktifkan',
                'description' => 'Dikirim saat layanan diaktifkan kembali',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'package_name' => 'Nama paket',
                    'active_until' => 'Aktif hingga',
                    'isp_name' => 'Nama ISP',
                ],
            ],
            self::CODE_SERVICE_EXPIRED => [
                'name' => 'Layanan Kedaluwarsa',
                'description' => 'Dikirim saat masa aktif layanan habis',
                'variables' => [
                    'customer_name' => 'Nama pelanggan',
                    'customer_id' => 'ID Pelanggan',
                    'expired_date' => 'Tanggal kedaluwarsa',
                    'package_name' => 'Nama paket',
                    'renewal_url' => 'URL Perpanjangan',
                    'isp_name' => 'Nama ISP',
                    'isp_phone' => 'Telepon ISP',
                ],
            ],
        ];
    }

    /**
     * Get template info by code
     */
    public static function getTemplateInfo(string $code): ?array
    {
        return self::templateCodes()[$code] ?? null;
    }

    /**
     * Relationship to POP
     */
    public function pop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific channel
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for email templates
     */
    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }

    /**
     * Scope for WhatsApp templates
     */
    public function scopeWhatsapp($query)
    {
        return $query->where('channel', 'whatsapp');
    }

    /**
     * Get template for specific POP and code
     * Falls back to global template if POP-specific doesn't exist
     */
    public static function getTemplate(string $code, string $channel, ?string $popId = null): ?self
    {
        // First try to find POP-specific template
        if ($popId) {
            $template = self::where('code', $code)
                ->where('channel', $channel)
                ->where('pop_id', $popId)
                ->active()
                ->first();
            
            if ($template) {
                return $template;
            }
        }

        // Fall back to global/default template
        return self::where('code', $code)
            ->where('channel', $channel)
            ->whereNull('pop_id')
            ->active()
            ->first();
    }

    /**
     * Parse template with variables
     */
    public function parse(array $variables): array
    {
        $subject = $this->email_subject;
        $emailBody = $this->email_body;
        $waBody = $this->wa_body;

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value ?? '', $subject ?? '');
            $emailBody = str_replace($placeholder, $value ?? '', $emailBody ?? '');
            $waBody = str_replace($placeholder, $value ?? '', $waBody ?? '');
        }

        return [
            'subject' => $subject,
            'email_body' => $emailBody,
            'wa_body' => $waBody,
        ];
    }

    /**
     * Get channel badge color
     */
    public function getChannelBadgeAttribute(): string
    {
        return match($this->channel) {
            'email' => 'primary',
            'whatsapp' => 'success',
            'sms' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get channel icon
     */
    public function getChannelIconAttribute(): string
    {
        return match($this->channel) {
            'email' => 'fas fa-envelope',
            'whatsapp' => 'fab fa-whatsapp',
            'sms' => 'fas fa-sms',
            default => 'fas fa-comment',
        };
    }
}
