<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Router extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'identity',
        'host',
        'api_port',
        'api_ssl_port',
        'use_ssl',
        'username',
        'password',
        'ros_version',
        'ros_major_version',
        'board_name',
        'architecture',
        'cpu',
        'total_memory',
        'free_memory',
        'total_hdd_space',
        'free_hdd_space',
        'uptime',
        'last_connected_at',
        'status',
        'notes',
        'latitude',
        'longitude',
        'is_active',
        'pop_id',
        'created_by',
    ];

    protected $casts = [
        'api_port' => 'integer',
        'api_ssl_port' => 'integer',
        'use_ssl' => 'boolean',
        'ros_major_version' => 'integer',
        'total_memory' => 'integer',
        'free_memory' => 'integer',
        'total_hdd_space' => 'integer',
        'free_hdd_space' => 'integer',
        'last_connected_at' => 'datetime',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get decrypted password
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        try {
            return $this->password ? Crypt::decryptString($this->password) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted password
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Check if ROS 7
     */
    public function isRos7(): bool
    {
        return $this->ros_major_version >= 7;
    }

    /**
     * Check if ROS 6
     */
    public function isRos6(): bool
    {
        return $this->ros_major_version < 7;
    }

    /**
     * Get formatted memory
     */
    public function getFormattedTotalMemoryAttribute(): string
    {
        return $this->formatBytes($this->total_memory);
    }

    public function getFormattedFreeMemoryAttribute(): string
    {
        return $this->formatBytes($this->free_memory);
    }

    public function getMemoryUsagePercentAttribute(): float
    {
        if (!$this->total_memory) return 0;
        return round((($this->total_memory - $this->free_memory) / $this->total_memory) * 100, 1);
    }

    public function getFormattedTotalHddAttribute(): string
    {
        return $this->formatBytes($this->total_hdd_space);
    }

    public function getFormattedFreeHddAttribute(): string
    {
        return $this->formatBytes($this->free_hdd_space);
    }

    public function getHddUsagePercentAttribute(): float
    {
        if (!$this->total_hdd_space) return 0;
        return round((($this->total_hdd_space - $this->free_hdd_space) / $this->total_hdd_space) * 100, 1);
    }

    protected function formatBytes($bytes): string
    {
        if (!$bytes) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Relationships
     */
    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeForPop($query, $popId)
    {
        return $query->where('pop_id', $popId);
    }
}
