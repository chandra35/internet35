<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GenieAcsService
{
    protected string $nbiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->nbiUrl = rtrim(config('services.genieacs.nbi_url', 'http://172.10.10.254:7557'), '/');
        $this->timeout = (int) config('services.genieacs.timeout', 10);
    }

    /**
     * Find GenieACS device by ONU serial number.
     * GenieACS device ID format: {OUI}-{ProductClass}-{SerialNumber}
     * The serial in GenieACS is the full hex SN (e.g. 48575443CA9CD3A3).
     */
    public function findDeviceBySerial(string $serialNumber): ?array
    {
        try {
            // The SN in our DB might be like "HWTC6ED42F9A" (short form)
            // In GenieACS, the serial is stored as full hex or in DeviceInfo
            // Try multiple search strategies

            // Strategy 1: Search by serial in _id (contains the hex serial)
            $hexSerial = $this->shortSnToHex($serialNumber);
            if ($hexSerial) {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->nbiUrl}/devices", [
                        'query' => json_encode(['_id' => ['$regex' => $hexSerial, '$options' => 'i']]),
                    ]);

                if ($response->ok()) {
                    $devices = $response->json();
                    if (!empty($devices)) {
                        return $this->parseDevice($devices[0]);
                    }
                }
            }

            // Strategy 2: Search by DeviceInfo.SerialNumber
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode([
                        'InternetGatewayDevice.DeviceInfo.SerialNumber._value' => ['$regex' => $serialNumber, '$options' => 'i'],
                    ]),
                ]);

            if ($response->ok()) {
                $devices = $response->json();
                if (!empty($devices)) {
                    return $this->parseDevice($devices[0]);
                }
            }

            // Strategy 3: Search by Device._DeviceId.SerialNumber
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode([
                        '_deviceId._SerialNumber' => ['$regex' => $serialNumber, '$options' => 'i'],
                    ]),
                ]);

            if ($response->ok()) {
                $devices = $response->json();
                if (!empty($devices)) {
                    return $this->parseDevice($devices[0]);
                }
            }

            return null;
        } catch (Exception $e) {
            Log::error("GenieACS findDeviceBySerial error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get device by GenieACS device ID.
     */
    public function getDevice(string $deviceId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices/{$deviceId}");

            if ($response->ok()) {
                return $this->parseDevice($response->json());
            }

            return null;
        } catch (Exception $e) {
            Log::error("GenieACS getDevice error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get full device info (parsed into structured format).
     */
    public function getDeviceInfo(string $deviceId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId]),
                ]);

            if (!$response->ok()) {
                return null;
            }

            $devices = $response->json();
            if (empty($devices)) {
                return null;
            }

            $device = $devices[0];
            $igd = $device['InternetGatewayDevice'] ?? $device['Device'] ?? [];
            $devInfo = $igd['DeviceInfo'] ?? [];

            return [
                'device_id' => $device['_id'],
                'last_inform' => $device['_lastInform'] ?? null,
                'registered' => $device['_registered'] ?? null,
                'manufacturer' => $this->getValue($devInfo, 'Manufacturer'),
                'model' => $this->getValue($devInfo, 'ModelName'),
                'software_version' => $this->getValue($devInfo, 'SoftwareVersion'),
                'hardware_version' => $this->getValue($devInfo, 'HardwareVersion'),
                'serial_number' => $this->getValue($devInfo, 'SerialNumber'),
                'provisioning_code' => $this->getValue($devInfo, 'ProvisioningCode'),
                'uptime' => $this->getValue($devInfo, 'UpTime'),
                'memory_status' => $this->getNestedValue($igd, 'DeviceInfo.MemoryStatus.Total'),
                'cpu_usage' => $this->getNestedValue($igd, 'DeviceInfo.ProcessStatus.CPUUsage'),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS getDeviceInfo error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get WAN connection info from device.
     */
    public function getWanInfo(string $deviceId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId]),
                ]);

            if (!$response->ok()) {
                return null;
            }

            $devices = $response->json();
            if (empty($devices)) {
                return null;
            }

            $device = $devices[0];
            $igd = $device['InternetGatewayDevice'] ?? $device['Device'] ?? [];
            $wanDevice = $igd['WANDevice'] ?? [];

            $connections = [];
            foreach ($wanDevice as $wdKey => $wdValue) {
                if (!is_array($wdValue) || $wdKey === '_object' || $wdKey === '_writable' || $wdKey === '_timestamp') continue;
                $wanConn = $wdValue['WANConnectionDevice'] ?? [];
                foreach ($wanConn as $wcKey => $wcValue) {
                    if (!is_array($wcValue) || $wcKey === '_object' || $wcKey === '_writable' || $wcKey === '_timestamp') continue;

                    // Check PPP connection
                    $ppp = $wcValue['WANPPPConnection'] ?? [];
                    foreach ($ppp as $pppKey => $pppValue) {
                        if (!is_array($pppValue) || $pppKey === '_object' || $pppKey === '_writable' || $pppKey === '_timestamp') continue;
                        $connections[] = [
                            'type' => 'PPPoE',
                            'path' => "InternetGatewayDevice.WANDevice.{$wdKey}.WANConnectionDevice.{$wcKey}.WANPPPConnection.{$pppKey}",
                            'name' => $this->getValue($pppValue, 'Name'),
                            'username' => $this->getValue($pppValue, 'Username'),
                            'status' => $this->getValue($pppValue, 'ConnectionStatus'),
                            'external_ip' => $this->getValue($pppValue, 'ExternalIPAddress'),
                            'vlan_id' => $this->getValue($pppValue, 'X_HW_VLAN'),
                        ];
                    }

                    // Check IP connection
                    $ipConn = $wcValue['WANIPConnection'] ?? [];
                    foreach ($ipConn as $ipKey => $ipValue) {
                        if (!is_array($ipValue) || $ipKey === '_object' || $ipKey === '_writable' || $ipKey === '_timestamp') continue;
                        $connections[] = [
                            'type' => 'IP',
                            'path' => "InternetGatewayDevice.WANDevice.{$wdKey}.WANConnectionDevice.{$wcKey}.WANIPConnection.{$ipKey}",
                            'name' => $this->getValue($ipValue, 'Name'),
                            'addressing_type' => $this->getValue($ipValue, 'AddressingType'),
                            'status' => $this->getValue($ipValue, 'ConnectionStatus'),
                            'external_ip' => $this->getValue($ipValue, 'ExternalIPAddress'),
                            'vlan_id' => $this->getValue($ipValue, 'X_HW_VLAN'),
                        ];
                    }
                }
            }

            return $connections;
        } catch (Exception $e) {
            Log::error("GenieACS getWanInfo error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get LAN hosts / DHCP leases from device.
     */
    public function getLanHosts(string $deviceId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId]),
                ]);

            if (!$response->ok()) {
                return [];
            }

            $devices = $response->json();
            if (empty($devices)) {
                return [];
            }

            $device = $devices[0];
            $igd = $device['InternetGatewayDevice'] ?? $device['Device'] ?? [];
            $lanDevice = $igd['LANDevice'] ?? [];
            $hosts = [];

            foreach ($lanDevice as $ldKey => $ldValue) {
                if (!is_array($ldValue) || $ldKey === '_object') continue;
                $hostList = $ldValue['Hosts'] ?? [];
                $hostEntries = $hostList['Host'] ?? [];
                foreach ($hostEntries as $hKey => $hValue) {
                    if (!is_array($hValue) || $hKey === '_object') continue;
                    $hosts[] = [
                        'hostname' => $this->getValue($hValue, 'HostName'),
                        'ip' => $this->getValue($hValue, 'IPAddress'),
                        'mac' => $this->getValue($hValue, 'MACAddress'),
                        'active' => $this->getValue($hValue, 'Active'),
                        'interface' => $this->getValue($hValue, 'InterfaceType'),
                    ];
                }
            }

            return $hosts;
        } catch (Exception $e) {
            Log::error("GenieACS getLanHosts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set parameter values on device.
     */
    public function setParameterValues(string $deviceId, array $parameterValues): array
    {
        try {
            $params = [];
            foreach ($parameterValues as $name => $value) {
                $params[] = [$name, $value[0], $value[1] ?? 'xsd:string'];
            }

            $response = Http::timeout($this->timeout)
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks", [
                    'name' => 'setParameterValues',
                    'parameterValues' => $params,
                ]);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'task_id' => $response->json('_id'),
                'status' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS setParameterValues error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Request device to refresh its parameter values (GetParameterValues).
     */
    public function refreshDevice(string $deviceId, string $parameterPath = ''): array
    {
        try {
            $task = ['name' => 'getParameterValues'];
            if ($parameterPath) {
                $task['parameterNames'] = [$parameterPath];
            }

            $response = Http::timeout($this->timeout)
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request", $task);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'task_id' => $response->json('_id'),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS refreshDevice error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reboot device via TR069.
     */
    public function rebootDevice(string $deviceId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request", [
                    'name' => 'reboot',
                ]);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'task_id' => $response->json('_id'),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS rebootDevice error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get pending tasks for a device.
     */
    public function getDeviceTasks(string $deviceId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/tasks", [
                    'query' => json_encode(['device' => $deviceId]),
                ]);

            return $response->ok() ? $response->json() : [];
        } catch (Exception $e) {
            Log::error("GenieACS getDeviceTasks error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a device from GenieACS.
     */
    public function deleteDevice(string $deviceId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete("{$this->nbiUrl}/devices/{$deviceId}");

            return $response->ok();
        } catch (Exception $e) {
            Log::error("GenieACS deleteDevice error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure WAN PPPoE on device via TR069.
     */
    public function configureWanPppoe(string $deviceId, array $config): array
    {
        $vlan = $config['vlan'] ?? 100;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $basePath = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection';

        // Check if WANPPPConnection instance already exists
        $wanInfo = $this->getWanInfo($deviceId);
        $existingPpp = null;
        if ($wanInfo) {
            foreach ($wanInfo as $wan) {
                if ($wan['type'] === 'PPPoE' && !empty($wan['path'])) {
                    $existingPpp = $wan['path'];
                    break;
                }
            }
        }

        if ($existingPpp) {
            // Use existing PPP connection
            $wanPath = $existingPpp;
        } else {
            // Create new WANPPPConnection instance via AddObject
            $addResult = $this->addObject($deviceId, "{$basePath}.");
            if (!$addResult['success']) {
                return ['success' => false, 'message' => 'Gagal membuat WANPPPConnection: ' . ($addResult['message'] ?? 'unknown error')];
            }
            // After AddObject, the new instance will be at index 1 (first instance)
            $wanPath = "{$basePath}.1";
        }

        $params = [
            "{$wanPath}.Enable" => [true, 'xsd:boolean'],
            "{$wanPath}.ConnectionType" => ['IP_Routed', 'xsd:string'],
            "{$wanPath}.Username" => [$username, 'xsd:string'],
            "{$wanPath}.Password" => [$password, 'xsd:string'],
            "{$wanPath}.NATEnabled" => [true, 'xsd:boolean'],
            "{$wanPath}.X_HW_VLAN" => [(int) $vlan, 'xsd:int'],
            "{$wanPath}.Name" => ['PPPoE_WAN', 'xsd:string'],
        ];

        return $this->setParameterValues($deviceId, $params);
    }

    /**
     * Get WiFi (WLAN) configuration from device.
     */
    public function getWifiInfo(string $deviceId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId]),
                ]);

            if (!$response->ok()) return [];

            $devices = $response->json();
            if (empty($devices)) return [];

            $device = $devices[0];
            $igd = $device['InternetGatewayDevice'] ?? $device['Device'] ?? [];
            $lanDevice = $igd['LANDevice'] ?? [];
            $wlans = [];

            foreach ($lanDevice as $ldKey => $ldValue) {
                if (!is_array($ldValue) || $ldKey === '_object' || $ldKey === '_writable' || $ldKey === '_timestamp') continue;
                $wlanConfig = $ldValue['WLANConfiguration'] ?? [];
                foreach ($wlanConfig as $wKey => $wValue) {
                    if (!is_array($wValue) || $wKey === '_object' || $wKey === '_writable' || $wKey === '_timestamp') continue;
                    $wlans[] = [
                        'path' => "InternetGatewayDevice.LANDevice.{$ldKey}.WLANConfiguration.{$wKey}",
                        'ssid' => $this->getValue($wValue, 'SSID'),
                        'enabled' => $this->getValue($wValue, 'Enable'),
                        'channel' => $this->getValue($wValue, 'Channel'),
                        'standard' => $this->getValue($wValue, 'Standard'),
                        'security_mode' => $this->getValue($wValue, 'BeaconType'),
                        'encryption' => $this->getValue($wValue, 'WPAEncryptionModes') ?? $this->getValue($wValue, 'IEEE11iEncryptionModes'),
                        'password' => $this->getValue($wValue, 'PreSharedKey.1.PreSharedKey') ?? $this->getValue($wValue, 'KeyPassphrase'),
                        'mac_address' => $this->getValue($wValue, 'BSSID'),
                        'total_associations' => $this->getValue($wValue, 'TotalAssociations'),
                    ];
                }
            }

            return $wlans;
        } catch (Exception $e) {
            Log::error("GenieACS getWifiInfo error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Configure WiFi SSID and password.
     */
    public function configureWifi(string $deviceId, array $config): array
    {
        $wlanPath = $config['wlan_path'] ?? 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1';

        $params = [];

        if (isset($config['ssid'])) {
            $params["{$wlanPath}.SSID"] = [$config['ssid'], 'xsd:string'];
        }

        if (isset($config['password'])) {
            $params["{$wlanPath}.PreSharedKey.1.PreSharedKey"] = [$config['password'], 'xsd:string'];
            $params["{$wlanPath}.KeyPassphrase"] = [$config['password'], 'xsd:string'];
        }

        if (isset($config['enabled'])) {
            $params["{$wlanPath}.Enable"] = [(bool) $config['enabled'], 'xsd:boolean'];
        }

        if (empty($params)) {
            return ['success' => false, 'message' => 'No parameters to set'];
        }

        return $this->setParameterValues($deviceId, $params);
    }

    /**
     * Get LAN port info from device.
     */
    public function getLanPortInfo(string $deviceId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId]),
                ]);

            if (!$response->ok()) return [];

            $devices = $response->json();
            if (empty($devices)) return [];

            $device = $devices[0];
            $igd = $device['InternetGatewayDevice'] ?? $device['Device'] ?? [];
            $lanDevice = $igd['LANDevice'] ?? [];
            $ports = [];

            foreach ($lanDevice as $ldKey => $ldValue) {
                if (!is_array($ldValue) || $ldKey === '_object' || $ldKey === '_writable' || $ldKey === '_timestamp') continue;
                $ethConfig = $ldValue['LANEthernetInterfaceConfig'] ?? [];
                foreach ($ethConfig as $eKey => $eValue) {
                    if (!is_array($eValue) || $eKey === '_object' || $eKey === '_writable' || $eKey === '_timestamp') continue;
                    $ports[] = [
                        'path' => "InternetGatewayDevice.LANDevice.{$ldKey}.LANEthernetInterfaceConfig.{$eKey}",
                        'name' => "eth_0/{$eKey}",
                        'enabled' => $this->getValue($eValue, 'Enable'),
                        'status' => $this->getValue($eValue, 'Status'),
                        'mac_address' => $this->getValue($eValue, 'MACAddress'),
                        'max_bit_rate' => $this->getValue($eValue, 'MaxBitRate'),
                        'duplex_mode' => $this->getValue($eValue, 'DuplexMode'),
                    ];
                }
            }

            return $ports;
        } catch (Exception $e) {
            Log::error("GenieACS getLanPortInfo error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add an object instance (e.g. create new WAN connection).
     */
    public function addObject(string $deviceId, string $objectPath): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request", [
                    'name' => 'addObject',
                    'objectName' => $objectPath,
                ]);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'task_id' => $response->json('_id'),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS addObject error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a pending task.
     */
    public function deleteTask(string $taskId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete("{$this->nbiUrl}/tasks/{$taskId}");
            return $response->ok();
        } catch (Exception $e) {
            Log::error("GenieACS deleteTask error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get comprehensive device summary for TR069 management page.
     */
    public function getDeviceSummary(string $deviceId): ?array
    {
        $deviceInfo = $this->getDeviceInfo($deviceId);
        if (!$deviceInfo) return null;

        return [
            'device' => $deviceInfo,
            'wan_connections' => $this->getWanInfo($deviceId) ?? [],
            'wifi' => $this->getWifiInfo($deviceId),
            'lan_ports' => $this->getLanPortInfo($deviceId),
            'lan_hosts' => $this->getLanHosts($deviceId),
            'tasks' => $this->getDeviceTasks($deviceId),
        ];
    }

    /**
     * Check if GenieACS server is reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->nbiUrl}/devices?query={}&limit=1");
            return $response->ok();
        } catch (Exception $e) {
            return false;
        }
    }

    // ===== Helper Methods =====

    protected function parseDevice(array $raw): array
    {
        $igd = $raw['InternetGatewayDevice'] ?? $raw['Device'] ?? [];
        $devInfo = $igd['DeviceInfo'] ?? [];

        return [
            'device_id' => $raw['_id'] ?? null,
            'last_inform' => $raw['_lastInform'] ?? null,
            'registered' => $raw['_registered'] ?? null,
            'manufacturer' => $this->getValue($devInfo, 'Manufacturer'),
            'model' => $this->getValue($devInfo, 'ModelName'),
            'software_version' => $this->getValue($devInfo, 'SoftwareVersion'),
            'hardware_version' => $this->getValue($devInfo, 'HardwareVersion'),
            'serial_number' => $this->getValue($devInfo, 'SerialNumber'),
        ];
    }

    protected function getValue(array $parent, string $key): mixed
    {
        if (!isset($parent[$key])) {
            return null;
        }

        $field = $parent[$key];

        if (is_array($field) && array_key_exists('_value', $field)) {
            return $field['_value'];
        }

        return null;
    }

    protected function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        if (is_array($current) && array_key_exists('_value', $current)) {
            return $current['_value'];
        }

        return null;
    }

    /**
     * Convert short SN format (e.g. HWTC6ED42F9A) to hex format used in GenieACS ID.
     * ONU SN = 4-byte vendor ID + 4-byte serial
     * HWTC = vendor (Huawei), 6ED42F9A = serial part
     * In GenieACS: full hex = 48575443 + 6ED42F9A (HWTC in ASCII hex + serial hex)
     */
    protected function shortSnToHex(string $sn): ?string
    {
        if (strlen($sn) < 8) {
            return null;
        }

        // First 4 chars are vendor code in ASCII
        $vendor = substr($sn, 0, 4);
        $serial = substr($sn, 4);

        // Convert vendor to hex
        $vendorHex = '';
        for ($i = 0; $i < strlen($vendor); $i++) {
            $vendorHex .= strtoupper(dechex(ord($vendor[$i])));
        }

        return $vendorHex . strtoupper($serial);
    }
}
