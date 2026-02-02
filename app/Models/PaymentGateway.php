<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'gateway_type',
        'gateway_name',
        'is_active',
        'is_sandbox',
        'sort_order',
        'credentials',
        'webhook_url',
        'callback_url',
        'return_url',
        'cancel_url',
        'fee_paid_by_customer',
        'additional_fee',
        'additional_fee_percentage',
        'sandbox_status',
        'sandbox_request_date',
        'sandbox_approved_date',
        'sandbox_rejection_reason',
        'sandbox_reviewed_by',
        'verification_documents',
        'verification_notes',
        'production_status',
        'production_request_date',
        'production_approved_date',
        'total_transactions',
        'total_amount',
        'last_transaction_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
        'fee_paid_by_customer' => 'boolean',
        'additional_fee' => 'decimal:2',
        'additional_fee_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'verification_documents' => 'array',
        'sandbox_request_date' => 'datetime',
        'sandbox_approved_date' => 'datetime',
        'production_request_date' => 'datetime',
        'production_approved_date' => 'datetime',
        'last_transaction_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    /**
     * Get the user (admin-pop) that owns this gateway
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer (super admin)
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sandbox_reviewed_by');
    }

    /**
     * Set credentials with encryption
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->attributes['credentials'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted credentials
     */
    public function getDecryptedCredentialsAttribute(): ?array
    {
        if (!$this->attributes['credentials']) {
            return null;
        }
        
        try {
            $decrypted = Crypt::decryptString($this->attributes['credentials']);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get gateway display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->gateway_name ?? self::gatewayLabels()[$this->gateway_type] ?? $this->gateway_type;
    }

    /**
     * Get gateway logo URL
     */
    public function getLogoUrlAttribute(): string
    {
        return match ($this->gateway_type) {
            'midtrans' => 'https://midtrans.com/assets/images/midtrans-logo.svg',
            'duitku' => 'https://duitku.com/images/logo-duitku.png',
            'xendit' => 'https://www.xendit.co/wp-content/uploads/2020/03/XENDIT-LOGOArtboard-1-copy-6.png',
            'tripay' => 'https://tripay.co.id/images/logo-dark.png',
            'ipaymu' => 'https://ipaymu.com/wp-content/uploads/2020/11/iPay88_-_iPaymu.png',
            default => '/assets/img/payment-gateway.png',
        };
    }

    /**
     * Check if gateway requires sandbox verification
     */
    public function requiresSandboxVerification(): bool
    {
        return in_array($this->gateway_type, ['duitku']);
    }

    /**
     * Get sandbox status badge
     */
    public function getSandboxStatusBadgeAttribute(): string
    {
        return match ($this->sandbox_status) {
            'not_required' => '<span class="badge badge-light">Tidak Perlu</span>',
            'not_submitted' => '<span class="badge badge-secondary">Belum Diajukan</span>',
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'in_review' => '<span class="badge badge-info">Sedang Direview</span>',
            'approved' => '<span class="badge badge-success">Disetujui</span>',
            'rejected' => '<span class="badge badge-danger">Ditolak</span>',
            default => '<span class="badge badge-secondary">-</span>',
        };
    }

    /**
     * Get production status badge
     */
    public function getProductionStatusBadgeAttribute(): string
    {
        return match ($this->production_status) {
            'not_ready' => '<span class="badge badge-secondary">Belum Siap</span>',
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'approved' => '<span class="badge badge-success">Aktif</span>',
            'rejected' => '<span class="badge badge-danger">Ditolak</span>',
            default => '<span class="badge badge-secondary">-</span>',
        };
    }

    /**
     * Check if can enable gateway
     */
    public function canEnable(): bool
    {
        if (!$this->decrypted_credentials) {
            return false;
        }
        
        if ($this->requiresSandboxVerification() && $this->sandbox_status !== 'approved') {
            return false;
        }
        
        return true;
    }

    /**
     * Check if can go to production
     */
    public function canGoProduction(): bool
    {
        if (!$this->is_sandbox) {
            return false;
        }
        
        if ($this->requiresSandboxVerification() && $this->sandbox_status !== 'approved') {
            return false;
        }
        
        // Require at least some test transactions
        return $this->total_transactions >= 3;
    }

    /**
     * Get credential fields for each gateway type
     */
    public static function credentialFields(?string $gatewayType = null): array
    {
        $fields = [
            'midtrans' => [
                ['key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text', 'required' => true, 'placeholder' => 'G123456789'],
                ['key' => 'client_key', 'label' => 'Client Key', 'type' => 'text', 'required' => true, 'placeholder' => 'SB-Mid-client-xxx'],
                ['key' => 'server_key', 'label' => 'Server Key', 'type' => 'password', 'required' => true, 'placeholder' => 'SB-Mid-server-xxx'],
            ],
            'duitku' => [
                ['key' => 'merchant_code', 'label' => 'Merchant Code', 'type' => 'text', 'required' => true, 'placeholder' => 'D1234'],
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your API Key'],
            ],
            'xendit' => [
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true, 'placeholder' => 'xnd_development_xxx'],
                ['key' => 'public_key', 'label' => 'Public Key', 'type' => 'text', 'required' => false, 'placeholder' => 'xnd_public_xxx'],
                ['key' => 'webhook_token', 'label' => 'Webhook Verification Token', 'type' => 'password', 'required' => false, 'placeholder' => 'Optional'],
            ],
            'tripay' => [
                ['key' => 'merchant_code', 'label' => 'Merchant Code', 'type' => 'text', 'required' => true, 'placeholder' => 'T12345'],
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your API Key'],
                ['key' => 'private_key', 'label' => 'Private Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your Private Key'],
            ],
            'ipaymu' => [
                ['key' => 'virtual_account', 'label' => 'Virtual Account', 'type' => 'text', 'required' => true, 'placeholder' => '0000001234567890'],
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your API Key'],
            ],
        ];
        
        if ($gatewayType) {
            return $fields[$gatewayType] ?? [];
        }
        
        return $fields;
    }

    /**
     * Get available gateway types
     */
    public static function gatewayTypes(): array
    {
        return [
            'midtrans' => [
                'name' => 'Midtrans',
                'logo' => 'midtrans.png',
                'description' => 'Payment gateway terpopuler di Indonesia dengan berbagai metode pembayaran.',
            ],
            'duitku' => [
                'name' => 'Duitku',
                'logo' => 'duitku.png',
                'description' => 'Payment gateway dengan fee rendah, memerlukan verifikasi untuk sandbox.',
            ],
            'xendit' => [
                'name' => 'Xendit',
                'logo' => 'xendit.png',
                'description' => 'Payment gateway modern dengan API yang mudah digunakan.',
            ],
            'tripay' => [
                'name' => 'Tripay',
                'logo' => 'tripay.png',
                'description' => 'Aggregator payment gateway dengan banyak pilihan channel.',
            ],
            'ipaymu' => [
                'name' => 'iPaymu',
                'logo' => 'ipaymu.png',
                'description' => 'Payment gateway lokal dengan setup mudah.',
            ],
        ];
    }

    /**
     * Get gateway labels
     */
    public static function gatewayLabels(): array
    {
        return [
            'midtrans' => 'Midtrans',
            'duitku' => 'Duitku',
            'xendit' => 'Xendit',
            'tripay' => 'Tripay',
            'ipaymu' => 'iPaymu',
        ];
    }

    /**
     * Get gateway descriptions
     */
    public static function gatewayDescriptions(): array
    {
        return [
            'midtrans' => 'Payment gateway terpopuler di Indonesia dengan berbagai metode pembayaran.',
            'duitku' => 'Payment gateway dengan fee rendah, memerlukan verifikasi untuk sandbox.',
            'xendit' => 'Payment gateway modern dengan API yang mudah digunakan.',
            'tripay' => 'Aggregator payment gateway dengan banyak pilihan channel.',
            'ipaymu' => 'Payment gateway lokal dengan setup mudah.',
        ];
    }

    /**
     * Get verification documents required for Duitku
     */
    public static function requiredDocuments(string $gatewayType): array
    {
        return match ($gatewayType) {
            'duitku' => [
                ['name' => 'ktp', 'label' => 'KTP Pemilik/Direktur', 'required' => true],
                ['name' => 'npwp', 'label' => 'NPWP', 'required' => false],
                ['name' => 'akta', 'label' => 'Akta Perusahaan (jika PT/CV)', 'required' => false],
                ['name' => 'nib', 'label' => 'NIB/SIUP', 'required' => false],
                ['name' => 'screenshot', 'label' => 'Screenshot Website/Aplikasi', 'required' => true],
            ],
            default => [],
        };
    }
}
