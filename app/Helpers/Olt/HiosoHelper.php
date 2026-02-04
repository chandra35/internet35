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
     * Hioso Enterprise IDs
     */
    protected const ENTERPRISE_HIOSO = '17409';
    protected const ENTERPRISE_HAISHUO = '25355';

    /**
     * Detected enterprise ID for this OLT instance
     */
    protected ?string $enterpriseId = null;

    /**
     * OID sets for different enterprise IDs
     * OIDs are the same structure, just different enterprise prefix
     */
    protected static array $oidSets = [
        '17409' => [
            'prefix' => '1.3.6.1.4.1.17409',
            'ponPortAdmin' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.2',
            'ponPortOper' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.3',
            'onuSerial' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
            'onuMac' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.3',
            'onuStatus' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.4',
            'onuDistance' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.7',
            'onuRxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.3',
            'onuTxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.4',
            'onuOltRxPower' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.5',
            'onuTemperature' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.6',
            'onuVoltage' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.7',
            'uncfgOnuSerial' => '1.3.6.1.4.1.17409.2.3.5.1.3.1.1.2',
            'onuAuthAction' => '1.3.6.1.4.1.17409.2.3.5.1.2.1.1.2',
        ],
        '25355' => [
            'prefix' => '1.3.6.1.4.1.25355',
            'ponPortAdmin' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.2',
            'ponPortOper' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.3',
            'onuSerial' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.2',
            'onuMac' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.3',
            'onuStatus' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.4',
            'onuDistance' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.7',
            'onuRxPower' => '1.3.6.1.4.1.25355.2.3.5.1.4.1.1.3',
            'onuTxPower' => '1.3.6.1.4.1.25355.2.3.5.1.4.1.1.4',
            'onuOltRxPower' => '1.3.6.1.4.1.25355.2.3.5.1.4.1.1.5',
            'onuTemperature' => '1.3.6.1.4.1.25355.2.3.5.1.4.1.1.6',
            'onuVoltage' => '1.3.6.1.4.1.25355.2.3.5.1.4.1.1.7',
            'uncfgOnuSerial' => '1.3.6.1.4.1.25355.2.3.5.1.3.1.1.2',
            'onuAuthAction' => '1.3.6.1.4.1.25355.2.3.5.1.2.1.1.2',
        ],
    ];

    /**
     * Get OID for this OLT's enterprise
     */
    protected function getOid(string $key): string
    {
        $enterpriseId = $this->getEnterpriseId();
        
        if (isset(self::$oidSets[$enterpriseId][$key])) {
            return self::$oidSets[$enterpriseId][$key];
        }
        
        // Fallback to standard Hioso
        return self::$oidSets['17409'][$key] ?? $this->hiosoOids[$key] ?? '';
    }

    /**
     * Get enterprise ID for this OLT
     * Uses cached value if available, otherwise detects via SNMP with short timeout
     */
    protected function getEnterpriseId(): string
    {
        if ($this->enterpriseId !== null) {
            return $this->enterpriseId;
        }

        // Check if already stored in OLT internal_notes (cached)
        if ($this->olt->internal_notes) {
            $notes = json_decode($this->olt->internal_notes, true);
            if (isset($notes['enterprise_id'])) {
                $this->enterpriseId = $notes['enterprise_id'];
                return $this->enterpriseId;
            }
        }

        // Detect from sysObjectID with short timeout
        try {
            // Use shorter timeout for enterprise detection
            $oldTimeout = $this->snmpTimeout;
            $this->snmpTimeout = 2; // 2 seconds max
            
            $sysObjectId = $this->snmpGet($this->commonOids['sysObjectID']);
            
            $this->snmpTimeout = $oldTimeout;
            
            if ($sysObjectId && preg_match('/(?:iso|\.?1)\.3\.6\.1\.4\.1\.(\d+)/', $sysObjectId, $matches)) {
                $this->enterpriseId = $matches[1];
                Log::info("Hioso OLT {$this->olt->name} detected enterprise ID: {$this->enterpriseId}");
                
                // Cache to OLT internal_notes
                $this->cacheEnterpriseId($this->enterpriseId);
                
                return $this->enterpriseId;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to detect enterprise ID: " . $e->getMessage());
        }

        // Default to Haishuo (25355) which uses Telnet - safer fallback
        $this->enterpriseId = self::ENTERPRISE_HAISHUO;
        $this->cacheEnterpriseId($this->enterpriseId);
        
        return $this->enterpriseId;
    }
    
    /**
     * Cache enterprise ID to OLT internal_notes
     */
    protected function cacheEnterpriseId(string $enterpriseId): void
    {
        try {
            $notes = $this->olt->internal_notes ? json_decode($this->olt->internal_notes, true) : [];
            $notes['enterprise_id'] = $enterpriseId;
            $this->olt->update(['internal_notes' => json_encode($notes)]);
        } catch (\Exception $e) {
            // Ignore cache errors
        }
    }

    /**
     * Identify Hioso OLT
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => 'hioso',
            'model' => null,
            'olt_type' => null, // EPON or GPON
            'description' => null,
            'firmware' => null,
            'hardware_version' => null,
            'serial_number' => null,
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

            // Get sysName - often contains EPON/GPON type
            $sysName = @snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.5.0', 5000000, 2);
            $sysName = $sysName ? trim(str_replace(['"', "'"], '', $sysName)) : '';

            // Detect OLT type (EPON or GPON) from sysName or sysDescr
            $combinedInfo = strtoupper($sysName . ' ' . $sysDescr);
            if (strpos($combinedInfo, 'GPON') !== false) {
                $result['olt_type'] = 'GPON';
            } elseif (strpos($combinedInfo, 'EPON') !== false) {
                $result['olt_type'] = 'EPON';
            }

            // Get sysObjectID to detect enterprise ID
            $sysObjectId = @snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.2.0', 5000000, 2);
            $enterpriseId = null;
            if ($sysObjectId && preg_match('/(?:iso|\.?1)\.3\.6\.1\.4\.1\.(\d+)/', $sysObjectId, $matches)) {
                $enterpriseId = $matches[1];
            }

            // Get model from sysDescr - try multiple patterns
            if (preg_match('/HA\d{4}/i', $sysDescr, $matches)) {
                // Standard Hioso model: HA7302, HA7304, HA7308
                $result['model'] = strtoupper($matches[0]);
            } elseif (preg_match('/HA\d{4}/i', $sysName, $matches)) {
                // Try from sysName
                $result['model'] = strtoupper($matches[0]);
            } elseif (preg_match('/([A-Z]{2,}\d{3,}[A-Z]*)/i', $sysDescr, $matches)) {
                // Generic model pattern: letters + numbers
                $result['model'] = strtoupper($matches[1]);
            } else {
                // Use cleaned sysDescr as model (remove quotes, trim)
                $cleanDescr = trim(str_replace(['"', "'"], '', $sysDescr));
                if (!empty($cleanDescr) && strlen($cleanDescr) <= 30) {
                    $result['model'] = $cleanDescr;
                } else {
                    // Derive from enterprise ID
                    $result['model'] = match($enterpriseId) {
                        '17409' => 'Hioso EPON',
                        '25355' => 'Haishuo EPON',
                        default => 'EPON OLT',
                    };
                }
            }

            // Try to count PON ports from PON port table
            $totalPonPorts = 0;
            
            // Try enterprise-specific OID first
            if ($enterpriseId && isset(self::$oidSets[$enterpriseId])) {
                $ponOid = self::$oidSets[$enterpriseId]['ponPortAdmin'];
                $ponPorts = @snmpwalkoid($ipAddress, $snmpCommunity, $ponOid, 5000000, 2);
                if ($ponPorts) {
                    $totalPonPorts = count($ponPorts);
                }
            }
            
            // Fallback: try Hioso standard OID (17409)
            if ($totalPonPorts == 0) {
                $ponPorts = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.2', 5000000, 2);
                if ($ponPorts) {
                    $totalPonPorts = count($ponPorts);
                }
            }
            
            // Fallback: count PON ports from ifDescr (look for "PON" or "EPON" or "GPON")
            // Also count uplink ports (GE, G1-Gx, Ethernet, etc.)
            $totalUplinkPorts = 0;
            if ($totalPonPorts == 0) {
                $ifDescrs = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.2.1.2.2.1.2', 5000000, 2);
                if ($ifDescrs) {
                    foreach ($ifDescrs as $oid => $val) {
                        $valLower = strtolower($val);
                        if (strpos($valLower, 'pon') !== false || 
                            strpos($valLower, 'epon') !== false || 
                            strpos($valLower, 'gpon') !== false) {
                            $totalPonPorts++;
                        } elseif (preg_match('/^g\d+$/i', trim($val)) || 
                                  preg_match('/^ge\d*/i', trim($val)) ||
                                  strpos($valLower, 'uplink') !== false ||
                                  strpos($valLower, 'ethernet') !== false) {
                            $totalUplinkPorts++;
                        }
                    }
                }
            }

            // Default for known models (only if still 0)
            if ($totalPonPorts == 0 && $result['model']) {
                if (stripos($result['model'], 'HA7308') !== false) {
                    $totalPonPorts = 8;
                } elseif (stripos($result['model'], 'HA7304') !== false) {
                    $totalPonPorts = 4;
                } elseif (stripos($result['model'], 'HA7302') !== false) {
                    $totalPonPorts = 2;
                }
            }
            
            // If still 0, don't guess - leave as 0 for user to configure
            $result['total_pon_ports'] = $totalPonPorts;
            $result['total_uplink_ports'] = $totalUplinkPorts > 0 ? $totalUplinkPorts : ($totalPonPorts > 0 ? 2 : 0);
            $result['success'] = true;
            
            if ($totalPonPorts > 0) {
                $result['message'] = 'OLT berhasil diidentifikasi';
            } else {
                $result['message'] = 'OLT teridentifikasi. PON ports tidak terdeteksi via SNMP, silakan isi manual.';
            }

        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get PON ports info
     * Uses SNMP enterprise OIDs, falls back to ifDescr detection
     */
    public function getPonPorts(): array
    {
        $ports = [];

        try {
            // Try SNMP enterprise OIDs first
            $adminStatuses = $this->snmpWalk($this->getOid('ponPortAdmin'));
            $operStatuses = $this->snmpWalk($this->getOid('ponPortOper'));

            if (!empty($adminStatuses)) {
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
                        'status' => ($operStatuses[$this->getOid('ponPortOper') . ".{$index}"] ?? 0) == 1 ? 'up' : 'down',
                    ];

                    $this->updatePonPort($slot, $port, end($ports));
                }
            }
            
            // Fallback: detect PON ports from ifDescr
            if (empty($ports)) {
                Log::info("Hioso: SNMP PON ports empty, detecting from ifDescr");
                $ports = $this->detectPonPortsFromInterfaces();
            }

        } catch (Exception $e) {
            Log::error("Hioso getPonPorts error: " . $e->getMessage());
            
            // Try fallback
            try {
                $ports = $this->detectPonPortsFromInterfaces();
            } catch (Exception $e2) {
                Log::error("Hioso getPonPorts fallback error: " . $e2->getMessage());
            }
        }

        return $ports;
    }

    /**
     * Detect PON ports from interface descriptions (ifDescr)
     */
    protected function detectPonPortsFromInterfaces(): array
    {
        $ports = [];
        
        $ifDescrs = $this->snmpWalk('1.3.6.1.2.1.2.2.1.2'); // ifDescr
        
        foreach ($ifDescrs as $oid => $name) {
            $name = trim(str_replace(['"', "'"], '', $name));
            
            // Match PON interface names like: Pon-Nni1, EPON0/1, PON1, epon-0/1, etc.
            // Use .*? to match any characters (including -Nni) before the port number
            if (preg_match('/pon.*?(\d+)$/i', $name, $m)) {
                $portNum = (int) $m[1];
                $slot = 0;
                
                // Check for slot/port format (e.g., EPON0/1)
                if (preg_match('/(\d+)\/(\d+)/', $name, $sp)) {
                    $slot = (int) $sp[1];
                    $portNum = (int) $sp[2];
                }
                
                $ports[] = [
                    'slot' => $slot,
                    'port' => $portNum,
                    'name' => $name,
                    'admin_status' => 'enabled',
                    'status' => 'up',
                ];
                
                $this->updatePonPort($slot, $portNum, end($ports));
            }
        }
        
        // Sort by port number
        usort($ports, fn($a, $b) => $a['port'] - $b['port']);
        
        Log::info("Hioso: Detected " . count($ports) . " PON ports from ifDescr");
        
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
            'admin_status' => $this->snmpGet($this->getOid('ponPortAdmin') . ".{$index}"),
            'oper_status' => $this->snmpGet($this->getOid('ponPortOper') . ".{$index}"),
        ];
    }

    /**
     * Get all ONUs
     * Tries SNMP first, falls back to Telnet if SNMP returns empty
     */
    public function getAllOnus(): array
    {
        $onus = [];

        try {
            // Try SNMP first
            $serialNumbers = $this->snmpWalk($this->getOid('onuSerial'));
            
            if (!empty($serialNumbers)) {
                $statuses = $this->snmpWalk($this->getOid('onuStatus'));
                $distances = $this->snmpWalk($this->getOid('onuDistance'));

                foreach ($serialNumbers as $oid => $serialRaw) {
                    // Parse slot.port.onuid
                    preg_match('/\.(\d+)\.(\d+)\.(\d+)$/', $oid, $matches);
                    if (count($matches) < 4) continue;

                    $slot = (int) $matches[1];
                    $port = (int) $matches[2];
                    $onuId = (int) $matches[3];
                    $index = "{$slot}.{$port}.{$onuId}";

                    $status = $statuses[$this->getOid('onuStatus') . ".{$index}"] ?? 0;

                    $onus[] = [
                        'slot' => $slot,
                        'port' => $port,
                        'onu_id' => $onuId,
                        'serial_number' => $this->parseSerialNumber($serialRaw),
                        'status' => $this->statusMap[$status] ?? 'unknown',
                        'distance' => $this->parseDistance($distances[$this->getOid('onuDistance') . ".{$index}"] ?? null),
                    ];
                }
            }

            // Fallback to Telnet if SNMP returned empty
            if (empty($onus)) {
                Log::info("Hioso SNMP returned empty, trying Telnet fallback for {$this->olt->name}");
                $onus = $this->getAllOnusViaTelnet();
            }

        } catch (Exception $e) {
            Log::error("Hioso getAllOnus SNMP error: " . $e->getMessage());
            
            // Try Telnet fallback on SNMP error
            try {
                $onus = $this->getAllOnusViaTelnet();
            } catch (Exception $te) {
                Log::error("Hioso getAllOnus Telnet fallback error: " . $te->getMessage());
            }
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
                $this->snmpGet($this->getOid('onuSerial') . ".{$index}") ?? ''
            ),
            'mac_address' => $this->snmpGet($this->getOid('onuMac') . ".{$index}"),
            'status' => $this->statusMap[$this->snmpGet($this->getOid('onuStatus') . ".{$index}")] ?? 'unknown',
            'distance' => $this->parseDistance($this->snmpGet($this->getOid('onuDistance') . ".{$index}")),
        ];

        return array_merge($info, $this->getOnuOpticalInfo($slot, $port, $onuId));
    }

    /**
     * Get ONU optical info via SNMP, fallback to Telnet
     * Note: Enterprise 25355 OLTs don't respond to SNMP enterprise OIDs, use Telnet directly
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        // For Enterprise 25355, skip SNMP and use Telnet directly (SNMP OIDs don't work)
        if ($this->getEnterpriseId() === self::ENTERPRISE_HAISHUO) {
            return $this->getOnuOpticalViaTelnet($slot, $port, $onuId);
        }
        
        $index = "{$slot}.{$port}.{$onuId}";

        $rxPower = $this->snmpGet($this->getOid('onuRxPower') . ".{$index}");
        $txPower = $this->snmpGet($this->getOid('onuTxPower') . ".{$index}");
        $oltRx = $this->snmpGet($this->getOid('onuOltRxPower') . ".{$index}");
        $temp = $this->snmpGet($this->getOid('onuTemperature') . ".{$index}");
        $volt = $this->snmpGet($this->getOid('onuVoltage') . ".{$index}");

        $result = [
            'rx_power' => $this->parseHiosoOpticalPower($rxPower),
            'tx_power' => $this->parseHiosoOpticalPower($txPower),
            'olt_rx_power' => $this->parseHiosoOpticalPower($oltRx),
            'temperature' => $temp ? ((float)$temp / 100) : null,
            'voltage' => $volt ? ((float)$volt / 1000) : null,
        ];
        
        // If SNMP returns no data, try Telnet
        if ($result['rx_power'] === null && $result['tx_power'] === null) {
            $telnetData = $this->getOnuOpticalViaTelnet($slot, $port, $onuId);
            if (!empty($telnetData)) {
                return $telnetData;
            }
        }
        
        return $result;
    }
    
    /**
     * Get single ONU optical data via Telnet
     * Command: show onu optical-ddm epon 0/{port} {onuId}
     * Note: Connection is kept open for reuse, caller should disconnect when done
     */
    protected function getOnuOpticalViaTelnet(int $slot, int $port, int $onuId): array
    {
        try {
            $this->telnetConnect();
            
            $command = "show onu optical-ddm epon {$slot}/{$port} {$onuId}";
            $output = $this->telnetCommand($command, 3);
            
            // DON'T disconnect here - keep connection for subsequent calls
            // Caller should call telnetDisconnect() when done with batch operations
            
            // Parse format:
            // Temperature  : 46.00 C
            // Voltage      : 3.00  V
            // TxBias       : 8.00  mA
            // TxPower      : 2.22 dBm
            // RxPower      : -14.60 dBm
            
            $data = [
                'tx_power' => null,
                'rx_power' => null,
                'olt_rx_power' => null,
                'temperature' => null,
                'voltage' => null,
            ];
            
            if (preg_match('/TxPower\s*:\s*(-?[\d.]+)/i', $output, $m)) {
                $data['tx_power'] = (float) $m[1];
            }
            if (preg_match('/RxPower\s*:\s*(-?[\d.]+)/i', $output, $m)) {
                $data['rx_power'] = (float) $m[1];
            }
            if (preg_match('/Temperature\s*:\s*(-?[\d.]+)/i', $output, $m)) {
                $data['temperature'] = (float) $m[1];
            }
            if (preg_match('/Voltage\s*:\s*([\d.]+)/i', $output, $m)) {
                $data['voltage'] = (float) $m[1];
            }
            
            return $data;
            
        } catch (Exception $e) {
            Log::error("Hioso: Failed to get optical via Telnet for ONU {$slot}/{$port}:{$onuId}: " . $e->getMessage());
            return [];
        }
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
            $uncfgSerials = $this->snmpWalk($this->getOid('uncfgOnuSerial'));

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
                $this->getOid('onuSerial') . ".{$index}",
                's',
                $serial
            );

            // Authorize ONU
            $this->snmpSet(
                $this->getOid('onuAuthAction') . ".{$index}",
                'i',
                1 // 1 = add/authorize
            );

            // Verify registration
            sleep(2);
            $verifySerial = $this->snmpGet($this->getOid('onuSerial') . ".{$index}");

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
                $this->getOid('onuAuthAction') . ".{$index}",
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
     * For Hioso OLTs with Enterprise 25355, uses Telnet data directly
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
            // Sync PON ports
            $this->getPonPorts();
            $result['pon_ports_synced'] = $this->olt->total_pon_ports;

            // Get all ONUs (may come from SNMP or Telnet)
            $allOnus = $this->getAllOnus();
            
            // Check if data came from Telnet (has 'source' => 'telnet')
            $isFromTelnet = !empty($allOnus) && isset($allOnus[0]['source']) && $allOnus[0]['source'] === 'telnet';

            foreach ($allOnus as $onuData) {
                try {
                    // If data is from Telnet, it's already complete - use directly
                    // If from SNMP, need to get additional info
                    if ($isFromTelnet) {
                        $fullInfo = $onuData;
                    } else {
                        $fullInfo = $this->getOnuInfo($onuData['slot'], $onuData['port'], $onuData['onu_id']);
                    }

                    // Ensure required fields
                    $fullInfo['olt_id'] = $this->olt->id;
                    $fullInfo['config_status'] = 'registered';
                    
                    // Save to database
                    $onu = $this->saveOnuToDatabase($fullInfo);

                    // Save signal history (optical data may be empty for Telnet)
                    $this->saveSignalHistory($onu, $fullInfo);

                    $result['onus_synced']++;
                    $result['signals_recorded']++;

                } catch (Exception $e) {
                    $result['errors'][] = "ONU {$onuData['slot']}/{$onuData['port']}:{$onuData['onu_id']}: " . $e->getMessage();
                }
            }

            // Update PON port stats from synced ONU data
            $this->updatePonPortStats($allOnus);

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
     * Update PON port statistics from ONU data
     */
    protected function updatePonPortStats(array $onus): void
    {
        $portStats = [];
        
        foreach ($onus as $onu) {
            $key = "{$onu['slot']}.{$onu['port']}";
            
            if (!isset($portStats[$key])) {
                $portStats[$key] = [
                    'slot' => $onu['slot'],
                    'port' => $onu['port'],
                    'total_onus' => 0,
                    'online_onus' => 0,
                    'offline_onus' => 0,
                ];
            }
            
            $portStats[$key]['total_onus']++;
            
            if ($onu['status'] === 'online') {
                $portStats[$key]['online_onus']++;
            } else {
                $portStats[$key]['offline_onus']++;
            }
        }
        
        foreach ($portStats as $stats) {
            $this->updatePonPort($stats['slot'], $stats['port'], [
                'total_onus' => $stats['total_onus'],
                'online_onus' => $stats['online_onus'],
                'offline_onus' => $stats['offline_onus'],
                'status' => 'up',
            ]);
        }
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

    // ========================================
    // TELNET METHODS (Fallback for Enterprise 25355 OLTs)
    // ========================================

    /**
     * Telnet connection resource
     */
    protected $telnetSocket = null;

    /**
     * Get all ONUs via Telnet with optical data
     * Command: show onu info epon 0/{port} all
     * Optical data can be skipped for faster sync (default: skip for speed)
     * 
     * @param bool $includeOptical Whether to include optical DDM data (slower)
     */
    protected function getAllOnusViaTelnet(bool $includeOptical = false): array
    {
        $onus = [];

        try {
            $this->telnetConnect();
            
            // Query each PON port
            $totalPorts = $this->olt->total_pon_ports ?? 4;
            
            for ($port = 1; $port <= $totalPorts; $port++) {
                // Get ONU basic info
                $portOnus = $this->getOnuInfoViaTelnet(0, $port);
                
                // Only query optical if explicitly requested (slow - ~1 sec per ONU)
                if ($includeOptical) {
                    // Collect ONLINE ONU IDs for optical query
                    $onlineOnuIds = [];
                    foreach ($portOnus as $onu) {
                        if (($onu['status'] ?? 'offline') === 'online') {
                            $onlineOnuIds[] = $onu['onu_id'];
                        }
                    }
                    
                    // Get optical data only for ONLINE ONUs
                    if (!empty($onlineOnuIds)) {
                        Log::info("Hioso: Querying optical for " . count($onlineOnuIds) . " online ONUs on port 0/{$port}");
                        $opticalData = $this->getPortOpticalViaTelnet(0, $port, $onlineOnuIds);
                        
                        // Merge optical data into ONU info
                        foreach ($portOnus as &$onu) {
                            $onuId = $onu['onu_id'];
                            if (isset($opticalData[$onuId])) {
                                $onu = array_merge($onu, $opticalData[$onuId]);
                            }
                        }
                        unset($onu);
                    }
                }
                
                $onus = array_merge($onus, $portOnus);
            }
            
            $this->telnetDisconnect();
            
        } catch (Exception $e) {
            Log::error("Hioso Telnet getAllOnus error: " . $e->getMessage());
            $this->telnetDisconnect();
        }

        return $onus;
    }

    /**
     * Get optical DDM data for all ONUs in a port via Telnet
     * Note: Hioso requires per-ONU query which is slow
     * Command: show onu optical-ddm epon 0/{port} {onuId}
     * 
     * For bulk sync, we query only ONLINE ONUs to save time
     * Returns array keyed by onu_id
     */
    protected function getPortOpticalViaTelnet(int $slot, int $port, array $onuIds = []): array
    {
        $optical = [];
        
        // If no ONU IDs provided, skip (caller should provide list of online ONUs)
        if (empty($onuIds)) {
            return $optical;
        }
        
        foreach ($onuIds as $onuId) {
            $command = "show onu optical-ddm epon {$slot}/{$port} {$onuId}";
            $output = $this->telnetCommand($command, 3);
            
            // Parse format:
            // Temperature  : 46.00 C
            // Voltage      : 3.00  V
            // TxBias       : 8.00  mA
            // TxPower      : 2.22 dBm
            // RxPower      : -14.60 dBm
            
            $data = [
                'tx_power' => null,
                'rx_power' => null,
                'olt_rx_power' => null,
                'temperature' => null,
                'voltage' => null,
            ];
            
            if (preg_match('/TxPower\s*:\s*(-?[\d.]+)/i', $output, $m)) {
                $data['tx_power'] = (float) $m[1];
            }
            if (preg_match('/RxPower\s*:\s*(-?[\d.]+)/i', $output, $m)) {
                $data['rx_power'] = (float) $m[1];
            }
            if (preg_match('/Temperature\s*:\s*(-?[\d.]+)/i', $output, $m)) {
                $data['temperature'] = (float) $m[1];
            }
            if (preg_match('/Voltage\s*:\s*([\d.]+)/i', $output, $m)) {
                $data['voltage'] = (float) $m[1];
            }
            
            // Only store if we got at least one value
            if ($data['tx_power'] !== null || $data['rx_power'] !== null) {
                $optical[$onuId] = $data;
            }
        }
        
        Log::info("Hioso: Got optical data for " . count($optical) . " ONUs on port {$slot}/{$port}");
        
        return $optical;
    }

    /**
     * Get ONU info for a specific port via Telnet
     */
    protected function getOnuInfoViaTelnet(int $slot, int $port): array
    {
        $onus = [];
        
        $command = "show onu info epon {$slot}/{$port} all";
        $output = $this->telnetCommand($command, 10);
        
        // Parse output
        // Format: OnuId  MacAddress        Status  Firmware ChipId Ge Fe Pots CtcStatus CtcVer Activate Uptime Name
        //         0/1:1  08:5c:1b:de:39:dd Down    3230     6301   4  2  1    --        30     Yes      0H0M0S Jani-28
        
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip header and empty lines
            if (empty($line) || strpos($line, 'OnuId') === 0 || strpos($line, '===') === 0) {
                continue;
            }
            
            // Match ONU line: slot/port:onuId MAC Status ...
            if (preg_match('/^(\d+)\/(\d+):(\d+)\s+([0-9a-f:]+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+\S+\s+\w+\s+(\S+)\s*(.*)?$/i', $line, $matches)) {
                $onuSlot = (int) $matches[1];
                $onuPort = (int) $matches[2];
                $onuId = (int) $matches[3];
                $macAddress = strtoupper($matches[4]);
                $statusRaw = $matches[5];
                $firmware = $matches[6];
                $chipId = $matches[7];
                $geCount = (int) $matches[8];
                $feCount = (int) $matches[9];
                $potsCount = (int) $matches[10];
                $ctcStatus = $matches[11];
                $uptime = $matches[12];
                $name = trim($matches[13] ?? '');
                
                // Parse uptime to seconds (format: 1D17H50M44S or 0H0M0S)
                $uptimeSeconds = $this->parseHiosoUptime($uptime);
                
                $onus[] = [
                    'slot' => $onuSlot,
                    'port' => $onuPort,
                    'onu_id' => $onuId,
                    'mac_address' => $macAddress,
                    'serial_number' => str_replace(':', '', $macAddress), // Use MAC as serial for Hioso
                    'status' => strtolower($statusRaw) === 'up' ? 'online' : 'offline',
                    'firmware' => $firmware,
                    'chip_id' => $chipId,
                    'ge_ports' => $geCount,
                    'fe_ports' => $feCount,
                    'pots_ports' => $potsCount,
                    'ctc_status' => $ctcStatus,
                    'uptime_seconds' => $uptimeSeconds,
                    'description' => $name,
                    'source' => 'telnet',
                ];
            }
        }
        
        Log::info("Hioso Telnet: Found " . count($onus) . " ONUs on port {$slot}/{$port}");
        
        return $onus;
    }

    /**
     * Parse Hioso uptime string to seconds
     * Format: 1D17H50M44S, 0H0M0S, 18D1H19M53S
     */
    protected function parseHiosoUptime(string $uptime): int
    {
        $seconds = 0;
        
        if (preg_match('/(\d+)D/i', $uptime, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)H/i', $uptime, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)M/i', $uptime, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)S/i', $uptime, $m)) {
            $seconds += (int) $m[1];
        }
        
        return $seconds;
    }

    /**
     * Get PON port optical DDM (Digital Diagnostic Monitoring) via Telnet
     * Reads TX Power, RX Power, Temperature, Voltage from OLT SFP transceiver
     * 
     * @return array Array of PON ports with optical DDM data
     */
    public function getPonOpticalPower(): array
    {
        $result = [];
        
        try {
            // Get PON ports from database
            $ponPorts = \App\Models\OltPonPort::where('olt_id', $this->olt->id)
                ->orderBy('slot')
                ->orderBy('port')
                ->get();
            
            if ($ponPorts->isEmpty()) {
                // Use default 4 PON ports for Hioso (typical for HA7304)
                $ports = [];
                for ($i = 1; $i <= 4; $i++) {
                    $ports[] = ['slot' => 0, 'port' => $i, 'name' => "PON 0/{$i}"];
                }
            } else {
                $ports = $ponPorts->map(function($p) {
                    return ['slot' => $p->slot, 'port' => $p->port, 'name' => $p->name ?? "PON {$p->slot}/{$p->port}"];
                })->toArray();
            }
            
            // Get optical DDM via dedicated connection
            $result = $this->getOpticalDdmViaTelnet($ports);
            
        } catch (\Exception $e) {
            Log::error("Hioso getPonOpticalPower error: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Get optical DDM data for all PON ports via single Telnet session
     */
    protected function getOpticalDdmViaTelnet(array $ports): array
    {
        $result = [];
        $socket = null;
        
        try {
            $ip = $this->olt->ip_address;
            $username = $this->olt->telnet_username ?? 'admin';
            $password = $this->olt->telnet_password ?? 'admin';
            
            // Connect
            $socket = @fsockopen($ip, 23, $errno, $errstr, 10);
            if (!$socket) {
                throw new \Exception("Cannot connect to {$ip}: {$errstr}");
            }
            
            stream_set_timeout($socket, 10);
            
            // Helper to read until pattern
            $readUntil = function($patterns, $timeout = 5) use ($socket) {
                $start = time();
                $buffer = '';
                while (time() - $start < $timeout) {
                    $chunk = @fread($socket, 1024);
                    if ($chunk) {
                        $buffer .= $chunk;
                        foreach ((array)$patterns as $p) {
                            if (stripos($buffer, $p) !== false) return $buffer;
                        }
                    }
                    usleep(50000);
                }
                return $buffer;
            };
            
            // Login
            $readUntil(['login:', 'Username:'], 10);
            fwrite($socket, "{$username}\r\n");
            usleep(200000);
            
            $readUntil(['Password:'], 5);
            fwrite($socket, "{$password}\r\n");
            usleep(200000);
            
            $readUntil(['>', '#', 'EPON'], 5);
            fwrite($socket, "enable\r\n");
            usleep(200000);
            
            $buf = $readUntil(['#', 'Password:'], 3);
            if (stripos($buf, 'Password') !== false) {
                fwrite($socket, "{$password}\r\n");
                usleep(200000);
                $readUntil(['#'], 3);
            }
            
            // Execute DDM command for each port
            foreach ($ports as $port) {
                $slot = $port['slot'];
                $portNum = $port['port'];
                $portName = $port['name'];
                
                fwrite($socket, "show epon {$slot}/{$portNum} optical-ddm\r\n");
                usleep(500000);
                
                $output = $readUntil(['EPON#'], 5);
                
                // Parse DDM output
                $txPower = null;
                $rxPower = null;
                $temperature = null;
                $voltage = null;
                $txBias = null;
                
                if (preg_match('/TxPower\s*:\s*([\-\d\.]+)\s*dBm/i', $output, $m)) {
                    $txPower = floatval($m[1]);
                }
                if (preg_match('/RxPower\s*:\s*([\-\d\.]+)\s*dBm/i', $output, $m)) {
                    $rxPower = floatval($m[1]);
                }
                if (preg_match('/Temperature\s*:\s*([\-\d\.]+)\s*C/i', $output, $m)) {
                    $temperature = floatval($m[1]);
                }
                if (preg_match('/Voltage\s*:\s*([\-\d\.]+)\s*V/i', $output, $m)) {
                    $voltage = floatval($m[1]);
                }
                if (preg_match('/TxBias\s*:\s*([\-\d\.]+)\s*mA/i', $output, $m)) {
                    $txBias = floatval($m[1]);
                }
                
                // Determine signal quality based on TX power
                $signalQuality = 'unknown';
                if ($txPower !== null) {
                    if ($txPower >= 0 && $txPower <= 10) {
                        $signalQuality = 'excellent';
                    } elseif ($txPower >= -3 && $txPower < 0) {
                        $signalQuality = 'good';
                    } elseif ($txPower >= -6 && $txPower < -3) {
                        $signalQuality = 'acceptable';
                    } elseif ($txPower < -6 || $txPower > 10) {
                        $signalQuality = 'warning';
                    }
                }
                
                $result[] = [
                    'slot' => $slot,
                    'port' => $portNum,
                    'name' => $portName,
                    'tx_power' => $txPower,
                    'rx_power' => $rxPower,
                    'temperature' => $temperature,
                    'voltage' => $voltage,
                    'tx_bias' => $txBias,
                    'signal_quality' => $signalQuality,
                    'tx_power_formatted' => $txPower !== null ? $txPower . ' dBm' : '-',
                    'rx_power_formatted' => $rxPower !== null ? $rxPower . ' dBm' : '-',
                    'temperature_formatted' => $temperature !== null ? $temperature . ' °C' : '-',
                    'voltage_formatted' => $voltage !== null ? $voltage . ' V' : '-',
                    'tx_bias_formatted' => $txBias !== null ? $txBias . ' mA' : '-',
                ];
            }
            
            // Logout
            @fwrite($socket, "exit\r\n");
            @fclose($socket);
            
        } catch (\Exception $e) {
            if ($socket) {
                @fclose($socket);
            }
            Log::error("Hioso getOpticalDdmViaTelnet error: " . $e->getMessage());
        }
        
        return $result;
    }

    /**
     * Connect via Telnet
     */
    protected function telnetConnect(): void
    {
        if ($this->telnetSocket) {
            return; // Already connected
        }

        $ip = $this->olt->ip_address;
        $port = $this->olt->telnet_port ?? 23;
        $username = $this->olt->telnet_username ?? 'admin';
        $password = $this->olt->telnet_password ?? 'admin';

        $this->telnetSocket = @fsockopen($ip, $port, $errno, $errstr, 10);
        
        if (!$this->telnetSocket) {
            throw new Exception("Telnet connection failed: {$errstr}");
        }

        stream_set_timeout($this->telnetSocket, 30);

        // Wait for login prompt
        $this->telnetReadUntil(['login:', 'Username:', 'user:'], 10);
        
        // Send username
        $this->telnetWrite($username);
        
        // Wait for password prompt
        $this->telnetReadUntil(['Password:', 'assword'], 5);
        
        // Send password
        $this->telnetWrite($password);
        
        // Wait for prompt
        $response = $this->telnetReadUntil(['>', '#', 'EPON'], 5);
        
        if (stripos($response, 'failed') !== false || stripos($response, 'incorrect') !== false) {
            $this->telnetDisconnect();
            throw new Exception("Telnet login failed");
        }

        // Enter privileged mode
        $this->telnetWrite('enable');
        $this->telnetReadUntil(['#', 'Password:'], 3);
        
        // If enable password needed, try with same password
        if ($this->telnetSocket) {
            $info = stream_get_meta_data($this->telnetSocket);
            if (!$info['timed_out']) {
                // Check if we got password prompt
                $buf = @fread($this->telnetSocket, 1024);
                if (stripos($buf, 'password') !== false) {
                    $this->telnetWrite($password);
                    $this->telnetReadUntil(['#'], 3);
                }
            }
        }

        Log::info("Hioso Telnet connected to {$ip}");
    }

    /**
     * Disconnect Telnet
     */
    protected function telnetDisconnect(): void
    {
        if ($this->telnetSocket) {
            @fwrite($this->telnetSocket, "exit\r\n");
            usleep(100000);
            @fwrite($this->telnetSocket, "exit\r\n");
            usleep(100000);
            @fclose($this->telnetSocket);
            $this->telnetSocket = null;
        }
    }

    /**
     * Send command via Telnet
     */
    protected function telnetCommand(string $command, int $timeout = 5): string
    {
        if (!$this->telnetSocket) {
            throw new Exception("Telnet not connected");
        }

        $this->telnetWrite($command);
        
        $output = '';
        $start = time();
        
        while (time() - $start < $timeout) {
            $chunk = @fread($this->telnetSocket, 4096);
            
            if ($chunk) {
                $output .= $chunk;
                
                // Handle pagination
                if (stripos($output, '--More--') !== false || stripos($output, '--- Enter Key') !== false) {
                    fwrite($this->telnetSocket, " "); // Space to continue
                    $output = preg_replace('/--More--/', '', $output);
                    $output = preg_replace('/--- Enter Key.*----/', '', $output);
                }
                
                // Check for prompt return
                if (preg_match('/EPON[#>]\s*$/', $output)) {
                    break;
                }
            }
            
            usleep(100000);
        }
        
        // Clean output
        $output = str_replace("\r", "", $output);
        
        // Remove command echo and prompt
        $lines = explode("\n", $output);
        $cleanLines = [];
        $skipFirst = true;
        
        foreach ($lines as $line) {
            if ($skipFirst && stripos($line, $command) !== false) {
                $skipFirst = false;
                continue;
            }
            if (!$skipFirst && stripos($line, 'EPON#') === false && stripos($line, 'EPON>') === false) {
                $cleanLines[] = $line;
            }
        }
        
        return implode("\n", $cleanLines);
    }

    /**
     * Write to Telnet socket
     */
    protected function telnetWrite(string $data): void
    {
        if ($this->telnetSocket) {
            fwrite($this->telnetSocket, $data . "\r\n");
            usleep(300000); // Wait 300ms
        }
    }

    /**
     * Read from Telnet until pattern found
     */
    protected function telnetReadUntil(array $patterns, int $timeout = 5): string
    {
        $buffer = '';
        $start = time();
        
        while (time() - $start < $timeout) {
            $char = @fread($this->telnetSocket, 1024);
            
            if ($char) {
                $buffer .= $char;
                
                foreach ($patterns as $pattern) {
                    if (stripos($buffer, $pattern) !== false) {
                        return $buffer;
                    }
                }
            }
            
            usleep(50000);
        }
        
        return $buffer;
    }

    /**
     * Get interface traffic statistics via SNMP
     * This works for all Hioso OLTs including Enterprise 25355
     * 
     * @return array Interface statistics with traffic data
     */
    public function getInterfaceStats(): array
    {
        $stats = [];
        
        try {
            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            
            $timeout = 3000000; // 3 seconds
            $retries = 1;
            
            // Get interface descriptions
            $ifDescr = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.2',
                $timeout,
                $retries
            );
            
            if (!$ifDescr) {
                return [];
            }
            
            // Get additional interface data
            $ifOperStatus = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.8', $timeout, $retries) ?: [];
            $ifSpeed = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.5', $timeout, $retries) ?: [];
            $ifInOctets = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.10', $timeout, $retries) ?: [];
            $ifOutOctets = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.16', $timeout, $retries) ?: [];
            $ifInErrors = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.14', $timeout, $retries) ?: [];
            $ifOutErrors = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.20', $timeout, $retries) ?: [];
            $ifInUcast = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.11', $timeout, $retries) ?: [];
            $ifOutUcast = @snmpwalkoid($this->olt->ip_address, $this->olt->snmp_community ?? 'public', '1.3.6.1.2.1.2.2.1.17', $timeout, $retries) ?: [];
            
            foreach ($ifDescr as $oid => $name) {
                // Extract interface index from OID
                $idx = substr($oid, strrpos($oid, '.') + 1);
                
                // Determine interface type
                $nameLower = strtolower($name);
                $type = 'other';
                if (strpos($nameLower, 'pon') !== false) {
                    $type = 'pon';
                } elseif (preg_match('/^g\d+$/i', trim($name))) {
                    $type = 'uplink';
                }
                
                // Get status
                $statusKey = "iso.3.6.1.2.1.2.2.1.8.$idx";
                $status = $ifOperStatus[$statusKey] ?? null;
                $statusStr = match((int)$status) {
                    1 => 'up',
                    2 => 'down',
                    3 => 'testing',
                    default => 'unknown'
                };
                
                // Get speed
                $speedKey = "iso.3.6.1.2.1.2.2.1.5.$idx";
                $speed = $ifSpeed[$speedKey] ?? 0;
                $speedMbps = round($speed / 1000000);
                
                // Get traffic counters
                $inOctets = $ifInOctets["iso.3.6.1.2.1.2.2.1.10.$idx"] ?? 0;
                $outOctets = $ifOutOctets["iso.3.6.1.2.1.2.2.1.16.$idx"] ?? 0;
                $inErrors = $ifInErrors["iso.3.6.1.2.1.2.2.1.14.$idx"] ?? 0;
                $outErrors = $ifOutErrors["iso.3.6.1.2.1.2.2.1.20.$idx"] ?? 0;
                $inPackets = $ifInUcast["iso.3.6.1.2.1.2.2.1.11.$idx"] ?? 0;
                $outPackets = $ifOutUcast["iso.3.6.1.2.1.2.2.1.17.$idx"] ?? 0;
                
                $stats[$idx] = [
                    'index' => (int)$idx,
                    'name' => trim(str_replace(['"', "'"], '', $name)),
                    'type' => $type,
                    'status' => $statusStr,
                    'speed_mbps' => $speedMbps,
                    'in_octets' => (int)$inOctets,
                    'out_octets' => (int)$outOctets,
                    'in_bytes_formatted' => $this->formatBytes($inOctets),
                    'out_bytes_formatted' => $this->formatBytes($outOctets),
                    'in_errors' => (int)$inErrors,
                    'out_errors' => (int)$outErrors,
                    'in_packets' => (int)$inPackets,
                    'out_packets' => (int)$outPackets,
                ];
            }
            
            // Sort by index
            ksort($stats);
            
        } catch (\Exception $e) {
            Log::error("Error getting interface stats: " . $e->getMessage());
        }
        
        return array_values($stats);
    }

    /**
     * Get PON port traffic statistics only
     * 
     * @return array PON port statistics
     */
    public function getPonTrafficStats(): array
    {
        $allStats = $this->getInterfaceStats();
        
        return array_filter($allStats, function($stat) {
            return $stat['type'] === 'pon';
        });
    }

    /**
     * Get uplink port traffic statistics only
     * 
     * @return array Uplink port statistics
     */
    public function getUplinkTrafficStats(): array
    {
        $allStats = $this->getInterfaceStats();
        
        return array_filter($allStats, function($stat) {
            return $stat['type'] === 'uplink';
        });
    }

    /**
     * Get traffic summary for dashboard
     * 
     * @return array Traffic summary with totals
     */
    public function getTrafficSummary(): array
    {
        $stats = $this->getInterfaceStats();
        
        $ponStats = array_filter($stats, fn($s) => $s['type'] === 'pon');
        $uplinkStats = array_filter($stats, fn($s) => $s['type'] === 'uplink');
        
        $totalPonIn = array_sum(array_column($ponStats, 'in_octets'));
        $totalPonOut = array_sum(array_column($ponStats, 'out_octets'));
        $totalUplinkIn = array_sum(array_column($uplinkStats, 'in_octets'));
        $totalUplinkOut = array_sum(array_column($uplinkStats, 'out_octets'));
        
        $ponUp = count(array_filter($ponStats, fn($s) => $s['status'] === 'up'));
        $uplinkUp = count(array_filter($uplinkStats, fn($s) => $s['status'] === 'up'));
        
        // Get optical power data
        $opticalData = $this->getOpticalPowerSummary();
        
        return [
            'pon_ports' => [
                'total' => count($ponStats),
                'up' => $ponUp,
                'down' => count($ponStats) - $ponUp,
                'in_bytes' => $totalPonIn,
                'out_bytes' => $totalPonOut,
                'in_formatted' => $this->formatBytes($totalPonIn),
                'out_formatted' => $this->formatBytes($totalPonOut),
                'ports' => array_values($ponStats),
            ],
            'uplink_ports' => [
                'total' => count($uplinkStats),
                'up' => $uplinkUp,
                'down' => count($uplinkStats) - $uplinkUp,
                'in_bytes' => $totalUplinkIn,
                'out_bytes' => $totalUplinkOut,
                'in_formatted' => $this->formatBytes($totalUplinkIn),
                'out_formatted' => $this->formatBytes($totalUplinkOut),
                'ports' => array_values($uplinkStats),
            ],
            'optical_power' => $opticalData,
            'interfaces' => $stats,
            'collected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
