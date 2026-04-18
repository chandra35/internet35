<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OltCard extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olt_id',
        'rack',
        'shelf',
        'slot',
        'configured_type',
        'real_type',
        'port_count',
        'hardware_version',
        'software_version',
        'status',
        'role',
        'description',
        'vlan_config',
        'last_sync_at',
    ];

    protected $casts = [
        'rack' => 'integer',
        'shelf' => 'integer',
        'slot' => 'integer',
        'port_count' => 'integer',
        'vlan_config' => 'array',
        'last_sync_at' => 'datetime',
    ];

    // --- Relationships ---

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPorts(): HasMany
    {
        return $this->hasMany(OltPonPort::class, 'card_id');
    }

    public function uplinks(): HasMany
    {
        return $this->hasMany(OltUplink::class, 'card_id');
    }

    // --- Accessors ---

    public function getSlotIdentifierAttribute(): string
    {
        return "{$this->rack}/{$this->shelf}/{$this->slot}";
    }

    public function getDisplayNameAttribute(): string
    {
        $type = $this->real_type ?: $this->configured_type ?: 'Empty';
        return "Slot {$this->slot} ({$type})";
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'inservice' => '<span class="badge badge-success">In Service</span>',
            'standby' => '<span class="badge badge-info">Standby</span>',
            'offline' => '<span class="badge badge-secondary">Offline</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            default => '<span class="badge badge-warning">Unknown</span>',
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        return match ($this->role) {
            'gpon' => '<span class="badge badge-primary"><i class="fas fa-network-wired"></i> GPON</span>',
            'epon' => '<span class="badge badge-primary"><i class="fas fa-network-wired"></i> EPON</span>',
            'uplink' => '<span class="badge badge-info"><i class="fas fa-arrow-up"></i> Uplink/Mgmt</span>',
            'management' => '<span class="badge badge-warning"><i class="fas fa-cog"></i> Management</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->role) . '</span>',
        };
    }

    public function getIsGponAttribute(): bool
    {
        return $this->role === 'gpon';
    }

    // --- Scopes ---

    public function scopeInService($query)
    {
        return $query->where('status', 'inservice');
    }

    public function scopeGpon($query)
    {
        return $query->where('role', 'gpon');
    }
}
