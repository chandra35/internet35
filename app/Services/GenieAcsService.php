<?php

namespace App\Services;

use Carbon\Carbon;
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
     * Encode '%' in device ID for use in URL path segments.
     *
     * GenieACS stores some device IDs with literal '%' characters
     * (e.g. "00259E-HG8145X6%2D10-..."). HTTP clients (Guzzle/cURL) decode
     * percent-encoded sequences when parsing URL strings, so "%2D" becomes "-"
     * and GenieACS returns 404. Double-encoding '%' → '%25' ensures the literal
     * '%' survives the HTTP client's URL parsing.
     *
     * For device IDs without '%', this is a no-op — no impact on existing ONUs.
     */
    private function safeDeviceId(string $deviceId): string
    {
        return str_replace('%', '%25', $deviceId);
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
            // Huawei encodes vendor prefix as ASCII hex (HWTC -> 48575443), other
            // vendors (e.g. Fiberhome FHTT...) store the serial as-is in _id.
            // Try both the hex-encoded form and the raw serial.
            $idCandidates = [];
            $hexSerial = $this->shortSnToHex($serialNumber);
            if ($hexSerial) {
                $idCandidates[] = $hexSerial;
            }
            $idCandidates[] = $serialNumber;

            foreach (array_unique($idCandidates) as $idRegex) {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->nbiUrl}/devices", [
                        'query' => json_encode(['_id' => ['$regex' => $idRegex, '$options' => 'i']]),
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
        $deviceId = $this->safeDeviceId($deviceId);
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
                            'gateway' => $this->getValue($pppValue, 'DefaultGateway') ?: $this->getValue($pppValue, 'RemoteIPAddress'),
                            'dns' => trim(($this->getValue($pppValue, 'DNSServers') ?: '') . ' ' . ($this->getValue($pppValue, 'X_HW_DNS') ?: '')),
                            'uptime' => $this->getValue($pppValue, 'Uptime'),
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
                            'subnet_mask' => $this->getValue($ipValue, 'SubnetMask'),
                            'gateway' => $this->getValue($ipValue, 'DefaultGateway'),
                            'dns' => $this->getValue($ipValue, 'DNSServers'),
                            'uptime' => $this->getValue($ipValue, 'Uptime'),
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
    public function setParameterValues(string $deviceId, array $parameterValues, bool $connectionRequest = false): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        $params = [];
        foreach ($parameterValues as $name => $value) {
            $params[] = [$name, $value[0], $value[1] ?? 'xsd:string'];
        }
        $payload = ['name' => 'setParameterValues', 'parameterValues' => $params];

        try {
            $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks";
            if ($connectionRequest) {
                $url .= '?connection_request';
            }

            $response = Http::timeout($this->timeout)->asJson()->post($url, $payload);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'pending' => $response->status() === 202,
                'task_id' => $response->json('_id'),
                'status'  => $response->status(),
            ];
        } catch (Exception $e) {
            // cURL 52 (empty reply) or 28 (timeout) = Connection Request to ONU failed.
            // Queue without connection_request so task runs at next periodic inform.
            if ($connectionRequest && preg_match('/cURL error (52|28|7)/', $e->getMessage())) {
                Log::warning("GenieACS setParameterValues CR failed ({$e->getMessage()}), queuing without connection_request");
                try {
                    $queued = Http::timeout(10)->asJson()
                        ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks", $payload);
                    if ($queued->status() === 200 || $queued->status() === 202) {
                        return [
                            'success' => true,
                            'pending' => true,
                            'task_id' => $queued->json('_id'),
                            'status'  => $queued->status(),
                        ];
                    }
                } catch (Exception $inner) {
                    Log::error("GenieACS setParameterValues queue fallback failed: " . $inner->getMessage());
                }
            }
            Log::error("GenieACS setParameterValues error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Request device to refresh its parameter values (GetParameterValues).
     */
    public function refreshDevice(string $deviceId, string $parameterPath = ''): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        try {
            $task = ['name' => 'getParameterValues'];
            if ($parameterPath) {
                $task['parameterNames'] = [$parameterPath];
            }

            $response = Http::timeout($this->timeout)
                ->asJson()
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
        $deviceId = $this->safeDeviceId($deviceId);
        try {
            $response = Http::timeout($this->timeout)
                ->asJson()
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
        $deviceId = $this->safeDeviceId($deviceId);
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
     *
     * Path discovery (penting untuk Huawei pasca factory reset):
     *  - Default firmware Huawei kadang punya WANConnectionDevice.1 = WANIPConnection
     *    (TR069 mgmt) saja, BELUM ada WANPPPConnection di mana pun.
     *  - Hard-code path `WCD.1.WANPPPConnection.1` akan menyebabkan fault 9005
     *    "Invalid parameter name" karena slot itu reserved untuk IPConnection.
     *  - Helper findOrCreatePppoeWanPath() mendeteksi PPP eksisting, atau
     *    addObject WANConnectionDevice baru bila perlu.
     */
    public function configureWanPppoe(string $deviceId, array $config): array
    {
        $vlan = $config['vlan'] ?? 100;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $pathResult = $this->findOrCreatePppoeWanPath($deviceId);
        if (!$pathResult['success']) {
            return $pathResult; // {success:false, pending?:bool, message:string}
        }
        $wanPath = $pathResult['path'];

        $params = $this->buildWanPppoeParams($wanPath, [
            'username' => $username,
            'password' => $password,
            'vlan'     => $vlan,
            'enable'   => true,
            'name'     => 'PPPoE_WAN',
        ], $this->getBrandByDeviceId($deviceId));

        return $this->setParameterValues($deviceId, $params, true);
    }

    /**
     * Find existing PPPoE WAN path or create one (idempotent, multi-step aware).
     *
     * Returns one of:
     *   {success:true,  path:'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.N.WANPPPConnection.M'}
     *   {success:false, pending:true,  message:'...tunggu inform berikutnya...'}
     *   {success:false, message:'...error...'}
     */
    protected function findOrCreatePppoeWanPath(string $deviceId): array
    {
        // 1. Reuse existing PPP if any
        $wanInfo = $this->getWanInfo($deviceId) ?: [];
        foreach ($wanInfo as $wan) {
            if (($wan['type'] ?? null) === 'PPPoE' && !empty($wan['path'])) {
                return ['success' => true, 'path' => $wan['path']];
            }
        }

        // 2. Avoid duplicate addObject in queue
        foreach ($this->getDeviceTasks($deviceId) as $task) {
            if (($task['name'] ?? '') === 'addObject' &&
                str_contains($task['objectName'] ?? '', 'WANConnectionDevice')) {
                return [
                    'success' => false,
                    'pending' => true,
                    'message' => 'Task AddObject WANConnectionDevice/WANPPPConnection masih dalam antrian. Tunggu ONU inform berikutnya (1-3 menit), lalu klik "Setup PPPoE WAN" lagi.',
                ];
            }
        }

        // 3. Cari WCD existing yang punya container WANPPPConnection (boleh kosong)
        //    dan tidak dipakai oleh WANIPConnection (untuk hindari konflik TR069 mgmt).
        $wcdParent = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice';
        $resp = Http::timeout($this->timeout)->get("{$this->nbiUrl}/devices", [
            'query'      => json_encode(['_id' => $deviceId]),
            'projection' => $wcdParent,
        ]);
        $tree = $resp->json()[0]['InternetGatewayDevice']['WANDevice']['1']['WANConnectionDevice'] ?? [];

        $candidateWcd = null;
        foreach ($tree as $idx => $val) {
            if (!is_array($val) || str_starts_with((string) $idx, '_')) continue;
            $hasIp  = !empty(array_filter(($val['WANIPConnection']  ?? []), 'is_array'));
            $hasPpp = !empty(array_filter(($val['WANPPPConnection'] ?? []), 'is_array'));
            // PPP container ada (mungkin kosong) tapi instance belum ada → bisa addObject di sini
            if (!$hasPpp && !$hasIp && array_key_exists('WANPPPConnection', $val)) {
                $candidateWcd = $idx;
                break;
            }
        }

        // 4. Kalau tidak ada WCD kosong, buat WCD baru
        if ($candidateWcd === null) {
            // Snapshot WCD indices before addObject to detect the new one
            $existingWcdIdx = array_filter(array_keys($tree), fn($k) => is_numeric($k));

            $addWcd = $this->addObject($deviceId, "{$wcdParent}.", true);
            if (!$addWcd['success']) {
                return ['success' => false, 'message' => 'Gagal membuat WANConnectionDevice baru: ' . ($addWcd['message'] ?? 'unknown')];
            }
            if (!($addWcd['completed'] ?? false)) {
                return [
                    'success' => false,
                    'pending' => true,
                    'message' => 'AddObject WANConnectionDevice di-queue. Tunggu ONU inform (1-3 menit), lalu klik "Setup PPPoE WAN" lagi.',
                ];
            }
            $candidateWcd = $addWcd['instance'] ?? null;

            // instanceNumber missing in some GenieACS responses — re-read tree to find new WCD
            if (!$candidateWcd) {
                $resp2 = Http::timeout($this->timeout)->get("{$this->nbiUrl}/devices", [
                    'query'      => json_encode(['_id' => $deviceId]),
                    'projection' => $wcdParent,
                ]);
                $tree2 = $resp2->json()[0]['InternetGatewayDevice']['WANDevice']['1']['WANConnectionDevice'] ?? [];
                foreach (array_keys($tree2) as $idx2) {
                    if (is_numeric($idx2) && !in_array($idx2, $existingWcdIdx)) {
                        $candidateWcd = $idx2;
                        break;
                    }
                }
                // Still not found — pick highest numeric index
                if (!$candidateWcd) {
                    $numericIdx = array_filter(array_keys($tree2), 'is_numeric');
                    if ($numericIdx) {
                        $candidateWcd = max($numericIdx);
                    }
                }
            }

            if (!$candidateWcd) {
                return [
                    'success' => false,
                    'pending' => true,
                    'message' => 'AddObject WCD berhasil tapi index tidak diketahui. Tunggu ONU inform (1-3 menit), lalu klik "Setup PPPoE WAN" lagi.',
                ];
            }
        }

        // 5. AddObject WANPPPConnection di WCD terpilih
        $pppParent = "{$wcdParent}.{$candidateWcd}.WANPPPConnection";
        $addPpp = $this->addObject($deviceId, "{$pppParent}.", true);
        if (!$addPpp['success']) {
            return ['success' => false, 'message' => 'Gagal membuat WANPPPConnection di WCD.' . $candidateWcd . ': ' . ($addPpp['message'] ?? 'unknown')];
        }
        if (!($addPpp['completed'] ?? false)) {
            return [
                'success' => false,
                'pending' => true,
                'message' => 'AddObject WANPPPConnection di-queue. Tunggu ONU inform (1-3 menit), lalu klik "Setup PPPoE WAN" lagi.',
            ];
        }
        $pppInstance = $addPpp['instance'] ?? 1;

        return [
            'success' => true,
            'path'    => "{$pppParent}.{$pppInstance}",
        ];
    }

    /**
     * Build WAN PPPoE SetParameterValues payload tailored per vendor.
     *
     * Different ONU vendors expose VLAN tagging through different TR-069
     * parameters. This helper centralizes the per-brand differences so
     * configureWanPppoe()/updateWanPppoe() stay generic.
     *
     * Supported brands: huawei (X_HW_VLAN), fiberhome (VLAN encoded in .Name).
     * Other brands fall back to standard parameters with no VLAN tagging
     * (caller should configure VLAN at OLT/OMCI side instead).
     *
     * $config keys (all optional):
     *   - username, password (string)
     *   - vlan     (int)
     *   - enable   (bool, default true when key present)
     *   - name     (string, used as base for vendor-specific Name encoding)
     */
    protected function buildWanPppoeParams(string $wanPath, array $config, string $brand = 'unknown'): array
    {
        $params = [];

        if (array_key_exists('enable', $config)) {
            $params["{$wanPath}.Enable"] = [(bool) $config['enable'], 'xsd:boolean'];
            $params["{$wanPath}.ConnectionType"] = ['IP_Routed', 'xsd:string'];
            $params["{$wanPath}.NATEnabled"] = [true, 'xsd:boolean'];
        }

        if (isset($config['username'])) {
            $params["{$wanPath}.Username"] = [(string) $config['username'], 'xsd:string'];
        }
        if (isset($config['password']) && $config['password'] !== '') {
            $params["{$wanPath}.Password"] = [(string) $config['password'], 'xsd:string'];
        }

        $vlan = isset($config['vlan']) ? (int) $config['vlan'] : null;
        $baseName = $config['name'] ?? 'PPPoE_WAN';

        switch ($brand) {
            case 'huawei':
                if ($vlan !== null) {
                    $params["{$wanPath}.X_HW_VLAN"] = [$vlan, 'xsd:int'];
                }
                if (array_key_exists('enable', $config)) {
                    $params["{$wanPath}.Name"] = [$baseName, 'xsd:string'];
                    // PPPoE WAN HARUS dikunci ke service "INTERNET" saja.
                    // Default firmware Huawei sering "TR069_INTERNET" → ONU pakai
                    // IP PPPoE publik (10.x) sebagai ConnectionRequestURL, sehingga
                    // GenieACS (di network internal 172.10.10.x) tidak bisa kirim
                    // Connection Request balik → semua task setParameterValues
                    // menumpuk di antrian.
                    // Lihat docs/TR069_HUAWEI_SERVICELIST.md
                    $params["{$wanPath}.X_HW_SERVICELIST"] = ['INTERNET', 'xsd:string'];
                }
                break;

            case 'fiberhome':
                // Fiberhome encodes VLAN inside the Name field using
                // convention "{idx}_{MODE}_{R|B}_VID_{vlan}". Picking idx=2
                // because idx=1 is typically reserved for TR069 mgmt WAN.
                // Setting only Name (no separate VLAN parameter exists).
                if ($vlan !== null) {
                    $params["{$wanPath}.Name"] = ["2_INTERNET_R_VID_{$vlan}", 'xsd:string'];
                } elseif (array_key_exists('enable', $config)) {
                    $params["{$wanPath}.Name"] = [$baseName, 'xsd:string'];
                }
                break;

            default:
                // Unknown / generic vendors: only set Name when caller asked
                // for a fresh setup. VLAN must be provisioned at the OLT.
                if (array_key_exists('enable', $config)) {
                    $params["{$wanPath}.Name"] = [$baseName, 'xsd:string'];
                }
                break;
        }

        return $params;
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
                    $channel = $this->getValue($wValue, 'Channel');
                    // Detect band: prefer explicit OperatingFrequencyBand, fallback to channel number
                    $freqBand = $this->getValue($wValue, 'OperatingFrequencyBand')
                        ?? $this->getValue($wValue, 'X_HW_FrequencyBand')
                        ?? $this->getValue($wValue, 'X_HW_FREQ_BAND');
                    if (!$freqBand && $channel !== null && $channel !== '') {
                        $ch = (int) $channel;
                        $freqBand = ($ch >= 36) ? '5GHz' : '2.4GHz';
                    }

                    $wlans[] = [
                        'path' => "InternetGatewayDevice.LANDevice.{$ldKey}.WLANConfiguration.{$wKey}",
                        'index' => $wKey,
                        'ssid' => $this->getValue($wValue, 'SSID'),
                        'enabled' => $this->getValue($wValue, 'Enable'),
                        'channel' => $channel,
                        'band' => $freqBand,
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

        // Always use connection_request so device is woken immediately
        return $this->setParameterValues($deviceId, $params, true);
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
                        'path'       => "InternetGatewayDevice.LANDevice.{$ldKey}.LANEthernetInterfaceConfig.{$eKey}",
                        'index'      => (int) $eKey,
                        'name'       => $this->getValue($eValue, 'Name') ?: "eth0:{$eKey}",
                        'enabled'    => $this->getValue($eValue, 'Enable'),
                        'status'     => $this->getValue($eValue, 'Status'),
                        'mac_address'=> $this->getValue($eValue, 'MACAddress'),
                        'max_bit_rate' => $this->getValue($eValue, 'MaxBitRate'),
                        'hw_speed'   => $this->getValue($eValue, 'X_HW_Speed'),
                        'duplex_mode'=> $this->getValue($eValue, 'DuplexMode'),
                        'hw_duplex'  => $this->getValue($eValue, 'X_HW_DuplexMode'),
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
     * Get DHCP server info from LANHostConfigManagement.
     */
    public function getLanDhcpInfo(string $deviceId): array
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
            $result = [];

            foreach ($lanDevice as $ldKey => $ldValue) {
                if (!is_array($ldValue) || $ldKey === '_object' || $ldKey === '_writable' || $ldKey === '_timestamp') continue;

                $lhcm = $ldValue['LANHostConfigManagement'] ?? [];
                if (empty($lhcm)) continue;

                // Detect if critical params have never been fetched (no _value/_timestamp)
                // If so, trigger a targeted getParameterValues in the background.
                $hasIpValue = isset($lhcm['IPRouters']['_value']) || isset($lhcm['IPRouters']['_timestamp']);
                $hasDhcpValue = isset($lhcm['DHCPServerEnable']['_value']) || isset($lhcm['DHCPServerEnable']['_timestamp']);
                if (!$hasIpValue || !$hasDhcpValue) {
                    $this->triggerLanParamFetch($deviceId, (string) $ldKey);
                }

                // IP address: try LANHostConfigManagement.IPRouters first,
                // then fall back to LANIPInterface.1.IPInterfaceIPAddress (Huawei / some vendors)
                $ipAddress  = $this->getValue($lhcm, 'IPRouters');
                $subnetMask = $this->getValue($lhcm, 'SubnetMask');

                $lanIpIface = $ldValue['LANIPInterface'] ?? [];
                foreach ($lanIpIface as $ifKey => $ifValue) {
                    if (!is_array($ifValue) || str_starts_with((string) $ifKey, '_')) continue;
                    if (!$ipAddress)  $ipAddress  = $this->getValue($ifValue, 'IPInterfaceIPAddress');
                    if (!$subnetMask) $subnetMask = $this->getValue($ifValue, 'SubnetMask');
                    break;
                }

                $result = [
                    'dhcp_server_enable'   => $this->getValue($lhcm, 'DHCPServerEnable'),
                    'ip_interface_address' => $ipAddress,
                    'subnet_mask'          => $subnetMask,
                    'min_address'          => $this->getValue($lhcm, 'MinAddress'),
                    'max_address'          => $this->getValue($lhcm, 'MaxAddress'),
                    'lease_time'           => $this->getValue($lhcm, 'DHCPLeaseTime'),
                    'dns_servers'          => $this->getValue($lhcm, 'DNSServers'),
                    'domain_name'          => $this->getValue($lhcm, 'DomainName'),
                    'gateway_mac'          => $this->getValue($lhcm, 'MACAddress'),
                    'needs_fetch'          => (!$hasIpValue || !$hasDhcpValue),
                ];
                break;
            }

            return $result;
        } catch (Exception $e) {
            Log::error("GenieACS getLanDhcpInfo error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set LAN / DHCP server configuration.
     * Writes to LANDevice.1.LANHostConfigManagement and LANIPInterface.1 (standard TR-098).
     * Also writes LANIPInterface for vendors (Huawei) that store the LAN IP there.
     */
    public function setLanDhcpConfig(string $deviceId, array $config): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        try {
            $base  = 'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement';
            $iface = 'InternetGatewayDevice.LANDevice.1.LANIPInterface.1';

            $paramValues = [];

            // IP address: write to both IPRouters (DHCP option) and LANIPInterface (actual LAN IP)
            if (!empty($config['gateway_ip'])) {
                $paramValues[] = ["{$base}.IPRouters",          $config['gateway_ip'], 'xsd:string'];
                $paramValues[] = ["{$iface}.IPInterfaceIPAddress", $config['gateway_ip'], 'xsd:string'];
            }
            // Subnet mask: write to both LHCM and LANIPInterface
            if (!empty($config['subnet_mask'])) {
                $paramValues[] = ["{$base}.SubnetMask",     $config['subnet_mask'], 'xsd:string'];
                $paramValues[] = ["{$iface}.SubnetMask",    $config['subnet_mask'], 'xsd:string'];
            }

            $simpleMap = [
                'dhcp_server_enable' => ["{$base}.DHCPServerEnable", 'xsd:boolean'],
                'min_address'        => ["{$base}.MinAddress",        'xsd:string'],
                'max_address'        => ["{$base}.MaxAddress",        'xsd:string'],
                'lease_time'         => ["{$base}.DHCPLeaseTime",     'xsd:unsignedInt'],
                'dns_servers'        => ["{$base}.DNSServers",        'xsd:string'],
                'domain_name'        => ["{$base}.DomainName",        'xsd:string'],
            ];
            foreach ($simpleMap as $key => [$oid, $type]) {
                if (!array_key_exists($key, $config) || $config[$key] === null || $config[$key] === '') continue;
                $paramValues[] = [$oid, (string) $config[$key], $type];
            }

            if (empty($paramValues)) {
                return ['success' => false, 'message' => 'Tidak ada parameter yang diubah.'];
            }

            $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request&timeout=20000";
            $response = Http::timeout(30)
                ->asJson()
                ->post($url, [
                    'name'            => 'setParameterValues',
                    'parameterValues' => $paramValues,
                ]);

            $ok = in_array($response->status(), [200, 202]);
            return [
                'success' => $ok,
                'pending' => $response->status() === 202,
                'message' => $ok ? 'Konfigurasi LAN/DHCP berhasil disimpan.' : 'Gagal: ' . $response->body(),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS setLanDhcpConfig error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Add a MAC address to WiFi blacklist on Huawei devices.
     * For non-Huawei or Ethernet clients, this is a no-op (returns success so DB entry still persists).
     */
    public function blockClientMac(string $deviceId, string $mac, string $brand = 'unknown'): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        // Normalise MAC to colon-separated uppercase
        $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
        if (strlen($mac) === 12) {
            $mac = implode(':', str_split($mac, 2));
        }

        // Only Huawei supports WiFi MAC blacklist via TR-069 in a known-good way
        if ($brand !== 'huawei') {
            return ['success' => true, 'device_blocked' => false, 'message' => 'MAC disimpan di daftar blokir. Pemblokiran di perangkat hanya didukung untuk Huawei.'];
        }

        try {
            // Fetch current blacklist entries from both WiFi bands
            $resp = Http::timeout($this->timeout)->get("{$this->nbiUrl}/devices", ['query' => json_encode(['_id' => $deviceId])]);
            $devices = $resp->ok() ? $resp->json() : [];
            $igd = $devices[0]['InternetGatewayDevice'] ?? $devices[0]['Device'] ?? [];
            $lanDevice = $igd['LANDevice']['1'] ?? [];
            $wlanConfigs = $lanDevice['WLANConfiguration'] ?? [];

            $results = [];
            foreach ($wlanConfigs as $idx => $wlan) {
                if (!is_array($wlan) || !isset($wlan['SSID'])) continue;

                $blacklistOid = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$idx}.AccessControl.X_HW_BlockList";
                $enableOid    = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$idx}.AccessControl.Enable";

                $currentList = $this->getValue($wlan['AccessControl'] ?? [], 'X_HW_BlockList') ?? '';
                $macs = array_filter(array_map('trim', explode(',', $currentList)));
                if (!in_array($mac, $macs)) {
                    $macs[] = $mac;
                }
                $newList = implode(',', $macs);

                $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request&timeout=15000";
                $response = Http::timeout(30)->asJson()->post($url, [
                    'name' => 'setParameterValues',
                    'parameterValues' => [
                        [$enableOid, 'true', 'xsd:boolean'],
                        [$blacklistOid, $newList, 'xsd:string'],
                    ],
                ]);
                $results[] = $response->status();
            }

            $ok = !empty($results);
            return ['success' => true, 'device_blocked' => $ok, 'message' => 'MAC diblokir di perangkat.'];
        } catch (Exception $e) {
            Log::error("GenieACS blockClientMac error: " . $e->getMessage());
            // Don't fail — the DB entry still gets saved
            return ['success' => true, 'device_blocked' => false, 'message' => 'MAC disimpan, tapi gagal kirim ke perangkat: ' . $e->getMessage()];
        }
    }

    /**
     * Remove a MAC address from WiFi blacklist on Huawei devices.
     */
    public function unblockClientMac(string $deviceId, string $mac, string $brand = 'unknown'): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
        if (strlen($mac) === 12) {
            $mac = implode(':', str_split($mac, 2));
        }

        if ($brand !== 'huawei') {
            return ['success' => true, 'device_unblocked' => false, 'message' => 'MAC dihapus dari daftar blokir.'];
        }

        try {
            $resp = Http::timeout($this->timeout)->get("{$this->nbiUrl}/devices", ['query' => json_encode(['_id' => $deviceId])]);
            $devices = $resp->ok() ? $resp->json() : [];
            $igd = $devices[0]['InternetGatewayDevice'] ?? $devices[0]['Device'] ?? [];
            $lanDevice = $igd['LANDevice']['1'] ?? [];
            $wlanConfigs = $lanDevice['WLANConfiguration'] ?? [];

            foreach ($wlanConfigs as $idx => $wlan) {
                if (!is_array($wlan) || !isset($wlan['SSID'])) continue;

                $blacklistOid = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.{$idx}.AccessControl.X_HW_BlockList";
                $currentList = $this->getValue($wlan['AccessControl'] ?? [], 'X_HW_BlockList') ?? '';
                $macs = array_filter(array_map('trim', explode(',', $currentList)));
                $macs = array_values(array_filter($macs, fn($m) => strtoupper($m) !== $mac));
                $newList = implode(',', $macs);

                $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request&timeout=15000";
                Http::timeout(30)->asJson()->post($url, [
                    'name' => 'setParameterValues',
                    'parameterValues' => [[$blacklistOid, $newList, 'xsd:string']],
                ]);
            }

            return ['success' => true, 'device_unblocked' => true, 'message' => 'MAC berhasil di-unblok dari perangkat.'];
        } catch (Exception $e) {
            Log::error("GenieACS unblockClientMac error: " . $e->getMessage());
            return ['success' => true, 'device_unblocked' => false, 'message' => 'MAC dihapus dari list, tapi gagal update perangkat: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a WAN connection instance (WANPPPConnection only — never call on IP/management WANs).
     */
    public function deleteWanConnection(string $deviceId, string $wanPath): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        // Safety guard: only allow deleting PPPoE connections
        if (!str_contains($wanPath, 'WANPPPConnection')) {
            return ['success' => false, 'message' => 'Hanya WAN PPPoE yang boleh dihapus.'];
        }

        try {
            $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request&timeout=15000";

            $response = Http::timeout(30)
                ->asJson()
                ->post($url, [
                    'name' => 'deleteObject',
                    'objectName' => rtrim($wanPath, '.'),
                ]);

            $ok = $response->status() === 200 || $response->status() === 202;

            return [
                'success' => $ok,
                'pending' => $response->status() === 202,
                'message' => $ok ? 'WAN berhasil dihapus.' : 'Gagal menghapus WAN: ' . $response->body(),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS deleteWanConnection error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update an existing PPPoE WAN connection (username, password, VLAN).
     * $wanPath must point to an existing WANPPPConnection.X path.
     */
    public function updateWanPppoe(string $deviceId, string $wanPath, array $config): array
    {
        // Safety guard: only allow editing PPPoE connections
        if (!str_contains($wanPath, 'WANPPPConnection')) {
            return ['success' => false, 'message' => 'Hanya WAN PPPoE yang boleh diedit.'];
        }

        $brand = $this->getBrandByDeviceId($deviceId);
        $params = $this->buildWanPppoeParams($wanPath, $config, $brand);

        if (empty($params)) {
            return ['success' => false, 'message' => 'Tidak ada parameter yang diubah.'];
        }

        return $this->setParameterValues($deviceId, $params, true);
    }

    /**
     * Add an object instance (e.g. create new WAN connection).
     */
    public function addObject(string $deviceId, string $objectPath, bool $waitComplete = false): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        $payload = [
            'name'       => 'addObject',
            'objectName' => rtrim($objectPath, '.'),
        ];
        try {
            $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request";
            if ($waitComplete) {
                $url .= '&timeout=15000';
            }

            $response = Http::timeout(30)->asJson()->post($url, $payload);

            return [
                'success'   => $response->status() === 200 || $response->status() === 202,
                'completed' => $response->status() === 200,
                'task_id'   => $response->json('_id'),
                'instance'  => $response->json('instanceNumber'),
            ];
        } catch (Exception $e) {
            // cURL 52 (empty reply) or 28 (timeout) = Connection Request to ONU failed
            // (e.g. CR auth mismatch after password change). Fall back to queuing the
            // task without connection_request so it runs at next periodic inform.
            if (preg_match('/cURL error (52|28|7)/', $e->getMessage())) {
                Log::warning("GenieACS addObject CR failed ({$e->getMessage()}), queuing without connection_request");
                try {
                    $queued = Http::timeout(10)->asJson()
                        ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks", $payload);
                    if ($queued->status() === 200 || $queued->status() === 202) {
                        return [
                            'success'   => true,
                            'completed' => false,
                            'pending'   => true,
                            'task_id'   => $queued->json('_id'),
                            'instance'  => null,
                        ];
                    }
                } catch (Exception $inner) {
                    Log::error("GenieACS addObject queue fallback failed: " . $inner->getMessage());
                }
            }
            Log::error("GenieACS addObject error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete an object instance on device (TR-069 DeleteObject).
     */
    public function deleteObject(string $deviceId, string $objectPath): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        try {
            $url = "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request&timeout=15000";

            $response = Http::timeout(30)
                ->asJson()
                ->post($url, [
                    'name' => 'deleteObject',
                    'objectName' => rtrim($objectPath, '.'),
                ]);

            return [
                'success'   => $response->status() === 200 || $response->status() === 202,
                'completed' => $response->status() === 200,
                'pending'   => $response->status() === 202,
            ];
        } catch (Exception $e) {
            Log::error("GenieACS deleteObject error: " . $e->getMessage());
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
     * Change web UI user password via X_HW_UserInfo.
     * For HG8245H: username is 'admin' (user level) or 'telecomadmin' (admin level).
     */
    public function setWebUserPassword(string $deviceId, string $username, string $password): array
    {
        return $this->setParameterValues($deviceId, [
            'InternetGatewayDevice.X_HW_UserInfo.UserName' => [$username, 'xsd:string'],
            'InternetGatewayDevice.X_HW_UserInfo.Password' => [$password, 'xsd:string'],
        ], true);
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
            'lan_dhcp' => $this->getLanDhcpInfo($deviceId),
            'tasks' => $this->getDeviceTasks($deviceId),
        ];
    }

    /**
     * Clear all pending tasks for a device before creating new ones.
     */
    public function clearDeviceTasks(string $deviceId, ?string $taskName = null): int
    {
        $tasks = $this->getDeviceTasks($deviceId);
        $cleared = 0;
        foreach ($tasks as $task) {
            if ($taskName && ($task['name'] ?? '') !== $taskName) continue;
            if ($this->deleteTask($task['_id'] ?? '')) {
                $cleared++;
            }
        }
        return $cleared;
    }

    /**
     * Fire-and-forget: request specific LANHostConfigManagement parameter values
     * for devices where GenieACS has the param definition but no fetched value.
     */
    protected function triggerLanParamFetch(string $deviceId, string $lanIndex = '1'): void
    {
        $deviceId = $this->safeDeviceId($deviceId);
        $base = "InternetGatewayDevice.LANDevice.{$lanIndex}.LANHostConfigManagement";
        $params = [
            "{$base}.IPRouters",
            "{$base}.SubnetMask",
            "{$base}.DHCPServerEnable",
            "{$base}.MinAddress",
            "{$base}.MaxAddress",
            "{$base}.DHCPLeaseTime",
            "{$base}.DNSServers",
            "{$base}.DomainName",
            "{$base}.MACAddress",
        ];
        try {
            Http::timeout(5)->asJson()->post(
                "{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request",
                ['name' => 'getParameterValues', 'parameterNames' => $params]
            );
        } catch (Exception $e) {
            Log::warning("GenieACS triggerLanParamFetch: " . $e->getMessage());
        }
    }

    /**
     * Smart refresh: clear existing getParameterValues tasks first, then create one.
     * Also sends a targeted task for LAN/DHCP params that may never have been fetched.
     */
    public function smartRefresh(string $deviceId): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        // Clear existing pending getParameterValues tasks to avoid accumulation
        $this->clearDeviceTasks($deviceId, 'getParameterValues');

        // Targeted task: force-fetch LANHostConfigManagement fields that
        // GenieACS may only know as "writable" but never retrieved the values for.
        $lanParams = [
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.IPRouters',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.SubnetMask',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DHCPServerEnable',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.MinAddress',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.MaxAddress',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DHCPLeaseTime',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DNSServers',
            'InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DomainName',
        ];
        try {
            Http::timeout($this->timeout)
                ->asJson()
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request", [
                    'name'           => 'getParameterValues',
                    'parameterNames' => $lanParams,
                ]);
        } catch (Exception $e) {
            Log::warning("GenieACS smartRefresh LAN task error: " . $e->getMessage());
        }

        return $this->refreshDevice($deviceId, 'InternetGatewayDevice.');
    }

    /**
     * Get security/firewall info from device.
     * Auto-detects brand and uses appropriate OID paths.
     */
    public function getSecurityInfo(string $deviceId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId]),
                ]);

            if (!$response->ok()) return [];
            $devices = $response->json();
            if (empty($devices)) return [];

            $rawDevice = $devices[0];
            $brand     = $this->detectBrand($rawDevice);
            $igd       = $rawDevice['InternetGatewayDevice'] ?? $rawDevice['Device'] ?? [];

            $result = match ($brand) {
                'huawei'  => $this->getSecurityInfoHuawei($igd),
                'zte'     => $this->getSecurityInfoZte($igd),
                default   => $this->getSecurityInfoGeneric($igd), // nokia, fiberhome, tp-link, sercomm, calix, dzs, etc.
            };

            // Centralize brand label here so routing method doesn't need to know the brand name
            $brandLabels = [
                'huawei'    => 'Huawei',
                'zte'       => 'ZTE',
                'tp-link'   => 'TP-Link',
                'fiberhome' => 'FiberHome',
                'nokia'     => 'Nokia',
                'sercomm'   => 'Sercomm',
                'calix'     => 'Calix',
                'dzs'       => 'DZS',
                'unknown'   => 'Unknown',
            ];

            return array_merge($result, [
                'brand'       => $brand,
                'brand_label' => $brandLabels[$brand] ?? ucfirst($brand),
            ]);
        } catch (Exception $e) {
            Log::error("GenieACS getSecurityInfo error: " . $e->getMessage());
            return [];
        }
    }

    // ── Brand-specific security info getters ─────────────────────────────────

    protected function getSecurityInfoHuawei(array $igd): array
    {
        $xhwSec  = $igd['X_HW_Security'] ?? [];
        $acl     = $xhwSec['AclServices'] ?? [];
        $dos     = $xhwSec['Dosfilter'] ?? [];
        $fwLevel = $this->getValue($xhwSec, 'X_HW_FirewallLevel') ?? $this->getValue($xhwSec, 'Level');

        $ui        = $igd['UserInterface'] ?? [];
        $cliSsh    = $ui['X_HW_CLISSHControl'] ?? [];
        $cliTelnet = $ui['X_HW_CLITelnetAccess'] ?? [];
        $cliUsers  = $ui['X_HW_CLIUserInfo'] ?? [];
        $webUsers  = $ui['X_HW_WebUserInfo'] ?? [];

        $cliUser1 = [];
        foreach ($cliUsers as $k => $v) {
            if (!is_array($v) || str_starts_with((string) $k, '_')) continue;
            $cliUser1 = $v;
            break;
        }

        $webUser  = $webUsers['1'] ?? $webUsers[1] ?? [];
        $webAdmin = $webUsers['2'] ?? $webUsers[2] ?? [];

        return [
            'brand_label'    => 'Huawei',
            'acl_supported'  => true,
            'cli_supported'  => true,
            'firewall_level' => $fwLevel,
            'default_gateway' => $this->getValue($igd['Layer3Forwarding'] ?? [], 'DefaultConnectionService'),
            'dns_servers'    => $this->extractDnsFromWan($igd),
            'acs'            => $this->extractAcsInfo($igd),
            'acl' => [
                'ftp_lan'    => $this->asBool($this->getValue($acl, 'FTPLanEnable')),
                'ftp_wan'    => $this->asBool($this->getValue($acl, 'FTPWanEnable')),
                'http_lan'   => $this->asBool($this->getValue($acl, 'HTTPLanEnable')),
                'http_wan'   => $this->asBool($this->getValue($acl, 'HTTPWanEnable')),
                'ssh_lan'    => $this->asBool($this->getValue($acl, 'SSHLanEnable')),
                'ssh_wan'    => $this->asBool($this->getValue($acl, 'SSHWanEnable')),
                'samba_lan'  => $this->asBool($this->getValue($acl, 'SamBaLanEnable')),
                'samba_wan'  => $this->asBool($this->getValue($acl, 'SamBaWanEnable')),
                'telnet_lan' => $this->asBool($this->getValue($acl, 'TELNETLanEnable')),
                'telnet_wan' => $this->asBool($this->getValue($acl, 'TELNETWanEnable')),
                'icmp_echo'  => $this->asBool($this->getValue($dos, 'IcmpEchoReplyEn')),
            ],
            'cli' => [
                'ssh_enable'    => $this->asBool($this->getValue($cliSsh, 'Enable')),
                'telnet_enable' => $this->asBool($this->getValue($cliTelnet, 'Access')),
                'telnet_port'   => $this->getValue($cliTelnet, 'TelnetPort'),
                'telnet_wan'    => $this->asBool($this->getValue($cliTelnet, 'X_HW_WanSecurityEnable')),
                'username'      => $this->getValue($cliUser1, 'Username'),
            ],
            'web_user' => [
                'enable'   => $this->asBool($this->getValue($webUser, 'Enable')),
                'username' => $this->getValue($webUser, 'UserName'),
            ],
            'web_admin' => [
                'enable'   => $this->asBool($this->getValue($webAdmin, 'Enable')),
                'username' => $this->getValue($webAdmin, 'UserName'),
            ],
        ];
    }

    protected function getSecurityInfoZte(array $igd): array
    {
        // Standard TR-098 Users table (IGD.Users.User.{i}.*)
        $users    = $igd['Users']['User'] ?? [];
        $stdUser1 = $users['1'] ?? $users[1] ?? [];
        $stdUser2 = $users['2'] ?? $users[2] ?? [];

        // ZTE vendor security extensions (model-dependent, try common paths)
        $zteSec  = $igd['X_ZTE-COM_SecurityMgmt'] ?? $igd['X_ZTE_COM_SecurityMgmt']
                ?? $igd['X_ZTE-COM_Security'] ?? [];
        $fwLevel = $this->getValue($zteSec, 'FirewallLevel') ?? $this->getValue($zteSec, 'Level');

        return [
            'brand_label'    => 'ZTE',
            'acl_supported'  => false,
            'cli_supported'  => false,
            'firewall_level' => $fwLevel,
            'default_gateway' => $this->getValue($igd['Layer3Forwarding'] ?? [], 'DefaultConnectionService'),
            'dns_servers'    => $this->extractDnsFromWan($igd),
            'acs'            => $this->extractAcsInfo($igd),
            'acl'            => null,
            'cli'            => null,
            'web_user' => [
                'enable'   => $this->asBool($this->getValue($stdUser1, 'Enable')),
                'username' => $this->getValue($stdUser1, 'Username'),
            ],
            'web_admin' => [
                'enable'   => $this->asBool($this->getValue($stdUser2, 'Enable')),
                'username' => $this->getValue($stdUser2, 'Username'),
            ],
        ];
    }

    protected function getSecurityInfoTpLink(array $igd): array
    {
        // TP-Link typically uses standard TR-098 Users table
        $users = $igd['Users']['User'] ?? [];
        $user1 = $users['1'] ?? $users[1] ?? [];
        $user2 = $users['2'] ?? $users[2] ?? [];

        return [
            'brand_label'    => 'TP-Link',
            'acl_supported'  => false,
            'cli_supported'  => false,
            'firewall_level' => null,
            'default_gateway' => $this->getValue($igd['Layer3Forwarding'] ?? [], 'DefaultConnectionService'),
            'dns_servers'    => $this->extractDnsFromWan($igd),
            'acs'            => $this->extractAcsInfo($igd),
            'acl'            => null,
            'cli'            => null,
            'web_user' => [
                'enable'   => $this->asBool($this->getValue($user1, 'Enable')),
                'username' => $this->getValue($user1, 'Username'),
            ],
            'web_admin' => [
                'enable'   => $this->asBool($this->getValue($user2, 'Enable')),
                'username' => $this->getValue($user2, 'Username'),
            ],
        ];
    }

    protected function getSecurityInfoGeneric(array $igd): array
    {
        // Unknown brand: try standard TR-098 Users table, return minimal info
        $users = $igd['Users']['User'] ?? [];
        $user1 = $users['1'] ?? $users[1] ?? [];
        $user2 = $users['2'] ?? $users[2] ?? [];

        return [
            'brand_label'    => 'Unknown',
            'acl_supported'  => false,
            'cli_supported'  => false,
            'firewall_level' => null,
            'default_gateway' => $this->getValue($igd['Layer3Forwarding'] ?? [], 'DefaultConnectionService'),
            'dns_servers'    => $this->extractDnsFromWan($igd),
            'acs'            => $this->extractAcsInfo($igd),
            'acl'            => null,
            'cli'            => null,
            'web_user' => [
                'enable'   => $this->asBool($this->getValue($user1, 'Enable')),
                'username' => $this->getValue($user1, 'Username'),
            ],
            'web_admin' => [
                'enable'   => $this->asBool($this->getValue($user2, 'Enable')),
                'username' => $this->getValue($user2, 'Username'),
            ],
        ];
    }

    /**
     * Set security / remote access settings.
     * Auto-detects brand and uses appropriate OID paths.
     */
    public function setSecuritySettings(string $deviceId, array $settings): array
    {
        try {
            $resp  = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", ['query' => json_encode(['_id' => $deviceId])]);
            $brand = ($resp->ok() && !empty($resp->json()))
                ? $this->detectBrand($resp->json()[0])
                : 'unknown';
        } catch (Exception $e) {
            $brand = 'unknown';
        }

        return match ($brand) {
            'huawei'  => $this->setSecuritySettingsHuawei($deviceId, $settings),
            // All standard TR-098 brands use the same Users.User.{i} table
            'zte', 'tp-link', 'fiberhome', 'nokia', 'sercomm', 'calix', 'dzs'
                      => $this->setSecuritySettingsZte($deviceId, $settings),
            default   => ['success' => false, 'message' => 'Brand ONU tidak dikenali. Pengaturan tidak dapat dikirim secara otomatis.'],
        };
    }

    // ── Brand-specific security setters ──────────────────────────────────────

    protected function setSecuritySettingsHuawei(string $deviceId, array $settings): array
    {
        $params  = [];
        $aclBase = 'InternetGatewayDevice.X_HW_Security.AclServices';
        $dosBase = 'InternetGatewayDevice.X_HW_Security.Dosfilter';
        $uiBase  = 'InternetGatewayDevice.UserInterface';

        $boolMap = [
            'acl_ftp_lan'       => "{$aclBase}.FTPLanEnable",
            'acl_ftp_wan'       => "{$aclBase}.FTPWanEnable",
            'acl_http_lan'      => "{$aclBase}.HTTPLanEnable",
            'acl_http_wan'      => "{$aclBase}.HTTPWanEnable",
            'acl_ssh_lan'       => "{$aclBase}.SSHLanEnable",
            'acl_ssh_wan'       => "{$aclBase}.SSHWanEnable",
            'acl_samba_lan'     => "{$aclBase}.SamBaLanEnable",
            'acl_samba_wan'     => "{$aclBase}.SamBaWanEnable",
            'acl_telnet_lan'    => "{$aclBase}.TELNETLanEnable",
            'acl_telnet_wan'    => "{$aclBase}.TELNETWanEnable",
            'acl_icmp_echo'     => "{$dosBase}.IcmpEchoReplyEn",
            'cli_ssh_enable'    => "{$uiBase}.X_HW_CLISSHControl.Enable",
            'cli_telnet_enable' => "{$uiBase}.X_HW_CLITelnetAccess.Access",
            'cli_telnet_wan'    => "{$uiBase}.X_HW_CLITelnetAccess.X_HW_WanSecurityEnable",
            'web_user_enable'   => "{$uiBase}.X_HW_WebUserInfo.1.Enable",
            'web_admin_enable'  => "{$uiBase}.X_HW_WebUserInfo.2.Enable",
        ];

        foreach ($boolMap as $key => $oid) {
            if (array_key_exists($key, $settings)) {
                $params[$oid] = [(bool) $settings[$key], 'xsd:boolean'];
            }
        }

        if (array_key_exists('web_user_username', $settings) && $settings['web_user_username'] !== '') {
            $params["{$uiBase}.X_HW_WebUserInfo.1.UserName"] = [$settings['web_user_username'], 'xsd:string'];
        }
        if (array_key_exists('web_admin_username', $settings) && $settings['web_admin_username'] !== '') {
            $params["{$uiBase}.X_HW_WebUserInfo.2.UserName"] = [$settings['web_admin_username'], 'xsd:string'];
        }
        if (array_key_exists('web_user_password', $settings) && $settings['web_user_password'] !== '') {
            $params["{$uiBase}.X_HW_WebUserInfo.1.Password"] = [$settings['web_user_password'], 'xsd:string'];
        }
        if (array_key_exists('web_admin_password', $settings) && $settings['web_admin_password'] !== '') {
            $params["{$uiBase}.X_HW_WebUserInfo.2.Password"] = [$settings['web_admin_password'], 'xsd:string'];
        }
        if (array_key_exists('cli_password', $settings) && $settings['cli_password'] !== '') {
            $params["{$uiBase}.X_HW_CLIUserInfo.1.Userpassword"] = [$settings['cli_password'], 'xsd:string'];
        }

        if (empty($params)) {
            return ['success' => false, 'message' => 'Tidak ada parameter yang diubah'];
        }

        return $this->setParameterValues($deviceId, $params, true);
    }

    protected function setSecuritySettingsZte(string $deviceId, array $settings): array
    {
        $params   = [];
        $userBase = 'InternetGatewayDevice.Users.User';

        // Standard TR-098 Users table (supported by ZTE and TP-Link)
        $map = [
            'web_user_enable'   => ["{$userBase}.1.Enable",    'xsd:boolean'],
            'web_admin_enable'  => ["{$userBase}.2.Enable",    'xsd:boolean'],
        ];
        foreach ($map as $key => [$oid, $type]) {
            if (array_key_exists($key, $settings)) {
                $params[$oid] = [$type === 'xsd:boolean' ? (bool) $settings[$key] : $settings[$key], $type];
            }
        }
        if (array_key_exists('web_user_username', $settings) && $settings['web_user_username'] !== '') {
            $params["{$userBase}.1.Username"] = [$settings['web_user_username'], 'xsd:string'];
        }
        if (array_key_exists('web_user_password', $settings) && $settings['web_user_password'] !== '') {
            $params["{$userBase}.1.Password"] = [$settings['web_user_password'], 'xsd:string'];
        }
        if (array_key_exists('web_admin_username', $settings) && $settings['web_admin_username'] !== '') {
            $params["{$userBase}.2.Username"] = [$settings['web_admin_username'], 'xsd:string'];
        }
        if (array_key_exists('web_admin_password', $settings) && $settings['web_admin_password'] !== '') {
            $params["{$userBase}.2.Password"] = [$settings['web_admin_password'], 'xsd:string'];
        }

        if (empty($params)) {
            return ['success' => false, 'message' => 'Tidak ada parameter yang diubah'];
        }

        return $this->setParameterValues($deviceId, $params, true);
    }

    /**
     * Get current firmware info and check if download is possible.
     */
    public function getFirmwareInfo(string $deviceId): array
    {
        $info = $this->getDeviceInfo($deviceId);
        return [
            'current_version' => $info['software_version'] ?? null,
            'hardware_version' => $info['hardware_version'] ?? null,
            'model' => $info['model'] ?? null,
            'manufacturer' => $info['manufacturer'] ?? null,
        ];
    }

    /**
     * Send firmware download task to device.
     */
    public function downloadFirmware(string $deviceId, string $fileUrl, string $fileSize = '0'): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        try {
            $response = Http::timeout(30)
                ->asJson()
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request", [
                    'name' => 'download',
                    'file' => $fileUrl,
                    'fileType' => '1 Firmware Upgrade Image',
                ]);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'task_id' => $response->json('_id'),
                'message' => $response->status() === 200 ? 'Firmware download selesai' : 'Task firmware download dikirim',
            ];
        } catch (Exception $e) {
            Log::error("GenieACS downloadFirmware error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Factory reset device via TR069.
     */
    public function factoryReset(string $deviceId): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        try {
            $response = Http::timeout($this->timeout)
                ->asJson()
                ->post("{$this->nbiUrl}/devices/{$deviceId}/tasks?connection_request", [
                    'name' => 'factoryReset',
                ]);

            return [
                'success' => $response->status() === 200 || $response->status() === 202,
                'task_id' => $response->json('_id'),
            ];
        } catch (Exception $e) {
            Log::error("GenieACS factoryReset error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get users currently connected to ONU (from DHCP leases / hosts).
     * Returns formatted host list with connection details.
     */
    public function getConnectedUsers(string $deviceId): array
    {
        $hosts = $this->getLanHosts($deviceId);
        $wifis = $this->getWifiInfo($deviceId);

        // Build MAC → WiFi SSID map from associated devices
        $wifiMacs = [];
        // (Note: not all devices expose per-SSID association MACs via TR069)

        $users = [];
        foreach ($hosts as $host) {
            $users[] = [
                'hostname' => $host['host_name'] ?? $host['hostname'] ?? '',
                'ip' => $host['ip'] ?? '',
                'mac' => $host['mac'] ?? '',
                'active' => $host['active'] ?? false,
                'interface' => $host['interface'] ?? $host['layer2_interface'] ?? '',
                'address_source' => $host['address_source'] ?? 'DHCP',
                'lease_time' => $host['lease_time_remaining'] ?? null,
            ];
        }

        return $users;
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

    /**
     * Detect ONU brand from raw GenieACS device data.
     * Detect ONU brand from raw GenieACS device data.
     * Returns: 'huawei' | 'zte' | 'tp-link' | 'fiberhome' | 'nokia' | 'sercomm' | 'calix' | 'dzs' | 'unknown'
     *
     * Detection priority:
     *   1. Vendor-specific IGD keys (most reliable — if X_HW_Security exists, it's Huawei)
     *   2. Manufacturer OUI (IEEE assigned, very reliable)
     *   3. Manufacturer string from DeviceInfo
     *   4. GenieACS device ID prefix
     */
    public function getBrandByDeviceId(string $deviceId): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->nbiUrl}/devices", ['query' => json_encode(['_id' => $deviceId])]);
            if ($response->ok()) {
                $devices = $response->json();
                if (!empty($devices)) {
                    return $this->detectBrand($devices[0]);
                }
            }
        } catch (Exception $e) {
            Log::error("GenieACS getBrandByDeviceId error: " . $e->getMessage());
        }
        return 'unknown';
    }

    public function detectBrand(array $rawDevice): string
    {
        $igd     = $rawDevice['InternetGatewayDevice'] ?? $rawDevice['Device'] ?? [];
        $devInfo = $igd['DeviceInfo'] ?? [];

        // Priority 1: vendor-specific top-level IGD keys (zero false positives)
        if (isset($igd['X_HW_Security']) || isset($igd['X_HW_UserInfo'])) return 'huawei';
        foreach (array_keys($igd) as $igdKey) {
            $igdKey = (string) $igdKey;
            if (str_starts_with($igdKey, 'X_ZTE-COM_') || str_starts_with($igdKey, 'X_ZTE_COM_'))         return 'zte';
            if (str_starts_with($igdKey, 'X_TP-LINK_') || str_starts_with($igdKey, 'X_TPLINK_'))          return 'tp-link';
            if (str_starts_with($igdKey, 'X_FH_') || str_starts_with($igdKey, 'X_FiberHome_'))            return 'fiberhome';
            if (str_starts_with($igdKey, 'X_NOKIA_COM_') || str_starts_with($igdKey, 'X_ALCL_COM_'))      return 'nokia';
            if (str_starts_with($igdKey, 'X_SERCOMM_COM_') || str_starts_with($igdKey, 'X_SERCOMM_ORG_')) return 'sercomm';
            if (str_starts_with($igdKey, 'X_CALIX_COM_'))                                                  return 'calix';
            if (str_starts_with($igdKey, 'X_DASAN_COM_') || str_starts_with($igdKey, 'X_DZS_COM_'))       return 'dzs';
        }

        // Priority 2: Manufacturer OUI (IEEE assigned, normalized: lowercase hex, no dashes/colons)
        $oui = strtolower(preg_replace('/[^a-fA-F0-9]/', '', $this->getValue($devInfo, 'ManufacturerOUI') ?? ''));
        $ouiMap = [
            // Huawei
            '00259e' => 'huawei', '00e0fc' => 'huawei', '70b3d5' => 'huawei',
            '0c96e6' => 'huawei', '48570c' => 'huawei', '485754' => 'huawei',
            // ZTE
            '001e73' => 'zte', '00197e' => 'zte', '0024b2' => 'zte',
            'f44c7f' => 'zte', '0019b8' => 'zte', '2c957f' => 'zte',
            // TP-Link
            '70625d' => 'tp-link', 'b0a7b9' => 'tp-link', 'ec086b' => 'tp-link',
            '00001a' => 'tp-link', '001d0f' => 'tp-link',
            // FiberHome Telecommunication Technologies
            '000aeb' => 'fiberhome', '301893' => 'fiberhome', '001eaf' => 'fiberhome',
            '7c9a7b' => 'fiberhome', 'b4a5ac' => 'fiberhome', '485754' => 'fiberhome',
            // Nokia / Nokia Bell Labs / Alcatel-Lucent ONT
            '000fe2' => 'nokia', '001fe2' => 'nokia', '002201' => 'nokia',
            '002269' => 'nokia', '001b9e' => 'nokia', '00224b' => 'nokia',
            '002422' => 'nokia', '0025e4' => 'nokia', '00012f' => 'nokia',
            '001484' => 'nokia', '001aab' => 'nokia', '002070' => 'nokia',
            '00244b' => 'nokia', '00260b' => 'nokia', '049226' => 'nokia',
            // Sercomm
            '001325' => 'sercomm', '001a2a' => 'sercomm', '0026b8' => 'sercomm',
            '101f74' => 'sercomm', '00904c' => 'sercomm',
            // Calix
            '00158b' => 'calix', '002530' => 'calix', '109add' => 'calix',
            // DZS / Dasan / Zhone
            '000ab3' => 'dzs', '001cd6' => 'dzs', '00249f' => 'dzs',
            '001988' => 'dzs', '5c49eb' => 'dzs',
        ];
        if ($oui && isset($ouiMap[$oui])) return $ouiMap[$oui];

        // Priority 3: Manufacturer string (case-insensitive substring match)
        $mfr = strtolower($this->getValue($devInfo, 'Manufacturer') ?? '');
        if (str_contains($mfr, 'huawei'))                            return 'huawei';
        if (str_contains($mfr, 'zte'))                               return 'zte';
        if (str_contains($mfr, 'tp-link') || str_contains($mfr, 'tp link')) return 'tp-link';
        if (str_contains($mfr, 'fiberhome') || str_contains($mfr, 'fiber home')) return 'fiberhome';
        if (str_contains($mfr, 'nokia') || str_contains($mfr, 'alcatel')) return 'nokia';
        if (str_contains($mfr, 'sercomm'))                           return 'sercomm';
        if (str_contains($mfr, 'calix'))                             return 'calix';
        if (str_contains($mfr, 'dasan') || str_contains($mfr, 'zhone') || str_contains($mfr, 'dzs')) return 'dzs';

        // Priority 4: GenieACS device ID prefix often encodes OUI
        $devId = strtolower($rawDevice['_id'] ?? '');
        if (str_contains($devId, '00259e') || str_contains($devId, '485754') || str_contains($devId, 'huawei')) return 'huawei';
        if (str_contains($devId, '001e73') || str_contains($devId, 'zte'))    return 'zte';
        if (str_contains($devId, '000aeb') || str_contains($devId, '301893')) return 'fiberhome';

        return 'unknown';
    }

    /**
     * Extract standard ACS/ManagementServer info (same for all brands).
     */
    protected function extractAcsInfo(array $igd): array
    {
        $ms = $igd['ManagementServer'] ?? [];
        return [
            'url'                    => $this->getValue($ms, 'URL'),
            'username'               => $this->getValue($ms, 'Username'),
            'periodic_inform'        => $this->getValue($ms, 'PeriodicInformEnable'),
            'periodic_interval'      => $this->getValue($ms, 'PeriodicInformInterval'),
            'connection_request_url' => $this->getValue($ms, 'ConnectionRequestURL'),
        ];
    }

    /**
     * Extract DNS servers from active WAN connections (works for TR-098 IGD structure).
     */
    protected function extractDnsFromWan(array $igd): array
    {
        $dns = [];
        foreach ($igd['WANDevice'] ?? [] as $wdKey => $wdValue) {
            if (!is_array($wdValue) || str_starts_with((string) $wdKey, '_')) continue;
            foreach ($wdValue['WANConnectionDevice'] ?? [] as $wcKey => $wcValue) {
                if (!is_array($wcValue) || str_starts_with((string) $wcKey, '_')) continue;
                foreach (['WANPPPConnection', 'WANIPConnection'] as $ct) {
                    foreach ($wcValue[$ct] ?? [] as $ck => $cv) {
                        if (!is_array($cv) || str_starts_with((string) $ck, '_')) continue;
                        $d = $this->getValue($cv, 'DNSServers');
                        if ($d) $dns = array_merge($dns, explode(',', $d));
                    }
                }
            }
        }
        return array_unique(array_filter($dns));
    }

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

    /**
     * Normalize a TR-069 boolean value ("True"/"False"/"1"/"0"/true/false) to PHP bool.
     * Returns null if the value is null/unknown.
     */
    protected function asBool(mixed $value): ?bool
    {
        if ($value === null) return null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
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
     * Bulk fetch all devices from GenieACS NBI with only the fields needed for ONU sync.
     * Returns array keyed by device _id, value is a parsed array with:
     *   device_id, last_inform, rx_power, temperature, wan_ip, software_version,
     *   hardware_version, manufacturer, model, serial_hex
     */
    public function getDevicesForSync(): array
    {
        try {
            $projection = implode(',', [
                '_id',
                '_lastInform',
                'VirtualParameters.RXPower',
                'VirtualParameters.gettemp',
                'VirtualParameters.pppoeIP',
                'VirtualParameters.getSerialNumber',
                'VirtualParameters.getTxPower',
                'VirtualParameters.getWanStatus',
                'InternetGatewayDevice.DeviceInfo.SoftwareVersion',
                'InternetGatewayDevice.DeviceInfo.HardwareVersion',
                'InternetGatewayDevice.DeviceInfo.Manufacturer',
                'InternetGatewayDevice.DeviceInfo.ModelName',
                'Device.DeviceInfo.SoftwareVersion',
                'Device.DeviceInfo.HardwareVersion',
                'Device.DeviceInfo.Manufacturer',
                'Device.DeviceInfo.ModelName',
            ]);

            $response = Http::timeout(30)->get("{$this->nbiUrl}/devices", [
                'projection' => $projection,
            ]);

            if (!$response->ok()) {
                Log::warning("GenieACS getDevicesForSync HTTP {$response->status()}");
                return [];
            }

            $result = [];
            foreach ($response->json() as $device) {
                $id  = $device['_id'] ?? null;
                if (!$id) continue;

                $vp  = $device['VirtualParameters'] ?? [];
                $igd = $device['InternetGatewayDevice']['DeviceInfo']
                    ?? $device['Device']['DeviceInfo']
                    ?? [];

                $rxRaw = $vp['RXPower']['_value'] ?? null;
                $rx    = ($rxRaw !== null && $rxRaw !== 'N/A' && is_numeric($rxRaw))
                    ? (float) $rxRaw : null;

                $tempRaw = $vp['gettemp']['_value'] ?? null;
                $temp    = ($tempRaw !== null && $tempRaw !== 'N/A' && is_numeric($tempRaw))
                    ? (float) $tempRaw : null;

                $wanIp = $vp['pppoeIP']['_value'] ?? null;
                $wanIp = ($wanIp && $wanIp !== '0.0.0.0') ? $wanIp : null;

                $serialHex = $vp['getSerialNumber']['_value'] ?? null;

                $txRaw = $vp['getTxPower']['_value'] ?? null;
                $tx    = ($txRaw !== null && $txRaw !== 'N/A' && is_numeric($txRaw))
                    ? (float) $txRaw : null;

                $wanStatus = $vp['getWanStatus']['_value'] ?? null;

                $brand = $this->detectBrand($device);

                $result[$id] = [
                    'device_id'        => $id,
                    'last_inform'      => $device['_lastInform'] ?? null,
                    'rx_power'         => $rx,
                    'tx_power'         => $tx,
                    'temperature'      => $temp,
                    'wan_ip'           => $wanIp,
                    'wan_status'       => $wanStatus,
                    'software_version' => $this->getValue($igd, 'SoftwareVersion'),
                    'hardware_version' => $this->getValue($igd, 'HardwareVersion'),
                    'manufacturer'     => $this->getValue($igd, 'Manufacturer'),
                    'model'            => $this->getValue($igd, 'ModelName'),
                    'serial_hex'       => $serialHex,
                    'brand'            => $brand !== 'unknown' ? $brand : null,
                ];
            }

            return $result;
        } catch (Exception $e) {
            Log::error("GenieACS getDevicesForSync error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch enriched ONU data from GenieACS for a single serial number.
     * Returns array ready for $onu->update(). Returns [] if device not found or error.
     */
    public function enrichOnuFromGenieAcs(string $serialNumber): array
    {
        try {
            $device = $this->findDeviceBySerial($serialNumber);
            if (!$device) return [];
            return $this->getEnrichDataByDeviceId($device['device_id']);
        } catch (Exception $e) {
            Log::warning("GenieACS enrichOnuFromGenieAcs [{$serialNumber}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch VirtualParameters + DeviceInfo for a device ID and build DB update array.
     * Returns keys: rx_power, tx_power, temperature, wan_ip, software_version,
     *               hardware_version, vendor, onu_type, last_online_at
     */
    public function getEnrichDataByDeviceId(string $deviceId): array
    {
        $deviceId = $this->safeDeviceId($deviceId);
        $projection = implode(',', [
            '_lastInform',
            'VirtualParameters.RXPower',
            'VirtualParameters.gettemp',
            'VirtualParameters.pppoeIP',
            'VirtualParameters.getTxPower',
            'VirtualParameters.getWanStatus',
            'InternetGatewayDevice.DeviceInfo.SoftwareVersion',
            'InternetGatewayDevice.DeviceInfo.HardwareVersion',
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'Device.DeviceInfo.SoftwareVersion',
            'Device.DeviceInfo.HardwareVersion',
            'Device.DeviceInfo.Manufacturer',
            'Device.DeviceInfo.ModelName',
        ]);
        try {
            $response = Http::timeout(10)->get("{$this->nbiUrl}/devices", [
                'query'      => json_encode(['_id' => $deviceId]),
                'projection' => $projection,
            ]);

            if (!$response->ok() || empty($response->json())) return [];

            $device  = $response->json()[0];
            $vp      = $device['VirtualParameters'] ?? [];
            $igd     = $device['InternetGatewayDevice']['DeviceInfo']
                    ?? $device['Device']['DeviceInfo']
                    ?? [];

            $updates = [];

            // RX power (dBm, float string like "-22")
            $rxRaw = $vp['RXPower']['_value'] ?? null;
            if ($rxRaw !== null && $rxRaw !== 'N/A' && is_numeric($rxRaw)) {
                $updates['rx_power'] = (float) $rxRaw;
            }

            // TX power (dBm, float) — from new getTxPower VP
            $txRaw = $vp['getTxPower']['_value'] ?? null;
            if ($txRaw !== null && $txRaw !== 'N/A' && is_numeric($txRaw)) {
                $updates['tx_power'] = (float) $txRaw;
            }

            // Temperature (°C)
            $tempRaw = $vp['gettemp']['_value'] ?? null;
            if ($tempRaw !== null && $tempRaw !== 'N/A' && is_numeric($tempRaw)) {
                $updates['temperature'] = (float) $tempRaw;
            }

            // WAN IP from PPPoE
            $wanIp = $vp['pppoeIP']['_value'] ?? null;
            if ($wanIp && $wanIp !== '0.0.0.0') {
                $updates['wan_ip'] = $wanIp;
            }

            // Software version
            $sw = $this->getValue($igd, 'SoftwareVersion');
            if ($sw) $updates['software_version'] = $sw;

            // Hardware version
            $hw = $this->getValue($igd, 'HardwareVersion');
            if ($hw) $updates['hardware_version'] = $hw;

            // Vendor — detectBrand() pakai 4 layer (IGD keys, OUI, Manufacturer, device ID)
            // jauh lebih reliable daripada hanya string Manufacturer yang sering null.
            $detectedBrand = $this->detectBrand($device);
            if ($detectedBrand !== 'unknown') {
                $updates['vendor'] = $this->normalizeVendorCode($detectedBrand);
            }

            // ONU model
            $model = $this->getValue($igd, 'ModelName');
            if ($model) $updates['onu_type'] = $model;

            // WAN status → online/offline inference from getWanStatus VP
            $wanStatus = $vp['getWanStatus']['_value'] ?? null;
            if ($wanStatus === 'Connected') {
                $updates['status'] = 'online';
            }

            // last_online_at from last_inform if within 6 hours
            $lastInform = $device['_lastInform'] ?? null;
            if ($lastInform) {
                $inform = Carbon::parse($lastInform);
                if ($inform->diffInHours(now()) <= 6) {
                    $updates['last_online_at'] = $inform;
                }
            }

            return $updates;
        } catch (Exception $e) {
            Log::warning("GenieACS getEnrichDataByDeviceId [{$deviceId}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create or update a VirtualParameter in GenieACS via NBI.
     * $script is a JS function body returning [timestamp, value].
     */
    public function createVirtualParameter(string $name, string $script): bool
    {
        try {
            $response = Http::timeout(10)
                ->asJson()
                ->put("{$this->nbiUrl}/virtual_parameters/{$name}", ['script' => $script]);
            return $response->successful();
        } catch (Exception $e) {
            Log::error("GenieACS createVirtualParameter [{$name}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize brand name (from detectBrand() or Manufacturer string)
     * to 4-char vendor code stored in ONU.vendor.
     *
     * Accepts both detectBrand() output ('huawei', 'zte', …)
     * and raw Manufacturer strings (substring matched).
     */
    private function normalizeVendorCode(string $brand): string
    {
        $m = strtolower($brand);
        if (str_contains($m, 'huawei'))    return 'HWTC';
        if (str_contains($m, 'zte'))       return 'ZTEG';
        if (str_contains($m, 'fiberhome') || $m === 'fiberhome') return 'FHTT';
        if (str_contains($m, 'nokia') || str_contains($m, 'alcatel') || $m === 'nokia') return 'ALCL';
        if (str_contains($m, 'tp-link') || $m === 'tp-link')   return 'TPLN';
        if (str_contains($m, 'mikrotik'))  return 'MIKR';
        if (str_contains($m, 'raisecom') || $m === 'dzs')       return 'GGCL';
        if ($m === 'sercomm')              return 'SRCM';
        if ($m === 'calix')                return 'CLIX';
        return strtoupper(substr($brand, 0, 4));
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
