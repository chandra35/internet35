<?php

namespace App\Helpers\Olt;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ZTE C320/C300 OLT Helper
 * 
 * Supports:
 * - SNMP for monitoring
 * - Telnet/SSH for configuration
 * - Full provisioning capabilities
 * 
 * Based on ZTE GPON MIB and CLI commands
 */
class ZteC320Helper extends BaseOltHelper
{
    /**
     * ZTE specific OIDs
     */
    protected array $zteOids = [
        // System
        'sysDescr' => '1.3.6.1.2.1.1.1.0',
        'sysName' => '1.3.6.1.2.1.1.5.0',
        'sysLocation' => '1.3.6.1.2.1.1.6.0',
        'zxAnEponSystemMacAddress' => '1.3.6.1.4.1.3902.1015.1010.1.1.1.0',
        'zxAnSystemProductName' => '1.3.6.1.4.1.3902.1015.2.1.1.1.0',
        'zxAnSystemSoftwareVersion' => '1.3.6.1.4.1.3902.1015.2.1.1.4.0',
        'zxAnSystemHardwareVersion' => '1.3.6.1.4.1.3902.1015.2.1.1.5.0',
        
        // Shelf/Slot/Board info
        'zxAnShelfSlotNum' => '1.3.6.1.4.1.3902.1015.2.1.2.1.2.0',
        'zxAnBoardTable' => '1.3.6.1.4.1.3902.1015.2.1.3.3.1',
        'zxAnBoardType' => '1.3.6.1.4.1.3902.1015.2.1.3.3.1.2',
        'zxAnBoardAdminState' => '1.3.6.1.4.1.3902.1015.2.1.3.3.1.3',
        'zxAnBoardOperState' => '1.3.6.1.4.1.3902.1015.2.1.3.3.1.4',
        'zxAnBoardPonPortNum' => '1.3.6.1.4.1.3902.1015.2.1.3.3.1.7',
        'zxAnBoardUpPortNum' => '1.3.6.1.4.1.3902.1015.2.1.3.3.1.8',
        
        // PON Port
        'zxAnGponOltPonIfTable' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1',
        'zxAnGponOltPonIfAdminStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.2',
        'zxAnGponOltPonIfOperStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.3',
        
        // ONU
        'zxAnGponOnuTable' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1',
        'zxAnGponOnuSerialNumber' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.1', // Index: slot.port.onuid
        'zxAnGponOnuRunStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2',
        'zxAnGponOnuAdminStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.3',
        'zxAnGponOnuName' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.4',
        'zxAnGponOnuType' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.5',
        'zxAnGponOnuVendorId' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.6',
        'zxAnGponOnuDistance' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.7',
        'zxAnGponOnuPhaseState' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.8',
        'zxAnGponOnuSoftwareVer' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.14',
        'zxAnGponOnuHardwareVer' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.15',
        
        // ONU Optical Info
        'zxAnGponOnuOpticalDdmTable' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1',
        'zxAnGponOnuRxPowerLevel' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.3', // OLT Rx from ONU
        'zxAnGponOnuTxPowerLevel' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.4', // ONU Tx
        'zxAnGponOnuOnuRxPowerLevel' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.5', // ONU Rx
        'zxAnGponOnuTemperature' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.6',
        'zxAnGponOnuVoltage' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.7',
        'zxAnGponOnuBiasCurrent' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.8',
        
        // Traffic Statistics
        'zxAnGponOnuPerfInOctets' => '1.3.6.1.4.1.3902.1082.500.10.2.3.9.1.2',
        'zxAnGponOnuPerfOutOctets' => '1.3.6.1.4.1.3902.1082.500.10.2.3.9.1.3',
        'zxAnGponOnuPerfInPackets' => '1.3.6.1.4.1.3902.1082.500.10.2.3.9.1.4',
        'zxAnGponOnuPerfOutPackets' => '1.3.6.1.4.1.3902.1082.500.10.2.3.9.1.5',
        
        // ONU Profile
        'zxAnGponOnuLineProfile' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.9',
        'zxAnGponOnuServiceProfile' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.10',
        
        // PON Port SFP DDM (OLT-side transceiver)
        'zxAnGponOltPonOpticalTxPower' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.10',
        'zxAnGponOltPonOpticalRxPower' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.11',
        'zxAnGponOltPonOpticalTemp' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.12',
        'zxAnGponOltPonOpticalVoltage' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.13',
        'zxAnGponOltPonOpticalBias' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.14',

        // Unconfigured ONUs
        'zxAnGponOltUncfgOnuTable' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1',
        'zxAnGponOltUncfgOnuSerialNo' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.2',
    ];

    /**
     * ONU Run Status mapping
     */
    protected array $runStatusMap = [
        1 => 'online',
        2 => 'offline',
        3 => 'los',
        4 => 'dying_gasp',
        5 => 'power_off',
    ];

    /**
     * ZTE Board Type mapping
     */
    protected static array $boardTypeMap = [
        'GTGO' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'GTGH' => ['type' => 'PON', 'pon_ports' => 16, 'uplink_ports' => 0],
        'ETGO' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'ETGH' => ['type' => 'PON', 'pon_ports' => 16, 'uplink_ports' => 0],
        'HUTQ' => ['type' => 'Uplink', 'pon_ports' => 0, 'uplink_ports' => 4],
        'SCXN' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'SCXL' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'SMXA' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'PRAM' => ['type' => 'Power', 'pon_ports' => 0, 'uplink_ports' => 0],
    ];

