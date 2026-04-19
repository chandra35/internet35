<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olt_id',
        'type',
        'name',
        'profile_id',
        'config',
        'description',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public const TYPE_LINE = 'line';
    public const TYPE_SERVICE = 'service';
    public const TYPE_TRAFFIC = 'traffic';
    public const TYPE_TCONT = 'tcont';

    public const TYPES = [
        self::TYPE_LINE    => 'Line Profile',
        self::TYPE_SERVICE => 'Service Profile',
        self::TYPE_TRAFFIC => 'Traffic Profile (Downstream)',
        self::TYPE_TCONT   => 'TCONT Profile (Upstream DBA)',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLineProfiles($query)
    {
        return $query->where('type', self::TYPE_LINE);
    }

    public function scopeServiceProfiles($query)
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    public function scopeTrafficProfiles($query)
    {
        return $query->where('type', self::TYPE_TRAFFIC);
    }

    public function scopeTcontProfiles($query)
    {
        return $query->where('type', self::TYPE_TCONT);
    }
}
