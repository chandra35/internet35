<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingTestimonial extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'position',
        'company',
        'content',
        'image',
        'rating',
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
            'rating' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/testimonials/' . $this->image);
        }
        return asset('assets/img/default-avatar.png');
    }
}
