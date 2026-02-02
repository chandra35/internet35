<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'old_data',
        'new_data',
        'ip_address',
        'local_ip',
        'country',
        'country_code',
        'region',
        'region_name',
        'city',
        'district',
        'zip',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'org',
        'as_name',
        'os',
        'os_version',
        'browser',
        'browser_version',
        'device',
        'device_type',
        'is_mobile',
        'is_tablet',
        'is_desktop',
        'is_bot',
        'user_agent',
        'url',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
            'is_mobile' => 'boolean',
            'is_tablet' => 'boolean',
            'is_desktop' => 'boolean',
            'is_bot' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Get the user that owns the activity log
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get action color attribute
     */
    public function getActionColorAttribute(): string
    {
        $colors = [
            'login' => 'success',
            'logout' => 'secondary',
            'login_failed' => 'danger',
            'create' => 'primary',
            'update' => 'info',
            'delete' => 'danger',
            'view' => 'light',
        ];

        return $colors[$this->action] ?? 'secondary';
    }

    /**
     * Get organization attribute alias
     */
    public function getOrganizationAttribute(): ?string
    {
        return $this->org;
    }

    /**
     * Get AS number attribute alias
     */
    public function getAsNumberAttribute(): ?string
    {
        return $this->as_name;
    }

    /**
     * Get postal code attribute alias
     */
    public function getPostalCodeAttribute(): ?string
    {
        return $this->zip;
    }

    /**
     * Get region code attribute alias  
     */
    public function getRegionCodeAttribute(): ?string
    {
        return null; // Not stored separately
    }

    /**
     * Get platform attribute alias
     */
    public function getPlatformAttribute(): ?string
    {
        return $this->os;
    }

    /**
     * Get platform version attribute alias
     */
    public function getPlatformVersionAttribute(): ?string
    {
        return $this->os_version;
    }

    /**
     * Get device model attribute (not stored)
     */
    public function getDeviceModelAttribute(): ?string
    {
        return null;
    }

    /**
     * Get is_robot attribute alias
     */
    public function getIsRobotAttribute(): bool
    {
        return $this->is_bot ?? false;
    }
}