    /**
     * Identify OLT - Get board info via SNMP without needing full model
     * Used for initial setup before saving to database
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => 'zte',
            'model' => null,
            'description' => null,
            'firmware' => null,
            'hardware_version' => null,
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'slots' => [],
            'boards' => [],
            'message' => '',
        ];

        try {
            // Check if using Telnet/SSH directly (without SNMP)
            $useTelnet = !empty($credentials['telnet_enabled']);
            $useSsh = !empty($credentials['ssh_enabled']);

            if ($useTelnet || $useSsh) {
                // Identify via CLI directly
                $cliResult = self::identifyViaCli($ipAddress, $credentials);
                
                if ($cliResult['success']) {
                    $result['success'] = true;
                    $result['boards'] = $cliResult['boards'];
                    $result['total_pon_ports'] = $cliResult['total_pon_ports'];
                    $result['total_uplink_ports'] = $cliResult['total_uplink_ports'];
                    $result['model'] = $cliResult['model'] ?? 'ZTE OLT';
                    $result['description'] = $cliResult['description'] ?? 'Connected via ' . ($useTelnet ? 'Telnet' : 'SSH');
                    $result['message'] = 'OLT berhasil diidentifikasi via ' . ($useTelnet ? 'Telnet' : 'SSH');
                    return $result;
                } else {
                    $result['message'] = $cliResult['message'] ?? 'Tidak dapat terhubung via ' . ($useTelnet ? 'Telnet' : 'SSH');
                    return $result;
                }
            }

            // Using SNMP - check if extension available
            if (!function_exists('snmpget')) {
                $result['message'] = 'SNMP extension tidak terinstall di PHP.';
                return $result;
            }

            \snmp_set_quick_print(true);
            \snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            
            // Test basic SNMP connectivity (5 second timeout)
            $snmpTimeout = 5000000; // 5 seconds in microseconds
            $sysDescr = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.1.0', $snmpTimeout, 2);
            
            if ($sysDescr === false) {
                $result['message'] = 'Tidak dapat terhubung via SNMP. Periksa IP, port, dan community string.';
                return $result;
            }

            $result['description'] = $sysDescr;

            // Check if it's really a ZTE device
            if (stripos($sysDescr, 'ZTE') === false && stripos($sysDescr, 'ZXA10') === false) {
                $result['message'] = 'Perangkat bukan ZTE OLT. System Description: ' . $sysDescr;
                return $result;
            }

            // Determine model from sysDescr
            if (preg_match('/ZXA10\s*(\w+)/i', $sysDescr, $matches)) {
                $result['model'] = strtoupper($matches[1]);
            } elseif (preg_match('/C\d{3}/i', $sysDescr, $matches)) {
                $result['model'] = strtoupper($matches[0]);
            }

            // Get system name
            $sysName = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.5.0', $snmpTimeout, 2);
            if ($sysName) {
                $result['sys_name'] = $sysName;
            }

            // Try to get ZTE specific product info
            $productName = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.1.1.0', $snmpTimeout, 2);
            if ($productName) {
                $result['model'] = $productName;
            }

            // Get firmware version
            $firmware = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.1.4.0', $snmpTimeout, 2);
            if ($firmware) {
                $result['firmware'] = $firmware;
            }

            // Get hardware version
            $hwVersion = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.1.5.0', $snmpTimeout, 2);
            if ($hwVersion) {
                $result['hardware_version'] = $hwVersion;
            }

            // Get board/slot information (with timeout)
            $boardTypes = @\snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.3.3.1.2', $snmpTimeout, 2);
            $boardPonPorts = @\snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.3.3.1.7', $snmpTimeout, 2);
            $boardUpPorts = @\snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.3.3.1.8', $snmpTimeout, 2);
            $boardOperState = @\snmpwalkoid($ipAddress, $snmpCommunity, '1.3.6.1.4.1.3902.1015.2.1.3.3.1.4', $snmpTimeout, 2);

            $totalPonPorts = 0;
            $totalUplinkPorts = 0;
            $boards = [];

            if ($boardTypes) {
                foreach ($boardTypes as $oid => $boardType) {
                    // Extract shelf.slot from OID
                    preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                    if (count($matches) < 3) continue;

                    $shelf = (int)$matches[1];
                    $slot = (int)$matches[2];
                    
                    $ponPorts = 0;
                    $upPorts = 0;
                    
                    // Get from SNMP if available
                    $ponOid = str_replace('.2.', '.7.', $oid);
                    $upOid = str_replace('.2.', '.8.', $oid);
                    
                    if (isset($boardPonPorts[$ponOid])) {
                        $ponPorts = (int)$boardPonPorts[$ponOid];
                    } elseif (isset(self::$boardTypeMap[$boardType])) {
                        $ponPorts = self::$boardTypeMap[$boardType]['pon_ports'];
                    }
                    
                    if (isset($boardUpPorts[$upOid])) {
                        $upPorts = (int)$boardUpPorts[$upOid];
                    } elseif (isset(self::$boardTypeMap[$boardType])) {
                        $upPorts = self::$boardTypeMap[$boardType]['uplink_ports'];
                    }

                    $operOid = str_replace('.2.', '.4.', $oid);
                    $operState = isset($boardOperState[$operOid]) ? ((int)$boardOperState[$operOid] == 1 ? 'online' : 'offline') : 'unknown';

                    $boards[] = [
                        'shelf' => $shelf,
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

            // If SNMP board walk failed, try to get from CLI via Telnet/SSH
            if (empty($boards) && !empty($credentials)) {
                $cliResult = self::identifyViaCli($ipAddress, $credentials);
                if ($cliResult['success']) {
                    $boards = $cliResult['boards'];
                    $totalPonPorts = $cliResult['total_pon_ports'];
                    $totalUplinkPorts = $cliResult['total_uplink_ports'];
                }
            }

            // Default fallback for C320 if can't detect
            if ($totalPonPorts == 0 && stripos($result['model'], 'C320') !== false) {
                $totalPonPorts = 16; // Default for C320
                $totalUplinkPorts = 4;
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
     * Identify via CLI (Telnet/SSH)
     */
    protected static function identifyViaCli(string $ipAddress, array $credentials): array
    {
        $result = [
            'success' => false,
            'boards' => [],
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'model' => null,
            'description' => null,
            'message' => '',
        ];

        try {
            $useTelnet = !empty($credentials['telnet_enabled']);
            $useSsh = !empty($credentials['ssh_enabled']);

            $port = $useTelnet ? ($credentials['telnet_port'] ?? 23) : ($credentials['ssh_port'] ?? 22);
            $username = $useTelnet ? ($credentials['telnet_username'] ?? '') : ($credentials['ssh_username'] ?? '');
            $password = $useTelnet ? ($credentials['telnet_password'] ?? '') : ($credentials['ssh_password'] ?? '');

            if (empty($username) || empty($password)) {
                $result['message'] = 'Username dan password harus diisi';
                return $result;
            }

            if ($useSsh) {
                // Use SSH
                $result = self::identifyViaSsh($ipAddress, $port, $username, $password);
                return $result;
            }

            // Use Telnet (with 10 second connection timeout)
            $connectTimeout = 10;
            $streamTimeout = 10;
            
            $fp = @fsockopen($ipAddress, $port, $errno, $errstr, $connectTimeout);
            if (!$fp) {
                $result['message'] = "Tidak dapat terhubung ke Telnet port $port: $errstr ($errno)";
                return $result;
            }

            stream_set_timeout($fp, $streamTimeout);

            // Login sequence
            usleep(500000);
            fread($fp, 4096); // Clear buffer
            fwrite($fp, "$username\r\n");
            usleep(500000);
            fread($fp, 4096);
            fwrite($fp, "$password\r\n");
            usleep(1000000);
            $loginResponse = fread($fp, 4096);

            // Check if login failed
            if (stripos($loginResponse, 'invalid') !== false || stripos($loginResponse, 'fail') !== false || stripos($loginResponse, 'denied') !== false) {
                fclose($fp);
                $result['message'] = 'Login gagal. Periksa username dan password.';
                return $result;
            }

            // Try to get version info
            fwrite($fp, "show version\r\n");
            usleep(2000000);
            $versionOutput = fread($fp, 4096);
            
            // Parse version for model
            if (preg_match('/ZXA10\s*(\w+)/i', $versionOutput, $modelMatch)) {
                $result['model'] = strtoupper($modelMatch[1]);
            } elseif (preg_match('/C\d{3}/i', $versionOutput, $modelMatch)) {
                $result['model'] = strtoupper($modelMatch[0]);
            }
            $result['description'] = trim(preg_replace('/\s+/', ' ', substr($versionOutput, 0, 200)));

            // Send command to show rack/card info
            fwrite($fp, "show card\r\n");
            usleep(2000000);
            
            $output = '';
            $readAttempts = 0;
            while (!feof($fp) && $readAttempts < 10) {
                $line = fread($fp, 4096);
                if ($line === false || empty($line)) {
                    $readAttempts++;
                    usleep(500000);
                    continue;
                }
                $output .= $line;
                if (strpos($line, '#') !== false || strpos($line, '>') !== false) {
                    break;
                }
                $readAttempts++;
            }
            fclose($fp);

            // Parse show card output
            // Example: "1 1 GTGO 8 online" or "Shelf  Slot  CardName  Config  Oper  Software"
            $totalPon = 0;
            $totalUp = 0;
            $boards = [];

            // Try multiple parsing patterns
            $patterns = [
                '/(\d+)\s+(\d+)\s+(\w+)\s+\w+\s+(online|offline|inservice)/i', // ZTE C320/C300
                '/(\d+)\s+(\d+)\s+(\w+)\s+.*?(online|offline)/i', // Generic
                '/Slot\s*(\d+).*?(\w+GTGO|\w+GTGH|\w+ETGO|\w+ETGH|\w+HUTQ|\w+SCXN).*?(online|offline)/i', // Alternative
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $output, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $shelf = isset($match[1]) ? (int)$match[1] : 1;
                        $slot = isset($match[2]) ? (int)$match[2] : (int)$match[1];
                        $boardType = strtoupper($match[3] ?? $match[2]);
                        $status = strtolower($match[4] ?? $match[3] ?? 'unknown');
                        
                        // Normalize status
                        if ($status === 'inservice') $status = 'online';

                        $ponPorts = self::$boardTypeMap[$boardType]['pon_ports'] ?? 0;
                        $upPorts = self::$boardTypeMap[$boardType]['uplink_ports'] ?? 0;

                        $boards[] = [
                            'shelf' => $shelf,
                            'slot' => $slot,
                            'board_type' => $boardType,
                            'type_category' => self::$boardTypeMap[$boardType]['type'] ?? 'Unknown',
                            'pon_ports' => $ponPorts,
                            'uplink_ports' => $upPorts,
                            'oper_state' => $status,
                        ];

                        $totalPon += $ponPorts;
                        $totalUp += $upPorts;
                    }
                    if (!empty($boards)) break; // Found valid data
                }
            }

