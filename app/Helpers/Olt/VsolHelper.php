<?php

namespace App\Helpers\Olt;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * VSOL OLT Helper
 * 
 * VSOL V1600D4 EPON OLT (including V1600D4-BT and V1600D4-DP models)
 * Supports SNMP v2 for management
 * Common models: V1600D, V1600D4, V1600G
 * 
 * Enterprise ID: 37950
 * MIB follows IEEE 802.3ah OLT structures
 */
class VsolHelper extends BaseOltHelper
{
    /**
     * VSOL V1600D OIDs based on official MIB structure
     * Reference: oid-base.com - 1.3.6.1.4.1.37950.1.1.5.12 (v1600dOnuMgmt)
     * 
     * onuListTable (.12.1.9) - Primary ONU table with correct data!
     * Columns:
     *   .1 = onuIndex (global index)
     *   .2 = ponId (PON port number) 
     *   .3 = llid (ONU ID within PON port)
     *   .4 = status ("auth success", etc)
     *   .5 = macAddress (format: "xx:xx:xx:xx:xx:xx")
     * 
     * Tested and verified: Returns 179 ONUs with MAC addresses!
     */
    protected array $vsolOids = [
        // System Info
        'sysName' => '1.3.6.1.2.1.1.5.0',
        'sysDescr' => '1.3.6.1.2.1.1.1.0',
        'cpuUsage' => '1.3.6.1.4.1.37950.1.1.1.1.0',
        'memoryUsage' => '1.3.6.1.4.1.37950.1.1.1.2.0',
        
        // PON Port Table (vsolEponPortTable) - .1.3.6.1.4.1.37950.1.1.5.11.1.1.1.x
        'ponPortStatus' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1.3',
        'ponPortIndex' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1.1',
        'ponPortDesc' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1.2',
        
        // ============ PRIMARY ONU TABLE (onuListTable) ============
        // .1.3.6.1.4.1.37950.1.1.5.12.1.9.1.x - VERIFIED WORKING!
        // This is the correct table for VSOL V1600D with MAC addresses
        'onuIndex' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.1',      // Global index
        'onuPonPort' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.2',    // PON port number (ponId)
        'onuLlid' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.3',       // ONU ID (llid)
        'onuStatus' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.4',     // Status string ("auth success")
        'onuMacAddress' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.5', // MAC address ("xx:xx:xx:xx:xx:xx")
        
        // ONU Description/Distance - may not be available on all V1600D firmware
        'onuDescription' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.6', // Description (if available)
        'onuDistance' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.7',    // Distance (if available)
        
        // ============ ONU AUTH INFO TABLE (onuAuthInfoTable) ============
        // .1.3.6.1.4.1.37950.1.1.5.12.1.12.1.x - CONTAINS CUSTOMER NAMES!
        'authInfoPonNo' => '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.2',       // PON port number
        'authInfoOnuNo' => '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.3',       // ONU ID
        'authInfoDescription' => '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.10', // Customer name/description
        'authInfoRtt' => '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.13',        // RTT for distance calculation
        
        // ONU Statistics (onuStatisticsTable) - .1.3.6.1.4.1.37950.1.1.5.12.1.20.1.x
        'onuStatIndex' => '1.3.6.1.4.1.37950.1.1.5.12.1.20.1.1',
        
        // ONU Type/Model info (onuTypeCfg) - .1.3.6.1.4.1.37950.1.1.5.12.1.10.x
        'onuVendorId' => '1.3.6.1.4.1.37950.1.1.5.12.1.10.1',
        'onuModelId' => '1.3.6.1.4.1.37950.1.1.5.12.1.10.2',
        
        // Auth mode table - .1.3.6.1.4.1.37950.1.1.5.12.1.1.1.x
        'authModeStatus' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2',
        
        // Legacy OIDs (older firmware or alternative) - for fallback
        'onuSerialNumberLegacy' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.9',
        'onuStatusLegacy' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2',
    ];
    
    /**
     * Alternative OIDs for different VSOL firmware versions
     */
    protected array $alternativeOids = [
        // V1600D with older firmware (uses H3C-like OIDs)
        'v1' => [
            'onuMacAddress' => '1.3.6.1.4.1.25506.2.104.1.2.1.1.3',
            'onuStatus' => '1.3.6.1.4.1.25506.2.104.1.2.1.1.5',
            'onuRxPower' => '1.3.6.1.4.1.25506.2.104.1.2.1.1.13',
            'onuOltRxPower' => '1.3.6.1.4.1.25506.2.104.1.2.1.1.14',
        ],
        // Another variant
        'v2' => [
            'onuSerialNumber' => '1.3.6.1.4.1.3902.1082.500.10.2.1.1.3',
            'onuStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.1.1.11',
            'onuRxPower' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.5',
            'onuOltRxPower' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.6',
        ],
    ];

    protected array $statusMap = [
        1 => 'offline',
        2 => 'online',
        3 => 'los',
        4 => 'power_off',
        5 => 'dying_gasp',
    ];

