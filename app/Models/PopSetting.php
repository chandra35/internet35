<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class PopSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        // ISP Info
        'isp_name',
        'isp_tagline',
        'isp_logo',
        'isp_logo_dark',
        'isp_favicon',
        // POP Info
        'pop_name',
        'pop_code',
        'pop_prefix',
        // Mikrotik sync settings
        'mikrotik_sync_enabled',
        'mikrotik_auto_sync',
        // Radius settings
        'radius_enabled',
        'radius_host',
        'radius_port',
        'radius_database',
        'radius_username',
        'radius_password',
        'radius_nas_ip',
        'radius_nas_secret',
        'radius_coa_port',
        'radius_auto_sync',
        // Address
        'address',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'postal_code',
        'latitude',
        'longitude',
        // Contact
        'phone',
        'phone_secondary',
        'email',
        'email_billing',
        'email_support',
        'website',
        'whatsapp',
        'telegram',
        'instagram',
        'facebook',
        // Invoice
        'invoice_prefix',
        'invoice_due_days',
        'invoice_notes',
        'invoice_footer',
        'invoice_terms',
        'bank_accounts',
        // Tax
        'ppn_enabled',
        'ppn_percentage',
        'ppn_method',
        'ppn_display',
        'npwp',
        'npwp_name',
        'npwp_address',
        // Business
        'business_name',
        'business_type',
        'nib',
        'isp_license_number',
        'business_permit_number',
        'business_permit_date',
        // SMTP Settings
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        // WhatsApp Gateway
        'wa_enabled',
        'wa_provider',
        'wa_api_url',
        'wa_api_key',
        'wa_sender_number',
        // SMS Gateway
        'sms_enabled',
        'sms_provider',
        'sms_api_url',
        'sms_api_key',
        'sms_sender_id',
    ];

    protected $casts = [
        'ppn_enabled' => 'boolean',
        'ppn_percentage' => 'decimal:2',
        'invoice_due_days' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'business_permit_date' => 'date',
        'bank_accounts' => 'array',
        'mikrotik_sync_enabled' => 'boolean',
        'mikrotik_auto_sync' => 'boolean',
        'radius_enabled' => 'boolean',
        'radius_auto_sync' => 'boolean',
        'radius_port' => 'integer',
        'radius_coa_port' => 'integer',
        'smtp_enabled' => 'boolean',
        'smtp_port' => 'integer',
        'wa_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
    ];

    /**
     * Set radius password with encryption
     */
    public function setRadiusPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['radius_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Set radius NAS secret with encryption
     */
    public function setRadiusNasSecretAttribute($value): void
    {
        if ($value) {
            $this->attributes['radius_nas_secret'] = Crypt::encryptString($value);
        }
    }

    /**
     * Set SMTP password with encryption
     */
    public function setSmtpPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['smtp_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Set WhatsApp API key with encryption
     */
    public function setWaApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['wa_api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Set SMS API key with encryption
     */
    public function setSmsApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['sms_api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get decrypted radius password
     */
    public function getDecryptedRadiusPasswordAttribute(): ?string
    {
        if ($this->radius_password) {
            try {
                return Crypt::decryptString($this->radius_password);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get decrypted radius NAS secret
     */
    public function getDecryptedRadiusNasSecretAttribute(): ?string
    {
        if ($this->radius_nas_secret) {
            try {
                return Crypt::decryptString($this->radius_nas_secret);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get decrypted SMTP password
     */
    public function getDecryptedSmtpPasswordAttribute(): ?string
    {
        if ($this->smtp_password) {
            try {
                return Crypt::decryptString($this->smtp_password);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get decrypted WhatsApp API key
     */
    public function getDecryptedWaApiKeyAttribute(): ?string
    {
        if ($this->wa_api_key) {
            try {
                return Crypt::decryptString($this->wa_api_key);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get decrypted SMS API key
     */
    public function getDecryptedSmsApiKeyAttribute(): ?string
    {
        if ($this->sms_api_key) {
            try {
                return Crypt::decryptString($this->sms_api_key);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get the user (admin-pop) that owns this setting
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get province
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Province::class, 'province_code', 'code');
    }

    /**
     * Get city
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\City::class, 'city_code', 'code');
    }

    /**
     * Get district
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\District::class, 'district_code', 'code');
    }

    /**
     * Get village
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Village::class, 'village_code', 'code');
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->isp_logo) {
            return Storage::url($this->isp_logo);
        }
        return null;
    }

    /**
     * Get dark logo URL
     */
    public function getLogoDarkUrlAttribute(): ?string
    {
        if ($this->isp_logo_dark) {
            return Storage::url($this->isp_logo_dark);
        }
        return $this->logo_url;
    }

    /**
     * Get favicon URL
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if ($this->isp_favicon) {
            return Storage::url($this->isp_favicon);
        }
        return null;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->village?->name,
            $this->district?->name,
            $this->city?->name,
            $this->province?->name,
            $this->postal_code,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get formatted NPWP
     */
    public function getFormattedNpwpAttribute(): ?string
    {
        if (!$this->npwp) {
            return null;
        }
        
        $npwp = preg_replace('/[^0-9]/', '', $this->npwp);
        if (strlen($npwp) !== 15) {
            return $this->npwp;
        }
        
        return sprintf(
            '%s.%s.%s.%s-%s.%s',
            substr($npwp, 0, 2),
            substr($npwp, 2, 3),
            substr($npwp, 5, 3),
            substr($npwp, 8, 1),
            substr($npwp, 9, 3),
            substr($npwp, 12, 3)
        );
    }

    /**
     * Get formatted WhatsApp link
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        if (!$this->whatsapp) {
            return null;
        }
        
        $number = preg_replace('/[^0-9]/', '', $this->whatsapp);
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        
        return "https://wa.me/{$number}";
    }

    /**
     * Calculate PPN amount
     */
    public function calculatePpn(float $amount): float
    {
        if (!$this->ppn_enabled) {
            return 0;
        }
        
        return round($amount * ($this->ppn_percentage / 100), 2);
    }

    /**
     * Get or create setting for a user
     */
    public static function getOrCreateForUser(string $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'invoice_prefix' => 'INV-',
                'invoice_due_days' => 7,
                'ppn_enabled' => false,
                'ppn_percentage' => 11.00,
            ]
        );
    }

    /**
     * Copy settings from another pop
     */
    public function copyFrom(self $source, array $except = ['id', 'user_id', 'isp_name', 'pop_name', 'pop_code', 'created_at', 'updated_at']): self
    {
        $data = collect($source->toArray())->except($except)->toArray();
        $this->fill($data);
        $this->save();
        
        return $this;
    }

    /**
     * Business type options
     */
    public static function businessTypes(): array
    {
        return [
            'PT' => 'Perseroan Terbatas (PT)',
            'CV' => 'Commanditaire Vennootschap (CV)',
            'UD' => 'Usaha Dagang (UD)',
            'Firma' => 'Firma',
            'Perorangan' => 'Perorangan',
            'Koperasi' => 'Koperasi',
            'Yayasan' => 'Yayasan',
        ];
    }
}
