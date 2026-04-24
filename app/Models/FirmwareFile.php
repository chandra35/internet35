<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FirmwareFile extends Model
{
    protected $fillable = [
        'brand',
        'model_pattern',
        'version',
        'filename',
        'original_name',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // -----------------------------------------------------------------
    // Accessors / Helpers
    // -----------------------------------------------------------------

    /**
     * Get the public download URL served by the app.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('admin.firmware.download', $this->id);
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    /**
     * Brand label for display.
     */
    public function getBrandLabelAttribute(): string
    {
        return match ($this->brand) {
            'huawei'    => 'Huawei',
            'zte'       => 'ZTE',
            'fiberhome' => 'FiberHome',
            'nokia'     => 'Nokia',
            'tp-link'   => 'TP-Link',
            'sercomm'   => 'Sercomm',
            default     => strtoupper($this->brand),
        };
    }

    /**
     * Find compatible firmware files for an ONU brand+model.
     * Matches:
     *   1. brand = $brand AND model_pattern = exact model
     *   2. brand = $brand AND model_pattern IS NULL (wildcard for all models of brand)
     *   3. brand = $brand AND model_pattern ends with '*' (prefix match)
     */
    public static function forOnu(string $brand, ?string $model = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('brand', $brand)
            ->get()
            ->filter(function (self $fw) use ($model) {
                if ($fw->model_pattern === null || $fw->model_pattern === '') {
                    return true; // wildcard — semua model brand ini
                }
                if ($model === null) {
                    return false;
                }
                $pattern = $fw->model_pattern;
                if (str_ends_with($pattern, '*')) {
                    $prefix = rtrim($pattern, '*');
                    return str_starts_with(strtoupper($model), strtoupper($prefix));
                }
                return strtoupper($model) === strtoupper($pattern);
            })
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Storage disk path for this firmware file.
     */
    public function storagePath(): string
    {
        return 'firmware/' . $this->filename;
    }

    /**
     * Delete physical file when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $fw) {
            Storage::disk('local')->delete($fw->storagePath());
        });
    }
}