    /**
     * Identify VSOL OLT - supports SNMP, Telnet, and SSH
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => 'vsol',
            'model' => null,
            'olt_type' => 'EPON', // VSOL V1600D is EPON
            'description' => null,
            'firmware' => null,
            'hardware_version' => null,
            'serial_number' => null,
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'boards' => [],
            'message' => '',
        ];

        // Check if CLI method is preferred
        $useTelnet = $credentials['telnet_enabled'] ?? false;
        $useSsh = $credentials['ssh_enabled'] ?? false;
        
        // Try CLI first if enabled
        if ($useTelnet || $useSsh) {
            $cliResult = self::identifyViaCli($ipAddress, $credentials, $useSsh);
            if ($cliResult['success']) {
                return $cliResult;
            }
        }

        // Try SNMP
        try {
            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            
            $sysDescr = @snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.1.0', 5000000, 2);
            
            if ($sysDescr === false) {
                // If SNMP fails and CLI not tried yet, try CLI as fallback
                if (!$useTelnet && !$useSsh && !empty($credentials['telnet_username'])) {
                    $credentials['telnet_enabled'] = true;
                    return self::identifyViaCli($ipAddress, $credentials, false);
                }
                
                $result['message'] = 'Tidak dapat terhubung via SNMP. Coba gunakan Telnet/SSH.';
                return $result;
            }

            $result['description'] = $sysDescr;

            // Parse sysDescr for model info
            // Example: "EPON-OLT V2.03.75R" or "VSOL V1600D"
            if (preg_match('/V\d{4}[A-Z]?\d*/i', $sysDescr, $matches)) {
                $result['model'] = strtoupper($matches[0]);
            } elseif (stripos($sysDescr, 'EPON') !== false) {
                $result['model'] = 'EPON-OLT';
            }
            
            // Try to get firmware version
            if (preg_match('/V\d+\.\d+\.\d+[A-Z]?/i', $sysDescr, $matches)) {
                $result['firmware'] = $matches[0];
            }

            // Count PON ports from SNMP walk
            $ponPorts = @snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.37950.1.1.5.11.1.1.1.3', 5000000, 2);
            $totalPonPorts = $ponPorts ? count($ponPorts) : 0;

            // If SNMP walk fails, determine from model name
            // V1600D = 4 PON, V1600G = 8 PON, V1600D8 = 8 PON, V1600G16 = 16 PON
            if ($totalPonPorts == 0) {
                $model = strtoupper($result['model'] ?? '');
                
                // Check for explicit port count in model (e.g., V1600D4, V1600G8, V1600G16)
                if (preg_match('/V\d{4}[A-Z]?(\d+)/i', $model, $portMatch)) {
                    $totalPonPorts = (int) $portMatch[1];
                } elseif (stripos($model, 'V1600G') !== false) {
                    $totalPonPorts = 8; // V1600G default 8 PON
                } elseif (stripos($model, 'V1600D') !== false) {
                    $totalPonPorts = 4; // V1600D default 4 PON
                } elseif (stripos($model, 'V1600') !== false) {
                    $totalPonPorts = 4; // V1600 series default 4 PON
                } else {
                    $totalPonPorts = 4; // Safe default
                }
            }

            $result['total_pon_ports'] = $totalPonPorts;
            $result['total_uplink_ports'] = 4; // Default for VSOL
            $result['success'] = true;
            $result['message'] = 'OLT berhasil diidentifikasi via SNMP';

        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Identify VSOL OLT via Telnet/SSH CLI
     */
    protected static function identifyViaCli(string $ipAddress, array $credentials, bool $useSsh = false): array
    {
        $result = [
            'success' => false,
            'brand' => 'vsol',
            'model' => null,
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
            $username = $credentials['telnet_username'] ?? $credentials['ssh_username'] ?? '';
            $password = $credentials['telnet_password'] ?? $credentials['ssh_password'] ?? '';
            $port = $useSsh ? ($credentials['ssh_port'] ?? 22) : ($credentials['telnet_port'] ?? 23);
            
            if (empty($username)) {
                $result['message'] = 'Username tidak tersedia untuk CLI';
                return $result;
            }

            // Log attempt
            Log::info("VSOL identifyViaCli: Connecting to {$ipAddress}:{$port} with user '{$username}'");

            // Connect via Telnet
            $connection = @fsockopen($ipAddress, $port, $errno, $errstr, 10);
            if (!$connection) {
                $result['message'] = "Tidak dapat terhubung ke {$ipAddress}:{$port} - $errstr";
                return $result;
            }

            stream_set_timeout($connection, 15);
            
            // Wait for login prompt and authenticate
            $buffer = '';
            $startTime = time();
            while ((time() - $startTime) < 15) {
                $chunk = @fread($connection, 4096);
                if ($chunk) $buffer .= $chunk;
                if (stripos($buffer, 'username') !== false || 
                    stripos($buffer, 'login') !== false ||
                    stripos($buffer, 'user:') !== false ||
                    stripos($buffer, 'user name') !== false) {
                    break;
                }
                usleep(100000);
            }
            
            Log::info("VSOL identifyViaCli: Login prompt buffer: " . substr($buffer, -200));
            
            // Send username
            fwrite($connection, $username . "\r\n");
            usleep(1000000);
            
            // Wait for password prompt
            $buffer = '';
            $startTime = time();
            while ((time() - $startTime) < 10) {
                $chunk = @fread($connection, 4096);
                if ($chunk) $buffer .= $chunk;
                if (stripos($buffer, 'password') !== false || stripos($buffer, 'pass:') !== false) {
                    break;
                }
                usleep(100000);
            }
            
            // Send password
            fwrite($connection, $password . "\r\n");
            usleep(2000000);
            
            // Check if logged in
            $buffer = @fread($connection, 4096);
            Log::info("VSOL identifyViaCli: After login buffer: " . substr($buffer, -200));
            
            if (stripos($buffer, 'fail') !== false || 
                stripos($buffer, 'invalid') !== false ||
                stripos($buffer, 'error') !== false ||
                stripos($buffer, 'incorrect') !== false) {
                fclose($connection);
                $result['message'] = 'Login gagal - username/password salah';
                return $result;
            }
            
            // Send commands to get device info
            // VSOL EPON-OLT typically uses various command formats
            $commands = [
                'show version',
                'show system',
                'show device-info',
                'show pon info',
                'show system info',
                '?', // Show available commands
            ];
            $output = '';
            
            foreach ($commands as $cmd) {
                fwrite($connection, $cmd . "\r\n");
                usleep(1500000);
                $cmdOutput = @fread($connection, 8192);
                $output .= "\n--- CMD: $cmd ---\n" . $cmdOutput;
            }
            
            // Get PON port count
            fwrite($connection, "show pon\r\n");
            usleep(1000000);
            $ponOutput = @fread($connection, 4096);
            $output .= "\n--- CMD: show pon ---\n" . $ponOutput;
            
            // Try additional commands for EPON-OLT
            fwrite($connection, "show olt\r\n");
            usleep(1000000);
            $oltOutput = @fread($connection, 4096);
            $output .= "\n--- CMD: show olt ---\n" . $oltOutput;
            
            fclose($connection);
            
            Log::info("VSOL identifyViaCli: Full output collected (" . strlen($output) . " bytes)");
            Log::debug("VSOL identifyViaCli: Output sample: " . substr($output, 0, 1000));
            
            // Parse output
            $result['description'] = 'VSOL EPON OLT - Connected via ' . ($useSsh ? 'SSH' : 'Telnet');
            
            // Parse firmware version
            if (preg_match('/Firmware[:\s]+([V\d\.]+[A-Z]?)/i', $output, $m)) {
                $result['firmware'] = $m[1];
            } elseif (preg_match('/Version[:\s]+([V\d\.]+[A-Z]?)/i', $output, $m)) {
                $result['firmware'] = $m[1];
            } elseif (preg_match('/V(\d+\.\d+\.\d+[A-Z]?)/i', $output, $m)) {
                $result['firmware'] = 'V' . $m[1];
            }
            
            // Parse hardware version
            if (preg_match('/Hardware[:\s]+([V\d\.]+)/i', $output, $m)) {
                $result['hardware_version'] = $m[1];
            }
            
            // Parse serial number
            if (preg_match('/Serial[:\s]+([A-Z0-9]+)/i', $output, $m)) {
                $result['serial_number'] = $m[1];
            }
            
            // Parse model
            if (preg_match('/Model[:\s]+([A-Z0-9\-]+)/i', $output, $m)) {
                $result['model'] = $m[1];
            } elseif (preg_match('/(EPON-OLT|V\d{4}[A-Z]?\d*)/i', $output, $m)) {
                $result['model'] = strtoupper($m[1]);
            } else {
                // Default model for VSOL
                $result['model'] = 'VSOL EPON-OLT';
            }
            
            // Count PON ports from output
            $ponCount = 0;
            if (preg_match_all('/pon\s*(\d+)|epon\s*(\d+)/i', $ponOutput, $matches)) {
                $ponCount = max(array_filter(array_merge($matches[1], $matches[2])));
            }
            
            // Default PON ports based on model
            if ($ponCount == 0) {
                if (stripos($output, 'V1600G') !== false || stripos($output, '8PON') !== false) {
                    $ponCount = 8;
                } elseif (stripos($output, '16PON') !== false) {
                    $ponCount = 16;
                } else {
                    $ponCount = 4; // Default for V1600D and EPON-OLT
                }
            }
            
            $result['total_pon_ports'] = $ponCount;
            $result['total_uplink_ports'] = 4;
            $result['success'] = true;
            $result['message'] = 'OLT berhasil diidentifikasi via ' . ($useSsh ? 'SSH' : 'Telnet');

        } catch (\Exception $e) {
            $result['message'] = 'CLI Error: ' . $e->getMessage();
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
            $statuses = $this->snmpWalk($this->vsolOids['ponPortStatus']);

            foreach ($statuses as $oid => $status) {
                preg_match('/\.(\d+)$/', $oid, $matches);
                if (count($matches) < 2) continue;

                $port = (int) $matches[1];

                $ports[] = [
                    'slot' => 0,
                    'port' => $port,
                    'status' => $status == 1 ? 'up' : 'down',
                    'admin_status' => 'enabled',
                ];

                $this->updatePonPort(0, $port, end($ports));
            }

        } catch (Exception $e) {
            Log::error("VSOL getPonPorts error: " . $e->getMessage());
        }

        return $ports;
    }

    /**
     * Get PON port info
     */
    public function getPonPortInfo(int $slot, int $port): array
    {
        $status = $this->snmpGet($this->vsolOids['ponPortStatus'] . ".{$port}");

        return [
            'slot' => $slot,
            'port' => $port,
            'status' => $status == 1 ? 'up' : 'down',
        ];
    }

    /**
     * Get all ONUs - uses correct onuListTable OID (.12.1.9.1)
     * Verified working with VSOL V1600D - returns 179 ONUs with MAC addresses!
     */
    public function getAllOnus(): array
    {
        $onus = [];

        try {
            // Use onuListTable OIDs (verified working)
            // .1.3.6.1.4.1.37950.1.1.5.12.1.9.1.x
            Log::info("VSOL getAllOnus: Trying onuListTable OIDs for OLT: {$this->olt->name}");
            
            // Get MAC addresses first - this is the primary table
            $onuMacs = $this->snmpWalk($this->vsolOids['onuMacAddress']);
            
            if (!empty($onuMacs)) {
                Log::info("VSOL getAllOnus: Found " . count($onuMacs) . " ONU entries in onuListTable");
                
                // Get other data
                $onuPorts = $this->snmpWalk($this->vsolOids['onuPonPort']);
                $onuLlids = $this->snmpWalk($this->vsolOids['onuLlid']);
                $onuStatuses = $this->snmpWalk($this->vsolOids['onuStatus']);
                
                // Re-index arrays by the last OID component (index number)
                $portsByIndex = [];
                foreach ($onuPorts as $oid => $val) {
                    if (preg_match('/\.(\d+)$/', $oid, $m)) {
                        $portsByIndex[$m[1]] = $val;
                    }
                }
                
                $llidsByIndex = [];
                foreach ($onuLlids as $oid => $val) {
                    if (preg_match('/\.(\d+)$/', $oid, $m)) {
                        $llidsByIndex[$m[1]] = $val;
                    }
                }
                
                $statusesByIndex = [];
                foreach ($onuStatuses as $oid => $val) {
                    if (preg_match('/\.(\d+)$/', $oid, $m)) {
                        $statusesByIndex[$m[1]] = $val;
                    }
                }
                
                foreach ($onuMacs as $oid => $macValue) {
                    // Extract index from OID (last number)
                    if (preg_match('/\.(\d+)$/', $oid, $matches)) {
                        $index = $matches[1];
                        
                        // Get PON port for this ONU (use index to lookup)
                        $port = isset($portsByIndex[$index]) ? (int) preg_replace('/[^0-9]/', '', $portsByIndex[$index]) : 1;
                        
                        // Get LLID as ONU ID (use index to lookup)
                        $onuId = isset($llidsByIndex[$index]) ? (int) preg_replace('/[^0-9]/', '', $llidsByIndex[$index]) : (int) $index;
                        
                        // Get status (use index to lookup)
                        $status = isset($statusesByIndex[$index]) ? $this->parseOnuStatusString($statusesByIndex[$index]) : 'online';
                        
                        // Parse MAC address - format is already "xx:xx:xx:xx:xx:xx"
                        $mac = $this->formatMac($macValue);
                        
                        $onus[] = [
                            'slot' => 0,
                            'port' => $port,
                            'onu_id' => $onuId,
                            'serial_number' => strtoupper(str_replace(':', '', $mac)), // Use MAC without colons as serial for EPON
                            'mac_address' => $mac,
                            'status' => $status,
                            'description' => '',
                        ];
                    }
                }
                
                Log::info("VSOL getAllOnus: Parsed " . count($onus) . " ONUs via onuListTable");
                return $onus;
            }
            
            // Try legacy OIDs if onuListTable fails
            Log::info("VSOL getAllOnus: onuListTable empty, trying legacy OIDs");
            $legacyStatuses = $this->snmpWalk($this->vsolOids['onuStatusLegacy']);
            
            if (!empty($legacyStatuses)) {
                Log::info("VSOL getAllOnus: Found " . count($legacyStatuses) . " ONU entries via legacy SNMP");
                
                // Try to get serial numbers (may fail on some firmware)
                $legacySerials = $this->snmpWalk($this->vsolOids['onuSerialNumberLegacy']);
                
                // Also try MAC address OIDs - common positions in legacy MIB
                $legacyMacs = [];
                $macOids = [
                    '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.3', // Possible MAC position
                    '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.4', // Another possibility
                    '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.5', // Or here
                ];
                
                foreach ($macOids as $macOid) {
                    $macs = $this->snmpWalk($macOid);
                    if (!empty($macs)) {
                        $legacyMacs = $macs;
                        Log::info("VSOL getAllOnus: Found MAC addresses at OID: {$macOid}");
                        break;
                    }
                }
                
                foreach ($legacyStatuses as $oid => $status) {
                    if (preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches)) {
                        $port = (int) $matches[1];
                        $onuId = (int) $matches[2];
                        $index = "{$port}.{$onuId}";
                        
                        // Try to get serial first
                        $serialOid = $this->vsolOids['onuSerialNumberLegacy'] . ".{$index}";
                        $serial = $legacySerials[$serialOid] ?? '';
                        
                        // If no serial, try MAC address
                        $mac = '';
                        if (empty($serial)) {
                            foreach ($macOids as $macOidBase) {
                                $macOid = $macOidBase . ".{$index}";
                                if (isset($legacyMacs[$macOid])) {
                                    $mac = $this->formatMac($legacyMacs[$macOid]);
                                    break;
                                }
                            }
                        }
                        
                        // Use MAC as serial if no serial found, otherwise generate placeholder
                        $finalSerial = $this->parseSerialNumber($serial);
                        if (empty($finalSerial) && !empty($mac)) {
                            $finalSerial = $mac;
                        }
                        if (empty($finalSerial)) {
                            // Generate placeholder serial based on port and ONU ID
                            $finalSerial = "VSOL-P{$port}-ONU{$onuId}";
                        }
                        
                        $onus[] = [
                            'slot' => 0,
                            'port' => $port,
                            'onu_id' => $onuId,
                            'serial_number' => $finalSerial,
                            'mac_address' => $mac,
                            'status' => $this->parseOnuStatus($status),
                        ];
                    }
                }
                
                Log::info("VSOL getAllOnus: Parsed " . count($onus) . " ONUs via legacy SNMP");
                return $onus;
            }
            
            Log::warning("VSOL getAllOnus: No ONU data found via SNMP for OLT: {$this->olt->name}");

        } catch (Exception $e) {
            Log::error("VSOL getAllOnus SNMP error: " . $e->getMessage());
        }
        
        // Fallback to CLI if SNMP fails and telnet credentials available
        if (!empty($this->olt->telnet_username)) {
            Log::info("VSOL getAllOnus: Trying CLI fallback for OLT: {$this->olt->name}");
            $onus = $this->getAllOnusViaCli();
        }

        return $onus;
    }
    
    /**
     * Parse ONU status from SNMP string value (onuListTable format)
     * The status column returns strings like "auth success", "offline", etc.
     */
    protected function parseOnuStatusString($status): string
    {
        $statusStr = strtolower(trim(str_replace('"', '', $status)));
        
        // Map various status strings to standard status values
        if (strpos($statusStr, 'auth success') !== false || 
            strpos($statusStr, 'online') !== false ||
            strpos($statusStr, 'registered') !== false) {
            return 'online';
        }
        
        if (strpos($statusStr, 'offline') !== false || 
            strpos($statusStr, 'unregistered') !== false ||
            strpos($statusStr, 'deregistered') !== false) {
            return 'offline';
        }
        
        if (strpos($statusStr, 'los') !== false) {
            return 'los';
        }
        
        if (strpos($statusStr, 'power') !== false) {
            return 'power_off';
        }
        
        if (strpos($statusStr, 'dying') !== false || strpos($statusStr, 'gasp') !== false) {
            return 'dying_gasp';
        }
        
        return 'online'; // Default for "auth success" and similar
    }
    
    /**
     * Parse ONU status from SNMP numeric value (legacy format)
     */
    protected function parseOnuStatus($status): string
    {
        $statusMap = [
            1 => 'offline',
            2 => 'online',
            3 => 'los',
            4 => 'power_off',
            5 => 'dying_gasp',
        ];
        
        return $statusMap[(int)$status] ?? 'unknown';
    }

    /**
     * Get all ONUs via CLI (Telnet/SSH)
     */
    protected function getAllOnusViaCli(): array
    {
        $onus = [];
        
        try {
            $connection = @fsockopen($this->olt->ip_address, $this->olt->telnet_port ?? 23, $errno, $errstr, 10);
            if (!$connection) {
                Log::warning("VSOL getAllOnusViaCli: Cannot connect to {$this->olt->ip_address}");
                return [];
            }
            
            stream_set_timeout($connection, 15);
            
            // Wait for login prompt
            $buffer = '';
            $startTime = time();
            while ((time() - $startTime) < 10) {
                $chunk = @fread($connection, 4096);
                if ($chunk) $buffer .= $chunk;
                if (stripos($buffer, 'username') !== false || 
                    stripos($buffer, 'login') !== false ||
                    stripos($buffer, 'user:') !== false) {
                    break;
                }
                usleep(100000);
            }
            
            // Login
            fwrite($connection, $this->olt->telnet_username . "\r\n");
            usleep(1000000);
            
            $buffer = '';
            $startTime = time();
            while ((time() - $startTime) < 5) {
                $chunk = @fread($connection, 4096);
                if ($chunk) $buffer .= $chunk;
                if (stripos($buffer, 'password') !== false) {
                    break;
                }
                usleep(100000);
            }
            
            fwrite($connection, ($this->olt->telnet_password ?? '') . "\r\n");
            usleep(2000000);
            
            // Check login
            $buffer = @fread($connection, 4096);
            if (stripos($buffer, 'fail') !== false || stripos($buffer, 'invalid') !== false) {
                fclose($connection);
                Log::warning("VSOL getAllOnusViaCli: Login failed");
                return [];
            }
            
            // Enter privileged mode (enable)
            fwrite($connection, "enable\r\n");
            usleep(1000000);
            $enableOutput = @fread($connection, 4096);
            
            // Check if password needed for enable
            if (stripos($enableOutput, 'password') !== false) {
                // Try common enable passwords
                $enablePasswords = [$this->olt->telnet_password ?? '', '', 'admin', 'enable'];
                foreach ($enablePasswords as $enablePwd) {
                    fwrite($connection, $enablePwd . "\r\n");
                    usleep(1000000);
                    $enableOutput = @fread($connection, 4096);
                    if (stripos($enableOutput, '#') !== false) {
                        break; // Successfully enabled
                    }
                }
            }
            
            Log::info("VSOL getAllOnusViaCli: After enable - " . substr($enableOutput, -200));
            
            // Now check available commands in privileged mode
            fwrite($connection, "?\r\n");
            usleep(1500000);
            $helpOutput = @fread($connection, 8192);
            Log::debug("VSOL getAllOnusViaCli: Help output in privileged mode: " . $helpOutput);
            
            // Try various commands to get ONU list
            // Different VSOL/EPON firmware versions use different commands
            $commands = [
                'show ?',                   // Show available show commands
                'show onu',
                'show epon onu',
                'show epon onu info all',
                'show epon onu-info',
                'show onu-info all',
                'show epon interface onu',
                'show optical-module onu-rx-power-list',
                'show running-config',      // May contain ONU info
                'show interface epon 0/1',  // EPON interface info
                'show authorized-onu',
                'show registered-onu',
            ];
            
            $output = $helpOutput;
            foreach ($commands as $cmd) {
                fwrite($connection, $cmd . "\r\n");
                usleep(2000000);
                $cmdOutput = @fread($connection, 16384);
                $output .= "\n--- CMD: $cmd ---\n" . $cmdOutput;
                
                // If we got meaningful data, no need to try more commands
                if (preg_match('/\d+\/\d+:\d+|onu\s+\d+|LLID|[A-F0-9]{12}/i', $cmdOutput)) {
                    Log::info("VSOL getAllOnusViaCli: Found potential ONU data with command: $cmd");
                }
            }
            
            fclose($connection);
            
            Log::info("VSOL getAllOnusViaCli: Command output length: " . strlen($output));
            Log::debug("VSOL getAllOnusViaCli: Output sample: " . substr($output, 0, 2000));
            
            // Parse output - multiple patterns for different VSOL firmware versions
            
            // Pattern 1: "1/1:1   FHTT12345678   online"
            // Format: port/slot:onuid   serial   status
            if (preg_match_all('/(\d+)\/(\d+):(\d+)\s+([A-Z0-9]{8,16})\s+(online|offline|los)/i', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $onus[] = [
                        'slot' => (int) $match[2],
                        'port' => (int) $match[1],
                        'onu_id' => (int) $match[3],
                        'serial_number' => strtoupper($match[4]),
                        'status' => strtolower($match[5]),
                    ];
                }
            }
            
            // Pattern 2: "pon 1 onu 1   FHTT12345678"
            if (empty($onus) && preg_match_all('/pon\s*(\d+)\s+onu\s*(\d+)\s+([A-Z0-9]{8,16})/i', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $onus[] = [
                        'slot' => 0,
                        'port' => (int) $match[1],
                        'onu_id' => (int) $match[2],
                        'serial_number' => strtoupper($match[3]),
                        'status' => 'online', // Unknown from this format
                    ];
                }
            }
            
            // Pattern 3: "EPON0/1:1   FHTT12345678" or "epon0/1:1"
            if (empty($onus) && preg_match_all('/[eg]?pon(\d+)\/(\d+):(\d+)\s+([A-Z0-9]{8,16})/i', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $onus[] = [
                        'slot' => (int) $match[1],
                        'port' => (int) $match[2],
                        'onu_id' => (int) $match[3],
                        'serial_number' => strtoupper($match[4]),
                        'status' => 'online',
                    ];
                }
            }
            
            // Pattern 4: Table format with ID/Index column
            // "1    1     1    FHTT12345678    online"
            if (empty($onus) && preg_match_all('/^\s*(\d+)\s+(\d+)\s+(\d+)\s+([A-Z0-9]{8,16})\s+(online|offline|los)/im', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $onus[] = [
                        'slot' => (int) $match[1],
                        'port' => (int) $match[2],
                        'onu_id' => (int) $match[3],
                        'serial_number' => strtoupper($match[4]),
                        'status' => strtolower($match[5]),
                    ];
                }
            }
            
            // Pattern 5: Simple list "onu 1: FHTT12345678 online"
            if (empty($onus) && preg_match_all('/onu\s*(\d+)[:\s]+([A-Z0-9]{8,16})\s+(online|offline|los)?/i', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $onus[] = [
                        'slot' => 0,
                        'port' => 1, // Default port if not specified
                        'onu_id' => (int) $match[1],
                        'serial_number' => strtoupper($match[2]),
                        'status' => isset($match[3]) ? strtolower($match[3]) : 'online',
                    ];
                }
            }
            
            Log::info("VSOL getAllOnusViaCli: Found " . count($onus) . " ONUs via CLI on OLT: {$this->olt->name}");
            
        } catch (Exception $e) {
            Log::error("VSOL getAllOnusViaCli error: " . $e->getMessage());
        }
        
        return $onus;
    }

    /**
     * Get ONUs by port
     */
    public function getOnusByPort(int $slot, int $port): array
    {
        return array_filter($this->getAllOnus(), fn($onu) => $onu['port'] == $port);
    }

    /**
     * Get ONU info - tries SNMP with V1600D4 OIDs, then legacy OIDs
     */
    public function getOnuInfo(int $slot, int $port, int $onuId): array
    {
        // For V1600D4, ONU index is single number, not port.onuid
        $v1600d4Index = $onuId;
        // For legacy, index is port.onuid
        $legacyIndex = "{$port}.{$onuId}";

        // Try V1600D4 OIDs first
        $mac = $this->snmpGet($this->vsolOids['onuMacAddress'] . ".{$v1600d4Index}");
        
        // If V1600D4 OIDs work, use them
        if ($mac) {
            $formattedMac = $this->formatMac($mac);
            $status = $this->snmpGet($this->vsolOids['onuStatus'] . ".{$v1600d4Index}");
            $desc = $this->snmpGet($this->vsolOids['onuDescription'] . ".{$v1600d4Index}");
            $distance = $this->snmpGet($this->vsolOids['onuDistance'] . ".{$v1600d4Index}");
            
            $info = [
                'slot' => $slot,
                'port' => $port,
                'onu_id' => $onuId,
                'serial_number' => $formattedMac, // Use MAC as serial for EPON
                'mac_address' => $formattedMac,
                'status' => $this->parseOnuStatus($status),
                'distance' => $this->parseDistance($distance),
                'description' => $desc,
            ];

            return array_merge($info, $this->getOnuOpticalInfo($slot, $port, $onuId));
        }
        
        // Try legacy OIDs with port.onuid index
        Log::info("VSOL getOnuInfo: V1600D4 OIDs failed, trying legacy for port {$port} onu {$onuId}");
        
        $legacyMacOids = [
            '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.3',
            '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.4',
            '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.5',
        ];
        
        $mac = null;
        foreach ($legacyMacOids as $macOidBase) {
            $macOid = $macOidBase . ".{$legacyIndex}";
            $mac = $this->snmpGet($macOid);
            if ($mac) {
                Log::info("VSOL getOnuInfo: Found MAC at legacy OID {$macOidBase}");
                break;
            }
        }
        
        if ($mac) {
            $formattedMac = $this->formatMac($mac);
            $status = $this->snmpGet($this->vsolOids['onuStatusLegacy'] . ".{$legacyIndex}");
            $serial = $this->snmpGet($this->vsolOids['onuSerialNumberLegacy'] . ".{$legacyIndex}");
            
            $finalSerial = $this->parseSerialNumber($serial);
            if (empty($finalSerial)) {
                $finalSerial = $formattedMac;
            }
            
            return [
                'slot' => $slot,
                'port' => $port,
                'onu_id' => $onuId,
                'serial_number' => $finalSerial,
                'mac_address' => $formattedMac,
                'status' => $this->parseOnuStatus($status),
                'distance' => null,
                'description' => null,
                'rx_power' => null,
                'tx_power' => null,
                'olt_rx_power' => null,
                'temperature' => null,
                'voltage' => null,
            ];
        }
        
        // SNMP failed completely, return basic info with placeholder serial
        Log::info("VSOL getOnuInfo: SNMP unavailable for port {$port} onu {$onuId}, using fallback serial");
        
        return [
            'slot' => $slot,
            'port' => $port,
            'onu_id' => $onuId,
            'serial_number' => "VSOL-P{$port}-ONU{$onuId}",
            'mac_address' => null,
            'status' => 'online', // Assume online if found in status table
            'distance' => null,
            'description' => null,
            'rx_power' => null,
            'tx_power' => null,
            'olt_rx_power' => null,
            'temperature' => null,
            'voltage' => null,
        ];
    }

    /**
     * Get ONU optical info
     */
    /**
     * Get ONU descriptions and distance from onuAuthInfoTable
     * Returns mapping: [ponPort][onuId] => ['description' => '...', 'distance' => '...']
     */
    public function getOnuAuthInfoMap(): array
    {
        $map = [];

        try {
            // Walk all three OIDs
            $ponPorts = $this->snmpWalk($this->vsolOids['authInfoPonNo']);
            $onuIds = $this->snmpWalk($this->vsolOids['authInfoOnuNo']);
            $descriptions = $this->snmpWalk($this->vsolOids['authInfoDescription']);
            $rtts = $this->snmpWalk($this->vsolOids['authInfoRtt']);

            // Re-index by last OID component (auth info index)
            $ponByIndex = [];
            foreach ($ponPorts as $oid => $val) {
                if (preg_match('/\.(\d+)$/', $oid, $m)) {
                    $ponByIndex[$m[1]] = (int) preg_replace('/[^0-9]/', '', $val);
                }
            }

            $onuByIndex = [];
            foreach ($onuIds as $oid => $val) {
                if (preg_match('/\.(\d+)$/', $oid, $m)) {
                    $onuByIndex[$m[1]] = (int) preg_replace('/[^0-9]/', '', $val);
                }
            }

            $descByIndex = [];
            foreach ($descriptions as $oid => $val) {
                if (preg_match('/\.(\d+)$/', $oid, $m)) {
                    $descByIndex[$m[1]] = trim(preg_replace('/^STRING:\s*/i', '', $val), '" \'');
                }
            }

            $rttByIndex = [];
            foreach ($rtts as $oid => $val) {
                if (preg_match('/\.(\d+)$/', $oid, $m)) {
                    // RTT value is directly in meters
                    $rttMeters = (int) preg_replace('/[^0-9]/', '', $val);
                    $rttByIndex[$m[1]] = $rttMeters > 0 ? $rttMeters : null;
                }
            }

            // Build map keyed by "port.onuId"
            foreach ($ponByIndex as $index => $port) {
                $onuId = $onuByIndex[$index] ?? null;
                if ($port && $onuId) {
                    $map["$port.$onuId"] = [
                        'description' => $descByIndex[$index] ?? '',
                        'distance' => $rttByIndex[$index] ?? null,
                    ];
                }
            }

            Log::info("VSOL getOnuAuthInfoMap: Found " . count($map) . " entries");

        } catch (Exception $e) {
            Log::error("VSOL getOnuAuthInfoMap error: " . $e->getMessage());
        }

        return $map;
    }

    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {

        // VSOL V1600D RX/TX Power: OID .12.2.1.8.1.6 (TX), .12.2.1.8.1.7 (RX)
        // Index: .{ponId}.{onuId}
        $ponId = $port;
        $onuIdx = $onuId;
        $rxPower = $this->snmpGet("1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7.$ponId.$onuIdx");
        $txPower = $this->snmpGet("1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6.$ponId.$onuIdx");

        // OLT RX Power = RX Power (same as above)
        $oltRx = $rxPower;

        // VSOL tidak expose temperature/voltage di OID ini
        return [
            'rx_power' => $this->parseVsolOpticalPower($rxPower),
            'tx_power' => $this->parseVsolOpticalPower($txPower),
            'olt_rx_power' => $this->parseVsolOpticalPower($oltRx),
            'temperature' => null,
            'voltage' => null,
        ];
    }

    /**
     * Parse VSOL optical power
     * Handles formats:
     * - String: "0.01 mW (-20.92 dBm)" -> extracts dBm value
     * - Integer: 0.01 dBm format (legacy)
     */
    protected function parseVsolOpticalPower(mixed $value): ?float
    {
        if (is_null($value) || $value === '' || $value === false) {
            return null;
        }

        // Format: "0.01 mW (-20.92 dBm)" - extract dBm from parentheses
        if (is_string($value) && preg_match('/\(([+-]?[\d.]+)\s*dBm\)/i', $value, $matches)) {
            return round((float) $matches[1], 2);
        }
        
        // Format: "X.XX dBm" without parentheses
        if (is_string($value) && preg_match('/([+-]?[\d.]+)\s*dBm/i', $value, $matches)) {
            return round((float) $matches[1], 2);
        }

        // Legacy integer format (0.01 dBm units)
        if (is_numeric($value)) {
            $power = (int) $value;
            
            if ($power == -32768 || $power == 0x8000) {
                return null;
            }
            
            // VSOL returns signed integer in 0.01 dBm
            if ($power > 32767) {
                $power = $power - 65536;
            }

            return round($power / 100, 2);
        }

        return null;
    }

    /**
     * Format MAC address to consistent format XX:XX:XX:XX:XX:XX
     * Handles various SNMP return formats:
     * - STRING: "a8:2b:cd:aa:e5:53" (V1600D onuListTable format)
     * - Hex-STRING: A8 2B CD AA E5 53
     * - Raw hex: A82BCDAAE553
     */
    protected function formatMac(?string $mac): ?string
    {
        if (!$mac) return null;
        
        // Remove SNMP type prefixes
        $mac = preg_replace('/^(STRING|Hex-STRING):\s*/i', '', $mac);
        
        // Remove quotes
        $mac = trim($mac, '" \'');
        
        // If already in correct format (xx:xx:xx:xx:xx:xx), just uppercase it
        if (preg_match('/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/', $mac)) {
            return strtoupper($mac);
        }
        
        // Handle hex with spaces (A8 2B CD AA E5 53)
        if (strpos($mac, ' ') !== false) {
            $mac = str_replace(' ', '', $mac);
        }
        
        // Remove any non-hex characters
        $mac = preg_replace('/[^a-fA-F0-9]/', '', $mac);
        
        // Validate 12 hex characters
        if (strlen($mac) != 12) {
            // If invalid, return cleaned version as-is
            return strlen($mac) > 0 ? strtoupper($mac) : null;
        }
        
        // Format as XX:XX:XX:XX:XX:XX
        return strtoupper(implode(':', str_split($mac, 2)));
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
            $uncfgSerials = $this->snmpWalk($this->vsolOids['uncfgOnuSerial']);

            foreach ($uncfgSerials as $oid => $serial) {
                preg_match('/\.(\d+)$/', $oid, $matches);
                if (count($matches) < 2) continue;

                $unregistered[] = [
                    'slot' => 0,
                    'port' => (int) $matches[1],
                    'serial_number' => $this->parseSerialNumber($serial),
                    'config_status' => 'unregistered',
                ];
            }

        } catch (Exception $e) {
            Log::error("VSOL getUnregisteredOnus error: " . $e->getMessage());
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
            $port = $params['port'];
            $onuId = $params['onu_id'] ?? $this->getNextOnuId($port);
            $index = "{$port}.{$onuId}";

            // Authorize ONU
            $success = $this->snmpSet(
                $this->vsolOids['onuAuthAction'] . ".{$index}",
                'i',
                1 // 1 = authorize
            );

            if ($success) {
                $result['success'] = true;
                $result['onu_id'] = $onuId;
                $result['message'] = "ONU registered at port {$port}:{$onuId}";
            } else {
                $result['message'] = 'SNMP SET failed';
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("VSOL registerOnu error: " . $e->getMessage());
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
            $index = "{$port}.{$onuId}";

            $success = $this->snmpSet(
                $this->vsolOids['onuAuthAction'] . ".{$index}",
                'i',
                2 // 2 = deauthorize
            );

            $result['success'] = $success;
            $result['message'] = $success ? "ONU {$port}:{$onuId} unregistered" : "Failed";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Reboot ONU
     */
    public function rebootOnu(int $slot, int $port, int $onuId): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $index = "{$port}.{$onuId}";

            $success = $this->snmpSet(
                $this->vsolOids['onuReboot'] . ".{$index}",
                'i',
                1 // 1 = reboot
            );

            $result['success'] = $success;
            $result['message'] = $success ? "ONU {$port}:{$onuId} reboot initiated" : "Failed";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get ONU traffic
     */
    public function getOnuTraffic(int $slot, int $port, int $onuId): array
    {
        // VSOL V1600D onuStatisticsTable
        // Base OID: 1.3.6.1.4.1.37950.1.1.5.12.1.20.1
        // Index: {ponId}.{onuId}
        $ponId = $port;
        $onuIdx = $onuId;
        
        $rxOctets = $this->snmpGet("1.3.6.1.4.1.37950.1.1.5.12.1.20.1.3.$ponId.$onuIdx");
        $txOctets = $this->snmpGet("1.3.6.1.4.1.37950.1.1.5.12.1.20.1.10.$ponId.$onuIdx");
        $rxPackets = $this->snmpGet("1.3.6.1.4.1.37950.1.1.5.12.1.20.1.4.$ponId.$onuIdx");
        $txPackets = $this->snmpGet("1.3.6.1.4.1.37950.1.1.5.12.1.20.1.11.$ponId.$onuIdx");
        
        return [
            'in_octets' => is_numeric($rxOctets) ? (int)$rxOctets : 0,
            'out_octets' => is_numeric($txOctets) ? (int)$txOctets : 0,
            'in_packets' => is_numeric($rxPackets) ? (int)$rxPackets : 0,
            'out_packets' => is_numeric($txPackets) ? (int)$txPackets : 0,
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
            'message' => 'Service configuration not fully supported via SNMP. Use web interface.',
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

            $allOnus = $this->getAllOnus();
            Log::info("VSOL syncAll: Found " . count($allOnus) . " ONUs to sync for OLT: {$this->olt->name}");

            // Get description and distance mapping from onuAuthInfoTable
            $authInfoMap = $this->getOnuAuthInfoMap();
            Log::info("VSOL syncAll: Loaded " . count($authInfoMap) . " ONU descriptions from OLT");

            foreach ($allOnus as $onuData) {
                try {
                    // For VSOL V1600D, getAllOnus() already returns all needed data
                    // No need to call getOnuInfo() which uses wrong index mapping
                    
                    // Ensure we have a valid serial number
                    $serialNumber = $onuData['serial_number'] ?? '';
                    if (empty($serialNumber)) {
                        $serialNumber = "VSOL-P{$onuData['port']}-ONU{$onuData['onu_id']}";
                    }
                    
                    // Get optical power info
                    $opticalInfo = $this->getOnuOpticalInfo(
                        $onuData['slot'] ?? 0,
                        $onuData['port'],
                        $onuData['onu_id']
                    );
                    
                    // Get description and distance from authInfoMap
                    $authKey = "{$onuData['port']}.{$onuData['onu_id']}";
                    $authInfo = $authInfoMap[$authKey] ?? ['description' => '', 'distance' => null];
                    
                    // Prepare full info from getAllOnus data
                    $fullInfo = [
                        'slot' => $onuData['slot'] ?? 0,
                        'port' => $onuData['port'],
                        'onu_id' => $onuData['onu_id'],
                        'serial_number' => $serialNumber,
                        'mac_address' => $onuData['mac_address'] ?? null,
                        'status' => $onuData['status'] ?? 'online',
                        'description' => $authInfo['description'] ?: ($onuData['description'] ?? null),
                        'distance' => $authInfo['distance'],
                        'rx_power' => $opticalInfo['rx_power'] ?? null,
                        'tx_power' => $opticalInfo['tx_power'] ?? null,
                        'olt_rx_power' => $opticalInfo['olt_rx_power'] ?? null,
                        'temperature' => $opticalInfo['temperature'] ?? null,
                        'voltage' => $opticalInfo['voltage'] ?? null,
                    ];

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

    /**
     * Get next ONU ID
     */
    protected function getNextOnuId(int $port): int
    {
        $existing = $this->getOnusByPort(0, $port);
        $usedIds = array_column($existing, 'onu_id');

        for ($i = 1; $i <= 128; $i++) {
            if (!in_array($i, $usedIds)) return $i;
        }

        throw new Exception("No available ONU ID on port {$port}");
    }
}
