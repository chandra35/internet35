<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteSetting extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'key',
        'group',
        'label',
        'value',
        'type',
        'options',
        'description',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'order' => 'integer',
        ];
    }

    /**
     * Get setting by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function setValue(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();
        if ($setting) {
            return $setting->update(['value' => $value]);
        }
        return false;
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group)
    {
        return static::where('group', $group)->orderBy('order')->get();
    }
}
