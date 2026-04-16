<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'olt_id',
        'name',
        'notes',
    ];

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class);
    }
}
