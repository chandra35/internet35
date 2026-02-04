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
        'olt_id',
        'olt_pon_port',
        'olt_slot',
        'parent_odp_id',
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
        'splitter_level',
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
        'olt_pon_port' => 'integer',
        'olt_slot' => 'integer',
        'splitter_level' => 'integer',
    ];

    /**
     * Generate unique code for ODP
     * Supports both ODC-based and direct OLT-based topology
     */
    public static function generateCode($odcId = null, $oltId = null, $popId = null): string
    {
        $prefix = 'ODP';
        
        // If ODC-based
        if ($odcId) {
            $odc = Odc::find($odcId);
            if ($odc && $odc->code) {
                $prefix = str_replace('ODC', 'ODP', $odc->code);
            }
            
            $lastOdp = self::where('odc_id', $odcId)
                ->orderBy('created_at', 'desc')
                ->first();
            $count = self::where('odc_id', $odcId)->count();
        }
        // If direct OLT-based
        elseif ($oltId) {
            $olt = Olt::find($oltId);
            if ($olt && $olt->name) {
                $prefix = 'ODP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $olt->name), 0, 8));
            }
            
            $lastOdp = self::where('olt_id', $oltId)
                ->whereNull('odc_id')
                ->orderBy('created_at', 'desc')
                ->first();
            $count = self::where('olt_id', $oltId)->whereNull('odc_id')->count();
        }
        // Fallback to POP-based
        else {
            $pop = User::find($popId);
            if ($pop && $pop->code) {
                $prefix = $pop->code . '-ODP';
            }
            
            $lastOdp = self::where('pop_id', $popId)
                ->whereNull('odc_id')
                ->whereNull('olt_id')
                ->orderBy('created_at', 'desc')
                ->first();
            $count = self::where('pop_id', $popId)->whereNull('odc_id')->whereNull('olt_id')->count();
        }
        
        // Get next sequence
        if ($lastOdp && preg_match('/(\d+)$/', $lastOdp->code, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = ($count ?? 0) + 1;
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

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function parentOdp()
    {
        return $this->belongsTo(Odp::class, 'parent_odp_id');
    }

    public function childOdps()
    {
        return $this->hasMany(Odp::class, 'parent_odp_id');
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
     * Get connection source (ODC, OLT, or Parent ODP)
     */
    public function getConnectionSourceAttribute(): string
    {
        if ($this->odc_id && $this->odc) {
            return 'ODC: ' . $this->odc->code;
        }
        if ($this->parent_odp_id && $this->parentOdp) {
            return 'ODP: ' . $this->parentOdp->code;
        }
        if ($this->olt_id && $this->olt) {
            $source = 'OLT: ' . $this->olt->name;
            if ($this->olt_pon_port) {
                $source .= ' (PON ' . $this->olt_pon_port . ')';
            }
            return $source;
        }
        return '-';
    }

    /**
     * Check if connected directly to OLT (no ODC)
     */
    public function isDirectOlt(): bool
    {
        return $this->olt_id && !$this->odc_id && !$this->parent_odp_id;
    }

    /**
     * Check if connected via ODC
     */
    public function isViaOdc(): bool
    {
        return (bool) $this->odc_id;
    }

    /**
     * Check if connected via parent ODP (cascade/relay)
     */
    public function isCascade(): bool
    {
        return (bool) $this->parent_odp_id;
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
