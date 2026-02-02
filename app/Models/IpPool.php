<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpPool extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'router_id',
        'pop_id',
        'mikrotik_id',
        'name',
        'ranges',
        'next_pool',
        'comment',
        'is_synced',
        'last_synced_at',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Router relationship
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * POP owner relationship
     */
    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    /**
     * Profiles using this pool
     */
    public function profiles()
    {
        return $this->hasMany(PppProfile::class, 'remote_address', 'name');
    }

    /**
     * Parse ranges to array
     */
    public function getParsedRangesAttribute(): array
    {
        if (!$this->ranges) {
            return [];
        }

        return array_map('trim', explode(',', $this->ranges));
    }

    /**
     * Calculate total IPs in pool
     */
    public function getTotalIpsAttribute(): int
    {
        $total = 0;
        foreach ($this->parsed_ranges as $range) {
            if (str_contains($range, '-')) {
                [$start, $end] = explode('-', $range);
                $startLong = ip2long(trim($start));
                $endLong = ip2long(trim($end));
                if ($startLong !== false && $endLong !== false) {
                    $total += abs($endLong - $startLong) + 1;
                }
            } else {
                $total += 1;
            }
        }
        return $total;
    }

    /**
     * Convert Mikrotik pool data to model attributes
     */
    public static function fromMikrotikData(array $data, string $routerId, ?string $popId = null): array
    {
        return [
            'router_id' => $routerId,
            'pop_id' => $popId,
            'mikrotik_id' => $data['.id'] ?? null,
            'name' => $data['name'] ?? '',
            'ranges' => $data['ranges'] ?? '',
            'next_pool' => $data['next-pool'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_synced' => true,
            'last_synced_at' => now(),
        ];
    }
}
