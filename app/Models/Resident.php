<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'no_kk', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'agama', 'pendidikan', 'status_perkawinan', 'nama_ayah', 'nama_ibu',
        'alamat', 'dusun', 'rw', 'rt', 'kelurahan',
        'data_status', 'data_notes',
        'province_code', 'city_code', 'district_code', 'village_code',
        'uploaded_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function village()
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Village::class, 'village_code', 'code');
    }
}
