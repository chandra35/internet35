<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopResidentAccess extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pop_resident_access';

    protected $fillable = [
        'pop_id', 'village_code', 'granted_by', 'granted_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function village()
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Village::class, 'village_code', 'code');
    }
}
