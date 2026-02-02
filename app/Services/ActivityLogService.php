<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class ActivityLogService
{
    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Log activity
     */
    public function log(
        string $action,
        ?string $module = null,
        ?string $description = null,
        ?array $oldData = null,
        ?array $newData = null
    ): ActivityLog {
        $request = request();
        $ipAddress = $this->getClientIp();
        
        // Force get location data - always try to get location
        $locationData = $this->getLocationData($ipAddress);

        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => $ipAddress,
            'local_ip' => $request->server('REMOTE_ADDR'),
            'country' => $locationData['country'] ?? null,
            'country_code' => $locationData['countryCode'] ?? null,
            'region' => $locationData['region'] ?? null,
            'region_name' => $locationData['regionName'] ?? null,
            'city' => $locationData['city'] ?? null,
            'district' => $locationData['district'] ?? null,
            'zip' => $locationData['zip'] ?? null,
            'latitude' => $locationData['lat'] ?? null,
            'longitude' => $locationData['lon'] ?? null,
            'timezone' => $locationData['timezone'] ?? null,
            'isp' => $locationData['isp'] ?? null,
            'org' => $locationData['org'] ?? null,
            'as_name' => $locationData['as'] ?? null,
            'os' => $this->agent->platform(),
            'os_version' => $this->agent->version($this->agent->platform()),
            'browser' => $this->agent->browser(),
            'browser_version' => $this->agent->version($this->agent->browser()),
            'device' => $this->agent->device(),
            'device_type' => $this->getDeviceType(),
            'is_mobile' => $this->agent->isMobile(),
            'is_tablet' => $this->agent->isTablet(),
            'is_desktop' => $this->agent->isDesktop(),
            'is_bot' => $this->agent->isRobot(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }

    /**
     * Get client IP address
     */
    protected function getClientIp(): ?string
    {
        $request = request();
        
        foreach ([
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ] as $key) {
            if ($request->server($key)) {
                $ip = $request->server($key);
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $request->ip();
    }

    /**
     * Get location data from IP (force detection)
     * Will try to get public IP if local IP is detected
     */
    protected function getLocationData(?string $ip): array
    {
        try {
            // If local IP, try to get public IP first
            if (!$ip || $ip === '127.0.0.1' || $ip === '::1' || $this->isPrivateIp($ip)) {
                // Get public IP from external service
                $publicIpResponse = Http::timeout(3)->get('https://api.ipify.org?format=json');
                if ($publicIpResponse->successful()) {
                    $publicIpData = $publicIpResponse->json();
                    $ip = $publicIpData['ip'] ?? null;
                }
            }

            // If still no valid IP, try alternative service
            if (!$ip || $this->isPrivateIp($ip)) {
                $altResponse = Http::timeout(3)->get('https://ifconfig.me/ip');
                if ($altResponse->successful()) {
                    $ip = trim($altResponse->body());
                }
            }

            // Now get location data
            if ($ip && !$this->isPrivateIp($ip)) {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,isp,org,as");
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === 'success') {
                        return $data;
                    }
                }

                // Fallback to ipinfo.io
                $fallbackResponse = Http::timeout(5)->get("https://ipinfo.io/{$ip}/json");
                if ($fallbackResponse->successful()) {
                    $fallbackData = $fallbackResponse->json();
                    if (!isset($fallbackData['error'])) {
                        // Parse location from ipinfo format
                        $loc = explode(',', $fallbackData['loc'] ?? '');
                        return [
                            'country' => $fallbackData['country'] ?? null,
                            'countryCode' => $fallbackData['country'] ?? null,
                            'region' => $fallbackData['region'] ?? null,
                            'regionName' => $fallbackData['region'] ?? null,
                            'city' => $fallbackData['city'] ?? null,
                            'district' => null,
                            'zip' => $fallbackData['postal'] ?? null,
                            'lat' => isset($loc[0]) ? floatval($loc[0]) : null,
                            'lon' => isset($loc[1]) ? floatval($loc[1]) : null,
                            'timezone' => $fallbackData['timezone'] ?? null,
                            'isp' => $fallbackData['org'] ?? null,
                            'org' => $fallbackData['org'] ?? null,
                            'as' => null,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail
            \Log::warning('Location detection failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Check if IP is private/local
     */
    protected function isPrivateIp(?string $ip): bool
    {
        if (!$ip) {
            return true;
        }
        
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Get device type
     */
    protected function getDeviceType(): string
    {
        if ($this->agent->isTablet()) {
            return 'Tablet';
        }
        if ($this->agent->isMobile()) {
            return 'Mobile';
        }
        if ($this->agent->isDesktop()) {
            return 'Desktop';
        }
        if ($this->agent->isRobot()) {
            return 'Bot';
        }
        return 'Unknown';
    }

    /**
     * Quick logging methods
     */
    public function logLogin(): ActivityLog
    {
        return $this->log('login', 'auth', 'User logged in');
    }

    public function logLogout(): ActivityLog
    {
        return $this->log('logout', 'auth', 'User logged out');
    }

    public function logCreate(string $module, string $description, ?array $newData = null): ActivityLog
    {
        return $this->log('create', $module, $description, null, $newData);
    }

    public function logUpdate(string $module, string $description, ?array $oldData = null, ?array $newData = null): ActivityLog
    {
        return $this->log('update', $module, $description, $oldData, $newData);
    }

    public function logDelete(string $module, string $description, ?array $oldData = null): ActivityLog
    {
        return $this->log('delete', $module, $description, $oldData);
    }

    public function logView(string $module, string $description): ActivityLog
    {
        return $this->log('view', $module, $description);
    }
}
