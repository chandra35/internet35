<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltVlan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olt_id',
        'vlan_id',
        'name',
        'type',
        'description',
        'uplink_ports',
        'is_synced',
        'last_sync_at',
    ];

    protected $casts = [
        'vlan_id' => 'integer',
        'uplink_ports' => 'array',
        'is_synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    // --- Relationships ---

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    // --- Accessors ---

    public function getDisplayNameAttribute(): string
    {
        $name = $this->name ?: "VLAN {$this->vlan_id}";
        return "{$this->vlan_id} - {$name}";
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            'service' => '<span class="badge badge-primary"><i class="fas fa-globe"></i> Service</span>',
            'management' => '<span class="badge badge-warning"><i class="fas fa-tools"></i> Management</span>',
            'voip' => '<span class="badge badge-success"><i class="fas fa-phone"></i> VoIP</span>',
            'iptv' => '<span class="badge badge-info"><i class="fas fa-tv"></i> IPTV</span>',
            'infra' => '<span class="badge badge-secondary"><i class="fas fa-server"></i> Infra</span>',
            default => '<span class="badge badge-dark">Other</span>',
        };
    }

    public function getUplinkPortsDisplayAttribute(): string
    {
        if (empty($this->uplink_ports)) {
            return '-';
        }
        return implode(', ', $this->uplink_ports);
    }

    // --- Scopes ---

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeService($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeManagement($query)
    {
        return $query->where('type', 'management');
    }

    public function scopeSynced($query)
    {
        return $query->where('is_synced', true);
    }
}
