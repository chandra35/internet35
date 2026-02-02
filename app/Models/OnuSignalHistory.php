<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnuSignalHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'onu_id',
        'olt_id',
        'rx_power',
        'tx_power',
        'olt_rx_power',
        'temperature',
        'voltage',
        'bias_current',
        'status',
        'distance',
        'recorded_at',
    ];

    protected $casts = [
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
        'olt_rx_power' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:2',
        'bias_current' => 'decimal:2',
        'distance' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    // Relationships

    public function onu(): BelongsTo
    {
        return $this->belongsTo(Onu::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    // Scopes

    public function scopeForOnu($query, $onuId)
    {
        return $query->where('onu_id', $onuId);
    }

    public function scopeForOlt($query, $oltId)
    {
        return $query->where('olt_id', $oltId);
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    public function scopeLastHours($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    public function scopeLastDays($query, $days = 7)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }
}
