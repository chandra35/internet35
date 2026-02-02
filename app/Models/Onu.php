<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Onu extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'olt_id',
        'pon_port_id',
        'customer_id',
        'odp_id',
        'serial_number',
        'mac_address',
        'name',
        'slot',
        'port',
        'onu_id',
        'onu_type',
        'vendor',
        'software_version',
        'hardware_version',
        'status',
        'config_status',
        'auth_status',
        'rx_power',
        'tx_power',
        'olt_rx_power',
        'temperature',
        'voltage',
        'bias_current',
        'distance',
        'uptime_seconds',
        'last_online_at',
        'last_offline_at',
        'in_octets',
        'out_octets',
        'in_packets',
        'out_packets',
        'line_profile',
        'service_profile',
        'service_ports',
        'vlan_config',
        'mgmt_ip',
        'wan_ip',
        'pppoe_username',
        'odp_port',
        'description',
        'notes',
        'last_sync_at',
        'created_by',
    ];

    protected $casts = [
        'slot' => 'integer',
        'port' => 'integer',
        'onu_id' => 'integer',
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
        'olt_rx_power' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:2',
        'bias_current' => 'decimal:2',
        'distance' => 'decimal:2',
        'uptime_seconds' => 'integer',
        'in_octets' => 'integer',
        'out_octets' => 'integer',
        'in_packets' => 'integer',
        'out_packets' => 'integer',
        'service_ports' => 'array',
        'vlan_config' => 'array',
        'odp_port' => 'integer',
        'last_online_at' => 'datetime',
        'last_offline_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_LOS = 'los';
    public const STATUS_DYING_GASP = 'dying_gasp';
    public const STATUS_POWER_OFF = 'power_off';
    public const STATUS_UNKNOWN = 'unknown';

    public const CONFIG_REGISTERED = 'registered';
    public const CONFIG_UNREGISTERED = 'unregistered';
    public const CONFIG_CONFIGURING = 'configuring';
    public const CONFIG_FAILED = 'failed';

    /**
     * Get port position (slot/port/onu_id)
     */
    public function getPositionAttribute(): string
    {
        return "{$this->slot}/{$this->port}/{$this->onu_id}";
    }

    /**
     * Get GPON index for ZTE
     */
    public function getGponIndexAttribute(): string
    {
        return "gpon_onu-{$this->slot}/{$this->port}:{$this->onu_id}";
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'online' => 'success',
            'offline' => 'secondary',
            'los' => 'danger',
            'dying_gasp' => 'warning',
            'power_off' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'online' => 'Online',
            'offline' => 'Offline',
            'los' => 'LOS (Loss of Signal)',
            'dying_gasp' => 'Dying Gasp',
            'power_off' => 'Power Off',
            default => 'Unknown',
        };
    }

    /**
     * Get config status badge
     */
    public function getConfigStatusBadgeAttribute(): string
    {
        return match($this->config_status) {
            'registered' => 'success',
            'unregistered' => 'warning',
            'configuring' => 'info',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get signal quality based on rx_power
     * Good: > -20 dBm
     * Normal: -20 to -25 dBm
     * Weak: -25 to -28 dBm
     * Critical: < -28 dBm
     */
    public function getSignalQualityAttribute(): string
    {
        if (is_null($this->olt_rx_power)) return 'unknown';
        
        $power = (float) $this->olt_rx_power;
        
        if ($power > -20) return 'excellent';
        if ($power > -23) return 'good';
        if ($power > -26) return 'normal';
        if ($power > -28) return 'weak';
        return 'critical';
    }

    /**
     * Get signal badge class
     */
    public function getSignalBadgeAttribute(): string
    {
        return match($this->signal_quality) {
            'excellent' => 'success',
            'good' => 'info',
            'normal' => 'primary',
            'weak' => 'warning',
            'critical' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get formatted uptime
     */
    public function getUptimeFormattedAttribute(): string
    {
        if (!$this->uptime_seconds) return '-';
        
        $days = floor($this->uptime_seconds / 86400);
        $hours = floor(($this->uptime_seconds % 86400) / 3600);
        $minutes = floor(($this->uptime_seconds % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        }
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Get formatted distance
     */
    public function getDistanceFormattedAttribute(): string
    {
        if (!$this->distance) return '-';
        
        if ($this->distance >= 1000) {
            return number_format($this->distance / 1000, 2) . ' km';
        }
        return number_format($this->distance, 0) . ' m';
    }

    /**
     * Check if ONU is online
     */
    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    /**
     * Check if signal is weak
     */
    public function hasWeakSignal(): bool
    {
        return in_array($this->signal_quality, ['weak', 'critical']);
    }

    // Relationships

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort(): BelongsTo
    {
        return $this->belongsTo(OltPonPort::class, 'pon_port_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signalHistories(): HasMany
    {
        return $this->hasMany(OnuSignalHistory::class);
    }

    // Scopes

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->whereIn('status', ['offline', 'los', 'dying_gasp', 'power_off']);
    }

    public function scopeRegistered($query)
    {
        return $query->where('config_status', 'registered');
    }

    public function scopeUnregistered($query)
    {
        return $query->where('config_status', 'unregistered');
    }

    public function scopeWeakSignal($query, $threshold = -26)
    {
        return $query->whereNotNull('olt_rx_power')
            ->where('olt_rx_power', '<', $threshold);
    }

    public function scopeForOlt($query, $oltId)
    {
        return $query->where('olt_id', $oltId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('customer_id');
    }
}
