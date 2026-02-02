<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingSlider extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image',
        'link',
        'link_text',
        'order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/sliders/' . $this->image);
        }
        return asset('assets/img/default-slider.jpg');
    }
}
