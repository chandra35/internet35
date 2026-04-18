<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltUplink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olt_id',
        'card_id',
        'interface_name',
        'interface_type',
        'rack',
        'shelf',
        'slot',
        'port',
        'switchport_mode',
        'tagged_vlans',
        'native_vlan',
        'status',
        'admin_status',
        'in_octets',
        'out_octets',
        'in_rate_bps',
        'out_rate_bps',
        'description',
        'last_sync_at',
    ];

    protected $casts = [
        'rack' => 'integer',
        'shelf' => 'integer',
        'slot' => 'integer',
        'port' => 'integer',
        'tagged_vlans' => 'array',
        'native_vlan' => 'integer',
        'in_octets' => 'integer',
        'out_octets' => 'integer',
        'in_rate_bps' => 'integer',
        'out_rate_bps' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    // --- Relationships ---

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(OltCard::class, 'card_id');
    }

    // --- Accessors ---

    public function getDisplayNameAttribute(): string
    {
        return $this->interface_name;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'up' => '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Up</span>',
            'down' => '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Down</span>',
            default => '<span class="badge badge-warning">Unknown</span>',
        };
    }

    public function getTaggedVlansDisplayAttribute(): string
    {
        if (empty($this->tagged_vlans)) {
            return '-';
        }
        return implode(', ', $this->tagged_vlans);
    }

    public function getInRateFormattedAttribute(): string
    {
        return $this->formatRate($this->in_rate_bps);
    }

    public function getOutRateFormattedAttribute(): string
    {
        return $this->formatRate($this->out_rate_bps);
    }

    private function formatRate(int $bps): string
    {
        if ($bps >= 1_000_000_000) {
            return round($bps / 1_000_000_000, 2) . ' Gbps';
        }
        if ($bps >= 1_000_000) {
            return round($bps / 1_000_000, 2) . ' Mbps';
        }
        if ($bps >= 1_000) {
            return round($bps / 1_000, 2) . ' Kbps';
        }
        return $bps . ' bps';
    }
}
