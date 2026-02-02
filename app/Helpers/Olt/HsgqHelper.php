<?php

namespace App\Helpers\Olt;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * HSGQ OLT Helper
 * 
 * HSGQ OLTs typically use similar OID structure to Hioso
 * Common models: HSGQ-8PON, HSGQ-16PON
 */
class HsgqHelper extends BaseOltHelper
{
    /**
     * HSGQ specific OIDs
     */
    protected array $hsgqOids = [
        // ONU Table
        'onuSerialNumber' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
        'onuMacAddress' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.3',
        'onuStatus' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.4',
        'onuDistance' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.7',
        
        // Optical Info (may use different base OID)
        'onuRxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.3',
        'onuTxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.4',
        'onuOltRxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.5',
        
        // Alternative OIDs for some HSGQ models
        'altOnuSerialNumber' => '1.3.6.1.4.1.3320.101.10.1.1.3',
        'altOnuStatus' => '1.3.6.1.4.1.3320.101.10.1.1.26',
        'altOnuRxPower' => '1.3.6.1.4.1.3320.101.10.5.1.5',
        'altOnuTxPower' => '1.3.6.1.4.1.3320.101.10.5.1.6',
        
        // PON Port
        'ponPortStatus' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.3',
        
        // Unconfigured
        'uncfgOnuSerial' => '1.3.6.1.4.1.17409.2.3.5.1.3.1.1.2',
    ];

    protected array $statusMap = [
        1 => 'online',
        2 => 'offline',
        3 => 'los',
        4 => 'power_off',
    ];

    /**
     * Identify HSGQ OLT
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => 'hsgq',
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
            if (preg_match('/HSGQ[- ]?(\d+PON|\w+)/i', $sysDescr, $matches)) {
                $result['model'] = 'HSGQ-' . strtoupper($matches[1]);
            }

            // Count PON ports
            $ponPorts = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.3', 5000000, 2);
            $totalPonPorts = $ponPorts ? count($ponPorts) : 0;

            // Default based on model name
            if ($totalPonPorts == 0) {
                if (stripos($sysDescr, '16') !== false || stripos($result['model'], '16') !== false) {
                    $totalPonPorts = 16;
                } else {
                    $totalPonPorts = 8; // Default
                }
            }

            $result['total_pon_ports'] = $totalPonPorts;
            $result['total_uplink_ports'] = 2;
            $result['success'] = true;
            $result['message'] = 'OLT berhasil diidentifikasi';

        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get PON ports
     */
    public function getPonPorts(): array
    {
        $ports = [];

        try {
            $statuses = $this->snmpWalk($this->hsgqOids['ponPortStatus']);

            foreach ($statuses as $oid => $status) {
                preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 3) continue;

                $slot = (int) $matches[1];
                $port = (int) $matches[2];

                $ports[] = [
                    'slot' => $slot,
                    'port' => $port,
                    'status' => $status == 1 ? 'up' : 'down',
                    'admin_status' => 'enabled',
                ];

                $this->updatePonPort($slot, $port, end($ports));
            }

        } catch (Exception $e) {
            Log::error("HSGQ getPonPorts error: " . $e->getMessage());
        }

