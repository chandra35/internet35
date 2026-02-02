<?php

namespace App\Helpers\Olt;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Huawei OLT Helper
 * 
 * Supports MA5608T, MA5680T series
 * Uses similar OID structure to ZTE but with Huawei enterprise MIB
 */
class HuaweiHelper extends BaseOltHelper
{
    /**
     * Huawei specific OIDs
     */
    protected array $huaweiOids = [
        // System Info
        'hwSysSwVersion' => '1.3.6.1.4.1.2011.6.3.4.1.2.0',
        'hwSysName' => '1.3.6.1.4.1.2011.2.6.1.1.3.0',
        
        // Board Info
        'hwFrameType' => '1.3.6.1.4.1.2011.6.3.3.2.1.3',
        'hwSlotType' => '1.3.6.1.4.1.2011.6.3.3.2.1.5',
        'hwSlotOperStatus' => '1.3.6.1.4.1.2011.6.3.3.2.1.7',
        
        // ONU Table (hwGponDeviceOntEntry)
        'ontSerialNumber' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3',
        'ontStatus' => '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.15',
        'ontDistance' => '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.19',
        'ontDescription' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.9',
        
        // Optical Info
        'ontRxPower' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4',
        'ontTxPower' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.5',
        'ontOltRxPower' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.6',
        'ontTemperature' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.1',
        'ontVoltage' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.2',
        
        // PON Port
        'ponPortStatus' => '1.3.6.1.4.1.2011.6.128.1.1.2.21.1.8',
        
        // Unconfigured ONT
        'uncfgOntSerial' => '1.3.6.1.4.1.2011.6.128.1.1.2.45.1.3',
    ];

    protected array $statusMap = [
        1 => 'online',
        2 => 'offline',
        3 => 'los',
        4 => 'dying_gasp',
        5 => 'power_off',
    ];

    /**
     * Huawei board type mapping
     */
    protected static array $boardTypeMap = [
        'H801GPBH' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'H801GPBD' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'H802GPBD' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'H806GPBD' => ['type' => 'PON', 'pon_ports' => 16, 'uplink_ports' => 0],
        'H801MCUD' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'H801MCUD1' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'H801X2CS' => ['type' => 'Uplink', 'pon_ports' => 0, 'uplink_ports' => 2],
        'H801GICF' => ['type' => 'Uplink', 'pon_ports' => 0, 'uplink_ports' => 4],
    ];

    /**
     * Identify Huawei OLT
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => 'huawei',
            'model' => null,
            'description' => null,
            'firmware' => null,
            'hardware_version' => null,
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
            if (preg_match('/MA\d{4}[A-Z]?/i', $sysDescr, $matches)) {
                $result['model'] = strtoupper($matches[0]);
            }

            // Get firmware version
            $firmware = @snmpget($ipAddress, $snmpCommunity, '1.3.6.1.4.1.2011.6.3.4.1.2.0', 5000000, 2);
            if ($firmware) {
                $result['firmware'] = $firmware;
            }

            // Get board info
            $boardTypes = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.2011.6.3.3.2.1.5', 5000000, 2);
            $boardStatuses = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.2011.6.3.3.2.1.7', 5000000, 2);

            $totalPonPorts = 0;
            $totalUplinkPorts = 0;
            $boards = [];

            if ($boardTypes) {
                foreach ($boardTypes as $oid => $boardType) {
                    preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                    if (count($matches) < 3) continue;

                    $frame = (int)$matches[1];
                    $slot = (int)$matches[2];

                    $ponPorts = self::$boardTypeMap[$boardType]['pon_ports'] ?? 0;
                    $upPorts = self::$boardTypeMap[$boardType]['uplink_ports'] ?? 0;

                    $statusOid = str_replace('.5.', '.7.', $oid);
                    $operState = isset($boardStatuses[$statusOid]) ? ((int)$boardStatuses[$statusOid] == 1 ? 'online' : 'offline') : 'unknown';

                    $boards[] = [
                        'shelf' => $frame,
                        'slot' => $slot,
                        'board_type' => $boardType,
                        'type_category' => self::$boardTypeMap[$boardType]['type'] ?? 'Unknown',
                        'pon_ports' => $ponPorts,
                        'uplink_ports' => $upPorts,
                        'oper_state' => $operState,
                    ];

                    $totalPonPorts += $ponPorts;
                    $totalUplinkPorts += $upPorts;
                }
            }

            // Fallback default for MA5608T
            if ($totalPonPorts == 0 && stripos($result['model'], 'MA5608') !== false) {
                $totalPonPorts = 8;
                $totalUplinkPorts = 2;
            }

            $result['boards'] = $boards;
            $result['total_pon_ports'] = $totalPonPorts;
            $result['total_uplink_ports'] = $totalUplinkPorts;
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
            $statuses = $this->snmpWalk($this->huaweiOids['ponPortStatus']);

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
            Log::error("Huawei getPonPorts error: " . $e->getMessage());
        }

        return $ports;
    }

    /**
     * Get PON port info
     */
    public function getPonPortInfo(int $slot, int $port): array
    {
        $index = "{$slot}.{$port}";
        $status = $this->snmpGet($this->huaweiOids['ponPortStatus'] . ".{$index}");

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
            $serialNumbers = $this->snmpWalk($this->huaweiOids['ontSerialNumber']);
            $statuses = $this->snmpWalk($this->huaweiOids['ontStatus']);

            foreach ($serialNumbers as $oid => $serialRaw) {
                // Huawei uses frame.slot.port.ontid
                preg_match('/\.(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 5) continue;

                $slot = (int) $matches[2]; // Using slot from position
                $port = (int) $matches[3];
                $onuId = (int) $matches[4];
                $index = "{$matches[1]}.{$slot}.{$port}.{$onuId}";

                $status = $statuses[$this->huaweiOids['ontStatus'] . ".{$index}"] ?? 2;

                $onus[] = [
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $onuId,
                    'serial_number' => $this->parseHuaweiSerial($serialRaw),
                    'status' => $this->statusMap[$status] ?? 'unknown',
                ];
            }

        } catch (Exception $e) {
            Log::error("Huawei getAllOnus error: " . $e->getMessage());
        }

        return $onus;
    }

