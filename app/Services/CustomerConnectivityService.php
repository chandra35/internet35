<?php

namespace App\Services;

use App\Helpers\Mikrotik\MikrotikService;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CustomerConnectivityService
{
    public function summary(Customer $customer, bool $includeWifi = false): array
    {
        $customer->loadMissing(['router', 'package', 'onu']);

        $ppp = $this->pppStatus($customer);
        $acsDevice = $this->resolveAcsDevice($customer);
        if (!$acsDevice && $customer->pppoe_username) {
            $match = $this->autoMatchAcsDevice($customer);
            if (!empty($match['success'])) {
                $customer->refresh();
                $customer->loadMissing(['router', 'package', 'onu']);
                $acsDevice = $match['device'] ?? $this->resolveAcsDevice($customer);
            }
        }
        $acsSummary = null;
        $wifi = [];

        if ($acsDevice) {
            $genieacs = new GenieAcsService();
            $lastInform = $acsDevice['last_inform'] ? Carbon::parse($acsDevice['last_inform']) : null;
            $acsSummary = [
                'found' => true,
                'device_id' => $acsDevice['device_id'],
                'serial_number' => $acsDevice['serial_number'] ?? $customer->acs_serial_number,
                'manufacturer' => $acsDevice['manufacturer'] ?? null,
                'model' => $acsDevice['model'] ?? null,
                'software_version' => $acsDevice['software_version'] ?? null,
                'last_inform' => $lastInform?->toIso8601String(),
                'last_inform_human' => $lastInform?->diffForHumans(),
                'online' => $lastInform ? $lastInform->gt(now()->subMinutes(15)) : false,
                'stale' => $lastInform ? $lastInform->lt(now()->subHours(6)) : true,
                'ui_url' => rtrim((string) config('services.genieacs.ui_url'), '/') . '/#/devices/' . urlencode($acsDevice['device_id']),
            ];

            if ($includeWifi) {
                $wifi = $genieacs->getWifiInfo($acsDevice['device_id']);
            }
        } else {
            $acsSummary = [
                'found' => false,
                'online' => false,
                'stale' => true,
            ];
        }

        $onu = $customer->onu;
        $onuSummary = $onu ? [
            'id' => $onu->id,
            'serial_number' => $onu->serial_number,
            'status' => $onu->status,
            'status_label' => $onu->status_label,
            'rx_power' => $onu->rx_power,
            'tx_power' => $onu->tx_power,
            'olt_rx_power' => $onu->olt_rx_power,
            'wan_ip' => $onu->wan_ip,
            'last_sync_at' => $onu->last_sync_at?->diffForHumans(),
        ] : null;

        return [
            'overall' => $this->overallStatus($customer, $ppp, $acsSummary, $onuSummary),
            'ppp' => $ppp,
            'acs' => $acsSummary,
            'onu' => $onuSummary,
            'wifi' => $wifi,
        ];
    }

    public function autoMatchAcsDevice(Customer $customer): array
    {
        $genieacs = new GenieAcsService();
        if (!$genieacs->isAvailable()) {
            return ['success' => false, 'message' => 'GenieACS tidak tersedia'];
        }

        $device = null;
        $source = null;

        if ($customer->acs_device_id) {
            $device = $genieacs->getDevice($customer->acs_device_id);
            $source = 'saved';
        }

        if (!$device && $customer->onu?->serial_number) {
            $device = $genieacs->findDeviceBySerial($customer->onu->serial_number);
            $source = 'onu_serial';
        }

        if (!$device && $customer->acs_serial_number) {
            $device = $genieacs->findDeviceBySerial($customer->acs_serial_number);
            $source = 'customer_serial';
        }

        if (!$device && $customer->pppoe_username) {
            $device = $genieacs->findDeviceByPppoeUsername($customer->pppoe_username);
            $source = 'pppoe_username';
        }

        if (!$device) {
            return ['success' => false, 'message' => 'Device ACS belum ditemukan untuk pelanggan ini'];
        }

        $customer->forceFill([
            'acs_device_id' => $device['device_id'],
            'acs_serial_number' => $device['serial_number'] ?? $customer->acs_serial_number,
            'acs_last_matched_at' => now(),
            'acs_metadata' => [
                'source' => $source,
                'manufacturer' => $device['manufacturer'] ?? null,
                'model' => $device['model'] ?? null,
                'matched_at' => now()->toIso8601String(),
            ],
        ])->save();

        if ($customer->onu && !$customer->onu->customer_id) {
            $customer->onu->update(['customer_id' => $customer->id]);
        }

        $this->tagAcsDevice($customer, $device['device_id']);

        return [
            'success' => true,
            'message' => 'Device ACS berhasil dihubungkan ke pelanggan',
            'source' => $source,
            'device' => $device,
        ];
    }

    public function wifiInfo(Customer $customer): array
    {
        $device = $this->resolveAcsDevice($customer);
        if (!$device) {
            return ['success' => false, 'message' => 'Device ACS belum terhubung'];
        }

        $genieacs = new GenieAcsService();
        return [
            'success' => true,
            'device' => $device,
            'wifi' => $genieacs->getWifiInfo($device['device_id']),
        ];
    }

    public function updateWifi(Customer $customer, array $data, bool $mainOnly = false): array
    {
        $device = $this->resolveAcsDevice($customer);
        if (!$device) {
            return ['success' => false, 'message' => 'Device ACS belum terhubung'];
        }

        if ($mainOnly && !str_contains($data['wlan_path'], 'WLANConfiguration.1')) {
            return ['success' => false, 'message' => 'Portal pelanggan hanya boleh mengubah SSID utama'];
        }

        $genieacs = new GenieAcsService();
        $result = $genieacs->configureWifi($device['device_id'], [
            'wlan_path' => $data['wlan_path'],
            'ssid' => $data['ssid'] ?? null,
            'password' => $data['password'] ?? null,
        ]);

        if (!empty($result['success']) && str_contains($data['wlan_path'], 'WLANConfiguration.1')) {
            $customer->onu?->update(array_filter([
                'wifi_ssid' => $data['ssid'] ?? null,
                'wifi_password' => $data['password'] ?? null,
            ], fn ($value) => $value !== null));
        }

        return $result;
    }

    public function resolveAcsDevice(Customer $customer): ?array
    {
        $genieacs = new GenieAcsService();
        if (!$genieacs->isAvailable()) {
            return null;
        }

        if ($customer->acs_device_id) {
            $device = $genieacs->getDevice($customer->acs_device_id);
            if ($device) {
                return $device;
            }
        }

        if ($customer->onu?->serial_number) {
            return $genieacs->findDeviceBySerial($customer->onu->serial_number);
        }

        if ($customer->acs_serial_number) {
            return $genieacs->findDeviceBySerial($customer->acs_serial_number);
        }

        return null;
    }

    protected function pppStatus(Customer $customer): array
    {
        if (!$customer->router || !$customer->pppoe_username) {
            return [
                'checked' => false,
                'online' => false,
                'status' => 'not_configured',
                'message' => 'Router atau username PPPoE belum lengkap',
            ];
        }

        $mikrotik = new MikrotikService();
        try {
            if (!$mikrotik->connectRouter($customer->router)) {
                return [
                    'checked' => true,
                    'online' => false,
                    'status' => 'router_unreachable',
                    'message' => 'Router tidak dapat dihubungi',
                ];
            }

            $activeSessions = $mikrotik->getPppActive();
            $session = collect($activeSessions)->first(fn ($row) => ($row['name'] ?? null) === $customer->pppoe_username);

            if (!$session) {
                return [
                    'checked' => true,
                    'online' => false,
                    'status' => 'offline',
                    'message' => 'PPPoE tidak aktif',
                ];
            }

            $customer->forceFill([
                'remote_address' => $session['address'] ?? $customer->remote_address,
                'caller_id' => $session['caller-id'] ?? $customer->caller_id,
                'last_connected_at' => now(),
            ])->save();

            return [
                'checked' => true,
                'online' => true,
                'status' => 'online',
                'message' => 'PPPoE aktif',
                'address' => $session['address'] ?? null,
                'caller_id' => $session['caller-id'] ?? null,
                'uptime' => $session['uptime'] ?? null,
                'service' => $session['service'] ?? null,
                'encoding' => $session['encoding'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning("CustomerConnectivityService PPP check failed for {$customer->id}: " . $e->getMessage());
            return [
                'checked' => true,
                'online' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        } finally {
            try {
                $mikrotik->disconnect();
            } catch (\Throwable) {
            }
        }
    }

    protected function overallStatus(Customer $customer, array $ppp, array $acs, ?array $onu): array
    {
        if ($ppp['online'] ?? false) {
            return ['status' => 'online', 'label' => 'Online', 'color' => 'success'];
        }

        if (($acs['online'] ?? false) && $onu && ($onu['status'] ?? null) === 'online') {
            return ['status' => 'device_online_ppp_offline', 'label' => 'Device online, PPP offline', 'color' => 'warning'];
        }

        if ($acs['online'] ?? false) {
            return ['status' => 'acs_online', 'label' => 'ACS online', 'color' => 'info'];
        }

        if ($customer->status !== 'active') {
            return ['status' => $customer->status, 'label' => $customer->status_label, 'color' => $customer->status_color];
        }

        return ['status' => 'offline', 'label' => 'Offline', 'color' => 'secondary'];
    }

    protected function tagAcsDevice(Customer $customer, string $deviceId): void
    {
        try {
            (new GenieAcsService())->tagDevice($deviceId, [
                'internet35',
                'customer-' . $customer->customer_id,
                'pppoe-' . $customer->pppoe_username,
                'pop-' . ($customer->pop_id ?? 'unknown'),
            ]);
        } catch (\Throwable $e) {
            Log::debug("CustomerConnectivityService tag ACS skipped: " . $e->getMessage());
        }
    }
}
