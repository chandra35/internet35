<?php

namespace App\Helpers\Olt;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Hioso OLT Helper
 * 
 * Supports SNMP for monitoring and configuration
 * Common models: HA7302, HA7304, HA7308
 */
class HiosoHelper extends BaseOltHelper
{
    /**
     * Hioso specific OIDs (based on EPON/GPON MIB)
     */
    protected array $hiosoOids = [
        // System Info
        'sysDescr' => '1.3.6.1.2.1.1.1.0',
        
        // PON Port
        'ponPortTable' => '1.3.6.1.4.1.17409.2.3.4.1.1.1',
        'ponPortAdminStatus' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.2',
        'ponPortOperStatus' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.3',
        
        // ONU Table
        'onuTable' => '1.3.6.1.4.1.17409.2.3.5.1.1.1',
        'onuSerialNumber' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
        'onuMacAddress' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.3',
        'onuStatus' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.4',
        'onuDistance' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.7',
        
        // ONU Optical Info
        'onuOpticalTable' => '1.3.6.1.4.1.17409.2.3.5.1.4.1',
        'onuRxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.3',
        'onuTxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.4',
        'onuOltRxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.5',
        'onuTemperature' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.6',
        'onuVoltage' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.7',
        
        // Unconfigured ONU
        'uncfgOnuTable' => '1.3.6.1.4.1.17409.2.3.5.1.3.1',
        'uncfgOnuSerial' => '1.3.6.1.4.1.17409.2.3.5.1.3.1.1.2',
        
        // ONU Config
        'onuAuthAction' => '1.3.6.1.4.1.17409.2.3.5.1.2.1.1.2', // 1=add, 2=delete
    ];

    /**
     * Status mapping
     */
    protected array $statusMap = [
        1 => 'online',
        2 => 'offline',
        3 => 'los',
        4 => 'power_off',
    ];

    /**
     * Identify Hioso OLT
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => 'hioso',
            'model' => null,
            'description' => null,
            'firmware' => null,
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'boards' => [],
            'message' => '',
        ];

        try {
            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            
            $sysDescr = @snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.1.0', 5000000, 2);
            
            if ($sysDescr === false) {
                $result['message'] = 'Tidak dapat terhubung via SNMP';
                return $result;
            }

            $result['description'] = $sysDescr;

            // Get model from sysDescr
            if (preg_match('/HA\d{4}/i', $sysDescr, $matches)) {
                $result['model'] = strtoupper($matches[0]);
            }

            // Count PON ports by walking PON port table
            $ponPorts = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.2', 5000000, 2);
            $totalPonPorts = $ponPorts ? count($ponPorts) : 0;

            // Default for common models
            if ($totalPonPorts == 0) {
                if (stripos($result['model'], 'HA7308') !== false) {
                    $totalPonPorts = 8;
                } elseif (stripos($result['model'], 'HA7304') !== false) {
                    $totalPonPorts = 4;
                } elseif (stripos($result['model'], 'HA7302') !== false) {
                    $totalPonPorts = 2;
                } else {
                    $totalPonPorts = 8; // Default
                }
            }

            $result['total_pon_ports'] = $totalPonPorts;
            $result['total_uplink_ports'] = 2; // Default for Hioso
            $result['success'] = true;
            $result['message'] = 'OLT berhasil diidentifikasi';

        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get PON ports info
     */
    public function getPonPorts(): array
    {
        $ports = [];

        try {
            $adminStatuses = $this->snmpWalk($this->hiosoOids['ponPortAdminStatus']);
            $operStatuses = $this->snmpWalk($this->hiosoOids['ponPortOperStatus']);

            foreach ($adminStatuses as $oid => $adminStatus) {
                preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 3) continue;

                $slot = (int) $matches[1];
                $port = (int) $matches[2];
                $index = "{$slot}.{$port}";

                $ports[] = [
                    'slot' => $slot,
                    'port' => $port,
                    'admin_status' => $adminStatus == 1 ? 'enabled' : 'disabled',
                    'status' => ($operStatuses[$this->hiosoOids['ponPortOperStatus'] . ".{$index}"] ?? 0) == 1 ? 'up' : 'down',
                ];

                $this->updatePonPort($slot, $port, end($ports));
            }

        } catch (Exception $e) {
            Log::error("Hioso getPonPorts error: " . $e->getMessage());
        }