    /**
     * Parse Huawei serial (hex format)
     */
    protected function parseHuaweiSerial(string $raw): string
    {
        // Huawei returns serial as hex string
        $raw = trim($raw);
        
        // If it's a hex string, convert to ASCII
        if (preg_match('/^[0-9A-Fa-f\s]+$/', $raw)) {
            $raw = str_replace(' ', '', $raw);
            $ascii = '';
            for ($i = 0; $i < strlen($raw); $i += 2) {
                $char = chr(hexdec(substr($raw, $i, 2)));
                if (ctype_print($char)) {
                    $ascii .= $char;
                }
            }
            if (strlen($ascii) >= 8) {
                return strtoupper($ascii);
            }
        }

        return strtoupper($this->parseSerialNumber($raw));
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
        // Huawei uses 0.slot.port.onuid format
        $index = "0.{$slot}.{$port}.{$onuId}";

        $info = [
            'slot' => $slot,
            'port' => $port,
            'onu_id' => $onuId,
            'serial_number' => $this->parseHuaweiSerial(
                $this->snmpGet($this->huaweiOids['ontSerialNumber'] . ".{$index}") ?? ''
            ),
            'status' => $this->statusMap[$this->snmpGet($this->huaweiOids['ontStatus'] . ".{$index}")] ?? 'unknown',
            'distance' => $this->parseDistance($this->snmpGet($this->huaweiOids['ontDistance'] . ".{$index}")),
            'description' => $this->snmpGet($this->huaweiOids['ontDescription'] . ".{$index}"),
        ];

        return array_merge($info, $this->getOnuOpticalInfo($slot, $port, $onuId));
    }

    /**
     * Get ONU optical info
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        $index = "0.{$slot}.{$port}.{$onuId}";

        $rxPower = $this->snmpGet($this->huaweiOids['ontRxPower'] . ".{$index}");
        $txPower = $this->snmpGet($this->huaweiOids['ontTxPower'] . ".{$index}");
        $oltRx = $this->snmpGet($this->huaweiOids['ontOltRxPower'] . ".{$index}");
        $temp = $this->snmpGet($this->huaweiOids['ontTemperature'] . ".{$index}");
        $volt = $this->snmpGet($this->huaweiOids['ontVoltage'] . ".{$index}");

        return [
            'rx_power' => $this->parseHuaweiPower($rxPower),
            'tx_power' => $this->parseHuaweiPower($txPower),
            'olt_rx_power' => $this->parseHuaweiPower($oltRx),
            'temperature' => $temp ? ((float)$temp / 256) : null,
            'voltage' => $volt ? ((float)$volt / 10000) : null,
        ];
    }

    /**
     * Parse Huawei optical power (0.01 dBm)
     */
    protected function parseHuaweiPower(mixed $value): ?float
    {
        if (is_null($value) || $value === '' || $value == 0x7FFFFFFF) {
            return null;
        }

        // Huawei returns value in 0.01 dBm
        return round((int)$value / 100, 2);
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
            $uncfgSerials = $this->snmpWalk($this->huaweiOids['uncfgOntSerial']);

            foreach ($uncfgSerials as $oid => $serial) {
                preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 3) continue;

                $unregistered[] = [
                    'slot' => (int) $matches[1],
                    'port' => (int) $matches[2],
                    'serial_number' => $this->parseHuaweiSerial($serial),
                    'config_status' => 'unregistered',
                ];
            }

        } catch (Exception $e) {
            Log::error("Huawei getUnregisteredOnus error: " . $e->getMessage());
        }

        return $unregistered;
    }

    /**
     * Register ONU
     */
    public function registerOnu(array $params): array
    {
        // Huawei typically requires CLI for registration
        return [
            'success' => false,
            'message' => 'ONU registration via SNMP not supported. Use CLI or web interface.',
        ];
    }

    /**
     * Unregister ONU
     */
    public function unregisterOnu(int $slot, int $port, int $onuId): array
    {
        return [
            'success' => false,
            'message' => 'ONU unregistration via SNMP not supported. Use CLI.',
        ];
    }

    /**
     * Reboot ONU
     */
    public function rebootOnu(int $slot, int $port, int $onuId): array
    {
        return [
            'success' => false,
            'message' => 'ONU reboot via SNMP not supported on Huawei',
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
            'message' => 'Service configuration requires CLI access.',
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
