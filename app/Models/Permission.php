<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;

    protected $fillable = [
        'name',
        'guard_name',
        'group',
        'description',
    ];

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Override to ensure UUID is properly casted
     */
    protected $casts = [
        'id' => 'string',
    ];
}