            // Default values if detection failed but connection succeeded
            if (empty($boards)) {
                $result['success'] = true;
                $result['model'] = $result['model'] ?? 'ZTE OLT';
                $result['total_pon_ports'] = 16; // Default
                $result['total_uplink_ports'] = 4;
                $result['message'] = 'Koneksi berhasil, tapi tidak dapat mendeteksi board. Menggunakan nilai default.';
                return $result;
            }

            $result['success'] = true;
            $result['boards'] = $boards;
            $result['total_pon_ports'] = $totalPon;
            $result['total_uplink_ports'] = $totalUp;
            $result['message'] = 'Berhasil diidentifikasi via Telnet';

        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Identify via SSH
     */
    protected static function identifyViaSsh(string $ipAddress, int $port, string $username, string $password): array
    {
        $result = [
            'success' => false,
            'boards' => [],
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'model' => null,
            'description' => null,
            'message' => '',
        ];

        // Check if SSH2 extension is available
        if (!function_exists('ssh2_connect')) {
            $result['message'] = 'SSH2 extension tidak terinstall di PHP. Silakan gunakan Telnet atau install php-ssh2.';
            return $result;
        }

        try {
            $connection = @ssh2_connect($ipAddress, $port);
            if (!$connection) {
                $result['message'] = "Tidak dapat terhubung ke SSH port $port";
                return $result;
            }

            if (!@ssh2_auth_password($connection, $username, $password)) {
                $result['message'] = 'SSH authentication gagal. Periksa username dan password.';
                return $result;
            }

            $stream = ssh2_exec($connection, 'show version');
            stream_set_blocking($stream, true);
            $versionOutput = stream_get_contents($stream);
            fclose($stream);

            // Parse version for model
            if (preg_match('/ZXA10\s*(\w+)/i', $versionOutput, $modelMatch)) {
                $result['model'] = strtoupper($modelMatch[1]);
            }
            $result['description'] = trim(preg_replace('/\s+/', ' ', substr($versionOutput, 0, 200)));

            // Get card info
            $stream = ssh2_exec($connection, 'show card');
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            fclose($stream);

            // Parse output (same logic as Telnet)
            $totalPon = 0;
            $totalUp = 0;
            $boards = [];

            if (preg_match_all('/(\d+)\s+(\d+)\s+(\w+)\s+.*?(online|offline|inservice)/i', $output, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $shelf = (int)$match[1];
                    $slot = (int)$match[2];
                    $boardType = strtoupper($match[3]);
                    $status = strtolower($match[4]);
                    if ($status === 'inservice') $status = 'online';

                    $ponPorts = self::$boardTypeMap[$boardType]['pon_ports'] ?? 0;
                    $upPorts = self::$boardTypeMap[$boardType]['uplink_ports'] ?? 0;

                    $boards[] = [
                        'shelf' => $shelf,
                        'slot' => $slot,
                        'board_type' => $boardType,
                        'type_category' => self::$boardTypeMap[$boardType]['type'] ?? 'Unknown',
                        'pon_ports' => $ponPorts,
                        'uplink_ports' => $upPorts,
                        'oper_state' => $status,
                    ];

                    $totalPon += $ponPorts;
                    $totalUp += $upPorts;
                }
            }

            if (empty($boards)) {
                $result['success'] = true;
                $result['model'] = $result['model'] ?? 'ZTE OLT';
                $result['total_pon_ports'] = 16;
                $result['total_uplink_ports'] = 4;
                $result['message'] = 'Koneksi SSH berhasil, menggunakan nilai default.';
                return $result;
            }

            $result['success'] = true;
            $result['boards'] = $boards;
            $result['total_pon_ports'] = $totalPon;
            $result['total_uplink_ports'] = $totalUp;
            $result['message'] = 'Berhasil diidentifikasi via SSH';

        } catch (\Exception $e) {
            $result['message'] = 'SSH Error: ' . $e->getMessage();
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
            // Get PON port admin status
            $adminStatuses = $this->snmpWalk($this->zteOids['zxAnGponOltPonIfAdminStatus']);
            $operStatuses = $this->snmpWalk($this->zteOids['zxAnGponOltPonIfOperStatus']);

            foreach ($adminStatuses as $oid => $adminStatus) {
                // Parse slot.port from OID index
                preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 3) continue;

                $slot = (int) $matches[1];
                $port = (int) $matches[2];

                $operOid = str_replace('1.2', '1.3', $oid);
                $operStatus = $operStatuses[$operOid] ?? 'unknown';

                $ports[] = [
                    'slot' => $slot,
                    'port' => $port,
                    'admin_status' => $adminStatus == 1 ? 'enabled' : 'disabled',
                    'status' => $operStatus == 1 ? 'up' : 'down',
                ];
            }

            // Update database
            foreach ($ports as $portData) {
                $this->updatePonPort($portData['slot'], $portData['port'], $portData);
            }

        } catch (Exception $e) {
            Log::error("ZTE getPonPorts error: " . $e->getMessage());
        }

