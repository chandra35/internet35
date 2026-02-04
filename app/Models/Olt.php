<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasPhotos;

class Olt extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasPhotos;

    protected $fillable = [
        'pop_id',
        'router_id',
        'name',
        'code',
        'description',
        'address',
        'brand',
        'model',
        'firmware_version',
        'serial_number',
        'ip_address',
        'snmp_port',
        'snmp_community',
        'snmp_version',
        'snmp_username',
        'snmp_auth_protocol',
        'snmp_auth_password',
        'snmp_priv_protocol',
        'snmp_priv_password',
        'telnet_enabled',
        'telnet_port',
        'telnet_username',
        'telnet_password',
        'ssh_enabled',
        'ssh_port',
        'ssh_username',
        'ssh_password',
        'ssh_key',
        'api_enabled',
        'api_url',
        'api_key',
        'api_secret',
        'latitude',
        'longitude',
        'total_pon_ports',
        'total_uplink_ports',
        'max_onu_per_port',
        'status',
        'last_sync_at',
        'last_online_at',
        'notes',
        'internal_notes',
        'created_by',
        'photos',
    ];

    protected $casts = [
        'telnet_enabled' => 'boolean',
        'ssh_enabled' => 'boolean',
        'api_enabled' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_pon_ports' => 'integer',
        'total_uplink_ports' => 'integer',
        'max_onu_per_port' => 'integer',
        'last_sync_at' => 'datetime',
        'last_online_at' => 'datetime',
        'photos' => 'array',
    ];

    protected $hidden = [
        'snmp_community',
        'snmp_auth_password',
        'snmp_priv_password',
        'telnet_password',
        'ssh_password',
        'ssh_key',
        'api_key',
        'api_secret',
    ];

    /**
     * Brand constants
     */
    public const BRAND_ZTE = 'zte';
    public const BRAND_HIOSO = 'hioso';
    public const BRAND_HSGQ = 'hsgq';
    public const BRAND_VSOL = 'vsol';
    public const BRAND_HUAWEI = 'huawei';
    public const BRAND_FIBERHOME = 'fiberhome';
    public const BRAND_BDCOM = 'bdcom';
    public const BRAND_OTHER = 'other';

    public const BRANDS = [
        self::BRAND_ZTE => 'ZTE',
        self::BRAND_HIOSO => 'Hioso',
        self::BRAND_HSGQ => 'HSGQ',
        self::BRAND_VSOL => 'VSOL',
        self::BRAND_HUAWEI => 'Huawei',
        self::BRAND_FIBERHOME => 'FiberHome',
        self::BRAND_BDCOM => 'BDCOM',
        self::BRAND_OTHER => 'Other',
    ];

    /**
     * Generate unique code for OLT
     */
    public static function generateCode($popId): string
    {
        $pop = User::find($popId);
        $prefix = 'OLT';
        
        if ($pop && $pop->code) {
            $prefix = $pop->code . '-OLT';
        }
        
        $lastOlt = self::where('pop_id', $popId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastOlt && preg_match('/(\d+)$/', $lastOlt->code, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = self::where('pop_id', $popId)->count() + 1;
        }
        
        return $prefix . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get brand label
     */
    public function getBrandLabelAttribute(): string
    {
        return self::BRANDS[$this->brand] ?? $this->brand;
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'maintenance' => 'warning',
            'offline' => 'danger',
            'inactive' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'maintenance' => 'Maintenance',
            'offline' => 'Offline',
            'inactive' => 'Inactive',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if OLT has coordinates
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get total registered ONU count
     */
    public function getRegisteredOnuCountAttribute(): int
    {
        return $this->onus()->whereIn('config_status', ['registered', 'configuring'])->count();
    }

    /**
     * Get online ONU count
     */
    public function getOnlineOnuCountAttribute(): int
    {
        return $this->onus()->where('status', 'online')->count();
    }

    /**
     * Get offline ONU count
     */
    public function getOfflineOnuCountAttribute(): int
    {
        return $this->onus()->whereIn('status', ['offline', 'los', 'dying_gasp', 'power_off'])->count();
    }

    /**
     * Get connection method
     */
    public function getConnectionMethodAttribute(): string
    {
        $methods = [];
        if ($this->snmp_community) $methods[] = 'SNMP';
        if ($this->telnet_enabled) $methods[] = 'Telnet';
        if ($this->ssh_enabled) $methods[] = 'SSH';
        if ($this->api_enabled) $methods[] = 'API';
        return implode(', ', $methods) ?: 'None';
    }

    // Relationships

    public function pop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ponPorts(): HasMany
    {
        return $this->hasMany(OltPonPort::class);
    }

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class);
    }

    public function odcs(): HasMany
    {
        return $this->hasMany(Odc::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(OltProfile::class);
    }

    public function signalHistories(): HasMany
    {
        return $this->hasMany(OnuSignalHistory::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPop($query, $popId)
    {
        return $query->where('pop_id', $popId);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
