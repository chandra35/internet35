<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingContent extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'section',
        'key',
        'title',
        'subtitle',
        'content',
        'image',
        'icon',
        'link',
        'link_text',
        'meta',
        'order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSection($query, $section)
    {
        return $query->where('section', $section);
    }
}
