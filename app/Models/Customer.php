<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'pop_id',
        'customer_id',
        'name',
        'email',
        'phone',
        'phone_alt',
        'nik',
        'birth_date',
        'gender',
        'address',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'postal_code',
        'latitude',
        'longitude',
        'photo_ktp',
        'photo_selfie',
        'photo_house',
        'router_id',
        'odp_id',
        'odp_port',
        'package_id',
        'pppoe_username',
        'pppoe_password',
        'mikrotik_secret_id',
        'service_type',
        'remote_address',
        'mac_address',
        'caller_id',
        'mikrotik_comment',
        'installation_date',
        'active_until',
        'due_date',
        'monthly_fee',
        'installation_fee',
        'billing_day',
        'grace_period_days',
        'status',
        'mikrotik_status',
        'last_connected_at',
        'suspended_at',
        'suspend_reason',
        'terminated_at',
        'terminate_reason',
        'sync_status',
        'last_synced_at',
        'mikrotik_data',
        'notes',
        'internal_notes',
        // Sync tracking
        'mikrotik_synced',
        'mikrotik_synced_at',
        'radius_synced',
        'radius_synced_at',
        'last_sync_error',
        'registered_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'installation_date' => 'date',
        'active_until' => 'date',
        'due_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'installation_fee' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'last_connected_at' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'mikrotik_data' => 'array',
        'mikrotik_synced' => 'boolean',
        'mikrotik_synced_at' => 'datetime',
        'radius_synced' => 'boolean',
        'radius_synced_at' => 'datetime',
    ];

    protected $appends = ['photo_ktp_url', 'photo_selfie_url', 'photo_house_url', 'status_label', 'status_color'];

    /**
     * Status labels
     */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'active' => 'Aktif',
            'suspended' => 'Suspend',
            'terminated' => 'Terminated',
            'expired' => 'Expired',
        ];
    }

    /**
     * Status colors
     */
    public static function statusColors(): array
    {
        return [
            'pending' => 'warning',
            'active' => 'success',
            'suspended' => 'danger',
            'terminated' => 'dark',
            'expired' => 'secondary',
        ];
    }

    /**
     * Service types
     */
    public static function serviceTypes(): array
    {
        return [
            'pppoe' => 'PPPoE',
            'hotspot' => 'Hotspot',
            'static' => 'Static IP',
        ];
    }

    /**
     * Get status label attribute
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Get status color attribute
     */
    public function getStatusColorAttribute(): string
    {
        return self::statusColors()[$this->status] ?? 'secondary';
    }

    /**
     * Set PPPoE password with encryption
     */
    public function setPppoePasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['pppoe_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get decrypted PPPoE password
     */
    public function getDecryptedPppoePasswordAttribute(): ?string
    {
        if ($this->pppoe_password) {
            try {
                return Crypt::decryptString($this->pppoe_password);
            } catch (\Exception $e) {
                // Return as-is if not encrypted (legacy data)
                return $this->pppoe_password;
            }
        }
        return null;
    }

    /**
     * Alias for decrypted_pppoe_password
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        return $this->decrypted_pppoe_password;
    }

    /**
     * Get KTP photo URL
     */
    public function getPhotoKtpUrlAttribute(): ?string
    {
        if ($this->photo_ktp) {
            return Storage::url('customers/ktp/' . $this->photo_ktp);
        }
        return null;
    }

    /**
     * Get selfie photo URL
     */
    public function getPhotoSelfieUrlAttribute(): ?string
    {
        if ($this->photo_selfie) {
            return Storage::url('customers/selfie/' . $this->photo_selfie);
        }
        return null;
    }

    /**
     * Get house photo URL
     */
    public function getPhotoHouseUrlAttribute(): ?string
    {
        if ($this->photo_house) {
            return Storage::url('customers/house/' . $this->photo_house);
        }
        return null;
    }

    /**
     * Generate customer ID with random digits (no dash)
     * Uses pop_prefix from integration settings, fallback to pop_code or 'CST'
     */
    public static function generateCustomerId(string $popId): string
    {
        $popSetting = PopSetting::where('user_id', $popId)->first();
        
        // Use pop_prefix first (from integration settings), fallback to pop_code, then 'CST'
        $prefix = $popSetting?->pop_prefix ?: ($popSetting?->pop_code ?? 'CST');
        
        // Generate 6 random digits
        do {
            $randomDigits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $customerId = $prefix . $randomDigits;
        } while (static::where('customer_id', $customerId)->exists());
        
        return $customerId;
    }

    // Relationships

    /**
     * User account for customer portal
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * POP owner
     */
    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    /**
     * Router
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * ODP (Optical Distribution Point)
     */
    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    /**
     * ONU
     */
    public function onu()
    {
        return $this->hasOne(Onu::class);
    }

    /**
     * Package
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Province
     */
    public function province()
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Province::class, 'province_code', 'code');
    }

    /**
     * City
     */
    public function city()
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\City::class, 'city_code', 'code');
    }

    /**
     * District
     */
    public function district()
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\District::class, 'district_code', 'code');
    }

    /**
     * Village
     */
    public function village()
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Village::class, 'village_code', 'code');
    }

    /**
     * Invoices
     */
    public function invoices()
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    /**
     * Payments
     */
    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * Creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Registered by
     */
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    // Scopes

    /**
     * Scope for POP
     */
    public function scopeForPop($query, $popId)
    {
        return $query->where('pop_id', $popId);
    }

    /**
     * Scope active
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pending
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope suspended
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Check if customer is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if due for payment
     */
    public function isDue(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [$this->address];
        
        if ($this->village) {
            $parts[] = $this->village->name;
        }
        if ($this->district) {
            $parts[] = $this->district->name;
        }
        if ($this->city) {
            $parts[] = $this->city->name;
        }
        if ($this->province) {
            $parts[] = $this->province->name;
        }
        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }
        
        return implode(', ', array_filter($parts));
    }
}
