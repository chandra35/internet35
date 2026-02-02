<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Odp extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'pop_id',
        'odc_id',
        'name',
        'code',
        'latitude',
        'longitude',
        'address',
        'total_ports',
        'used_ports',
        'odc_port',
        'status',
        'box_type',
        'splitter_type',
        'pole_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_ports' => 'integer',
        'used_ports' => 'integer',
        'odc_port' => 'integer',
    ];

    /**
     * Generate unique code for ODP
     */
    public static function generateCode($odcId): string
    {
        $odc = Odc::find($odcId);
        $prefix = 'ODP';
        
        // Get ODC code if available
        if ($odc && $odc->code) {
            // Replace ODC with ODP in the code
            $prefix = str_replace('ODC', 'ODP', $odc->code);
        }
        
        // Get next sequence
        $lastOdp = self::where('odc_id', $odcId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastOdp && preg_match('/(\d+)$/', $lastOdp->code, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = self::where('odc_id', $odcId)->count() + 1;
        }
        
        return $prefix . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get available ports count
     */
    public function getAvailablePortsAttribute(): int
    {
        return max(0, $this->total_ports - $this->used_ports);
    }

    /**
     * Get port usage percentage
     */
    public function getPortUsagePercentAttribute(): float
    {
        if (!$this->total_ports) return 0;
        return round(($this->used_ports / $this->total_ports) * 100, 1);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'maintenance' => 'warning',
            'inactive' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Aktif',
            'maintenance' => 'Maintenance',
            'inactive' => 'Tidak Aktif',
            default => 'Unknown',
        };
    }

    /**
     * Check if has coordinates
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude && $this->longitude;
    }

    /**
     * Relationships
     */
    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function odc()
    {
        return $this->belongsTo(Odc::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPop($query, $popId)
    {
        return $query->where('pop_id', $popId);
    }

    public function scopeForOdc($query, $odcId)
    {
        return $query->where('odc_id', $odcId);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
