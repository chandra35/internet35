<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasPhotos;

class Odc extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasPhotos;

    protected $fillable = [
        'pop_id',
        'olt_id',
        'olt_pon_port',
        'olt_slot',
        'name',
        'code',
        'latitude',
        'longitude',
        'address',
        'total_ports',
        'used_ports',
        'status',
        'cabinet_type',
        'cable_type',
        'cable_core',
        'cable_distance',
        'notes',
        'photos',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_ports' => 'integer',
        'used_ports' => 'integer',
        'cable_core' => 'integer',
        'cable_distance' => 'decimal:2',
        'olt_pon_port' => 'integer',
        'olt_slot' => 'integer',
        'photos' => 'array',
    ];

    /**
     * Generate unique code for ODC
     */
    public static function generateCode($popId): string
    {
        $pop = User::find($popId);
        $prefix = 'ODC';
        
        // Get POP code if available
        if ($pop && $pop->code) {
            $prefix = $pop->code . '-ODC';
        }
        
        // Get next sequence
        $lastOdc = self::where('pop_id', $popId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastOdc && preg_match('/(\d+)$/', $lastOdc->code, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = self::where('pop_id', $popId)->count() + 1;
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

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort()
    {
        return $this->belongsTo(OltPonPort::class, 'olt_id', 'olt_id')
            ->where('port', $this->olt_pon_port)
            ->where('slot', $this->olt_slot);
    }

    public function odps()
    {
        return $this->hasMany(Odp::class);
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

    public function scopeForOlt($query, $oltId)
    {
        return $query->where('olt_id', $oltId);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
