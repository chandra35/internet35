<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'router_id',
        'name',
        'mikrotik_profile_name',
        'mikrotik_profile_id',
        'rate_limit',
        'local_address',
        'remote_address',
        'parent_queue',
        'address_list',
        'dns_server',
        'session_timeout',
        'idle_timeout',
        'incoming_filter',
        'outgoing_filter',
        'bridge',
        'interface_list',
        'only_one',
        'mikrotik_comment',
        'price',
        'validity_days',
        'speed_up',
        'speed_down',
        'description',
        'sort_order',
        'is_active',
        'is_public',
        'sync_status',
        'last_synced_at',
        'mikrotik_data',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'validity_days' => 'integer',
        'speed_up' => 'integer',
        'speed_down' => 'integer',
        'session_timeout' => 'integer',
        'idle_timeout' => 'integer',
        'sort_order' => 'integer',
        'only_one' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'last_synced_at' => 'datetime',
        'mikrotik_data' => 'array',
    ];

    /**
     * Get the router that owns this package
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get the user who created this package
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Parse rate limit string to get upload/download speeds
     * Format: "10M/10M" or "rx/tx" or "10M/10M 5M/5M 10M/10M 10/10"
     */
    public function parseRateLimit(): array
    {
        if (empty($this->rate_limit)) {
            return ['download' => null, 'upload' => null];
        }

        // Take first part if burst is defined (format: max-limit burst-limit burst-threshold burst-time)
        $parts = explode(' ', $this->rate_limit);
        $maxLimit = $parts[0];

        // Split by "/" to get rx/tx (download/upload)
        $speeds = explode('/', $maxLimit);
        
        return [
            'download' => $speeds[0] ?? null, // rx (receive = download for client)
            'upload' => $speeds[1] ?? $speeds[0] ?? null, // tx (transmit = upload for client)
        ];
    }

    /**
     * Convert speed string to Kbps
     * Handles: 10M, 10m, 10000k, 10000K, 10000000
     */
    public static function speedToKbps(?string $speed): ?int
    {
        if (empty($speed)) {
            return null;
        }

        $speed = strtolower(trim($speed));
        
        if (preg_match('/^(\d+(?:\.\d+)?)(g|m|k)?$/', $speed, $matches)) {
            $value = (float) $matches[1];
            $unit = $matches[2] ?? '';
            
            return match ($unit) {
                'g' => (int) ($value * 1000000),
                'm' => (int) ($value * 1000),
                'k' => (int) $value,
                default => (int) ($value / 1000), // Assume bps if no unit
            };
        }

        return null;
    }

    /**
     * Format speed for display
     */
    public static function formatSpeed(?int $kbps): string
    {
        if ($kbps === null) {
            return '-';
        }

        if ($kbps >= 1000000) {
            return round($kbps / 1000000, 1) . ' Gbps';
        }
        if ($kbps >= 1000) {
            return round($kbps / 1000, 1) . ' Mbps';
        }
        return $kbps . ' Kbps';
    }

    /**
     * Get formatted download speed
     */
    public function getFormattedDownloadAttribute(): string
    {
        if ($this->speed_down) {
            return self::formatSpeed($this->speed_down);
        }
        
        $parsed = $this->parseRateLimit();
        $kbps = self::speedToKbps($parsed['download']);
        return self::formatSpeed($kbps);
    }

    /**
     * Get formatted upload speed
     */
    public function getFormattedUploadAttribute(): string
    {
        if ($this->speed_up) {
            return self::formatSpeed($this->speed_up);
        }
        
        $parsed = $this->parseRateLimit();
        $kbps = self::speedToKbps($parsed['upload']);
        return self::formatSpeed($kbps);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get sync status badge class
     */
    public function getSyncStatusBadgeAttribute(): string
    {
        return match ($this->sync_status) {
            'synced' => 'success',
            'pending_push' => 'warning',
            'pending_pull' => 'info',
            'conflict' => 'danger',
            'local_only' => 'secondary',
            'mikrotik_only' => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Get sync status label
     */
    public function getSyncStatusLabelAttribute(): string
    {
        return match ($this->sync_status) {
            'synced' => 'Synced',
            'pending_push' => 'Pending Push',
            'pending_pull' => 'Pending Pull',
            'conflict' => 'Conflict',
            'local_only' => 'Local Only',
            'mikrotik_only' => 'Mikrotik Only',
            default => 'Unknown',
        };
    }

    /**
     * Get sync status full badge HTML
     */
    public function getSyncStatusBadgeHtmlAttribute(): string
    {
        $class = $this->sync_status_badge;
        $label = $this->sync_status_label;
        $icon = match ($this->sync_status) {
            'synced' => 'fa-check-circle',
            'pending_push' => 'fa-arrow-up',
            'pending_pull' => 'fa-arrow-down',
            'conflict' => 'fa-exclamation-triangle',
            'local_only' => 'fa-desktop',
            'mikrotik_only' => 'fa-server',
            default => 'fa-question-circle',
        };
        
        return "<span class=\"badge badge-{$class}\"><i class=\"fas {$icon} mr-1\"></i>{$label}</span>";
    }

    /**
     * Create package from Mikrotik PPP Profile data
     */
    public static function createFromMikrotik(array $mikrotikData, string $routerId, ?string $createdBy = null): self
    {
        $rateLimit = $mikrotikData['rate-limit'] ?? null;
        $speeds = ['download' => null, 'upload' => null];
        
        if ($rateLimit) {
            $parts = explode(' ', $rateLimit);
            $maxLimit = $parts[0];
            $speedParts = explode('/', $maxLimit);
            $speeds['download'] = self::speedToKbps($speedParts[0] ?? null);
            $speeds['upload'] = self::speedToKbps($speedParts[1] ?? $speedParts[0] ?? null);
        }

        return self::create([
            'router_id' => $routerId,
            'name' => $mikrotikData['name'],
            'mikrotik_profile_name' => $mikrotikData['name'],
            'mikrotik_profile_id' => $mikrotikData['.id'] ?? null,
            'rate_limit' => $rateLimit,
            'local_address' => $mikrotikData['local-address'] ?? null,
            'remote_address' => $mikrotikData['remote-address'] ?? null,
            'parent_queue' => $mikrotikData['parent-queue'] ?? null,
            'address_list' => $mikrotikData['address-list'] ?? null,
            'dns_server' => $mikrotikData['dns-server'] ?? null,
            'session_timeout' => self::parseTimeout($mikrotikData['session-timeout'] ?? null),
            'idle_timeout' => self::parseTimeout($mikrotikData['idle-timeout'] ?? null),
            'incoming_filter' => $mikrotikData['incoming-filter'] ?? null,
            'outgoing_filter' => $mikrotikData['outgoing-filter'] ?? null,
            'bridge' => $mikrotikData['bridge'] ?? null,
            'interface_list' => $mikrotikData['interface-list'] ?? null,
            'only_one' => ($mikrotikData['only-one'] ?? 'no') === 'yes',
            'mikrotik_comment' => $mikrotikData['comment'] ?? null,
            'speed_down' => $speeds['download'],
            'speed_up' => $speeds['upload'],
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'mikrotik_data' => $mikrotikData,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Get data array from Mikrotik PPP Profile (for update)
     */
    public static function createFromMikrotikData(array $mikrotikData, string $routerId, ?string $createdBy = null): array
    {
        $rateLimit = $mikrotikData['rate-limit'] ?? null;
        $speeds = ['download' => null, 'upload' => null];
        
        if ($rateLimit) {
            $parts = explode(' ', $rateLimit);
            $maxLimit = $parts[0];
            $speedParts = explode('/', $maxLimit);
            $speeds['download'] = self::speedToKbps($speedParts[0] ?? null);
            $speeds['upload'] = self::speedToKbps($speedParts[1] ?? $speedParts[0] ?? null);
        }

        return [
            'router_id' => $routerId,
            'name' => $mikrotikData['name'],
            'mikrotik_profile_name' => $mikrotikData['name'],
            'mikrotik_profile_id' => $mikrotikData['.id'] ?? null,
            'rate_limit' => $rateLimit,
            'local_address' => $mikrotikData['local-address'] ?? null,
            'remote_address' => $mikrotikData['remote-address'] ?? null,
            'parent_queue' => $mikrotikData['parent-queue'] ?? null,
            'address_list' => $mikrotikData['address-list'] ?? null,
            'dns_server' => $mikrotikData['dns-server'] ?? null,
            'session_timeout' => self::parseTimeout($mikrotikData['session-timeout'] ?? null),
            'idle_timeout' => self::parseTimeout($mikrotikData['idle-timeout'] ?? null),
            'incoming_filter' => $mikrotikData['incoming-filter'] ?? null,
            'outgoing_filter' => $mikrotikData['outgoing-filter'] ?? null,
            'bridge' => $mikrotikData['bridge'] ?? null,
            'interface_list' => $mikrotikData['interface-list'] ?? null,
            'only_one' => ($mikrotikData['only-one'] ?? 'no') === 'yes',
            'mikrotik_comment' => $mikrotikData['comment'] ?? null,
            'speed_down' => $speeds['download'],
            'speed_up' => $speeds['upload'],
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'mikrotik_data' => $mikrotikData,
            'created_by' => $createdBy,
        ];
    }

    /**
     * Parse timeout string to seconds
     * Format: "1h", "30m", "1d", "1d1h30m"
     */
    public static function parseTimeout(?string $timeout): ?int
    {
        if (empty($timeout) || $timeout === 'none' || $timeout === '0s') {
            return null;
        }

        $seconds = 0;
        
        if (preg_match('/(\d+)w/', $timeout, $m)) {
            $seconds += (int) $m[1] * 604800;
        }
        if (preg_match('/(\d+)d/', $timeout, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)h/', $timeout, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)m/', $timeout, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)s/', $timeout, $m)) {
            $seconds += (int) $m[1];
        }

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * Convert to Mikrotik PPP Profile format
     */
    public function toMikrotikFormat(): array
    {
        $data = [
            'name' => $this->mikrotik_profile_name,
        ];

        if ($this->rate_limit) {
            $data['rate-limit'] = $this->rate_limit;
        }
        if ($this->local_address) {
            $data['local-address'] = $this->local_address;
        }
        if ($this->remote_address) {
            $data['remote-address'] = $this->remote_address;
        }
        if ($this->parent_queue) {
            $data['parent-queue'] = $this->parent_queue;
        }
        if ($this->address_list) {
            $data['address-list'] = $this->address_list;
        }
        if ($this->dns_server) {
            $data['dns-server'] = $this->dns_server;
        }
        if ($this->incoming_filter) {
            $data['incoming-filter'] = $this->incoming_filter;
        }
        if ($this->outgoing_filter) {
            $data['outgoing-filter'] = $this->outgoing_filter;
        }
        if ($this->bridge) {
            $data['bridge'] = $this->bridge;
        }
        if ($this->interface_list) {
            $data['interface-list'] = $this->interface_list;
        }
        if ($this->mikrotik_comment) {
            $data['comment'] = $this->mikrotik_comment;
        }
        
        $data['only-one'] = $this->only_one ? 'yes' : 'no';

        return $data;
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public packages
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope by router
     */
    public function scopeForRouter($query, $routerId)
    {
        return $query->where('router_id', $routerId);
    }
}
