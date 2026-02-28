<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitterRatio extends Model
{
    protected $fillable = [
        'type',
        'ratio',
        'name',
        'branch_loss',
        'relay_loss',
        'branch_percent',
        'relay_percent',
        'ports',
        'branch_color',
        'relay_color',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'branch_loss' => 'decimal:2',
        'relay_loss' => 'decimal:2',
        'branch_percent' => 'integer',
        'relay_percent' => 'integer',
        'ports' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope untuk equal splitters (1:N)
     */
    public function scopeEqual($query)
    {
        return $query->where('type', 'equal');
    }

    /**
     * Scope untuk unequal/rasio splitters
     */
    public function scopeUnequal($query)
    {
        return $query->where('type', 'unequal');
    }

    /**
     * Scope untuk active splitters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get sorted splitters
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('ratio');
    }

    /**
     * Calculate power output untuk branch (pelanggan)
     * 
     * @param float $inputPower Power masuk dalam dBm
     * @return float Power keluar ke branch dalam dBm
     */
    public function calculateBranchPower(float $inputPower): float
    {
        return $inputPower - $this->branch_loss;
    }

    /**
     * Calculate power output untuk relay (ke ODP berikutnya)
     * 
     * @param float $inputPower Power masuk dalam dBm
     * @return float|null Power keluar ke relay dalam dBm, null jika equal splitter
     */
    public function calculateRelayPower(float $inputPower): ?float
    {
        if ($this->type === 'equal' || $this->relay_loss === null) {
            return null;
        }
        return $inputPower - $this->relay_loss;
    }

    /**
     * Check if this is an unequal/rasio splitter
     */
    public function isUnequal(): bool
    {
        return $this->type === 'unequal';
    }

    /**
     * Check if this is an equal splitter
     */
    public function isEqual(): bool
    {
        return $this->type === 'equal';
    }

    /**
     * Get display label dengan info loss
     */
    public function getDisplayLabelAttribute(): string
    {
        if ($this->isEqual()) {
            return "{$this->ratio} ({$this->ports} port, Loss: {$this->branch_loss} dB)";
        }
        return "{$this->ratio} (Branch: {$this->branch_loss} dB, Relay: {$this->relay_loss} dB)";
    }

    /**
     * Get all active equal splitters as options for select
     */
    public static function getEqualOptions(): array
    {
        return self::equal()->active()->sorted()->get()->map(function ($item) {
            return [
                'value' => $item->ratio,
                'label' => $item->display_label,
                'loss' => $item->branch_loss,
                'ports' => $item->ports,
            ];
        })->toArray();
    }

    /**
     * Get all active unequal splitters as options for select
     */
    public static function getUnequalOptions(): array
    {
        return self::unequal()->active()->sorted()->get()->map(function ($item) {
            return [
                'value' => $item->ratio,
                'label' => $item->display_label,
                'branch_loss' => $item->branch_loss,
                'relay_loss' => $item->relay_loss,
                'branch_percent' => $item->branch_percent,
                'relay_percent' => $item->relay_percent,
                'branch_color' => $item->branch_color,
                'relay_color' => $item->relay_color,
            ];
        })->toArray();
    }

    /**
     * Find splitter by ratio string
     */
    public static function findByRatio(string $ratio): ?self
    {
        return self::where('ratio', $ratio)->first();
    }
}
