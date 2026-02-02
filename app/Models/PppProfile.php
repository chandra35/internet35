<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PppProfile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'router_id',
        'pop_id',
        'mikrotik_id',
        'name',
        'local_address',
        'remote_address',
        'bridge',
        'rate_limit',
        'incoming_filter',
        'outgoing_filter',
        'address_list',
        'session_timeout',
        'idle_timeout',
        'keepalive_timeout',
        'dns_server',
        'wins_server',
        'parent_queue',
        'queue_type',
        'change_tcp_mss',
        'use_upnp',
        'use_mpls',
        'use_compression',
        'use_encryption',
        'only_one',
        'comment',
        'is_default',
        'is_synced',
        'last_synced_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Router relationship
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * POP owner relationship
     */
    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    /**
     * Packages using this profile
     */
    public function packages()
    {
        return $this->hasMany(Package::class, 'mikrotik_profile_name', 'name');
    }

    /**
     * Parse rate limit to download/upload
     */
    public function getParsedRateLimitAttribute(): ?array
    {
        if (!$this->rate_limit) {
            return null;
        }

        $parts = explode('/', $this->rate_limit);
        return [
            'upload' => $parts[0] ?? null,
            'download' => $parts[1] ?? $parts[0] ?? null,
        ];
    }

    /**
     * Get formatted rate for display
     */
    public function getFormattedRateAttribute(): string
    {
        if (!$this->rate_limit) {
            return 'Unlimited';
        }
        return str_replace('/', ' / ', $this->rate_limit);
    }

    /**
     * Convert Mikrotik profile data to model attributes
     */
    public static function fromMikrotikData(array $data, string $routerId, ?string $popId = null): array
    {
        return [
            'router_id' => $routerId,
            'pop_id' => $popId,
            'mikrotik_id' => $data['.id'] ?? null,
            'name' => $data['name'] ?? '',
            'local_address' => $data['local-address'] ?? null,
            'remote_address' => $data['remote-address'] ?? null,
            'bridge' => $data['bridge'] ?? null,
            'rate_limit' => $data['rate-limit'] ?? null,
            'incoming_filter' => $data['incoming-filter'] ?? null,
            'outgoing_filter' => $data['outgoing-filter'] ?? null,
            'address_list' => $data['address-list'] ?? null,
            'session_timeout' => $data['session-timeout'] ?? null,
            'idle_timeout' => $data['idle-timeout'] ?? null,
            'keepalive_timeout' => $data['keepalive-timeout'] ?? null,
            'dns_server' => $data['dns-server'] ?? null,
            'wins_server' => $data['wins-server'] ?? null,
            'parent_queue' => $data['parent-queue'] ?? null,
            'queue_type' => $data['queue-type'] ?? null,
            'change_tcp_mss' => $data['change-tcp-mss'] ?? null,
            'use_upnp' => $data['use-upnp'] ?? null,
            'use_mpls' => $data['use-mpls'] ?? null,
            'use_compression' => $data['use-compression'] ?? null,
            'use_encryption' => $data['use-encryption'] ?? null,
            'only_one' => $data['only-one'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_default' => ($data['default'] ?? 'no') === 'yes',
            'is_synced' => true,
            'last_synced_at' => now(),
        ];
    }
}
