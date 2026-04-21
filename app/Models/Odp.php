<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasPhotos;

class Odp extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasPhotos;

    protected $fillable = [
        'pop_id',
        'odc_id',
        'olt_id',
        'olt_pon_port',
        'olt_slot',
        'zone_id',
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
        'odp_type',
        'box_type',
        'splitter_type',
        'splitter_level',
        'pole_number',
        'notes',
        'photos',
        'created_by',
        // Optical power fields
        'input_power',
        'fiber_distance',
        'fiber_loss_per_km',
        'splitter_ratio',
        'splitter_loss',
        'output_power',
        'cascade_output_power',
        'is_power_manual',
        // Splitter configuration
        'splitter_config_type',
        'unequal_ratio',
        'branch_splitter',
        'fiber_loss',
        'unequal_loss',
        'branch_loss',
        'total_loss',
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
        'photos' => 'array',
        'input_power' => 'decimal:2',
        'fiber_distance' => 'decimal:3',
        'fiber_loss_per_km' => 'decimal:2',
        'splitter_loss' => 'decimal:2',
        'output_power' => 'decimal:2',
        'cascade_output_power' => 'decimal:2',
        'is_power_manual' => 'boolean',
        'fiber_loss' => 'decimal:2',
        'unequal_loss' => 'decimal:2',
        'branch_loss' => 'decimal:2',
        'total_loss' => 'decimal:2',
    ];

    /**
     * Splitter loss constants (in dB)
     */
    const SPLITTER_LOSSES = [
        // Equal splitters
        '1:2' => 3.5,
        '1:4' => 7.0,
        '1:8' => 10.5,
        '1:16' => 14.0,
        '1:32' => 17.5,
        '1:64' => 21.0,
        // Unequal splitters - format: [main_port_loss, branch_port_loss]
        '90:10' => ['main' => 0.5, 'branch' => 10.0],
        '85:15' => ['main' => 0.7, 'branch' => 8.2],
        '80:20' => ['main' => 1.0, 'branch' => 7.0],
        '70:30' => ['main' => 1.5, 'branch' => 5.2],
        '60:40' => ['main' => 2.2, 'branch' => 4.0],
        '50:50' => ['main' => 3.0, 'branch' => 3.0],
    ];

    /**
     * Check if splitter ratio is unequal type
     */
    public function isUnequalSplitter(): bool
    {
        if (!$this->splitter_ratio) return false;
        return strpos($this->splitter_ratio, ':') !== false 
            && !str_starts_with($this->splitter_ratio, '1:');
    }

    /**
     * Get splitter loss for given ratio
     */
    public static function getSplitterLoss(string $ratio): array
    {
        if (!isset(self::SPLITTER_LOSSES[$ratio])) {
            return ['main' => 0, 'branch' => 0];
        }
        
        $loss = self::SPLITTER_LOSSES[$ratio];
        
        // Equal splitter returns same loss for all
        if (is_numeric($loss)) {
            return ['main' => $loss, 'branch' => $loss];
        }
        
        return $loss;
    }

    /**
     * Calculate optical power values
     */
    public function calculateOpticalPower(): array
    {
        $inputPower = $this->input_power;
        $fiberDistance = $this->fiber_distance ?? 0;
        $fiberLossPerKm = $this->fiber_loss_per_km ?? 0.35;
        $splitterRatio = $this->splitter_ratio;
        
        // Calculate fiber loss
        $fiberLoss = $fiberDistance * $fiberLossPerKm;
        $powerAfterFiber = $inputPower - $fiberLoss;
        
        // Get splitter loss
        $splitterLoss = self::getSplitterLoss($splitterRatio);
        
        // Calculate output power
        $outputPower = $powerAfterFiber - $splitterLoss['branch'];
        $cascadeOutputPower = $powerAfterFiber - $splitterLoss['main'];
        
        return [
            'fiber_loss' => round($fiberLoss, 2),
            'power_after_fiber' => round($powerAfterFiber, 2),
            'splitter_loss_main' => $splitterLoss['main'],
            'splitter_loss_branch' => $splitterLoss['branch'],
            'output_power' => round($outputPower, 2),
            'cascade_output_power' => round($cascadeOutputPower, 2),
            'is_warning' => $outputPower < -28, // Typical ONU sensitivity threshold
            'is_critical' => $outputPower < -30,
        ];
    }

    /**
     * Get source power from parent (OLT/ODC/parent ODP)
     */
    public function getSourcePower(): ?float
    {
        // If connected directly to OLT
        if ($this->olt_id && $this->olt) {
            // Try to get TX power from OLT PON port
            $ponPort = $this->olt->ponPorts()
                ->where('port_number', $this->olt_pon_port)
                ->first();
            
            if ($ponPort && $ponPort->tx_power) {
                return (float) $ponPort->tx_power;
            }
            
            // Fallback: typical OLT TX power
            return null;
        }
        
        // If connected via ODC
        if ($this->odc_id && $this->odc) {
            // ODC doesn't amplify, use OLT power - ODC fiber loss
            if ($this->odc->olt_id && $this->odc->olt) {
                $oltPonPort = $this->odc->olt->ponPorts()
                    ->where('port_number', $this->odc->olt_pon_port)
                    ->first();
                
                if ($oltPonPort && $oltPonPort->tx_power) {
                    // Subtract estimated fiber loss to ODC
                    $odcFiberLoss = ($this->odc->fiber_distance ?? 0) * 0.35;
                    return (float) $oltPonPort->tx_power - $odcFiberLoss;
                }
            }
            return null;
        }
        
        // If connected via parent ODP (cascade)
        if ($this->parent_odp_id && $this->parentOdp) {
            // Use cascade output power from parent ODP
            if ($this->parentOdp->cascade_output_power) {
                return (float) $this->parentOdp->cascade_output_power;
            }
            // Or regular output power if no cascade configured
            if ($this->parentOdp->output_power) {
                return (float) $this->parentOdp->output_power;
            }
        }
        
        return null;
    }

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
     * ODP type constants
     */
    const ODP_TYPES = [
        'gpon'   => 'GPON',
        'epon'   => 'EPON',
        'xgpon'  => 'XG-PON',
        'xgspon' => 'XGS-PON',
    ];

    /**
     * Get ODP type label
     */
    public function getOdpTypeLabelAttribute(): string
    {
        return self::ODP_TYPES[$this->odp_type] ?? strtoupper($this->odp_type ?? 'GPON');
    }

    /**
     * Get ODP type badge class
     */
    public function getOdpTypeBadgeAttribute(): string
    {
        return match($this->odp_type) {
            'gpon'   => 'success',
            'epon'   => 'primary',
            'xgpon'  => 'warning',
            'xgspon' => 'info',
            default  => 'secondary',
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

    public function zone()
    {
        return $this->belongsTo(Zone::class);
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

    public function onus()
    {
        return $this->hasMany(Onu::class);
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
