<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;

    protected $fillable = [
        'name',
        'guard_name',
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
