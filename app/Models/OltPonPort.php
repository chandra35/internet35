<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OltPonPort extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olt_id',
        'slot',
        'port',
        'name',
        'max_onu',
        'registered_onu',
        'online_onu',
        'status',
        'admin_status',
        'tx_power',
        'rx_power_min',
        'rx_power_max',
        'rx_power_avg',
        'in_octets',
        'out_octets',
        'in_errors',
        'out_errors',
        'description',
        'last_sync_at',
    ];

    protected $casts = [
        'slot' => 'integer',
        'port' => 'integer',
        'max_onu' => 'integer',
        'registered_onu' => 'integer',
        'online_onu' => 'integer',
        'tx_power' => 'decimal:2',
        'rx_power_min' => 'decimal:2',
        'rx_power_max' => 'decimal:2',
        'rx_power_avg' => 'decimal:2',
        'in_octets' => 'integer',
        'out_octets' => 'integer',
        'in_errors' => 'integer',
        'out_errors' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get port identifier (slot/port format)
     */
    public function getPortIdentifierAttribute(): string
    {
        return "{$this->slot}/{$this->port}";
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return "{$this->name} ({$this->port_identifier})";
        }
        return "PON {$this->port_identifier}";
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'up' => 'success',
            'down' => 'danger',
            'testing' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get available ONU slots
     */
    public function getAvailableOnuAttribute(): int
    {
        return max(0, $this->max_onu - $this->registered_onu);
    }

    /**
     * Get offline ONU count
     */
    public function getOfflineOnuAttribute(): int
    {
        return max(0, $this->registered_onu - $this->online_onu);
    }

    // Relationships

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class, 'pon_port_id');
    }

    // Scopes

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeEnabled($query)
    {
        return $query->where('admin_status', 'enabled');
    }
}