        return $ports;
    }

    /**
     * Get PON port info
     */
    public function getPonPortInfo(int $slot, int $port): array
    {
        $index = "{$slot}.{$port}";
        $status = $this->snmpGet($this->hsgqOids['ponPortStatus'] . ".{$index}");

        return [
            'slot' => $slot,
            'port' => $port,
            'status' => $status == 1 ? 'up' : 'down',
        ];
    }

    /**
     * Get all ONUs
     */
    public function getAllOnus(): array
    {
        $onus = [];

        try {
            // Try primary OIDs first
            $serialNumbers = $this->snmpWalk($this->hsgqOids['onuSerialNumber']);
            
            // Fall back to alternative OIDs if empty
            if (empty($serialNumbers)) {
                $serialNumbers = $this->snmpWalk($this->hsgqOids['altOnuSerialNumber']);
            }

            $statuses = $this->snmpWalk($this->hsgqOids['onuStatus']);
            if (empty($statuses)) {
                $statuses = $this->snmpWalk($this->hsgqOids['altOnuStatus']);
            }

            foreach ($serialNumbers as $oid => $serialRaw) {
                preg_match('/\.(\d+)\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 4) continue;

                $slot = (int) $matches[1];
                $port = (int) $matches[2];
                $onuId = (int) $matches[3];
                $index = "{$slot}.{$port}.{$onuId}";

                $statusKey = $this->hsgqOids['onuStatus'] . ".{$index}";
                $altStatusKey = $this->hsgqOids['altOnuStatus'] . ".{$index}";
                $status = $statuses[$statusKey] ?? $statuses[$altStatusKey] ?? 0;

                $onus[] = [
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $onuId,
                    'serial_number' => $this->parseSerialNumber($serialRaw),
                    'status' => $this->statusMap[$status] ?? 'unknown',
                ];
            }

        } catch (Exception $e) {
            Log::error("HSGQ getAllOnus error: " . $e->getMessage());
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

        $serial = $this->snmpGet($this->hsgqOids['onuSerialNumber'] . ".{$index}");
        if (!$serial) {
            $serial = $this->snmpGet($this->hsgqOids['altOnuSerialNumber'] . ".{$index}");
        }

        $info = [
            'slot' => $slot,
            'port' => $port,
            'onu_id' => $onuId,
            'serial_number' => $this->parseSerialNumber($serial ?? ''),
            'status' => $this->statusMap[$this->snmpGet($this->hsgqOids['onuStatus'] . ".{$index}")] ?? 'unknown',
            'distance' => $this->parseDistance($this->snmpGet($this->hsgqOids['onuDistance'] . ".{$index}")),
        ];

        return array_merge($info, $this->getOnuOpticalInfo($slot, $port, $onuId));
    }

    /**
     * Get ONU optical info
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        $index = "{$slot}.{$port}.{$onuId}";

        // Try primary OIDs
        $rxPower = $this->snmpGet($this->hsgqOids['onuRxPower'] . ".{$index}");
        $txPower = $this->snmpGet($this->hsgqOids['onuTxPower'] . ".{$index}");
        $oltRx = $this->snmpGet($this->hsgqOids['onuOltRxPower'] . ".{$index}");

        // Try alternative OIDs if primary fails
        if ($rxPower === null) {
            $rxPower = $this->snmpGet($this->hsgqOids['altOnuRxPower'] . ".{$index}");
        }
        if ($txPower === null) {
            $txPower = $this->snmpGet($this->hsgqOids['altOnuTxPower'] . ".{$index}");
        }

        return [
            'rx_power' => $this->parseOpticalPower($rxPower),
            'tx_power' => $this->parseOpticalPower($txPower),
            'olt_rx_power' => $this->parseOpticalPower($oltRx),
            'temperature' => null,
            'voltage' => null,
        ];
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
            $uncfgSerials = $this->snmpWalk($this->hsgqOids['uncfgOnuSerial']);

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
            Log::error("HSGQ getUnregisteredOnus error: " . $e->getMessage());
        }

        return $unregistered;
    }

    /**
     * Register ONU
     */
    public function registerOnu(array $params): array
    {
        return [
            'success' => false,
            'message' => 'ONU registration via SNMP not fully supported. Use web interface.',
        ];
    }

    /**
     * Unregister ONU
     */
    public function unregisterOnu(int $slot, int $port, int $onuId): array
    {
        return [
            'success' => false,
            'message' => 'ONU unregistration via SNMP not supported. Use web interface.',
        ];
    }

    /**
     * Reboot ONU
     */
    public function rebootOnu(int $slot, int $port, int $onuId): array
    {
        return [
            'success' => false,
            'message' => 'ONU reboot not supported via SNMP on HSGQ',
        ];
    }

    /**
     * Get ONU traffic
     */
    public function getOnuTraffic(int $slot, int $port, int $onuId): array
    {
        return [
            'in_octets' => 0,
            'out_octets' => 0,
            'in_packets' => 0,
            'out_packets' => 0,
        ];
    }

    /**
     * Get profiles
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
     * Apply service
     */
    public function applyServiceToOnu(int $slot, int $port, int $onuId, array $serviceConfig): array
    {
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
     * Sync all
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

            foreach ($this->getAllOnus() as $onuData) {
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
                    $result['errors'][] = $e->getMessage();
                }
            }

            $this->olt->update(['last_sync_at' => now(), 'status' => 'active']);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }
}