        return $ports;
    }

    /**
     * Get PON port info
     */
    public function getPonPortInfo(int $slot, int $port): array
    {
        $index = "{$slot}.{$port}";

        return [
            'slot' => $slot,
            'port' => $port,
            'admin_status' => $this->snmpGet($this->hiosoOids['ponPortAdminStatus'] . ".{$index}"),
            'oper_status' => $this->snmpGet($this->hiosoOids['ponPortOperStatus'] . ".{$index}"),
        ];
    }

    /**
     * Get all ONUs
     */
    public function getAllOnus(): array
    {
        $onus = [];

        try {
            $serialNumbers = $this->snmpWalk($this->hiosoOids['onuSerialNumber']);
            $statuses = $this->snmpWalk($this->hiosoOids['onuStatus']);
            $distances = $this->snmpWalk($this->hiosoOids['onuDistance']);

            foreach ($serialNumbers as $oid => $serialRaw) {
                // Parse slot.port.onuid
                preg_match('/\.(\d+)\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 4) continue;

                $slot = (int) $matches[1];
                $port = (int) $matches[2];
                $onuId = (int) $matches[3];
                $index = "{$slot}.{$port}.{$onuId}";

                $status = $statuses[$this->hiosoOids['onuStatus'] . ".{$index}"] ?? 0;

                $onus[] = [
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $onuId,
                    'serial_number' => $this->parseSerialNumber($serialRaw),
                    'status' => $this->statusMap[$status] ?? 'unknown',
                    'distance' => $this->parseDistance($distances[$this->hiosoOids['onuDistance'] . ".{$index}"] ?? null),
                ];
            }

        } catch (Exception $e) {
            Log::error("Hioso getAllOnus error: " . $e->getMessage());
        }

        return $onus;
    }

    /**
     * Get ONUs by port
     */
    public function getOnusByPort(int $slot, int $port): array
    {
        return array_filter($this->getAllOnus(), fn($onu) => 
            $onu['slot'] == $slot && $onu['port'] == $port
        );
    }

    /**
     * Get ONU info
     */
    public function getOnuInfo(int $slot, int $port, int $onuId): array
    {
        $index = "{$slot}.{$port}.{$onuId}";

        $info = [
            'slot' => $slot,
            'port' => $port,
            'onu_id' => $onuId,
            'serial_number' => $this->parseSerialNumber(
                $this->snmpGet($this->hiosoOids['onuSerialNumber'] . ".{$index}") ?? ''
            ),
            'mac_address' => $this->snmpGet($this->hiosoOids['onuMacAddress'] . ".{$index}"),
            'status' => $this->statusMap[$this->snmpGet($this->hiosoOids['onuStatus'] . ".{$index}")] ?? 'unknown',
            'distance' => $this->parseDistance($this->snmpGet($this->hiosoOids['onuDistance'] . ".{$index}")),
        ];

        return array_merge($info, $this->getOnuOpticalInfo($slot, $port, $onuId));
    }

    /**
     * Get ONU optical info
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        $index = "{$slot}.{$port}.{$onuId}";

        $rxPower = $this->snmpGet($this->hiosoOids['onuRxPower'] . ".{$index}");
        $txPower = $this->snmpGet($this->hiosoOids['onuTxPower'] . ".{$index}");
        $oltRx = $this->snmpGet($this->hiosoOids['onuOltRxPower'] . ".{$index}");
        $temp = $this->snmpGet($this->hiosoOids['onuTemperature'] . ".{$index}");
        $volt = $this->snmpGet($this->hiosoOids['onuVoltage'] . ".{$index}");

        return [
            'rx_power' => $this->parseHiosoOpticalPower($rxPower),
            'tx_power' => $this->parseHiosoOpticalPower($txPower),
            'olt_rx_power' => $this->parseHiosoOpticalPower($oltRx),
            'temperature' => $temp ? ((float)$temp / 100) : null,
            'voltage' => $volt ? ((float)$volt / 1000) : null,
        ];
    }

    /**
     * Parse Hioso optical power (returns in 0.01 dBm)
     */
    protected function parseHiosoOpticalPower(mixed $value): ?float
    {
        if (is_null($value) || $value === '' || $value == 0x7FFF || $value == 32767) {
            return null;
        }

        // Hioso returns signed 16-bit integer in 0.01 dBm
        $power = (int) $value;
        
        // Convert from unsigned to signed if needed
        if ($power > 32767) {
            $power = $power - 65536;
        }

        return round($power / 100, 2);
    }

    /**
     * Get ONU by serial
     */
    public function getOnuBySerial(string $serialNumber): ?array
    {
        $serialNumber = strtoupper($serialNumber);
        
        foreach ($this->getAllOnus() as $onu) {
            if (strtoupper($onu['serial_number']) === $serialNumber) {
                return $this->getOnuInfo($onu['slot'], $onu['port'], $onu['onu_id']);
            }
        }

        return null;
    }

    /**
     * Get unregistered ONUs
     */
    public function getUnregisteredOnus(): array
    {
        $unregistered = [];

        try {
            $uncfgSerials = $this->snmpWalk($this->hiosoOids['uncfgOnuSerial']);

            foreach ($uncfgSerials as $oid => $serial) {
                preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 3) continue;

                $unregistered[] = [
                    'slot' => (int) $matches[1],
                    'port' => (int) $matches[2],
                    'serial_number' => $this->parseSerialNumber($serial),
                    'config_status' => 'unregistered',
                ];
            }

        } catch (Exception $e) {
            Log::error("Hioso getUnregisteredOnus error: " . $e->getMessage());
        }

        return $unregistered;
    }

    /**
     * Register ONU via SNMP
     */
    public function registerOnu(array $params): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'onu_id' => null,
        ];

        try {
            $slot = $params['slot'];
            $port = $params['port'];
            $onuId = $params['onu_id'] ?? $this->getNextOnuId($slot, $port);
            $serial = strtoupper($params['serial_number']);

            $index = "{$slot}.{$port}.{$onuId}";

            // Set serial number
            $this->snmpSet(
                $this->hiosoOids['onuSerialNumber'] . ".{$index}",
                's',
                $serial
            );

            // Authorize ONU
            $this->snmpSet(
                $this->hiosoOids['onuAuthAction'] . ".{$index}",
                'i',
                1 // 1 = add/authorize
            );

            // Verify registration
            sleep(2);
            $verifySerial = $this->snmpGet($this->hiosoOids['onuSerialNumber'] . ".{$index}");

            if ($verifySerial && strtoupper($this->parseSerialNumber($verifySerial)) === $serial) {
                $result['success'] = true;
                $result['onu_id'] = $onuId;
                $result['message'] = "ONU registered at {$slot}/{$port}:{$onuId}";
            } else {
                $result['message'] = 'Registration verification failed';
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("Hioso registerOnu error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Unregister ONU
     */
    public function unregisterOnu(int $slot, int $port, int $onuId): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $index = "{$slot}.{$port}.{$onuId}";

            // Delete ONU authorization
            $this->snmpSet(
                $this->hiosoOids['onuAuthAction'] . ".{$index}",
                'i',
                2 // 2 = delete
            );

            $result['success'] = true;
            $result['message'] = "ONU {$slot}/{$port}:{$onuId} unregistered";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("Hioso unregisterOnu error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Reboot ONU (via SNMP if supported)
     */
    public function rebootOnu(int $slot, int $port, int $onuId): array
    {
        // Most Hioso OLTs don't support ONU reboot via SNMP
        // Would need telnet/web access
        return [
            'success' => false,
            'message' => 'ONU reboot not supported via SNMP on Hioso',
        ];
    }

    /**
     * Get ONU traffic
     */
    public function getOnuTraffic(int $slot, int $port, int $onuId): array
    {
        // Traffic OIDs may vary by model
        return [
            'in_octets' => 0,
            'out_octets' => 0,
            'in_packets' => 0,
            'out_packets' => 0,
        ];
    }

    /**
     * Get profiles (limited on Hioso via SNMP)
     */
    public function getProfiles(string $type = 'all'): array
    {
        return [
            'line' => [],
            'service' => [],
            'traffic' => [],
        ];
    }

    /**
     * Apply service to ONU
     */
    public function applyServiceToOnu(int $slot, int $port, int $onuId, array $serviceConfig): array
    {
        // Service configuration typically done via web interface on Hioso
        return [
            'success' => false,
            'message' => 'Service configuration not supported via SNMP. Use web interface.',
        ];
    }

    /**
     * Get uplink ports
     */
    public function getUplinkPorts(): array
    {
        return [];
    }

    /**
     * Sync all ONU data
     */
    public function syncAll(): array
    {
        $result = [
            'success' => true,
            'pon_ports_synced' => 0,
            'onus_synced' => 0,
            'signals_recorded' => 0,
            'errors' => [],
        ];

        try {
            $this->getPonPorts();
            $result['pon_ports_synced'] = $this->olt->total_pon_ports;

            $allOnus = $this->getAllOnus();

            foreach ($allOnus as $onuData) {
                try {
                    $fullInfo = $this->getOnuInfo($onuData['slot'], $onuData['port'], $onuData['onu_id']);

                    $onu = $this->saveOnuToDatabase(array_merge($fullInfo, [
                        'olt_id' => $this->olt->id,
                        'config_status' => 'registered',
                    ]));

                    $this->saveSignalHistory($onu, $fullInfo);

                    $result['onus_synced']++;
                    $result['signals_recorded']++;

                } catch (Exception $e) {
                    $result['errors'][] = "ONU {$onuData['slot']}/{$onuData['port']}:{$onuData['onu_id']}: " . $e->getMessage();
                }
            }

            $this->olt->update([
                'last_sync_at' => now(),
                'status' => 'active',
            ]);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get next available ONU ID
     */
    protected function getNextOnuId(int $slot, int $port): int
    {
        $existing = $this->getOnusByPort($slot, $port);
        $usedIds = array_column($existing, 'onu_id');

        for ($i = 1; $i <= 64; $i++) {
            if (!in_array($i, $usedIds)) return $i;
        }

        throw new Exception("No available ONU ID on port {$slot}/{$port}");
    }
}