        return $ports;
    }

    /**
     * Get specific PON port info
     */
    public function getPonPortInfo(int $slot, int $port): array
    {
        $index = "{$slot}.{$port}";

        return [
            'slot' => $slot,
            'port' => $port,
            'admin_status' => $this->snmpGet($this->zteOids['zxAnGponOltPonIfAdminStatus'] . ".{$index}"),
            'oper_status' => $this->snmpGet($this->zteOids['zxAnGponOltPonIfOperStatus'] . ".{$index}"),
        ];
    }

    /**
     * Get PON port optical power (SFP DDM) from OLT
     */
    public function getPonOpticalPower(): array
    {
        $result = [];

        try {
            // Try SNMP first for PON SFP DDM
            $txPowers = $this->snmpWalk($this->zteOids['zxAnGponOltPonOpticalTxPower']);

            if (!empty($txPowers)) {
                $rxPowers = $this->snmpWalk($this->zteOids['zxAnGponOltPonOpticalRxPower']);
                $temps = $this->snmpWalk($this->zteOids['zxAnGponOltPonOpticalTemp']);
                $voltages = $this->snmpWalk($this->zteOids['zxAnGponOltPonOpticalVoltage']);
                $biases = $this->snmpWalk($this->zteOids['zxAnGponOltPonOpticalBias']);

                foreach ($txPowers as $oid => $txRaw) {
                    preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                    if (count($matches) < 3) continue;

                    $slot = (int) $matches[1];
                    $port = (int) $matches[2];
                    $index = "{$slot}.{$port}";

                    $txPower = $this->parseDdmValue($txRaw);
                    $rxPower = $this->parseDdmValue($rxPowers[$this->zteOids['zxAnGponOltPonOpticalRxPower'] . ".{$index}"] ?? null);
                    $temp = $this->parseDdmValue($temps[$this->zteOids['zxAnGponOltPonOpticalTemp'] . ".{$index}"] ?? null);
                    $voltage = $this->parseDdmValue($voltages[$this->zteOids['zxAnGponOltPonOpticalVoltage'] . ".{$index}"] ?? null);
                    $bias = $this->parseDdmValue($biases[$this->zteOids['zxAnGponOltPonOpticalBias'] . ".{$index}"] ?? null);

                    $result[] = [
                        'slot' => $slot,
                        'port' => $port,
                        'name' => "gpon_olt-{$slot}/{$port}",
                        'tx_power' => $txPower,
                        'rx_power' => $rxPower,
                        'temperature' => $temp,
                        'voltage' => $voltage,
                        'tx_bias' => $bias,
                        'signal_quality' => $this->classifySignalQuality($txPower),
                        'tx_power_formatted' => $txPower !== null ? round($txPower, 2) . ' dBm' : '-',
                        'rx_power_formatted' => $rxPower !== null ? round($rxPower, 2) . ' dBm' : '-',
                        'temperature_formatted' => $temp !== null ? round($temp, 1) . ' °C' : '-',
                        'voltage_formatted' => $voltage !== null ? round($voltage, 2) . ' V' : '-',
                        'tx_bias_formatted' => $bias !== null ? round($bias, 2) . ' mA' : '-',
                    ];
                }
            }

            // If SNMP returned nothing, try CLI fallback
            if (empty($result)) {
                $result = $this->getPonOpticalPowerViaCli();
            }

        } catch (\Exception $e) {
            Log::error("ZTE getPonOpticalPower error: " . $e->getMessage());

            // Try CLI as fallback on SNMP error
            try {
                $result = $this->getPonOpticalPowerViaCli();
            } catch (\Exception $e2) {
                Log::error("ZTE getPonOpticalPower CLI fallback error: " . $e2->getMessage());
            }
        }

        return $result;
    }

    /**
     * Get PON optical power via CLI (fallback)
     */
    protected function getPonOpticalPowerViaCli(): array
    {
        $result = [];

        $ponPorts = \App\Models\OltPonPort::where('olt_id', $this->olt->id)
            ->orderBy('slot')->orderBy('port')->get();

        if ($ponPorts->isEmpty()) {
            // Default: discover from getPonPorts
            $discovered = $this->getPonPorts();
            $ports = array_map(fn($p) => [
                'slot' => $p['slot'],
                'port' => $p['port'],
                'name' => $p['name'] ?? "gpon_olt-{$p['slot']}/{$p['port']}",
            ], $discovered);
        } else {
            $ports = $ponPorts->map(fn($p) => [
                'slot' => $p->slot,
                'port' => $p->port,
                'name' => $p->name ?? "gpon_olt-{$p->slot}/{$p->port}",
            ])->toArray();
        }

        if (empty($ports) || !$this->supportsTelnet()) {
            return $result;
        }

        // Open single telnet session for all ports
        $fp = @fsockopen(
            $this->olt->ip_address,
            $this->olt->telnet_port ?? 23,
            $errno,
            $errstr,
            $this->telnetTimeout
        );

        if (!$fp) {
            throw new Exception("Telnet connection failed: {$errstr}");
        }

        stream_set_timeout($fp, $this->telnetTimeout);

        // Login
        $this->telnetWaitFor($fp, ['Username:', 'login:', '>']);
        fwrite($fp, $this->olt->telnet_username . "\r\n");
        $this->telnetWaitFor($fp, ['Password:', 'password:']);
        fwrite($fp, $this->olt->telnet_password . "\r\n");
        sleep(1);
        $this->telnetWaitFor($fp, ['>', '#', '$']);

        foreach ($ports as $portInfo) {
            $slot = $portInfo['slot'];
            $port = $portInfo['port'];

            // ZTE command to show PON transceiver info
            $cmd = "show gpon olt optical-transceiver-diagnosis gpon_olt-{$slot}/{$port}";
            fwrite($fp, $cmd . "\r\n");
            sleep(1);

            $buffer = '';
            $deadline = time() + $this->telnetTimeout;
            while (time() < $deadline) {
                $line = @fgets($fp, 4096);
                if ($line === false) {
                    usleep(100000);
                    continue;
                }
                $buffer .= $line;
                if (preg_match('/[>#\$]\s*$/', $line)) {
                    break;
                }
            }

            $txPower = null;
            $rxPower = null;
            $temp = null;
            $voltage = null;
            $bias = null;

            // Parse CLI output for DDM values
            if (preg_match('/[Tt]x\s*[Pp]ower\s*[:\(]\s*([-\d.]+)\s*(?:dBm)?/i', $buffer, $m)) {
                $txPower = (float) $m[1];
            }
            if (preg_match('/[Rr]x\s*[Pp]ower\s*[:\(]\s*([-\d.]+)\s*(?:dBm)?/i', $buffer, $m)) {
                $rxPower = (float) $m[1];
            }
            if (preg_match('/[Tt]emp(?:erature)?\s*[:\(]\s*([-\d.]+)/i', $buffer, $m)) {
                $temp = (float) $m[1];
            }
            if (preg_match('/[Vv]olt(?:age)?\s*[:\(]\s*([-\d.]+)/i', $buffer, $m)) {
                $voltage = (float) $m[1];
            }
            if (preg_match('/[Bb]ias\s*(?:[Cc]urrent)?\s*[:\(]\s*([-\d.]+)/i', $buffer, $m)) {
                $bias = (float) $m[1];
            }

            $result[] = [
                'slot' => $slot,
                'port' => $port,
                'name' => $portInfo['name'],
                'tx_power' => $txPower,
                'rx_power' => $rxPower,
                'temperature' => $temp,
                'voltage' => $voltage,
                'tx_bias' => $bias,
                'signal_quality' => $this->classifySignalQuality($txPower),
                'tx_power_formatted' => $txPower !== null ? round($txPower, 2) . ' dBm' : '-',
                'rx_power_formatted' => $rxPower !== null ? round($rxPower, 2) . ' dBm' : '-',
                'temperature_formatted' => $temp !== null ? round($temp, 1) . ' °C' : '-',
                'voltage_formatted' => $voltage !== null ? round($voltage, 2) . ' V' : '-',
                'tx_bias_formatted' => $bias !== null ? round($bias, 2) . ' mA' : '-',
            ];
        }

        fclose($fp);

        return $result;
    }

    /**
     * Parse DDM value from SNMP (ZTE returns integer * 100 or raw)
     */
    protected function parseDdmValue($raw): ?float
    {
        if ($raw === null || $raw === '' || $raw === 'noSuchInstance' || $raw === 'noSuchObject') {
            return null;
        }

        $val = is_numeric($raw) ? (float) $raw : null;
        if ($val === null) {
            // Try to extract numeric value from SNMP string
            if (preg_match('/([-\d.]+)/', (string) $raw, $m)) {
                $val = (float) $m[1];
            }
        }

        // ZTE typically returns power in units of 0.01 dBm
        if ($val !== null && abs($val) > 100) {
            $val = $val / 100.0;
        }

        return $val;
    }

    /**
     * Classify signal quality based on TX power (dBm)
     */
    protected function classifySignalQuality(?float $txPower): string
    {
        if ($txPower === null) return 'unknown';
        if ($txPower >= 1.0) return 'excellent';
        if ($txPower >= 0.0) return 'good';
        if ($txPower >= -2.0) return 'acceptable';
        return 'warning';
    }

    /**
     * Get all ONUs from OLT
     */
    public function getAllOnus(): array
    {
        $onus = [];

        try {
            // Get all ONU serial numbers
            $serialNumbers = $this->snmpWalk($this->zteOids['zxAnGponOnuSerialNumber']);
            $runStatuses = $this->snmpWalk($this->zteOids['zxAnGponOnuRunStatus']);
            $distances = $this->snmpWalk($this->zteOids['zxAnGponOnuDistance']);
            $types = $this->snmpWalk($this->zteOids['zxAnGponOnuType']);

            foreach ($serialNumbers as $oid => $serialRaw) {
                // Parse slot.port.onuid from OID
                preg_match('/\.(\d+)\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 4) continue;

                $slot = (int) $matches[1];
                $port = (int) $matches[2];
                $onuId = (int) $matches[3];
                $index = "{$slot}.{$port}.{$onuId}";

                $serialNumber = $this->parseSerialNumber($serialRaw);
                $statusOid = $this->zteOids['zxAnGponOnuRunStatus'] . ".{$index}";
                $status = $runStatuses[$statusOid] ?? 0;

                $onu = [
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $onuId,
                    'serial_number' => $serialNumber,
                    'status' => $this->runStatusMap[$status] ?? 'unknown',
                    'distance' => $this->parseDistance($distances[$this->zteOids['zxAnGponOnuDistance'] . ".{$index}"] ?? null),
                    'onu_type' => $types[$this->zteOids['zxAnGponOnuType'] . ".{$index}"] ?? null,
                ];

                $onus[] = $onu;
            }

        } catch (Exception $e) {
            Log::error("ZTE getAllOnus error: " . $e->getMessage());
        }

        return $onus;
    }

    /**
     * Get ONUs on specific port
     */
    public function getOnusByPort(int $slot, int $port): array
    {
        $allOnus = $this->getAllOnus();
        
        return array_filter($allOnus, fn($onu) => 
            $onu['slot'] == $slot && $onu['port'] == $port
        );
    }

    /**
     * Get detailed ONU info
     */
    public function getOnuInfo(int $slot, int $port, int $onuId): array
    {
        $index = "{$slot}.{$port}.{$onuId}";

        $info = [
            'slot' => $slot,
            'port' => $port,
            'onu_id' => $onuId,
            'serial_number' => $this->parseSerialNumber(
                $this->snmpGet($this->zteOids['zxAnGponOnuSerialNumber'] . ".{$index}") ?? ''
            ),
            'status' => $this->runStatusMap[$this->snmpGet($this->zteOids['zxAnGponOnuRunStatus'] . ".{$index}")] ?? 'unknown',
            'admin_status' => $this->snmpGet($this->zteOids['zxAnGponOnuAdminStatus'] . ".{$index}"),
            'name' => $this->snmpGet($this->zteOids['zxAnGponOnuName'] . ".{$index}"),
            'onu_type' => $this->snmpGet($this->zteOids['zxAnGponOnuType'] . ".{$index}"),
            'vendor' => $this->snmpGet($this->zteOids['zxAnGponOnuVendorId'] . ".{$index}"),
            'distance' => $this->parseDistance($this->snmpGet($this->zteOids['zxAnGponOnuDistance'] . ".{$index}")),
            'software_version' => $this->snmpGet($this->zteOids['zxAnGponOnuSoftwareVer'] . ".{$index}"),
            'hardware_version' => $this->snmpGet($this->zteOids['zxAnGponOnuHardwareVer'] . ".{$index}"),
            'line_profile' => $this->snmpGet($this->zteOids['zxAnGponOnuLineProfile'] . ".{$index}"),
            'service_profile' => $this->snmpGet($this->zteOids['zxAnGponOnuServiceProfile'] . ".{$index}"),
        ];

        // Get optical info
        $optical = $this->getOnuOpticalInfo($slot, $port, $onuId);
        
        return array_merge($info, $optical);
    }

    /**
     * Get ONU optical/signal info
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        $index = "{$slot}.{$port}.{$onuId}";

        $oltRxRaw = $this->snmpGet($this->zteOids['zxAnGponOnuRxPowerLevel'] . ".{$index}");
        $onuTxRaw = $this->snmpGet($this->zteOids['zxAnGponOnuTxPowerLevel'] . ".{$index}");
        $onuRxRaw = $this->snmpGet($this->zteOids['zxAnGponOnuOnuRxPowerLevel'] . ".{$index}");
        $tempRaw = $this->snmpGet($this->zteOids['zxAnGponOnuTemperature'] . ".{$index}");
        $voltRaw = $this->snmpGet($this->zteOids['zxAnGponOnuVoltage'] . ".{$index}");
        $biasRaw = $this->snmpGet($this->zteOids['zxAnGponOnuBiasCurrent'] . ".{$index}");

        return [
            'olt_rx_power' => $this->parseZteOpticalPower($oltRxRaw),
            'tx_power' => $this->parseZteOpticalPower($onuTxRaw),
            'rx_power' => $this->parseZteOpticalPower($onuRxRaw),
            'temperature' => $tempRaw ? ((float)$tempRaw / 256) : null,
            'voltage' => $voltRaw ? ((float)$voltRaw / 10000) : null,
            'bias_current' => $biasRaw ? ((float)$biasRaw / 500) : null,
        ];
    }

    /**
     * Parse ZTE optical power value
     * ZTE returns value * 100 in 0.01 dBm
     */
    protected function parseZteOpticalPower(mixed $value): ?float
    {
        if (is_null($value) || $value === '' || $value == 0x7FFFFFFF || $value == 2147483647) {
            return null;
        }

        // ZTE returns value as signed 32-bit integer in 0.01 dBm
        $power = (float) $value / 100;
        
        return round($power, 2);
    }

    /**
     * Get ONU by serial number
     */
    public function getOnuBySerial(string $serialNumber): ?array
    {
        $serialNumber = strtoupper($serialNumber);
        $allOnus = $this->getAllOnus();

        foreach ($allOnus as $onu) {
            if (strtoupper($onu['serial_number']) === $serialNumber) {
                return $this->getOnuInfo($onu['slot'], $onu['port'], $onu['onu_id']);
            }
        }

        return null;
    }

    /**
     * Get unregistered/unconfigured ONUs
     */
    public function getUnregisteredOnus(): array
    {
        $unregistered = [];

        try {
            // Try via SNMP first
            $uncfgOnus = $this->snmpWalk($this->zteOids['zxAnGponOltUncfgOnuSerialNo']);

            foreach ($uncfgOnus as $oid => $serial) {
                preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches);
                if (count($matches) < 3) continue;

                $unregistered[] = [
                    'slot' => (int) $matches[1],
                    'port' => (int) $matches[2],
                    'serial_number' => $this->parseSerialNumber($serial),
                    'config_status' => 'unregistered',
                ];
            }

            // If SNMP fails or returns empty, try CLI
            if (empty($unregistered) && ($this->supportsTelnet() || $this->supportsSsh())) {
                $output = $this->executeCommand('show gpon onu uncfg');
                $unregistered = $this->parseUnconfiguredOnuOutput($output);
            }

        } catch (Exception $e) {
            Log::error("ZTE getUnregisteredOnus error: " . $e->getMessage());
        }

        return $unregistered;
    }

    /**
     * Register an ONU
     * 
     * @param array $params [
     *   'serial_number' => string,
     *   'slot' => int,
     *   'port' => int,
     *   'onu_id' => int (optional, auto-assign if null),
     *   'name' => string,
     *   'line_profile' => string,
     *   'service_profile' => string,
     *   'vlan' => int (optional),
     *   'gem_port' => int (optional),
     * ]
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
            $serialNumber = strtoupper($params['serial_number']);
            $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $params['name'] ?? $serialNumber);
            $lineProfile = $params['line_profile'] ?? 'default';
            $serviceProfile = $params['service_profile'] ?? 'default';
            $tcontId = $params['tcont_id'] ?? 1;
            $gemPort = $params['gem_port'] ?? 1;
            $serviceId = $params['service_id'] ?? 1;
            $serviceMode = $params['service_port_mode'] ?? 'tag';

            // Determine ONU ID (auto-assign if not provided)
            $onuId = $params['onu_id'] ?? $this->getNextAvailableOnuId($slot, $port);

            // Build CLI commands
            $commands = [
                "configure terminal",
                "interface gpon_olt-{$slot}/{$port}",
                "onu {$onuId} type auto sn {$serialNumber}",
                "exit",
            ];

            // Add name/description
            $commands[] = "interface gpon_onu-{$slot}/{$port}:{$onuId}";
            $commands[] = "name {$name}";

            // Apply line profile/tcont even when VLAN isn't set so provisioning is closer to ZTE workflow.
            if (!empty($lineProfile) || isset($params['vlan'])) {
                $commands[] = "tcont {$tcontId} profile {$lineProfile}";
                $commands[] = "gemport {$gemPort} name gem{$gemPort} tcont {$tcontId}";
            }

            $commands[] = "exit";

            // Add service config if VLAN specified
            if (isset($params['vlan'])) {
                $vlan = $params['vlan'];
                $commands[] = "pon-onu-mng gpon_onu-{$slot}/{$port}:{$onuId}";
                $commands[] = "service {$serviceId} gemport {$gemPort} vlan {$vlan}";
                $commands[] = "vlan port eth_0/1 mode {$serviceMode} vlan {$vlan}";
                $commands[] = "exit";
            }

            $commands[] = "exit";
            $commands[] = "write memory";

            // Execute commands
            $output = $this->executeCommands($commands);

            if ($this->hasCliError($output)) {
                $result['message'] = "Registration failed: {$output}";
            } else {
                $result['success'] = true;
                $result['onu_id'] = $onuId;
                $result['message'] = "ONU registered successfully at {$slot}/{$port}:{$onuId}";
                $verification = $this->verifyRegisteredOnuConfig($slot, $port, $onuId, [
                    'name' => $name,
                    'line_profile' => $lineProfile,
                    'service_profile' => $serviceProfile,
                    'tcont_id' => $tcontId,
                    'gem_port' => $gemPort,
                    'service_id' => $serviceId,
                    'service_port_mode' => $serviceMode,
                    'vlan' => $params['vlan'] ?? null,
                ]);

                if (!empty($verification['warnings'])) {
                    $result['warnings'] = $verification['warnings'];
                    $result['message'] .= ' Warning: ' . implode(' ', $verification['warnings']);
                }

                $result['meta'] = [
                    'line_profile' => $lineProfile,
                    'service_profile' => $serviceProfile,
                    'tcont_id' => $tcontId,
                    'gem_port' => $gemPort,
                    'service_id' => $serviceId,
                    'service_port_mode' => $serviceMode,
                    'verification' => $verification,
                ];
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE registerOnu error: " . $e->getMessage());
        }

        return $result;
    }

    protected function hasCliError(string $output): bool
    {
        return (bool) preg_match('/\b(error|fail(?:ed)?|invalid|duplicate|incomplete|ambiguous|denied)\b/i', $output);
    }

    protected function verifyRegisteredOnuConfig(int $slot, int $port, int $onuId, array $params): array
    {
        $verification = [
            'verified' => true,
            'warnings' => [],
            'running_config' => null,
        ];

        if (!$this->supportsSsh() && !$this->supportsTelnet()) {
            $verification['verified'] = false;
            $verification['warnings'][] = 'ONU berhasil diregister, tapi verifikasi CLI tidak tersedia pada OLT ini.';
            return $verification;
        }

        try {
            $runningConfig = $this->getOnuRunningConfig($slot, $port, $onuId);
            $verification['running_config'] = $runningConfig;

            if ($this->hasCliError($runningConfig)) {
                $verification['verified'] = false;
                $verification['warnings'][] = 'Running-config ONU mengandung respons error, mohon cek manual di OLT.';
                return $verification;
            }

            $escapedName = preg_quote((string) ($params['name'] ?? ''), '/');
            $lineProfile = (string) ($params['line_profile'] ?? '');
            $serviceProfile = (string) ($params['service_profile'] ?? '');
            $tcontId = (int) ($params['tcont_id'] ?? 0);
            $gemPort = (int) ($params['gem_port'] ?? 0);
            $serviceId = (int) ($params['service_id'] ?? 0);
            $serviceMode = (string) ($params['service_port_mode'] ?? '');
            $vlan = $params['vlan'] ?? null;

            if ($escapedName !== '' && !preg_match("/\\bname\\s+{$escapedName}\\b/i", $runningConfig)) {
                $verification['warnings'][] = 'Nama ONU belum terlihat di running-config.';
            }

            if ($lineProfile !== '' && $tcontId > 0) {
                $escapedLineProfile = preg_quote($lineProfile, '/');
                if (!preg_match("/\\btcont\\s+{$tcontId}\\s+profile\\s+{$escapedLineProfile}\\b/i", $runningConfig)) {
                    $verification['warnings'][] = "Line profile {$lineProfile} belum terverifikasi di running-config.";
                }
            }

            if ($gemPort > 0 && $tcontId > 0 && !preg_match("/\\bgemport\\s+{$gemPort}\\b.*\\btcont\\s+{$tcontId}\\b/i", $runningConfig)) {
                $verification['warnings'][] = "GEM port {$gemPort} belum terverifikasi pada T-CONT {$tcontId}.";
            }

            if ($vlan !== null) {
                $escapedMode = preg_quote($serviceMode !== '' ? $serviceMode : 'tag', '/');

                if (!preg_match("/\\bservice\\s+{$serviceId}\\s+gemport\\s+{$gemPort}\\s+vlan\\s+{$vlan}\\b/i", $runningConfig)) {
                    $verification['warnings'][] = "Service VLAN {$vlan} belum terverifikasi di running-config ONU.";
                }

                if (!preg_match("/\\bvlan\\s+port\\s+eth_0\\/1\\s+mode\\s+{$escapedMode}\\s+vlan\\s+{$vlan}\\b/i", $runningConfig)) {
                    $verification['warnings'][] = "Binding VLAN port eth_0/1 mode {$serviceMode} belum terverifikasi.";
                }
            }

            if ($serviceProfile !== '' && strtolower($serviceProfile) !== 'default') {
                $verification['warnings'][] = "Service profile {$serviceProfile} tersimpan di aplikasi, tetapi helper ZTE saat ini masih mengandalkan parameter detail VLAN/GEM/T-CONT/service CLI, belum mapping perintah profile spesifik.";
            }

            if (!empty($verification['warnings'])) {
                $verification['verified'] = false;
            }
        } catch (Exception $e) {
            $verification['verified'] = false;
            $verification['warnings'][] = 'Verifikasi running-config ONU gagal: ' . $e->getMessage();
        }

        return $verification;
    }

    /**
     * Unregister/delete an ONU
     */
    public function unregisterOnu(int $slot, int $port, int $onuId): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $commands = [
                "configure terminal",
                "interface gpon_olt-{$slot}/{$port}",
                "no onu {$onuId}",
                "exit",
                "exit",
                "write memory",
            ];

            $output = $this->executeCommands($commands);

            if (str_contains($output, 'Error') || str_contains($output, 'fail')) {
                $result['message'] = "Unregistration failed: {$output}";
            } else {
                $result['success'] = true;
                $result['message'] = "ONU {$slot}/{$port}:{$onuId} unregistered successfully";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE unregisterOnu error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Reboot an ONU
     */
    public function rebootOnu(int $slot, int $port, int $onuId): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $commands = [
                "configure terminal",
                "pon-onu-mng gpon_onu-{$slot}/{$port}:{$onuId}",
                "reboot",
                "y",
                "exit",
                "exit",
            ];

            $output = $this->executeCommands($commands);

            if (preg_match('/Error|fail|invalid|unknown/i', $output)) {
                $result['message'] = "Reboot command mungkin gagal: " . trim(substr($output, 0, 200));
            } else {
                $result['success'] = true;
                $result['message'] = "ONU {$slot}/{$port}:{$onuId} reboot command berhasil dikirim";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE rebootOnu error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get ONU traffic statistics with rate calculation
     */
    public function getOnuTraffic(int $slot, int $port, int $onuId): array
    {
        $index = "{$slot}.{$port}.{$onuId}";

        $inOctets = (int) ($this->snmpGet($this->zteOids['zxAnGponOnuPerfInOctets'] . ".{$index}") ?? 0);
        $outOctets = (int) ($this->snmpGet($this->zteOids['zxAnGponOnuPerfOutOctets'] . ".{$index}") ?? 0);
        $inPackets = (int) ($this->snmpGet($this->zteOids['zxAnGponOnuPerfInPackets'] . ".{$index}") ?? 0);
        $outPackets = (int) ($this->snmpGet($this->zteOids['zxAnGponOnuPerfOutPackets'] . ".{$index}") ?? 0);

        $now = microtime(true);
        $cacheKey = "zte_traffic_{$this->olt->id}_{$index}";
        $prev = Cache::get($cacheKey);

        $inRate = null;
        $outRate = null;

        if ($prev && isset($prev['time'])) {
            $elapsed = $now - $prev['time'];
            if ($elapsed > 0 && $elapsed < 300) {
                $deltaIn = $inOctets - ($prev['in_octets'] ?? 0);
                $deltaOut = $outOctets - ($prev['out_octets'] ?? 0);

                // Handle counter wrap (32-bit)
                if ($deltaIn < 0) $deltaIn += 4294967296;
                if ($deltaOut < 0) $deltaOut += 4294967296;

                $inRate = ($deltaIn * 8) / $elapsed;   // bps
                $outRate = ($deltaOut * 8) / $elapsed;  // bps
            }
        }

        // Store current reading for next delta
        Cache::put($cacheKey, [
            'in_octets' => $inOctets,
            'out_octets' => $outOctets,
            'time' => $now,
        ], 300);

        return [
            'in_octets' => $inOctets,
            'out_octets' => $outOctets,
            'in_packets' => $inPackets,
            'out_packets' => $outPackets,
            'in_rate_bps' => $inRate,
            'out_rate_bps' => $outRate,
            'in_rate_formatted' => $inRate !== null ? $this->formatBitsPerSecond($inRate) : '-',
            'out_rate_formatted' => $outRate !== null ? $this->formatBitsPerSecond($outRate) : '-',
        ];
    }

    /**
     * Get profiles from OLT
     */
    public function getProfiles(string $type = 'all'): array
    {
        $profiles = [
            'line' => [],
            'service' => [],
            'traffic' => [],
        ];

        try {
            // Get profiles via CLI
            if ($type === 'all' || $type === 'line') {
                $output = $this->executeCommand('show running-config | include tcont');
                $profiles['line'] = $this->parseProfileOutput($output, 'tcont');
            }

            if ($type === 'all' || $type === 'service') {
                $output = $this->executeCommand('show running-config | include gemport');
                $profiles['service'] = $this->parseProfileOutput($output, 'gemport');
            }

            if ($type === 'all' || $type === 'traffic') {
                $output = $this->executeCommand('show running-config | include traffic-profile');
                $profiles['traffic'] = $this->parseProfileOutput($output, 'traffic');
            }

        } catch (Exception $e) {
            Log::error("ZTE getProfiles error: " . $e->getMessage());
        }

        return $type === 'all' ? $profiles : ($profiles[$type] ?? []);
    }

    /**
     * Apply service/VLAN configuration to ONU
     */
    public function applyServiceToOnu(int $slot, int $port, int $onuId, array $serviceConfig): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $vlan = $serviceConfig['vlan'] ?? 100;
            $gemPort = $serviceConfig['gem_port'] ?? 1;
            $serviceId = $serviceConfig['service_id'] ?? 1;
            $mode = $serviceConfig['mode'] ?? 'tag'; // tag, translate, transparent

            $commands = [
                "configure terminal",
                "interface gpon_onu-{$slot}/{$port}:{$onuId}",
            ];

            // Configure tcont and gemport if not exists
            if (isset($serviceConfig['bandwidth_profile'])) {
                $commands[] = "tcont {$gemPort} profile {$serviceConfig['bandwidth_profile']}";
            }
            $commands[] = "gemport {$gemPort} name gem{$gemPort} tcont {$gemPort}";
            $commands[] = "exit";

            // Configure service
            $commands[] = "pon-onu-mng gpon_onu-{$slot}/{$port}:{$onuId}";
            $commands[] = "service {$serviceId} gemport {$gemPort} vlan {$vlan}";
            $commands[] = "vlan port eth_0/1 mode {$mode} vlan {$vlan}";

            // Add PPPoE if specified
            if (isset($serviceConfig['pppoe']) && $serviceConfig['pppoe']) {
                $commands[] = "pppoe 1 nat enable user {$serviceConfig['pppoe_username']} password {$serviceConfig['pppoe_password']}";
            }

            $commands[] = "exit";
            $commands[] = "exit";
            $commands[] = "write memory";

            $output = $this->executeCommands($commands);

            if (str_contains($output, 'Error') || str_contains($output, 'fail')) {
                $result['message'] = "Service configuration failed: {$output}";
            } else {
                $result['success'] = true;
                $result['message'] = "Service configured successfully";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE applyServiceToOnu error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get uplink ports status
     */
    public function getUplinkPorts(): array
    {
        $ports = [];

        try {
            // Get uplink port status via CLI
            $output = $this->executeCommand('show interface brief | include gei');
            $lines = explode("\n", $output);

            foreach ($lines as $line) {
                if (preg_match('/gei_(\d+\/\d+)\s+(\w+)\s+(\w+)/', $line, $matches)) {
                    $ports[] = [
                        'name' => "gei_{$matches[1]}",
                        'admin_status' => $matches[2],
                        'oper_status' => $matches[3],
                    ];
                }
            }

        } catch (Exception $e) {
            Log::error("ZTE getUplinkPorts error: " . $e->getMessage());
        }

        return $ports;
    }

    /**
     * Sync all data from OLT to database
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
            $ponPorts = $this->getPonPorts();
            $result['pon_ports_synced'] = count($ponPorts);

            // Sync all ONUs
            $allOnus = $this->getAllOnus();

            foreach ($allOnus as $onuData) {
                try {
                    // Get full ONU info including optical
                    $fullInfo = $this->getOnuInfo(
                        $onuData['slot'],
                        $onuData['port'],
                        $onuData['onu_id']
                    );

                    // Get traffic stats
                    $traffic = $this->getOnuTraffic(
                        $onuData['slot'],
                        $onuData['port'],
                        $onuData['onu_id']
                    );

                    // Save to database
                    $onu = $this->saveOnuToDatabase(array_merge($fullInfo, $traffic, [
                        'olt_id' => $this->olt->id,
                        'config_status' => 'registered',
                    ]));

                    // Save signal history
                    $this->saveSignalHistory($onu, [
                        'rx_power' => $fullInfo['rx_power'] ?? null,
                        'tx_power' => $fullInfo['tx_power'] ?? null,
                        'olt_rx_power' => $fullInfo['olt_rx_power'] ?? null,
                        'temperature' => $fullInfo['temperature'] ?? null,
                        'voltage' => $fullInfo['voltage'] ?? null,
                        'bias_current' => $fullInfo['bias_current'] ?? null,
                        'status' => $fullInfo['status'] ?? null,
                        'distance' => $fullInfo['distance'] ?? null,
                    ]);

                    $result['onus_synced']++;
                    $result['signals_recorded']++;

                } catch (Exception $e) {
                    $result['errors'][] = "ONU {$onuData['slot']}/{$onuData['port']}:{$onuData['onu_id']}: " . $e->getMessage();
                }
            }

            // Sync PON port optical power (TX power from OLT SFP)
            try {
                $ponOpticalSynced = $this->syncPonOpticalPower();
                $result['pon_optical_synced'] = $ponOpticalSynced;
            } catch (Exception $e) {
                $result['errors'][] = "Failed to sync PON optical power: " . $e->getMessage();
            }

            // Update OLT last sync time
            $this->olt->update([
                'last_sync_at' => now(),
                'last_online_at' => now(),
                'status' => 'active',
            ]);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
            Log::error("ZTE syncAll error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Execute single CLI command
     */
    protected function executeCommand(string $command): string
    {
        if ($this->supportsSsh() && function_exists('ssh2_connect')) {
            return $this->sshCommand($command);
        }

        if ($this->supportsTelnet()) {
            return $this->telnetCommand($command);
        }

        throw new Exception('No CLI connection method available');
    }

    /**
     * Execute multiple CLI commands sequentially via a single session
     */
    protected function executeCommands(array $commands): string
    {
        if ($this->supportsSsh() && function_exists('ssh2_connect')) {
            return $this->executeCommand(implode("\n", $commands));
        }

        if (!$this->supportsTelnet()) {
            throw new Exception('No CLI connection method available');
        }

        $fp = @fsockopen(
            $this->olt->ip_address,
            $this->olt->telnet_port ?? 23,
            $errno,
            $errstr,
            $this->telnetTimeout
        );

        if (!$fp) {
            throw new Exception("Telnet connection failed: {$errstr}");
        }

        stream_set_timeout($fp, $this->telnetTimeout);

        // Login
        $this->telnetWaitFor($fp, ['Username:', 'login:', '>']);
        fwrite($fp, $this->olt->telnet_username . "\r\n");
        $this->telnetWaitFor($fp, ['Password:', 'password:']);
        fwrite($fp, $this->olt->telnet_password . "\r\n");
        sleep(1);
        $this->telnetWaitFor($fp, ['>', '#', '$']);

        // Send each command sequentially
        $fullOutput = '';
        foreach ($commands as $cmd) {
            fwrite($fp, $cmd . "\r\n");
            usleep(500000); // 500ms between commands
            $buffer = '';
            $deadline = time() + $this->telnetTimeout;
            while (time() < $deadline) {
                $line = @fgets($fp, 4096);
                if ($line === false) {
                    usleep(100000);
                    continue;
                }
                $buffer .= $line;
                if (preg_match('/[>#\$\)]\s*$/', $line)) {
                    break;
                }
            }
            $fullOutput .= $buffer;
        }

        fclose($fp);

        return $fullOutput;
    }

    /**
     * Get next available ONU ID on a port
     */
    protected function getNextAvailableOnuId(int $slot, int $port): int
    {
        $existingOnus = $this->getOnusByPort($slot, $port);
        $usedIds = array_column($existingOnus, 'onu_id');

        for ($i = 1; $i <= 128; $i++) {
            if (!in_array($i, $usedIds)) {
                return $i;
            }
        }

        throw new Exception("No available ONU ID on port {$slot}/{$port}");
    }

    /**
     * Parse unconfigured ONU output from CLI
     */
    protected function parseUnconfiguredOnuOutput(string $output): array
    {
        $onus = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            // Match patterns like: gpon_olt-1/1  1  ZTEG12345678
            if (preg_match('/gpon_olt-(\d+)\/(\d+)\s+\d+\s+(\w+)/', $line, $matches)) {
                $onus[] = [
                    'slot' => (int) $matches[1],
                    'port' => (int) $matches[2],
                    'serial_number' => $this->parseSerialNumber($matches[3]),
                    'config_status' => 'unregistered',
                ];
            }
        }

        return $onus;
    }

    /**
     * Parse profile output from CLI
     */
    protected function parseProfileOutput(string $output, string $type): array
    {
        $profiles = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match("/{$type}\s+(\d+)\s+(?:name\s+)?(\S+)?/i", $line, $matches)) {
                $profiles[] = [
                    'id' => $matches[1],
                    'name' => $matches[2] ?? "profile_{$matches[1]}",
                ];
            }
        }

        return $profiles;
    }

    /**
     * Configure ONU with full provisioning
     * Similar to NetNumen provisioning workflow
     */
    public function provisionOnu(array $params): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'steps' => [],
        ];

        try {
            $slot = $params['slot'];
            $port = $params['port'];
            $serialNumber = strtoupper($params['serial_number']);
            $onuId = $params['onu_id'] ?? $this->getNextAvailableOnuId($slot, $port);
            $name = $params['name'] ?? $serialNumber;

            // Step 1: Register ONU
            $result['steps'][] = 'Registering ONU...';
            $registerResult = $this->registerOnu([
                'slot' => $slot,
                'port' => $port,
                'onu_id' => $onuId,
                'serial_number' => $serialNumber,
                'name' => $name,
            ]);

            if (!$registerResult['success']) {
                throw new Exception("Registration failed: " . $registerResult['message']);
            }

            // Step 2: Wait for ONU to come online
            $result['steps'][] = 'Waiting for ONU to come online...';
            sleep(5);

            // Step 3: Configure service
            if (isset($params['vlan']) || isset($params['service'])) {
                $result['steps'][] = 'Configuring service...';
                
                $serviceConfig = [
                    'vlan' => $params['vlan'] ?? 100,
                    'gem_port' => $params['gem_port'] ?? 1,
                    'mode' => $params['vlan_mode'] ?? 'tag',
                ];

                if (isset($params['bandwidth_profile'])) {
                    $serviceConfig['bandwidth_profile'] = $params['bandwidth_profile'];
                }

                if (isset($params['pppoe_username'])) {
                    $serviceConfig['pppoe'] = true;
                    $serviceConfig['pppoe_username'] = $params['pppoe_username'];
                    $serviceConfig['pppoe_password'] = $params['pppoe_password'] ?? '';
                }

                $serviceResult = $this->applyServiceToOnu($slot, $port, $onuId, $serviceConfig);

                if (!$serviceResult['success']) {
                    throw new Exception("Service configuration failed: " . $serviceResult['message']);
                }
            }

            // Step 4: Configure management (optional)
            if (isset($params['mgmt_vlan'])) {
                $result['steps'][] = 'Configuring management...';
                $this->configureOnuManagement($slot, $port, $onuId, [
                    'vlan' => $params['mgmt_vlan'],
                    'ip' => $params['mgmt_ip'] ?? null,
                ]);
            }

            $result['success'] = true;
            $result['message'] = "ONU provisioned successfully at {$slot}/{$port}:{$onuId}";
            $result['onu_id'] = $onuId;

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE provisionOnu error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Configure ONU management access
     */
    public function configureOnuManagement(int $slot, int $port, int $onuId, array $config): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $commands = [
                "configure terminal",
                "pon-onu-mng gpon_onu-{$slot}/{$port}:{$onuId}",
            ];

            if (isset($config['vlan'])) {
                $commands[] = "mvlan {$config['vlan']}";
            }

            if (isset($config['ip'])) {
                $commands[] = "ip address {$config['ip']} mask 255.255.255.0 vlan {$config['vlan']}";
            }

            $commands[] = "exit";
            $commands[] = "exit";
            $commands[] = "write memory";

            $output = $this->executeCommands($commands);

            $result['success'] = !str_contains($output, 'Error');
            $result['message'] = $result['success'] ? 'Management configured' : $output;

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get ONU running configuration
     */
    public function getOnuRunningConfig(int $slot, int $port, int $onuId): string
    {
        return $this->executeCommand("show running-config interface gpon_onu-{$slot}/{$port}:{$onuId}");
    }

    /**
     * Reset ONU to factory defaults
     */
    public function resetOnuFactory(int $slot, int $port, int $onuId): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $commands = [
                "configure terminal",
                "pon-onu-mng gpon_onu-{$slot}/{$port}:{$onuId}",
                "restore factory",
                "y",
                "exit",
                "exit",
            ];

            $output = $this->executeCommands($commands);

            $result['success'] = true;
            $result['message'] = "Factory reset command sent to ONU {$slot}/{$port}:{$onuId}";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }
}
