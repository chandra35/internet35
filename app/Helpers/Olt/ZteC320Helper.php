<?php

namespace App\Helpers\Olt;

use Exception;
use App\Models\Zone;
use App\Models\Odp;
use App\Models\OltCard;
use App\Models\OltVlan;
use App\Models\OltUplink;
use Illuminate\Support\Str;
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
        
        // ONU — Index: ponIfIndex.onuId (2-part, ponIfIndex encodes type/rack/slot/port)
        'zxAnGponOnuTable' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1',
        'zxAnGponOnuType' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.1',             // Col 1: ONU model (STRING "F670L")
        'zxAnGponOnuName' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2',             // Col 2: ONU name
        'zxAnGponOnuDescription' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.3',      // Col 3: Description
        'zxAnGponOnuAdminStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.5',      // Col 5: Admin status
        'zxAnGponOnuSerialNumber' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.6',     // Col 6: Serial (binary: 4-byte vendor ASCII + 4-byte hex)
        'zxAnGponOnuLineProfile' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.9',      // Col 9: Line profile
        'zxAnGponOnuServiceProfile' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.10',  // Col 10: Service profile
        'zxAnGponOnuRunStatus' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.11',       // Col 11: Run status (1=online,2=offline,3=los,4=dying_gasp,5=power_off)
        'zxAnGponOnuPhaseState' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.12',      // Col 12: Phase state
        'zxAnGponOnuSoftwareVer' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.14',     // Col 14: Software version
        'zxAnGponOnuHardwareVer' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.15',     // Col 15: Hardware version
        'zxAnGponOnuAuthInfo' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.18',        // Col 18: Auth info "authType,serial"
        'zxAnGponOnuDistance' => '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.20',         // Col 20: Distance
        
        // ONU Optical Info — DDM table not reliable via SNMP on this firmware, use CLI fallback
        'zxAnGponOnuOpticalDdmTable' => '1.3.6.1.4.1.3902.1082.500.10.2.3.8.1',
        
        // Traffic Statistics (table 10)
        'zxAnGponOnuPerfInOctets' => '1.3.6.1.4.1.3902.1082.500.10.2.3.10.1.1',
        'zxAnGponOnuPerfOutOctets' => '1.3.6.1.4.1.3902.1082.500.10.2.3.10.1.2',
        
        // PON Port SFP DDM (OLT-side transceiver)
        'zxAnGponOltPonOpticalTxPower' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.10',
        'zxAnGponOltPonOpticalRxPower' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.11',
        'zxAnGponOltPonOpticalTemp' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.12',
        'zxAnGponOltPonOpticalVoltage' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.13',
        'zxAnGponOltPonOpticalBias' => '1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.14',

        // ONU Equipment ID — gives full model string (e.g. "F670LV9.0"), index: ponIfIndex.onuId
        'zxAnGponOnuEquipmentId'       => '1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.7',

        // Q-BRIDGE-MIB — Standard VLAN management (IEEE 802.1Q)
        'dot1qVlanStaticName' => '1.3.6.1.2.1.17.7.1.4.3.1.1',
        'dot1qVlanStaticEgressPorts' => '1.3.6.1.2.1.17.7.1.4.3.1.2',
        'dot1qVlanStaticUntaggedPorts' => '1.3.6.1.2.1.17.7.1.4.3.1.4',
        'dot1qVlanStaticRowStatus' => '1.3.6.1.2.1.17.7.1.4.3.1.5',
        'dot1qNumVlans' => '1.3.6.1.2.1.17.7.1.1.4.0',
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
        'GTGHK' => ['type' => 'PON', 'pon_ports' => 16, 'uplink_ports' => 0],
        'GTGOK' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'ETGO' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'ETGH' => ['type' => 'PON', 'pon_ports' => 16, 'uplink_ports' => 0],
        'ETGHK' => ['type' => 'PON', 'pon_ports' => 16, 'uplink_ports' => 0],
        'ETGOK' => ['type' => 'PON', 'pon_ports' => 8, 'uplink_ports' => 0],
        'HUTQ' => ['type' => 'Uplink', 'pon_ports' => 0, 'uplink_ports' => 4],
        'SCXN' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'SCXL' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'SMXA' => ['type' => 'Control', 'pon_ports' => 0, 'uplink_ports' => 2],
        'PRAM' => ['type' => 'Power', 'pon_ports' => 0, 'uplink_ports' => 0],
        'PRWG' => ['type' => 'Power', 'pon_ports' => 0, 'uplink_ports' => 0],
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
                    $result['model'] = $cliResult['model'] ?? 'ZTE C320';
                    $result['firmware'] = $cliResult['firmware'] ?? null;
                    $result['description'] = $cliResult['description'] ?? 'Connected via ' . ($useTelnet ? 'Telnet' : 'SSH');
                    $result['snmp_community'] = $cliResult['snmp_community'] ?? null;
                    $result['snmp_community_rw'] = $cliResult['snmp_community_rw'] ?? null;
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
            'firmware' => null,
            'description' => null,
            'snmp_community' => null,
            'snmp_community_rw' => null,
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
                $result = self::identifyViaSsh($ipAddress, $port, $username, $password);
                return $result;
            }

            // Use Telnet
            $fp = @fsockopen($ipAddress, $port, $errno, $errstr, 10);
            if (!$fp) {
                $result['message'] = "Tidak dapat terhubung ke Telnet port $port: $errstr ($errno)";
                return $result;
            }

            stream_set_timeout($fp, 15);

            // Helper to read until pattern
            $readUntil = function($patterns, $timeout = 15) use ($fp) {
                $buf = '';
                $start = time();
                $patterns = (array) $patterns;
                while (time() - $start < $timeout) {
                    $meta = stream_get_meta_data($fp);
                    if ($meta['timed_out']) break;
                    $c = @fgetc($fp);
                    if ($c === false) { usleep(50000); continue; }
                    $buf .= $c;
                    foreach ($patterns as $p) {
                        if (stripos($buf, $p) !== false) return $buf;
                    }
                }
                return $buf;
            };

            // Login sequence — wait for actual prompts
            $readUntil(['Username:', 'login:']);
            fwrite($fp, "$username\r\n");
            $readUntil(['Password:']);
            fwrite($fp, "$password\r\n");
            sleep(1);
            $loginResp = $readUntil(['#', '>']);

            if (stripos($loginResp, 'invalid') !== false || stripos($loginResp, 'fail') !== false || stripos($loginResp, 'denied') !== false) {
                fclose($fp);
                $result['message'] = 'Login gagal. Periksa username dan password.';
                return $result;
            }

            // Disable paging
            fwrite($fp, "terminal length 0\r\n");
            sleep(1);
            $readUntil(['#', '>']);

            // 1) Get model from sysDescr or hostname
            fwrite($fp, "show hostname\r\n");
            sleep(1);
            $hostnameOut = $readUntil(['#', '>']);

            // Try to detect model from banner/hostname
            $model = null;
            $firmware = null;
            if (preg_match('/ZXAN|ZXA10|C320|C300|C220/i', $loginResp . $hostnameOut, $m)) {
                $model = strtoupper($m[0]);
                if ($model === 'ZXAN') $model = 'C320';
            }

            // Try show system-group for firmware
            fwrite($fp, "show system-group\r\n");
            sleep(2);
            $sysOut = $readUntil(['#', '>']);
            if (preg_match('/sysDescr[:\s]+(.+)/i', $sysOut, $m)) {
                $result['description'] = trim($m[1]);
                if (preg_match('/C(\d{3})/i', $m[1], $mm)) {
                    $model = 'C' . $mm[1];
                }
                if (preg_match('/V[\d.]+\s+Software/i', $m[1], $mm)) {
                    $firmware = trim($mm[0]);
                }
            }
            if (preg_match('/sysName[:\s]+(\S+)/i', $sysOut, $m)) {
                $result['description'] = ($result['description'] ? $result['description'] . ' | ' : '') . 'Name: ' . trim($m[1]);
            }

            // 2) Show card — get board info
            fwrite($fp, "show card\r\n");
            sleep(2);
            $cardOut = $readUntil(['#', '>']);

            $totalPon = 0;
            $totalUp = 0;
            $boards = [];

            // Parse: Rack Shelf Slot CfgType RealType Port HardVer SoftVer Status
            // Example: 1    1     1    GTGH    GTGHK    16    V1.0.0  V2.1.0   INSERVICE
            $lines = explode("\n", $cardOut);
            foreach ($lines as $line) {
                $line = trim($line);
                // Skip header/separator
                if (empty($line) || strpos($line, '---') !== false || stripos($line, 'Rack') !== false || strpos($line, '#') !== false) continue;

                // Match: Rack Shelf Slot CfgType [RealType] Port [HardVer] [SoftVer] Status
                if (preg_match('/^(\d+)\s+(\d+)\s+(\d+)\s+(\w+)\s+(\w*)\s+(\d+)\s+.*?(INSERVICE|ONLINE|OFFLINE|STANDBY)/i', $line, $m)) {
                    $shelf = (int) $m[2];
                    $slot = (int) $m[3];
                    $cfgType = strtoupper($m[4]);
                    $realType = strtoupper($m[5]) ?: $cfgType;
                    $portCount = (int) $m[6];
                    $status = strtolower($m[7]);
                    if ($status === 'inservice') $status = 'online';
                } elseif (preg_match('/^(\d+)\s+(\d+)\s+(\d+)\s+(\w+)\s+(\d+)\s+.*?(INSERVICE|ONLINE|OFFLINE|STANDBY)/i', $line, $m)) {
                    // Without RealType: Rack Shelf Slot CfgType Port ... Status
                    $shelf = (int) $m[2];
                    $slot = (int) $m[3];
                    $cfgType = strtoupper($m[4]);
                    $realType = $cfgType;
                    $portCount = (int) $m[5];
                    $status = strtolower($m[6]);
                    if ($status === 'inservice') $status = 'online';
                } elseif (preg_match('/^(\d+)\s+(\d+)\s+(\d+)\s+(\w+)\s+(\d*)\s*(OFFLINE|STANDBY)/i', $line, $m)) {
                    // Offline card (may have empty columns)
                    $shelf = (int) $m[2];
                    $slot = (int) $m[3];
                    $cfgType = strtoupper($m[4]);
                    $realType = $cfgType;
                    $portCount = !empty($m[5]) ? (int) $m[5] : 0;
                    $status = 'offline';
                } else {
                    continue;
                }

                // Determine type from boardTypeMap or port count
                $typeInfo = self::$boardTypeMap[$cfgType] ?? self::$boardTypeMap[$realType] ?? null;
                $typeCategory = $typeInfo['type'] ?? 'Unknown';

                $ponPorts = 0;
                $upPorts = 0;

                if ($typeCategory === 'PON') {
                    $ponPorts = $portCount ?: ($typeInfo['pon_ports'] ?? 0);
                } elseif ($typeCategory === 'Uplink') {
                    $upPorts = $portCount ?: ($typeInfo['uplink_ports'] ?? 0);
                } elseif ($typeCategory === 'Control') {
                    // Control boards (SMXA, SCXN) have uplink ports
                    $upPorts = $portCount ?: ($typeInfo['uplink_ports'] ?? 0);
                }

                // Get firmware from SoftVer column if we don't have it yet
                if (!$firmware && preg_match('/V[\d.]+/', $line, $fwMatch)) {
                    $firmware = $fwMatch[0];
                }

                $boards[] = [
                    'shelf' => $shelf,
                    'slot' => $slot,
                    'board_type' => $realType ?: $cfgType,
                    'cfg_type' => $cfgType,
                    'type_category' => $typeCategory,
                    'pon_ports' => $ponPorts,
                    'uplink_ports' => $upPorts,
                    'port_count' => $portCount,
                    'oper_state' => $status,
                ];

                $totalPon += $ponPorts;
                $totalUp += $upPorts;
            }

            // 3) Read SNMP config
            fwrite($fp, "show running-config | include snmp-server community\r\n");
            sleep(2);
            $snmpOut = $readUntil(['#', '>']);

            $snmpRo = null;
            $snmpRw = null;
            // Parse: snmp-server community <name> view <view> ro|rw
            if (preg_match_all('/snmp-server\s+community\s+(\S+)\s+.*?\s+(ro|rw)\b/i', $snmpOut, $snmpMatches, PREG_SET_ORDER)) {
                foreach ($snmpMatches as $sm) {
                    if (strtolower($sm[2]) === 'ro' && !$snmpRo) {
                        $snmpRo = $sm[1];
                    } elseif (strtolower($sm[2]) === 'rw' && !$snmpRw) {
                        $snmpRw = $sm[1];
                    }
                }
            }

            fclose($fp);

            // If no boards detected but login succeeded
            if (empty($boards)) {
                $result['success'] = true;
                $result['model'] = $model ?? 'ZTE C320';
                $result['firmware'] = $firmware;
                $result['total_pon_ports'] = 16;
                $result['total_uplink_ports'] = 4;
                $result['snmp_community'] = $snmpRo;
                $result['snmp_community_rw'] = $snmpRw;
                $result['message'] = 'Koneksi berhasil, board tidak terdeteksi. Menggunakan nilai default.';
                return $result;
            }

            $result['success'] = true;
            $result['model'] = $model ?? 'ZTE C320';
            $result['firmware'] = $firmware;
            $result['boards'] = $boards;
            $result['total_pon_ports'] = $totalPon;
            $result['total_uplink_ports'] = $totalUp;
            $result['snmp_community'] = $snmpRo;
            $result['snmp_community_rw'] = $snmpRw;
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
                // Parse ponIfIndex from OID (single-indexed table)
                if (preg_match('/\.(\d+)$/', $oid, $matches)) {
                    $ponIfIndex = (int) $matches[1];
                    if ($ponIfIndex > 1000) {
                        $decoded = $this->decodePonIfIndex($ponIfIndex);
                        $slot = $decoded['slot'];
                        $port = $decoded['port'];
                    } else {
                        // Fallback: last 2 numbers as slot.port
                        preg_match('/\.(\d+)\.(\d+)$/', $oid, $m2);
                        if (!$m2) continue;
                        $slot = (int) $m2[1];
                        $port = (int) $m2[2];
                    }
                } else {
                    continue;
                }

                // Build matching oper status OID
                $operOid = str_replace('.2.', '.3.', $oid);
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
        $ponIfIndex = $this->encodePonIfIndex($slot, $port);
        $index = "{$ponIfIndex}";

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
                    // Parse ponIfIndex from OID (single-indexed)
                    if (preg_match('/\.(\d+)$/', $oid, $matches)) {
                        $ponIfIndex = (int) $matches[1];
                        if ($ponIfIndex > 1000) {
                            $decoded = $this->decodePonIfIndex($ponIfIndex);
                            $slot = $decoded['slot'];
                            $port = $decoded['port'];
                            $index = "{$ponIfIndex}";
                        } else {
                            preg_match('/\.(\d+)\.(\d+)$/', $oid, $m2);
                            if (!$m2) continue;
                            $slot = (int) $m2[1];
                            $port = (int) $m2[2];
                            $index = "{$slot}.{$port}";
                        }
                    } else {
                        continue;
                    }

                    $txPower = $this->parseDdmValue($txRaw);
                    $rxPower = $this->parseDdmValue($rxPowers[$this->zteOids['zxAnGponOltPonOpticalRxPower'] . ".{$index}"] ?? null);
                    $temp = $this->parseDdmValue($temps[$this->zteOids['zxAnGponOltPonOpticalTemp'] . ".{$index}"] ?? null);
                    $voltage = $this->parseDdmValue($voltages[$this->zteOids['zxAnGponOltPonOpticalVoltage'] . ".{$index}"] ?? null);
                    $bias = $this->parseDdmValue($biases[$this->zteOids['zxAnGponOltPonOpticalBias'] . ".{$index}"] ?? null);

                    $result[] = [
                        'slot' => $slot,
                        'port' => $port,
                        'name' => "gpon-olt_1/{$slot}/{$port}",
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
                'name' => $p['name'] ?? "gpon-olt_{$p['slot']}/{$p['port']}",
            ], $discovered);
        } else {
            $ports = $ponPorts->map(fn($p) => [
                'slot' => $p->slot,
                'port' => $p->port,
                'name' => $p->name ?? "gpon-olt_{$p->slot}/{$p->port}",
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
            $cmd = "show gpon olt optical-transceiver-diagnosis gpon-olt_1/{$slot}/{$port}";
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
     * Decode ZTE PON interface index to slot and port
     * ponIfIndex format: type(8bit) | rack(8bit) | slot(8bit) | port(8bit)
     * Example: 285278465 = 0x11010101 → type=0x11(GPON), rack=1, slot=1, port=1
     */
    protected function decodePonIfIndex(int $ponIfIndex): array
    {
        return [
            'slot' => ($ponIfIndex >> 8) & 0xFF,
            'port' => $ponIfIndex & 0xFF,
        ];
    }

    /**
     * Encode slot and port to ZTE PON interface index
     */
    protected function encodePonIfIndex(int $slot, int $port, int $rack = 1): int
    {
        return (0x11 << 24) | ($rack << 16) | ($slot << 8) | $port;
    }

    /**
     * Build ONU SNMP index string from slot/port/onuId
     */
    protected function buildOnuIndex(int $slot, int $port, int $onuId): string
    {
        $ponIfIndex = $this->encodePonIfIndex($slot, $port);
        return "{$ponIfIndex}.{$onuId}";
    }

    /**
     * Parse ONU index from SNMP OID (2-part: ponIfIndex.onuId)
     */
    protected function parseOnuIndex(string $oid): ?array
    {
        // Match last 2 numbers: ponIfIndex.onuId
        if (preg_match('/\.(\d+)\.(\d+)$/', $oid, $matches)) {
            $ponIfIndex = (int) $matches[1];
            $onuId = (int) $matches[2];

            // Large number = encoded ponIfIndex (2-part format)
            if ($ponIfIndex > 1000) {
                $decoded = $this->decodePonIfIndex($ponIfIndex);
                return ['slot' => $decoded['slot'], 'port' => $decoded['port'], 'onu_id' => $onuId];
            }
        }

        // Fallback: try 3-part format (slot.port.onuId)
        if (preg_match('/\.(\d+)\.(\d+)\.(\d+)$/', $oid, $matches)) {
            return ['slot' => (int)$matches[1], 'port' => (int)$matches[2], 'onu_id' => (int)$matches[3]];
        }

        return null;
    }

    /**
     * Parse serial number from binary SNMP value
     * ZTE returns 8 bytes: 4 ASCII vendor + 4 hex serial
     */
    protected function parseZteSerialNumber(string $raw): string
    {
        $raw = trim($raw);
        if (empty($raw)) return '';

        // Already readable serial like "ZTEGD2328864"
        if (preg_match('/^[A-Z]{4}[0-9A-F]{8}$/i', $raw)) {
            return strtoupper($raw);
        }

        // Binary format: 4 ASCII vendor bytes + 4 binary serial bytes
        if (strlen($raw) === 8) {
            $vendor = substr($raw, 0, 4);
            if (ctype_print($vendor)) {
                $hexPart = strtoupper(bin2hex(substr($raw, 4)));
                return strtoupper($vendor) . $hexPart;
            }
        }

        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $raw));
    }

    /**
     * Parse serial from auth info string "authType,serial"
     */
    protected function parseAuthInfoSerial(string $authInfo): string
    {
        $authInfo = trim($authInfo, " \t\n\r\0\x0B\"");
        $parts = explode(',', $authInfo, 2);
        return isset($parts[1]) ? strtoupper(trim($parts[1], " \t\n\r\0\x0B\"")) : '';
    }

    /**
     * Parse SmartOLT-style description into zone, odp, lat, long, auth_date
     * Format: zone_{ZONE}_odb_{ODP}[_lat_{LAT}_long_{LONG}]_authd_{DATE}
     */
    protected function parseDescription(?string $description): array
    {
        $result = [
            'zone' => null,
            'odp' => null,
            'latitude' => null,
            'longitude' => null,
            'auth_date' => null,
        ];

        if (!$description) return $result;

        $desc = trim($description, '" ');

        // Pattern with ODB: zone_{ZONE}_odb_{ODP}[_lat_{LAT}_long_{LONG}]_authd_{DATE}
        if (preg_match('/^zone_(.+?)_odb_(.+?)(?:_lat_([-\d.]+)_long_([-\d.]+))?_authd_(\d+)$/', $desc, $m)) {
            $result['zone'] = str_replace('_', ' ', trim($m[1]));
            $result['odp'] = str_replace('_', ' ', trim($m[2]));
            $result['latitude'] = isset($m[3]) && $m[3] !== '' ? (float) $m[3] : null;
            $result['longitude'] = isset($m[4]) && $m[4] !== '' ? (float) $m[4] : null;
            $result['auth_date'] = $m[5] ?? null;
            return $result;
        }

        // Pattern without ODB: zone_{ZONE}_authd_{DATE}
        if (preg_match('/^zone_(.+?)_authd_(\d+)$/', $desc, $m)) {
            $result['zone'] = str_replace('_', ' ', trim($m[1]));
            $result['auth_date'] = $m[2] ?? null;
            return $result;
        }

        return $result;
    }

    /**
     * Get all ONUs from OLT
     */
    public function getAllOnus(): array
    {
        $onus = [];

        try {
            // Walk auth info (col 18) for serial — most reliable format "authType,serial"
            $authInfos = $this->snmpWalk($this->zteOids['zxAnGponOnuAuthInfo']);
            $runStatuses = $this->snmpWalk($this->zteOids['zxAnGponOnuRunStatus']);
            $distances = $this->snmpWalk($this->zteOids['zxAnGponOnuDistance']);
            $types = $this->snmpWalk($this->zteOids['zxAnGponOnuType']);
            $names = $this->snmpWalk($this->zteOids['zxAnGponOnuName']);
            $descriptions = $this->snmpWalk($this->zteOids['zxAnGponOnuDescription']);

            // If auth info empty, fallback to binary serial column
            $useAuthInfo = !empty($authInfos);
            $serialSrc = $useAuthInfo ? $authInfos : $this->snmpWalk($this->zteOids['zxAnGponOnuSerialNumber']);

            foreach ($serialSrc as $oid => $value) {
                $parsed = $this->parseOnuIndex($oid);
                if (!$parsed) continue;

                $slot = $parsed['slot'];
                $port = $parsed['port'];
                $onuId = $parsed['onu_id'];
                $index = $this->buildOnuIndex($slot, $port, $onuId);

                $serialNumber = $useAuthInfo
                    ? $this->parseAuthInfoSerial($value)
                    : $this->parseZteSerialNumber($value);

                $statusOid = $this->zteOids['zxAnGponOnuRunStatus'] . ".{$index}";
                $status = $runStatuses[$statusOid] ?? null;
                // Fallback: single SNMP GET if walk missed this ONU
                if ($status === null) {
                    $status = $this->snmpGet($statusOid);
                }
                $status = (int) ($status ?? 0);

                $rawName = $names[$this->zteOids['zxAnGponOnuName'] . ".{$index}"] ?? null;
                $rawType = $types[$this->zteOids['zxAnGponOnuType'] . ".{$index}"] ?? null;
                $rawDesc = $descriptions[$this->zteOids['zxAnGponOnuDescription'] . ".{$index}"] ?? null;

                $onu = [
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $onuId,
                    'serial_number' => $serialNumber,
                    'status' => $this->runStatusMap[$status] ?? 'unknown',
                    'distance' => $this->parseDistance($distances[$this->zteOids['zxAnGponOnuDistance'] . ".{$index}"] ?? null),
                    'onu_type' => $rawType ? trim($rawType, '" ') : null,
                    'name' => $rawName ? trim($rawName, '" ') : null,
                    'description' => $rawDesc ? trim($rawDesc, '" ') : null,
                ];

                // Parse SmartOLT-style description for zone/odp/coordinates
                $descParsed = $this->parseDescription($rawDesc);
                $onu['zone_name'] = $descParsed['zone'];
                $onu['odp_name'] = $descParsed['odp'];
                $onu['latitude'] = $descParsed['latitude'];
                $onu['longitude'] = $descParsed['longitude'];
                $onu['auth_date'] = $descParsed['auth_date'];

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
        $index = $this->buildOnuIndex($slot, $port, $onuId);

        // Get serial from auth info (col 18) — most reliable
        $authInfo = $this->snmpGet($this->zteOids['zxAnGponOnuAuthInfo'] . ".{$index}") ?? '';
        $serialNumber = $this->parseAuthInfoSerial($authInfo);

        // Fallback to binary serial (col 6)
        if (empty($serialNumber)) {
            $serialRaw = $this->snmpGet($this->zteOids['zxAnGponOnuSerialNumber'] . ".{$index}") ?? '';
            $serialNumber = $this->parseZteSerialNumber($serialRaw);
        }

        // Extract vendor from serial (first 4 chars)
        $vendor = strlen($serialNumber) >= 4 ? substr($serialNumber, 0, 4) : null;

        $info = [
            'slot' => $slot,
            'port' => $port,
            'onu_id' => $onuId,
            'serial_number' => $serialNumber,
            'status' => $this->runStatusMap[$this->snmpGet($this->zteOids['zxAnGponOnuRunStatus'] . ".{$index}")] ?? 'unknown',
            'admin_status' => $this->snmpGet($this->zteOids['zxAnGponOnuAdminStatus'] . ".{$index}"),
            'name' => $this->snmpGet($this->zteOids['zxAnGponOnuName'] . ".{$index}"),
            'onu_type' => $this->snmpGet($this->zteOids['zxAnGponOnuType'] . ".{$index}"),
            'vendor' => $vendor,
            'distance' => $this->parseDistance($this->snmpGet($this->zteOids['zxAnGponOnuDistance'] . ".{$index}")),
            'software_version' => $this->snmpGet($this->zteOids['zxAnGponOnuSoftwareVer'] . ".{$index}"),
            'hardware_version' => $this->snmpGet($this->zteOids['zxAnGponOnuHardwareVer'] . ".{$index}"),
            'line_profile' => $this->snmpGet($this->zteOids['zxAnGponOnuLineProfile'] . ".{$index}"),
            'service_profile' => $this->snmpGet($this->zteOids['zxAnGponOnuServiceProfile'] . ".{$index}"),
        ];

        // Get optical info via CLI (SNMP DDM not reliable on this firmware)
        $optical = $this->getOnuOpticalInfoViaCli($slot, $port, $onuId);
        
        return array_merge($info, $optical);
    }

    /**
     * Get ONU optical/signal info via CLI (more reliable than SNMP DDM)
     */
    public function getOnuOpticalInfoViaCli(int $slot, int $port, int $onuId): array
    {
        $default = [
            'olt_rx_power' => null,
            'tx_power' => null,
            'rx_power' => null,
            'temperature' => null,
            'voltage' => null,
            'bias_current' => null,
            'distance' => null,
        ];

        try {
            if (!$this->supportsTelnet() && !$this->supportsSsh()) {
                return $default;
            }

            $output = $this->executeBatchCliCommands([
                "show pon power attenuation gpon-onu_1/{$slot}/{$port}:{$onuId}",
                "show gpon onu detail-info gpon-onu_1/{$slot}/{$port}:{$onuId}",
            ]);

            // Parse attenuation table:
            // up    Rx :-26.347(dbm)    Tx:2.427(dbm)    28.774(dB)   => OLT Rx, ONU Tx
            // down  Tx :6.983(dbm)      Rx:-20.606(dbm)  27.589(dB)   => OLT Tx, ONU Rx
            if (preg_match('/up\s+Rx\s*:\s*(-?[\d.]+)\(dbm\)\s+Tx\s*:\s*(-?[\d.]+)\(dbm\)/i', $output, $m)) {
                $default['olt_rx_power'] = (float) $m[1];
                $default['tx_power'] = (float) $m[2];
            }
            if (preg_match('/down\s+Tx\s*:\s*(-?[\d.]+)\(dbm\)\s+Rx\s*:\s*(-?[\d.]+)\(dbm\)/i', $output, $m)) {
                $default['rx_power'] = (float) $m[2];
            }
            // Parse distance from detail-info
            if (preg_match('/ONU Distance:\s+(\d+)m/', $output, $m)) {
                $default['distance'] = (int) $m[1];
            }

        } catch (Exception $e) {
            Log::debug("ZTE optical CLI failed for {$slot}/{$port}:{$onuId}: " . $e->getMessage());
        }

        return $default;
    }

    /**
     * Get ONU optical/signal info via SNMP (may not work on all firmware)
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        // Redirect to CLI-based method for reliability
        return $this->getOnuOpticalInfoViaCli($slot, $port, $onuId);
    }

    /**
     * Batch-fetch optical power and distance for all ONUs via CLI.
     * Uses batch commands per PON port (much faster than per-ONU queries).
     *
     * @param array $ponPorts Array of ['slot' => int, 'port' => int]
     * @return array Keyed by "slot/port:onuId" => ['olt_rx_power' => float|null, 'rx_power' => float|null, 'distance' => int|null]
     */
    public function getBatchOpticalAndDistance(array $ponPorts): array
    {
        $result = [];

        if (empty($ponPorts) || (!$this->supportsTelnet() && !$this->supportsSsh())) {
            return $result;
        }

        try {
            // Build command list: olt-rx and onu-rx per port (2 commands per port)
            $commands = [];
            $commandTypes = [];
            foreach ($ponPorts as $pp) {
                $slot = $pp['slot'];
                $port = $pp['port'];
                $commands[] = "show pon power olt-rx gpon-olt_1/{$slot}/{$port}";
                $commandTypes[] = 'olt-rx';
                $commands[] = "show pon power onu-rx gpon-olt_1/{$slot}/{$port}";
                $commandTypes[] = 'onu-rx';
            }

            $output = $this->executeBatchCliCommands($commands);

            // Split output by ZTE prompt (ZXAN# or similar hostname#)
            // Each section corresponds to one command in order
            $sections = preg_split('/\w+#/', $output);

            foreach ($sections as $i => $section) {
                $type = $commandTypes[$i] ?? null;
                if (!$type) continue;

                if ($type === 'olt-rx' || $type === 'onu-rx') {
                    preg_match_all('/gpon-onu_1\/(\d+)\/(\d+):(\d+)\s+(-?[\d.]+)\(dbm\)/', $section, $matches, PREG_SET_ORDER);
                    foreach ($matches as $m) {
                        $key = "{$m[1]}/{$m[2]}:{$m[3]}";
                        if (!isset($result[$key])) {
                            $result[$key] = ['olt_rx_power' => null, 'rx_power' => null];
                        }
                        $power = round((float) $m[4], 3);
                        if ($type === 'olt-rx') {
                            $result[$key]['olt_rx_power'] = $power;
                        } else {
                            $result[$key]['rx_power'] = $power;
                        }
                    }
                }
            }

        } catch (Exception $e) {
            Log::warning("ZTE batch optical CLI failed: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Execute batch CLI commands via telnet with proper prompt handling.
     * Uses fread instead of fgets to avoid blocking on prompts without newline.
     */
    protected function executeBatchCliCommands(array $commands): string
    {
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

        stream_set_timeout($fp, 2); // Short timeout for fread

        // Login
        $this->telnetWaitFor($fp, ['Username:', 'login:', '>']);
        fwrite($fp, $this->olt->telnet_username . "\r\n");
        $this->telnetWaitFor($fp, ['Password:', 'password:']);
        fwrite($fp, $this->olt->telnet_password . "\r\n");
        sleep(1);
        $this->telnetReadUntilPrompt($fp);

        // Disable paging
        fwrite($fp, "terminal length 0\r\n");
        usleep(500000);
        $this->telnetReadUntilPrompt($fp);

        // Execute each command and collect output
        $fullOutput = '';
        foreach ($commands as $cmd) {
            // Support __WAIT__N for delays (e.g. waiting for ONU sync)
            if (preg_match('/^__WAIT__(\d+)$/', $cmd, $m)) {
                sleep((int) $m[1]);
                continue;
            }
            fwrite($fp, $cmd . "\r\n");
            usleep(300000);
            $fullOutput .= $this->telnetReadUntilPrompt($fp);
        }

        fclose($fp);

        return $fullOutput;
    }

    /**
     * Read telnet output until we see a ZTE prompt (ZXAN# or similar).
     * Uses fread to avoid blocking on prompts without trailing newline.
     */
    protected function telnetReadUntilPrompt($fp, int $timeout = 15): string
    {
        $buffer = '';
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $chunk = @fread($fp, 4096);
            if ($chunk === false || $chunk === '') {
                usleep(100000);
                continue;
            }
            $buffer .= $chunk;

            // Check for ZTE prompt at end of buffer (e.g., "ZXAN#", "ZXAN(config)#", "ZXAN(gpon-onu-mng 1/1/1:19)#")
            // Also match confirmation prompts like "[yes/no]:" or "[Y/N]:"
            if (preg_match('/[\w)][#>]\s*$/', $buffer) || preg_match('/\]\s*:\s*$/', $buffer)) {
                break;
            }
        }

        return $buffer;
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
            // Prefer CLI - more reliable for unconfigured ONU detection
            if ($this->supportsTelnet() || $this->supportsSsh()) {
                $output = $this->executeBatchCliCommands(['show gpon onu uncfg']);
                $unregistered = $this->parseUnconfiguredOnuOutput($output);
            }

            // SNMP: enrich with model via zxAnGponOnuEquipmentId (OID indexed by ponIfIndex.onuId)
            // The OLT learns the equipment ID via OMCI even for unconfigured ONUs.
            if (!empty($unregistered)) {
                try {
                    $equipmentIds = $this->snmpWalk($this->zteOids['zxAnGponOnuEquipmentId']);

                    foreach ($unregistered as &$entry) {
                        if (!empty($entry['onu_type'])) continue;
                        if (empty($entry['onu_id'])) continue;

                        $index = $this->buildOnuIndex($entry['slot'], $entry['port'], $entry['onu_id']);
                        $lookupOid = $this->zteOids['zxAnGponOnuEquipmentId'] . '.' . $index;

                        // Search for this index in walked results
                        foreach ($equipmentIds as $oid => $model) {
                            if (str_ends_with($oid, '.' . $index)) {
                                $model = trim($model, " \t\n\r\0\x0B\"");
                                if ($model && $model !== '0') {
                                    $entry['onu_type'] = $model;
                                }
                                break;
                            }
                        }
                    }
                    unset($entry);
                } catch (Exception $e) {
                    Log::warning("ZTE uncfg SNMP equipment ID lookup failed: " . $e->getMessage());
                }
            }

            // Pure SNMP fallback if CLI not available — walk equipment IDs
            if (empty($unregistered) && !$this->supportsTelnet() && !$this->supportsSsh()) {
                // Without CLI, we can't reliably discover unconfigured ONUs via SNMP alone
                // (the uncfg ONU table OIDs vary by firmware). Log a warning.
                Log::warning("ZTE: CLI unavailable, unconfigured ONU discovery limited");
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
            $trafficProfile = $params['traffic_profile'] ?? 'SMARTOLT-1G-DOWN';
            $tcontId = $params['tcont_id'] ?? 1;
            $gemPort = $params['gem_port'] ?? 1;
            $serviceId = $params['service_id'] ?? 1;
            $serviceMode = $params['service_port_mode'] ?? 'tag';

            // Determine ONU ID (auto-assign if not provided)
            $onuId = $params['onu_id'] ?? $this->getNextAvailableOnuId($slot, $port);

            // Determine ONU type: use equipment ID from scan, or fall back to vendor prefix
            $rawOnuType = $params['onu_type'] ?? null;
            $onuType = $rawOnuType
                ? $this->mapEquipmentIdToOltType($rawOnuType)
                : $this->guessOnuType($serialNumber);

            // Build CLI commands — ZTE C320 V2.1.0 syntax
            // Interface format: gpon-olt_ and gpon-onu_ (with hyphen before olt/onu)
            $commands = [
                "configure terminal",
                "interface gpon-olt_1/{$slot}/{$port}",
                "onu {$onuId} type {$onuType} sn {$serialNumber}",
                "exit",
            ];

            // Configure ONU interface (tcont, gemport, service-port)
            $vlan = $params['vlan'] ?? null;
            $mgmtVlan = $params['mgmt_vlan'] ?? null;

            $commands[] = "interface gpon-onu_1/{$slot}/{$port}:{$onuId}";
            $commands[] = "name {$name}";

            // T-CONT 1: Internet traffic
            $commands[] = "tcont {$tcontId} profile {$lineProfile}";
            $commands[] = "gemport {$gemPort} tcont {$tcontId}";
            // Traffic shaping (downstream limit on gemport)
            $commands[] = "gemport {$gemPort} traffic-limit downstream {$trafficProfile}";

            // Service-port for internet VLAN
            if ($vlan) {
                $commands[] = "service-port 1 vport 1 user-vlan {$vlan} vlan {$vlan}";
            }

            // T-CONT 2 + gemport 2 for management VLAN (TR069)
            if ($mgmtVlan) {
                $commands[] = "tcont 2 profile SMARTOLT-VOIPMNG-10M";
                $commands[] = "gemport 2 tcont 2";
                $commands[] = "gemport 2 traffic-limit downstream SMARTOLT-VOIPMNG-10M";
                $commands[] = "service-port 2 vport 2 user-vlan {$mgmtVlan} vlan {$mgmtVlan}";
            }

            $commands[] = "exit";

            // Wait for ONU to synchronize before configuring pon-onu-mng
            $commands[] = "__WAIT__8";

            // pon-onu-mng context: service, vlan, WAN, ACS config
            $pppoeUser = $params['pppoe_username'] ?? null;
            $pppoePwd = $params['pppoe_password'] ?? '';
            $acsUrl = $params['acs_url'] ?? config('services.genieacs.cwmp_url', 'http://172.10.10.254:7547');
            $acsUser = config('services.genieacs.cwmp_username', '');
            $acsPwd = config('services.genieacs.cwmp_password', '');

            if ($vlan || $mgmtVlan) {
                $commands[] = "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}";

                // VoIP protocol (required by SmartOLT pattern)
                $commands[] = "voip protocol sip";

                // SmartOLT-style flow-based VLAN configuration
                if ($mgmtVlan) {
                    $commands[] = "flow 2 switch switch_0/1";
                }

                // Flow modes — tag-filter for all active flows
                $commands[] = "flow mode 1 tag-filter vlan-filter untag-filter discard";
                if ($mgmtVlan) {
                    $commands[] = "flow mode 2 tag-filter vlan-filter untag-filter discard";
                }

                // Flow VLAN assignments
                if ($vlan) {
                    $commands[] = "flow 1 pri 0 vlan {$vlan}";
                }
                if ($mgmtVlan) {
                    $commands[] = "flow 2 pri 2 vlan {$mgmtVlan}";
                }

                // Bind gemports to flows
                $commands[] = "gemport 1 flow 1";
                if ($mgmtVlan) {
                    $commands[] = "gemport 2 flow 2";
                }

                // Switchport bindings
                $commands[] = "switchport-bind switch_0/1 iphost 1";
                if ($mgmtVlan) {
                    $commands[] = "switchport-bind switch_0/1 iphost 2";
                    $commands[] = "switchport-bind switch_0/1 veip 1";
                }

                // Management IP host — DHCP for TR069
                if ($mgmtVlan) {
                    $commands[] = "ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable";
                }

                // PPPoE internet (optional)
                if ($pppoeUser && $vlan) {
                    $commands[] = "pppoe 1 nat enable user {$pppoeUser} password {$pppoePwd}";
                }

                // VLAN filter modes
                $commands[] = "vlan-filter-mode iphost 1 tag-filter vlan-filter untag-filter discard";
                if ($mgmtVlan) {
                    $commands[] = "vlan-filter-mode iphost 2 tag-filter vlan-filter untag-filter discard";
                }

                // VLAN filters
                if ($vlan) {
                    $commands[] = "vlan-filter iphost 1 pri 0 vlan {$vlan}";
                }
                if ($mgmtVlan) {
                    $commands[] = "vlan-filter iphost 2 pri 2 vlan {$mgmtVlan}";
                }

                // DHCP on all ETH UNI ports
                $commands[] = "dhcp-ip ethuni eth_0/1 from-onu";
                $commands[] = "dhcp-ip ethuni eth_0/2 from-onu";
                $commands[] = "dhcp-ip ethuni eth_0/3 from-onu";
                $commands[] = "dhcp-ip ethuni eth_0/4 from-onu";

                // TR069/ACS via VEIP
                if ($mgmtVlan) {
                    $commands[] = "veip 1 port udp 1232 host 2";
                    $commands[] = "tr069-mgmt 1 state unlock";
                    // ACS URL with optional basic auth credentials
                    $acsCmd = "tr069-mgmt 1 acs {$acsUrl}";
                    if ($acsUser && $acsPwd) {
                        $acsCmd .= " validate basic username {$acsUser} password {$acsPwd}";
                    }
                    $commands[] = $acsCmd;
                    $commands[] = "tr069-mgmt 1 tag pri 2 vlan {$mgmtVlan}";
                }

                // Security — allow web + management access
                $commands[] = "security-mgmt 998 state enable mode forward ingress-type lan protocol web https";
                $commands[] = "security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069";

                $commands[] = "exit";
            }

            $commands[] = "exit";
            $commands[] = "write";

            // Log commands for debugging
            \Log::info('ZTE registerOnu commands', ['commands' => $commands]);

            // Execute commands via batch CLI (uses telnetReadUntilPrompt to avoid hanging)
            $output = $this->executeBatchCliCommands($commands);

            // Log telnet output for debugging
            \Log::info('ZTE registerOnu telnet output', ['output' => $output]);

            // Check if ONU registration itself failed (not just pon-onu-mng config errors)
            $registrationFailed = !str_contains($output, 'Successful') 
                && (str_contains($output, 'Not support this ONU') 
                    || str_contains($output, 'already exist')
                    || str_contains($output, 'No sn match'));

            if ($registrationFailed) {
                $result['message'] = "Registration failed: {$output}";
            } else {
                $result['success'] = true;
                $result['onu_id'] = $onuId;
                $result['message'] = "ONU registered successfully at 1/{$slot}/{$port}:{$onuId}";
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
                "interface gpon-olt_1/{$slot}/{$port}",
                "no onu {$onuId}",
                "exit",
                "exit",
                "write",
            ];

            $output = $this->executeBatchCliCommands($commands);

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
                "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}",
                "reboot",
                "yes",
                "exit",
                "exit",
            ];

            $output = $this->executeBatchCliCommands($commands);

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
        $index = $this->buildOnuIndex($slot, $port, $onuId);

        $inOctets = (int) ($this->snmpGet($this->zteOids['zxAnGponOnuPerfInOctets'] . ".{$index}") ?? 0);
        $outOctets = (int) ($this->snmpGet($this->zteOids['zxAnGponOnuPerfOutOctets'] . ".{$index}") ?? 0);

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
                "interface gpon-onu_1/{$slot}/{$port}:{$onuId}",
            ];

            // Configure tcont and gemport if not exists
            if (isset($serviceConfig['bandwidth_profile'])) {
                $commands[] = "tcont {$gemPort} profile {$serviceConfig['bandwidth_profile']}";
            }
            $commands[] = "gemport {$gemPort} name gem{$gemPort} tcont {$gemPort}";
            $commands[] = "exit";

            // Configure service
            $commands[] = "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}";
            $commands[] = "service {$serviceId} gemport {$gemPort} vlan {$vlan}";
            $commands[] = "vlan port eth_0/1 mode {$mode} vlan {$vlan}";

            // Add PPPoE if specified
            if (isset($serviceConfig['pppoe']) && $serviceConfig['pppoe']) {
                $commands[] = "pppoe 1 nat enable user {$serviceConfig['pppoe_username']} password {$serviceConfig['pppoe_password']}";
            }

            $commands[] = "exit";
            $commands[] = "exit";
            $commands[] = "write";

            $output = $this->executeBatchCliCommands($commands);

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
     * Re-apply TCONT + Traffic profile on an already-registered ONU (without unregistering).
     * Use this to fix profile issues (e.g. ONU registered with 'default' 10Mbps profile).
     */
    public function reapplyProfiles(int $slot, int $port, int $onuId, string $tcontProfile, string $trafficProfile, int $tcontId = 1, int $gemPort = 1): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $commands = [
                "configure terminal",
                "interface gpon-onu_1/{$slot}/{$port}:{$onuId}",
                "tcont {$tcontId} profile {$tcontProfile}",
                "gemport {$gemPort} traffic-limit downstream {$trafficProfile}",
                "exit",
                "exit",
                "write",
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (stripos($output, 'error') !== false || stripos($output, 'fail') !== false || stripos($output, 'invalid') !== false) {
                $result['message'] = "Re-apply profile gagal: {$output}";
            } else {
                $result['success'] = true;
                $result['message'] = "Profile berhasil diterapkan ulang (TCONT: {$tcontProfile}, Traffic: {$trafficProfile})";
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE reapplyProfiles error: " . $e->getMessage());
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

            // Bulk-walk all ONUs (fast: single SNMP walk per OID column)
            $allOnus = $this->getAllOnus();

            // Bulk-walk traffic counters for all ONUs
            $bulkInOctets = $this->snmpWalk($this->zteOids['zxAnGponOnuPerfInOctets']);
            $bulkOutOctets = $this->snmpWalk($this->zteOids['zxAnGponOnuPerfOutOctets']);

            // Extract unique PON ports from ONU data for batch CLI
            $uniquePorts = [];
            foreach ($allOnus as $onu) {
                $portKey = "{$onu['slot']}/{$onu['port']}";
                if (!isset($uniquePorts[$portKey])) {
                    $uniquePorts[$portKey] = ['slot' => $onu['slot'], 'port' => $onu['port']];
                }
            }

            // Batch CLI: fetch optical power + distance for all ONUs (2-3 commands per port)
            $batchOptical = $this->getBatchOpticalAndDistance(array_values($uniquePorts));
            $result['batch_optical_count'] = count($batchOptical);

            // Cache for auto-created zones and ODPs (avoid repeated queries)
            $zoneCache = []; // name => Zone model
            $odpCache = [];  // "zone_id|odp_name" => Odp model

            foreach ($allOnus as $onuData) {
                try {
                    $index = $this->buildOnuIndex($onuData['slot'], $onuData['port'], $onuData['onu_id']);

                    // Extract vendor from serial (first 4 chars)
                    $vendor = !empty($onuData['serial_number']) && strlen($onuData['serial_number']) >= 4
                        ? substr($onuData['serial_number'], 0, 4) : null;

                    // Merge bulk traffic data
                    $inOctets = (int) ($bulkInOctets[$this->zteOids['zxAnGponOnuPerfInOctets'] . ".{$index}"] ?? 0);
                    $outOctets = (int) ($bulkOutOctets[$this->zteOids['zxAnGponOnuPerfOutOctets'] . ".{$index}"] ?? 0);

                    // Merge batch CLI optical power + distance
                    $cliKey = "{$onuData['slot']}/{$onuData['port']}:{$onuData['onu_id']}";
                    $cliData = $batchOptical[$cliKey] ?? [];

                    // Auto-create Zone and ODP from parsed description
                    $zoneId = null;
                    $odpId = null;

                    if (!empty($onuData['zone_name'])) {
                        $zoneName = $onuData['zone_name'];
                        if (!isset($zoneCache[$zoneName])) {
                            $zoneCache[$zoneName] = Zone::firstOrCreate(
                                ['olt_id' => $this->olt->id, 'name' => $zoneName],
                            );
                        }
                        $zoneId = $zoneCache[$zoneName]->id;
                    }

                    if (!empty($onuData['odp_name']) && $zoneId) {
                        $odpKey = "{$zoneId}|{$onuData['odp_name']}";
                        if (!isset($odpCache[$odpKey])) {
                            $odp = Odp::firstOrCreate(
                                ['olt_id' => $this->olt->id, 'name' => $onuData['odp_name']],
                                [
                                    'pop_id' => $this->olt->pop_id,
                                    'zone_id' => $zoneId,
                                    'code' => 'ODP-' . strtoupper(Str::slug($onuData['zone_name'] . '-' . $onuData['odp_name'])),
                                    'status' => 'active',
                                    'total_ports' => 8,
                                ]
                            );
                            // Update zone_id if ODP existed but had no zone
                            if (!$odp->zone_id) {
                                $odp->update(['zone_id' => $zoneId]);
                            }
                            // Update coordinates if available and ODP doesn't have them yet
                            if (!empty($onuData['latitude']) && !$odp->latitude) {
                                $odp->update([
                                    'latitude' => $onuData['latitude'],
                                    'longitude' => $onuData['longitude'],
                                ]);
                            }
                            $odpCache[$odpKey] = $odp;
                        }
                        $odpId = $odpCache[$odpKey]->id;
                    }

                    // Save to database using bulk-walked data + batch CLI optical
                    $onu = $this->saveOnuToDatabase([
                        'olt_id' => $this->olt->id,
                        'slot' => $onuData['slot'],
                        'port' => $onuData['port'],
                        'onu_id' => $onuData['onu_id'],
                        'serial_number' => $onuData['serial_number'],
                        'status' => $onuData['status'] ?? 'unknown',
                        'name' => $onuData['name'] ?? null,
                        'onu_type' => $onuData['onu_type'] ?? null,
                        'description' => $onuData['description'] ?? null,
                        'vendor' => $vendor,
                        'distance' => $cliData['distance'] ?? null,
                        'rx_power' => $cliData['rx_power'] ?? null,
                        'olt_rx_power' => $cliData['olt_rx_power'] ?? null,
                        'zone_id' => $zoneId,
                        'odp_id' => $odpId,
                        'in_octets' => $inOctets,
                        'out_octets' => $outOctets,
                        'config_status' => 'registered',
                    ]);

                    // Save signal history with CLI optical data
                    $this->saveSignalHistory($onu, [
                        'rx_power' => $cliData['rx_power'] ?? null,
                        'olt_rx_power' => $cliData['olt_rx_power'] ?? null,
                        'status' => $onuData['status'] ?? null,
                        'distance' => $cliData['distance'] ?? null,
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
     * Guess ONU type from serial number vendor prefix
     */
    /**
     * Map equipment ID (from SNMP) to OLT profile type name.
     * E.g. 'F670LV9.0' -> 'F670L', 'HG8245H' -> 'HG8245H'
     */
    protected function mapEquipmentIdToOltType(string $equipmentId): string
    {
        $id = trim($equipmentId);

        // Known OLT profile types (order matters: longer/more specific first)
        $knownProfiles = [
            'EG8141H5', 'EG8145V5', 'EG8143H5',
            'HG8245H', 'HG8245Q', 'HG8245W', 'HG8546M',
            'F670L', 'F660', 'F609', 'F6600',
            'OPEN_ZTE', 'OPEN_FIBERHOME', 'OPEN_NOKIA',
            'ALL',
        ];

        $upper = strtoupper($id);
        foreach ($knownProfiles as $profile) {
            if (str_starts_with($upper, strtoupper($profile))) {
                return $profile;
            }
        }

        // Fiberhome HG6xxx series (e.g. HG6145F, HG6145E) → use generic Fiberhome profile
        if (preg_match('/^HG6/i', $id)) {
            return 'OPEN_FIBERHOME';
        }

        // Fiberhome AN5506 series (e.g. AN5506-04-F)
        if (preg_match('/^AN5506/i', $id)) {
            return 'OPEN_FIBERHOME';
        }

        // Fallback: strip version suffix like V9.0, V5, etc.
        $stripped = preg_replace('/V\d+(\.\d+)?$/i', '', $id);
        if ($stripped && $stripped !== $id) {
            return $stripped;
        }

        return $id;
    }

    protected function guessOnuType(string $serialNumber, ?string $equipmentId = null): string
    {
        // If equipment ID is available, map it to OLT profile type
        if ($equipmentId) {
            return $this->mapEquipmentIdToOltType($equipmentId);
        }

        $vendor = strtoupper(substr($serialNumber, 0, 4));

        return match ($vendor) {
            'HWTC' => 'HG8245H',
            'ZTEG' => 'OPEN_ZTE',
            'ALCL' => 'OPEN_NOKIA',
            'FHTT' => 'OPEN_FIBERHOME',
            default => 'ALL',
        };
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
            $line = trim($line);

            // Format 1: gpon-onu_1/1/1:1  ZTEGD6D8B342  unknown
            if (preg_match('/gpon-onu_(\d+)\/(\d+)\/(\d+):(\d+)\s+(\w{4,16})\s+(\S+)?/i', $line, $matches)) {
                $rawType = strtoupper(trim($matches[6] ?? ''));
                $onus[] = [
                    'slot'          => (int) $matches[2],
                    'port'          => (int) $matches[3],
                    'pon_port'      => $matches[2] . '/' . $matches[3],
                    'onu_id'        => (int) $matches[4],
                    'serial_number' => strtoupper($matches[5]),
                    'onu_type'      => ($rawType && $rawType !== 'UNKNOWN') ? $rawType : null,
                    'config_status' => 'unregistered',
                ];
                continue;
            }

            // Format 2: gpon-olt_1/1  1  ZTEG12345678  F663N   (type optional)
            if (preg_match('/gpon[-_]olt[-_](\d+)\/(\d+)\s+\d+\s+(\w+)(?:\s+(\S+))?/', $line, $matches)) {
                $rawType = strtoupper(trim($matches[4] ?? ''));
                $onus[] = [
                    'slot'          => (int) $matches[1],
                    'port'          => (int) $matches[2],
                    'pon_port'      => $matches[1] . '/' . $matches[2],
                    'serial_number' => strtoupper($matches[3]),
                    'onu_type'      => ($rawType && $rawType !== 'UNKNOWN') ? $rawType : null,
                    'config_status' => 'unregistered',
                ];
                continue;
            }

            // Format 3: 1/1/1:1  ZTEG12345678  F663N
            if (preg_match('/(\d+)\/(\d+)\/(\d+):(\d+)\s+(\w{4,16})\s+(\S+)?/i', $line, $matches)) {
                $rawType = strtoupper(trim($matches[6] ?? ''));
                $onus[] = [
                    'slot'          => (int) $matches[2],
                    'port'          => (int) $matches[3],
                    'pon_port'      => $matches[2] . '/' . $matches[3],
                    'onu_id'        => (int) $matches[4],
                    'serial_number' => strtoupper($matches[5]),
                    'onu_type'      => ($rawType && $rawType !== 'UNKNOWN') ? $rawType : null,
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
                "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}",
            ];

            if (isset($config['vlan'])) {
                $commands[] = "mvlan {$config['vlan']}";
            }

            if (isset($config['ip'])) {
                $commands[] = "ip address {$config['ip']} mask 255.255.255.0 vlan {$config['vlan']}";
            }

            $commands[] = "exit";
            $commands[] = "exit";
            $commands[] = "write";

            $output = $this->executeBatchCliCommands($commands);

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
        return $this->executeBatchCliCommands([
            "show running-config interface gpon-onu_1/{$slot}/{$port}:{$onuId}",
        ]);
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
                "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}",
                "restore factory",
                "reboot",
                "yes",
                "exit",
                "exit",
            ];

            $output = $this->executeBatchCliCommands($commands);

            // Check for ONU unavailable or other errors
            if (preg_match('/ONU is unavailable/i', $output)) {
                $result['message'] = "Factory reset gagal: ONU sedang offline.";
            } elseif (preg_match('/%Error\s+\S+\s*:\s*(.+)/i', $output, $m)) {
                $result['message'] = "Factory reset gagal: " . trim($m[1]);
            } else {
                $result['success'] = true;
                $result['message'] = "Factory reset berhasil. ONU {$slot}/{$port}:{$onuId} akan restart ke default setting.";
            }

        } catch (Exception $e) {
            $result['message'] = 'Factory reset error: ' . $e->getMessage();
        }

        return $result;
    }

    // =========================================================================
    // READ-ONLY INFRASTRUCTURE SYNC METHODS
    // =========================================================================

    /**
     * Sync card/shelf information from OLT (READ-ONLY)
     * Parses `show card` output to update olt_cards table
     */
    public function syncCards(): array
    {
        $result = ['synced' => 0, 'errors' => []];

        try {
            $output = $this->executeBatchCliCommands([
                'show card',
            ]);

            $cards = $this->parseShowCard($output);

            foreach ($cards as $cardData) {
                try {
                    OltCard::updateOrCreate(
                        [
                            'olt_id' => $this->olt->id,
                            'rack' => $cardData['rack'],
                            'shelf' => $cardData['shelf'],
                            'slot' => $cardData['slot'],
                        ],
                        [
                            'configured_type' => $cardData['configured_type'],
                            'real_type' => $cardData['real_type'],
                            'port_count' => $cardData['port_count'],
                            'hardware_version' => $cardData['hardware_version'],
                            'software_version' => $cardData['software_version'],
                            'status' => $cardData['status'],
                            'role' => $cardData['role'],
                            'last_sync_at' => now(),
                        ]
                    );
                    $result['synced']++;
                } catch (Exception $e) {
                    $result['errors'][] = "Slot {$cardData['slot']}: " . $e->getMessage();
                }
            }

            // Link PON ports to their GPON cards
            $gponCards = OltCard::where('olt_id', $this->olt->id)
                ->where('role', 'gpon')
                ->get();

            foreach ($gponCards as $card) {
                $this->olt->ponPorts()
                    ->where('slot', $card->slot)
                    ->update(['card_id' => $card->id]);
            }

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("ZTE syncCards error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Parse `show card` CLI output
     */
    protected function parseShowCard(string $output): array
    {
        $cards = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match format: "1  1  1  GTGH    GTGHK   16   V2.1.0  V2.1.0  INSERVICE"
            // or: "1  1  3  SMXA    SMXA    3    V2.1.0  V2.1.0  INSERVICE"
            if (preg_match('/^(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)/i', $line, $m)) {
                $configuredType = $m[4];
                $realType = $m[5];
                $role = $this->detectCardRole($configuredType, $realType);

                $cards[] = [
                    'rack' => (int)$m[1],
                    'shelf' => (int)$m[2],
                    'slot' => (int)$m[3],
                    'configured_type' => $configuredType !== '--' ? $configuredType : null,
                    'real_type' => $realType !== '--' ? $realType : null,
                    'port_count' => (int)$m[6],
                    'hardware_version' => $m[7] !== '--' ? $m[7] : null,
                    'software_version' => $m[8] !== '--' ? $m[8] : null,
                    'status' => strtolower($m[9]),
                    'role' => $role,
                ];
            }
        }

        return $cards;
    }

    /**
     * Detect card role from its type string
     */
    protected function detectCardRole(string $configuredType, string $realType): string
    {
        $type = strtoupper($realType ?: $configuredType);

        // GPON cards: GTGH, GTGO, GTGV, GFGI, GFGH, etc.
        if (preg_match('/^(GT|GF)/i', $type)) {
            return 'gpon';
        }
        // EPON cards: ETGH, ETGO, etc.
        if (preg_match('/^ET/i', $type)) {
            return 'epon';
        }
        // Management/uplink: SMXA, SCXN, SCXL, HUVQ, etc.
        if (preg_match('/^(SM|SC|HU)/i', $type)) {
            return 'management';
        }
        // Power: PRWH, PRWG
        if (preg_match('/^PR/i', $type)) {
            return 'power';
        }
        // Fan: FANA, FANB
        if (preg_match('/^FAN/i', $type)) {
            return 'fan';
        }

        return 'other';
    }

    /**
     * Sync VLAN database from OLT (READ-ONLY)
     * Parses `show vlan` output to update olt_vlans table
     */
    public function syncVlans(): array
    {
        $result = ['synced' => 0, 'service_ports' => 0, 'errors' => []];

        try {
            // Step 1: Get VLAN list + service-port data in one session
            $output = $this->executeBatchCliCommands([
                'show vlan summary',
                'show service-port all',
            ]);

            // Parse VLAN IDs from summary
            $vlanIds = $this->parseVlanSummary($output);

            // Parse full service-port data (global + per-slot)
            $svcData = $this->parseServicePortData($output);
            $svcPortCounts = $svcData['global'];
            $perSlotVlans = $svcData['per_slot'];
            $result['service_ports'] = array_sum($svcPortCounts);

            // Step 2: Get detail for each VLAN (description, ports, etc.)
            if (!empty($vlanIds)) {
                $detailCommands = [];
                foreach ($vlanIds as $vid) {
                    $detailCommands[] = "show vlan {$vid}";
                }
                $detailOutput = $this->executeBatchCliCommands($detailCommands);
                $vlanDetails = $this->parseVlanDetails($detailOutput);
            } else {
                $vlanDetails = [];
            }

            // Also get uplink port VLAN associations
            $uplinkVlans = $this->getUplinkVlanMapping();

            // Build VLAN name lookup for card vlan_config display
            $vlanNames = [];
            foreach ($vlanDetails as $v) {
                $vlanNames[$v['vlan_id']] = $v['name'];
            }
            // Fallback for VLANs found in service-port but not in detail
            foreach ($vlanIds as $vid) {
                if (!isset($vlanNames[$vid])) {
                    $vlanNames[$vid] = 'VLAN' . str_pad($vid, 4, '0', STR_PAD_LEFT);
                }
            }

            // Merge VLAN IDs from summary + any extra from service-port data
            $allVlanIds = array_unique(array_merge($vlanIds, array_keys($svcPortCounts)));
            sort($allVlanIds);

            foreach ($allVlanIds as $vlanId) {
                try {
                    $detail = $vlanDetails[$vlanId] ?? null;

                    // Find uplink ports carrying this VLAN
                    $uplinkPorts = [];
                    foreach ($uplinkVlans as $ifName => $vlanIdList) {
                        if (in_array($vlanId, $vlanIdList)) {
                            $uplinkPorts[] = $ifName;
                        }
                    }

                    $updateData = [
                        'name' => $detail['name'] ?? $vlanNames[$vlanId] ?? "VLAN{$vlanId}",
                        'uplink_ports' => !empty($uplinkPorts) ? $uplinkPorts : null,
                        'service_port_count' => $svcPortCounts[$vlanId] ?? 0,
                        'is_synced' => true,
                        'last_sync_at' => now(),
                    ];

                    // Add detail data if available
                    if ($detail) {
                        $desc = $detail['description'] ?? null;
                        if ($desc && $desc !== 'N/A') {
                            $updateData['description'] = $desc;
                        }
                        if (!empty($detail['tagged_ports'])) {
                            $updateData['tagged_ports'] = $detail['tagged_ports'];
                        }
                        if (!empty($detail['untagged_ports'])) {
                            $updateData['untagged_ports'] = $detail['untagged_ports'];
                        }
                        if ($detail['multicast_mode'] ?? null) {
                            $updateData['multicast_mode'] = $detail['multicast_mode'];
                        }
                    }

                    OltVlan::updateOrCreate(
                        [
                            'olt_id' => $this->olt->id,
                            'vlan_id' => $vlanId,
                        ],
                        $updateData
                    );
                    $result['synced']++;
                } catch (Exception $e) {
                    $result['errors'][] = "VLAN {$vlanId}: " . $e->getMessage();
                }
            }

            // Update GPON cards with their VLAN config
            $gponCards = OltCard::where('olt_id', $this->olt->id)
                ->where('role', 'gpon')
                ->get();

            foreach ($gponCards as $card) {
                $slotVlans = $perSlotVlans[$card->slot] ?? [];
                if (!empty($slotVlans)) {
                    // Build structured vlan_config: [{vlan_id, name, count, ports}]
                    $vlanConfig = [];
                    foreach ($slotVlans as $vlanId => $info) {
                        $vlanConfig[] = [
                            'vlan_id' => $vlanId,
                            'name' => $vlanNames[$vlanId] ?? 'VLAN' . $vlanId,
                            'service_ports' => $info['count'],
                            'pon_ports' => $info['ports'],
                        ];
                    }
                    // Sort by service_ports desc
                    usort($vlanConfig, fn($a, $b) => $b['service_ports'] <=> $a['service_ports']);
                    $card->update(['vlan_config' => $vlanConfig]);
                } else {
                    $card->update(['vlan_config' => null]);
                }
            }

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("ZTE syncVlans error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Parse `show vlan summary` output to get list of VLAN IDs.
     * Output format: "All created vlan num: 11\nDetails are following:\n    1,11-12,20,100,111,334-335,338,1035,2035"
     */
    protected function parseVlanSummary(string $output): array
    {
        $vlanIds = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            // Match the detail line with comma-separated VLAN IDs and ranges
            if (preg_match('/^[\d,\-]+$/', $line) && strpos($line, ',') !== false) {
                $parts = explode(',', $line);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (preg_match('/^(\d+)-(\d+)$/', $part, $m)) {
                        // Range: 334-335
                        for ($i = (int)$m[1]; $i <= (int)$m[2]; $i++) {
                            if ($i >= 1 && $i <= 4094) {
                                $vlanIds[] = $i;
                            }
                        }
                    } elseif (is_numeric($part)) {
                        $vid = (int)$part;
                        if ($vid >= 1 && $vid <= 4094) {
                            $vlanIds[] = $vid;
                        }
                    }
                }
            }
        }

        return array_unique($vlanIds);
    }

    /**
     * Parse multiple `show vlan {id}` outputs into structured detail data.
     * Each VLAN detail block:
     *   vlanid          :111
     *   name            :VLAN0111
     *   description     :ACS
     *   multicast-packet:flood-unknown
     *   tpid            :0x8100
     *   vlan connect    :disable
     *   port(untagged):
     *     ...
     *   port(tagged):
     *     gpon-onu_1/1/1:1-8:2
     *     xgei_1/3/2
     */
    protected function parseVlanDetails(string $output): array
    {
        $vlans = []; // vlan_id => detail array
        $lines = explode("\n", $output);
        $currentVlan = null;
        $section = null; // 'tagged' or 'untagged'

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // vlanid :111
            if (preg_match('/^vlanid\s*:\s*(\d+)/', $trimmed, $m)) {
                $currentVlan = (int)$m[1];
                $vlans[$currentVlan] = [
                    'vlan_id' => $currentVlan,
                    'name' => null,
                    'description' => null,
                    'multicast_mode' => null,
                    'tagged_ports' => [],
                    'untagged_ports' => [],
                ];
                $section = null;
            }
            if (!$currentVlan) continue;

            // name :VLAN0111
            if (preg_match('/^name\s*:\s*(.+)/', $trimmed, $m)) {
                $vlans[$currentVlan]['name'] = trim($m[1]);
            }
            // description :ACS
            elseif (preg_match('/^description\s*:\s*(.+)/', $trimmed, $m)) {
                $vlans[$currentVlan]['description'] = trim($m[1]);
            }
            // multicast-packet:flood-unknown
            elseif (preg_match('/^multicast-packet\s*:\s*(.+)/', $trimmed, $m)) {
                $vlans[$currentVlan]['multicast_mode'] = trim($m[1]);
            }
            // port(untagged):
            elseif (preg_match('/^port\s*\(\s*untagged\s*\)\s*:/', $trimmed)) {
                $section = 'untagged';
            }
            // port(tagged):
            elseif (preg_match('/^port\s*\(\s*tagged\s*\)\s*:/', $trimmed)) {
                $section = 'tagged';
            }
            // Port entries (indented lines under tagged/untagged)
            elseif ($section && $trimmed !== '' && !preg_match('/^(vlanid|name|description|multicast|tpid|vlan connect|port\()/', $trimmed)) {
                // Check if this is a port line or a prompt/command line
                if (preg_match('/^(gpon-onu|gei|xgei)/', $trimmed) || preg_match('/^\S+_\d+\/\d+/', $trimmed)) {
                    // Split by whitespace — multiple port entries can be on one line
                    $portEntries = preg_split('/\s{2,}/', $trimmed);
                    foreach ($portEntries as $entry) {
                        $entry = trim($entry);
                        if ($entry !== '') {
                            $key = $section === 'tagged' ? 'tagged_ports' : 'untagged_ports';
                            $vlans[$currentVlan][$key][] = $entry;
                        }
                    }
                }
                // Reset section on prompt or new block
                elseif (preg_match('/[#>]\s*$/', $trimmed) || preg_match('/^show\s/', $trimmed)) {
                    $section = null;
                    $currentVlan = null;
                }
            }
        }

        return $vlans;
    }

    /**
     * Parse `show vlan` CLI output (legacy fallback)
     */
    protected function parseShowVlan(string $output): array
    {
        $vlans = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match: "111  VLAN0111  Enabled  Active" or similar
            if (preg_match('/^(\d+)\s+(\S+)\s+(Enabled|Disabled)\s+(Active|Inactive)/i', $line, $m)) {
                $vlanId = (int)$m[1];
                if ($vlanId >= 1 && $vlanId <= 4094) {
                    $vlans[] = [
                        'vlan_id' => $vlanId,
                        'name' => $m[2],
                    ];
                }
            }
            // Also match simpler "vlan 111" lines from vlan database
            elseif (preg_match('/^vlan\s+(\d+)/i', $line, $m)) {
                $vlanId = (int)$m[1];
                if ($vlanId >= 1 && $vlanId <= 4094) {
                    $vlans[] = [
                        'vlan_id' => $vlanId,
                        'name' => "VLAN" . str_pad($vlanId, 4, '0', STR_PAD_LEFT),
                    ];
                }
            }
        }

        // Deduplicate by vlan_id
        $unique = [];
        foreach ($vlans as $v) {
            $unique[$v['vlan_id']] = $v;
        }

        return array_values($unique);
    }

    /**
     * Parse `show service-port all` output to count service-ports per VLAN
     * Each line: "1  1  gpon-onu_1/1/1:1  gemport 1  user  vlan 335  ..." 
     * Returns: ['global' => [vlan_id => count], 'per_slot' => [slot => [vlan_id => ['count' => N, 'ports' => [port_nums]]]]]
     */
    protected function parseServicePortData(string $output): array
    {
        $global = [];  // vlan_id => count
        $perSlot = []; // slot => vlan_id => ['count' => N, 'ports' => []]
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            // Match: gpon-onu_1/SLOT/PORT:ONU ... vlan VLAN_ID
            if (preg_match('/gpon-onu_\d+\/(\d+)\/(\d+):(\d+)/', $line, $onuMatch) &&
                preg_match('/vlan\s+(\d+)/i', $line, $vlanMatch)) {
                $slot = (int)$onuMatch[1];
                $port = (int)$onuMatch[2];
                $vlanId = (int)$vlanMatch[1];

                // Global count
                $global[$vlanId] = ($global[$vlanId] ?? 0) + 1;

                // Per-slot breakdown
                if (!isset($perSlot[$slot][$vlanId])) {
                    $perSlot[$slot][$vlanId] = ['count' => 0, 'ports' => []];
                }
                $perSlot[$slot][$vlanId]['count']++;
                if (!in_array($port, $perSlot[$slot][$vlanId]['ports'])) {
                    $perSlot[$slot][$vlanId]['ports'][] = $port;
                    sort($perSlot[$slot][$vlanId]['ports']);
                }
            }
        }

        return ['global' => $global, 'per_slot' => $perSlot];
    }

    /**
     * Backward-compatible wrapper returning only global VLAN counts
     */
    protected function parseServicePortCounts(string $output): array
    {
        return $this->parseServicePortData($output)['global'];
    }

    /**
     * Get VLAN-to-uplink-port mapping from running config (READ-ONLY)
     */
    protected function getUplinkVlanMapping(): array
    {
        $mapping = []; // interface_name => [vlan_ids]

        try {
            $output = $this->executeBatchCliCommands([
                'show running-config | begin interface gei',
            ]);

            $currentIf = null;
            $lines = explode("\n", $output);

            foreach ($lines as $line) {
                $line = trim($line);

                // Match interface line: "interface gei_1/3/1" or "interface xgei_1/3/2"
                if (preg_match('/^interface\s+((x?gei)_\d+\/\d+\/\d+)/', $line, $m)) {
                    $currentIf = $m[1];
                    $mapping[$currentIf] = $mapping[$currentIf] ?? [];
                }
                // Match switchport vlan trunk line
                elseif ($currentIf && preg_match('/switchport\s+vlan\s+(\d+)\s+tag/i', $line, $m)) {
                    $mapping[$currentIf][] = (int)$m[1];
                }
                // Exit interface context on blank line or !
                elseif ($currentIf && ($line === '!' || $line === '' || str_starts_with($line, 'interface '))) {
                    if (str_starts_with($line, 'interface ')) {
                        // New interface — handled by the if above on next iteration
                        if (preg_match('/^interface\s+((x?gei)_\d+\/\d+\/\d+)/', $line, $m)) {
                            $currentIf = $m[1];
                            $mapping[$currentIf] = $mapping[$currentIf] ?? [];
                        } else {
                            $currentIf = null;
                        }
                    } elseif ($line === '!' || $line === '') {
                        $currentIf = null;
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning("ZTE getUplinkVlanMapping error: " . $e->getMessage());
        }

        return $mapping;
    }

    /**
     * Sync uplink port information from OLT (READ-ONLY)
     * Parses interface status + switchport config
     */
    public function syncUplinks(): array
    {
        $result = ['synced' => 0, 'errors' => []];

        try {
            $output = $this->executeBatchCliCommands([
                'show interface brief',
            ]);

            $interfaces = $this->parseInterfaceBrief($output);

            // Get VLAN mapping for trunk info
            $uplinkVlans = $this->getUplinkVlanMapping();

            // Get management cards to link uplinks
            $mgmtCards = OltCard::where('olt_id', $this->olt->id)
                ->whereIn('role', ['management', 'uplink'])
                ->get()
                ->keyBy('slot');

            foreach ($interfaces as $ifData) {
                try {
                    $cardId = $mgmtCards[$ifData['slot']]->id ?? null;

                    OltUplink::updateOrCreate(
                        [
                            'olt_id' => $this->olt->id,
                            'interface_name' => $ifData['interface_name'],
                        ],
                        [
                            'card_id' => $cardId,
                            'interface_type' => $ifData['interface_type'],
                            'rack' => $ifData['rack'],
                            'shelf' => $ifData['shelf'],
                            'slot' => $ifData['slot'],
                            'port' => $ifData['port'],
                            'status' => $ifData['oper_status'],
                            'admin_status' => $ifData['admin_status'],
                            'tagged_vlans' => $uplinkVlans[$ifData['interface_name']] ?? null,
                            'switchport_mode' => !empty($uplinkVlans[$ifData['interface_name']]) ? 'trunk' : null,
                            'last_sync_at' => now(),
                        ]
                    );
                    $result['synced']++;
                } catch (Exception $e) {
                    $result['errors'][] = "{$ifData['interface_name']}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("ZTE syncUplinks error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Parse `show interface brief` output for uplink/management ports
     */
    protected function parseInterfaceBrief(string $output): array
    {
        $interfaces = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match: "xgei_1/3/2  enable  up" or "gei_1/3/1  enable  up"
            if (preg_match('/^(x?gei)_(\d+)\/(\d+)\/(\d+)\s+(\w+)\s+(\w+)/i', $line, $m)) {
                $interfaces[] = [
                    'interface_name' => "{$m[1]}_{$m[2]}/{$m[3]}/{$m[4]}",
                    'interface_type' => strtolower($m[1]),
                    'rack' => (int)$m[2],
                    'shelf' => (int)$m[3],
                    'slot' => (int)$m[3],
                    'port' => (int)$m[4],
                    'admin_status' => strtolower($m[5]) === 'enable' ? 'enabled' : 'disabled',
                    'oper_status' => strtolower($m[6]) === 'up' ? 'up' : 'down',
                ];
            }
        }

        return $interfaces;
    }

    /**
     * Sync all infrastructure data from OLT (READ-ONLY)
     * Cards → VLANs → Uplinks → PON Ports (in correct dependency order)
     */
    public function syncInfrastructure(): array
    {
        $result = [
            'success' => true,
            'cards' => ['synced' => 0, 'errors' => []],
            'vlans' => ['synced' => 0, 'errors' => []],
            'uplinks' => ['synced' => 0, 'errors' => []],
            'pon_ports' => ['synced' => 0, 'errors' => []],
            'service_ports' => 0,
        ];

        try {
            // 1. Sync cards first (uplinks depend on card_id)
            $result['cards'] = $this->syncCards();

            // 2. Sync VLANs + service-port count
            $result['vlans'] = $this->syncVlans();
            $result['service_ports'] = $result['vlans']['service_ports'] ?? 0;

            // 3. Sync uplinks (needs card_id from step 1)
            $result['uplinks'] = $this->syncUplinks();

            // 4. Sync PON port ONU state per GPON card
            $result['pon_ports'] = $this->syncPonPortsFromCli();

        } catch (Exception $e) {
            $result['success'] = false;
            Log::error("ZTE syncInfrastructure error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Sync PON port ONU state from CLI (READ-ONLY)
     * Parses `show gpon onu state gpon-olt_1/{slot}/{port}` per active GPON port
     */
    public function syncPonPortsFromCli(): array
    {
        $result = ['synced' => 0, 'errors' => []];

        try {
            // Get GPON cards to know which slots have PON ports
            $gponCards = OltCard::where('olt_id', $this->olt->id)
                ->where('role', 'gpon')
                ->where('status', 'inservice')
                ->get();

            if ($gponCards->isEmpty()) {
                $result['errors'][] = 'Tidak ada kartu GPON aktif';
                return $result;
            }

            // Build commands: show gpon onu state per port
            $commands = [];
            foreach ($gponCards as $card) {
                for ($port = 1; $port <= $card->port_count; $port++) {
                    $commands[] = "show gpon onu state gpon-olt_1/{$card->slot}/{$port}";
                }
            }

            $output = $this->executeBatchCliCommands($commands);

            // Parse output for each port
            $portData = $this->parseGponOnuState($output);

            foreach ($portData as $key => $data) {
                try {
                    $card = $gponCards->firstWhere('slot', $data['slot']);

                    $this->olt->ponPorts()->updateOrCreate(
                        [
                            'olt_id' => $this->olt->id,
                            'slot' => $data['slot'],
                            'port' => $data['port'],
                        ],
                        [
                            'card_id' => $card?->id,
                            'registered_onu' => $data['registered'],
                            'online_onu' => $data['online'],
                            'status' => $data['registered'] > 0 ? 'up' : 'down',
                            'last_sync_at' => now(),
                        ]
                    );
                    $result['synced']++;
                } catch (Exception $e) {
                    $result['errors'][] = "PON 1/{$data['slot']}/{$data['port']}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("ZTE syncPonPortsFromCli error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Parse `show gpon onu state gpon-olt_1/X/Y` output
     * Returns per-port ONU counts: registered, online, offline, by status
     */
    protected function parseGponOnuState(string $output): array
    {
        $ports = [];
        $currentSlot = null;
        $currentPort = null;

        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Detect port context: "gpon-olt_1/1/3" or from command echo
            if (preg_match('/gpon-olt_1\/(\d+)\/(\d+)/', $line, $m)) {
                $currentSlot = (int)$m[1];
                $currentPort = (int)$m[2];
                $key = "{$currentSlot}/{$currentPort}";
                if (!isset($ports[$key])) {
                    $ports[$key] = [
                        'slot' => $currentSlot,
                        'port' => $currentPort,
                        'registered' => 0,
                        'online' => 0,
                        'onus' => [],
                    ];
                }
            }

            // Match ONU state line: "1  working  online  1/1/3   1" or similar
            // Format: OnuIndex  Admin  OperState  PON  OnuID
            if ($currentSlot && $currentPort &&
                preg_match('/^\d+\s+\w+\s+(online|offline|los|dying.?gasp|unknown|low.?power)/i', $line, $m)) {
                $key = "{$currentSlot}/{$currentPort}";
                $status = strtolower($m[1]);
                $ports[$key]['registered']++;
                if ($status === 'online') {
                    $ports[$key]['online']++;
                }
                $ports[$key]['onus'][] = $status;
            }
        }

        return $ports;
    }

    // =========================================================================
    // INFRASTRUCTURE WRITE OPERATIONS
    // =========================================================================

    /**
     * Configure uplink port: Add/Remove tagged VLANs, set admin state, description
     */
    public function configureUplink(string $interfaceName, array $params): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $commands = ['configure terminal', "interface {$interfaceName}"];

            // Add VLANs
            if (!empty($params['add_vlans'])) {
                foreach ($params['add_vlans'] as $vlanId) {
                    $vlanId = (int) $vlanId;
                    if ($vlanId > 0 && $vlanId <= 4094) {
                        $commands[] = "switchport vlan {$vlanId} tag";
                    }
                }
            }

            // Remove VLANs
            if (!empty($params['remove_vlans'])) {
                foreach ($params['remove_vlans'] as $vlanId) {
                    $vlanId = (int) $vlanId;
                    if ($vlanId > 0 && $vlanId <= 4094) {
                        $commands[] = "no switchport vlan {$vlanId} tag";
                    }
                }
            }

            // Set native VLAN (PVID)
            if (isset($params['native_vlan']) && $params['native_vlan'] !== '') {
                $nv = (int) $params['native_vlan'];
                if ($nv > 0 && $nv <= 4094) {
                    $commands[] = "switchport default vlan {$nv}";
                }
            }

            // Admin state
            if (isset($params['admin_status'])) {
                $commands[] = $params['admin_status'] === 'disabled' ? 'shutdown' : 'no shutdown';
            }

            // Description
            if (isset($params['description']) && $params['description'] !== '') {
                $desc = preg_replace('/[^A-Za-z0-9._\- ]/', '', $params['description']);
                $commands[] = "description {$desc}";
            }

            $commands[] = 'exit';
            $commands[] = 'exit';
            $commands[] = 'write';

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid|unknown command/i', $output)) {
                $result['message'] = 'Perintah mungkin gagal. Cek output: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "Konfigurasi {$interfaceName} berhasil disimpan";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE configureUplink error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Create VLAN on OLT
     */
    public function createVlan(int $vlanId, string $name = ''): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            if ($vlanId < 2 || $vlanId > 4094) {
                return ['success' => false, 'message' => 'VLAN ID harus antara 2-4094', 'output' => ''];
            }

            $commands = [
                'configure terminal',
                "vlan {$vlanId}",
            ];

            if ($name) {
                $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '', $name);
                $commands[] = "name {$safeName}";
            }

            $commands[] = 'exit';
            $commands[] = 'exit';
            $commands[] = 'write';

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid/i', $output) && !str_contains($output, 'already exist')) {
                $result['message'] = 'Gagal membuat VLAN: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "VLAN {$vlanId} berhasil dibuat di OLT";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE createVlan error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Delete VLAN from OLT
     */
    public function deleteVlan(int $vlanId): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            if ($vlanId < 2 || $vlanId > 4094) {
                return ['success' => false, 'message' => 'VLAN ID harus antara 2-4094', 'output' => ''];
            }

            $commands = [
                'configure terminal',
                "no vlan {$vlanId}",
                'exit',
                'write',
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|in use|cannot/i', $output)) {
                $result['message'] = 'Gagal menghapus VLAN: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "VLAN {$vlanId} berhasil dihapus dari OLT";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE deleteVlan error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Reboot a card/slot
     */
    public function rebootCard(int $rack, int $shelf, int $slot): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $commands = [
                'configure terminal',
                "reboot card {$rack}/{$shelf}/{$slot}",
                'y',
                'exit',
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid|not exist/i', $output)) {
                $result['message'] = 'Gagal reboot card: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "Card slot {$rack}/{$shelf}/{$slot} sedang di-reboot";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE rebootCard error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Reboot all ONUs on a specific PON port
     */
    public function rebootAllOnusOnPort(int $slot, int $port): array
    {
        $result = ['success' => false, 'message' => '', 'rebooted' => 0, 'output' => ''];

        try {
            // First get ONU list on this port
            $stateOutput = $this->executeBatchCliCommands([
                "show gpon onu state gpon-olt_1/{$slot}/{$port}",
            ]);

            // Count ONUs to reboot
            $onuIds = [];
            foreach (explode("\n", $stateOutput) as $line) {
                if (preg_match('/^\s*(\d+)\s+\w+\s+(online|offline|los)/i', $line, $m)) {
                    $onuIds[] = (int) $m[1];
                }
            }

            if (empty($onuIds)) {
                return ['success' => true, 'message' => "Tidak ada ONU di port 1/{$slot}/{$port}", 'rebooted' => 0, 'output' => ''];
            }

            // Reboot each ONU
            $commands = ['configure terminal'];
            foreach ($onuIds as $onuId) {
                $commands[] = "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}";
                $commands[] = 'reboot';
                $commands[] = 'y';
                $commands[] = 'exit';
            }
            $commands[] = 'exit';

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;
            $result['success'] = true;
            $result['rebooted'] = count($onuIds);
            $result['message'] = count($onuIds) . " ONU di port 1/{$slot}/{$port} sedang di-reboot";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE rebootAllOnusOnPort error: " . $e->getMessage());
        }

        return $result;
    }

    // =========================================================================
    // SNMP VLAN Management (Q-BRIDGE-MIB)
    // =========================================================================

    /**
     * Port index map for Q-BRIDGE bitmap byte 24.
     * ZTE C320 maps slot 3 uplink ports to bits in byte 24 (0-indexed) of the 192-byte PortList.
     * Bit 7 (0x80) = gei_1/3/1, Bit 6 (0x40) = xgei_1/3/2, Bit 5 (0x20) = gei_1/3/3
     */
    protected array $uplinkBitMap = [
        'gei_1/3/1'  => ['byte' => 24, 'bit' => 7],  // 0x80
        'xgei_1/3/2' => ['byte' => 24, 'bit' => 6],  // 0x40
        'gei_1/3/3'  => ['byte' => 24, 'bit' => 5],  // 0x20
    ];

    protected const PORTLIST_BYTES = 192;

    /**
     * Read all VLANs via SNMP Q-BRIDGE-MIB.
     * Returns array of VLANs with name, tagged/untagged ports.
     */
    public function getVlansViaSnmp(): array
    {
        $vlans = [];

        $names = $this->snmpWalk($this->zteOids['dot1qVlanStaticName']);
        $egressPorts = $this->snmpWalk($this->zteOids['dot1qVlanStaticEgressPorts']);
        $untaggedPorts = $this->snmpWalk($this->zteOids['dot1qVlanStaticUntaggedPorts']);

        if (empty($names)) {
            return $vlans;
        }

        $baseOid = $this->zteOids['dot1qVlanStaticName'];

        foreach ($names as $oid => $name) {
            // OID suffix is the VLAN ID: ...1.3.6.1.2.1.17.7.1.4.3.1.1.{vlanId}
            $vlanId = (int) substr($oid, strlen($baseOid) + 1);
            if ($vlanId < 1 || $vlanId > 4094) continue;

            $egressOid = $this->zteOids['dot1qVlanStaticEgressPorts'] . '.' . $vlanId;
            $untagOid = $this->zteOids['dot1qVlanStaticUntaggedPorts'] . '.' . $vlanId;

            $egressBitmap = $egressPorts[$egressOid] ?? '';
            $untagBitmap = $untaggedPorts[$untagOid] ?? '';

            $tagged = $this->parsePortBitmap($egressBitmap, $untagBitmap, 'tagged');
            $untagged = $this->parsePortBitmap($egressBitmap, $untagBitmap, 'untagged');

            $vlans[$vlanId] = [
                'vlan_id' => $vlanId,
                'name' => trim($name, '"'),
                'tagged_ports' => $tagged,
                'untagged_ports' => $untagged,
            ];
        }

        ksort($vlans);
        return $vlans;
    }

    /**
     * Parse Q-BRIDGE PortList bitmaps into port name arrays.
     * Egress = all ports in VLAN, Untagged = untagged subset.
     * Tagged = Egress AND NOT Untagged.
     */
    protected function parsePortBitmap(string $egressHex, string $untagHex, string $mode = 'tagged'): array
    {
        $ports = [];

        foreach ($this->uplinkBitMap as $portName => $map) {
            $byteIndex = $map['byte'];
            $bitIndex = $map['bit'];

            $egressByte = $this->getHexByte($egressHex, $byteIndex);
            $untagByte = $this->getHexByte($untagHex, $byteIndex);

            $inEgress = ($egressByte >> $bitIndex) & 1;
            $inUntag = ($untagByte >> $bitIndex) & 1;

            if ($mode === 'tagged' && $inEgress && !$inUntag) {
                $ports[] = $portName;
            } elseif ($mode === 'untagged' && $inUntag) {
                $ports[] = $portName;
            }
        }

        return $ports;
    }

    /**
     * Get a single byte value from SNMP hex string.
     * SNMP returns Hex-STRING like "00 00 00 ... E0 00 ..." with spaces.
     */
    protected function getHexByte(string $hexString, int $byteIndex): int
    {
        // SNMP_VALUE_PLAIN mode returns raw binary or hex string
        // Try hex space-separated format first: "00 00 00 E0 00 ..."
        $hexString = trim($hexString);
        if (preg_match('/^[0-9A-Fa-f]{2}(\s+[0-9A-Fa-f]{2})*$/', $hexString)) {
            $bytes = preg_split('/\s+/', $hexString);
            return isset($bytes[$byteIndex]) ? hexdec($bytes[$byteIndex]) : 0;
        }

        // Binary string
        if (isset($hexString[$byteIndex])) {
            return ord($hexString[$byteIndex]);
        }

        return 0;
    }

    /**
     * Build a 192-byte PortList bitmap from port names and mode.
     * Returns hex string suitable for SNMP SET.
     */
    protected function buildPortBitmap(array $taggedPorts, array $untaggedPorts, string $mode = 'egress'): string
    {
        $bytes = array_fill(0, self::PORTLIST_BYTES, 0);

        $ports = ($mode === 'egress')
            ? array_merge($taggedPorts, $untaggedPorts)  // Egress = tagged + untagged
            : $untaggedPorts;                             // Untagged = just untagged

        foreach ($ports as $portName) {
            if (isset($this->uplinkBitMap[$portName])) {
                $map = $this->uplinkBitMap[$portName];
                $bytes[$map['byte']] |= (1 << $map['bit']);
            }
        }

        // Build hex string
        $hex = '';
        foreach ($bytes as $b) {
            $hex .= sprintf('%02X ', $b);
        }

        return trim($hex);
    }

    /**
     * Update VLAN port membership via SNMP SET.
     * Sets both EgressPorts and UntaggedPorts bitmaps.
     */
    public function updateVlanPortsViaSnmp(int $vlanId, array $taggedPorts, array $untaggedPorts): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            if (!$this->supportsSnmpWrite()) {
                throw new Exception('SNMP RW community tidak dikonfigurasi pada OLT ini');
            }

            // Build bitmaps
            $egressHex = $this->buildPortBitmap($taggedPorts, $untaggedPorts, 'egress');
            $untagHex = $this->buildPortBitmap($taggedPorts, $untaggedPorts, 'untagged');

            // Convert hex string to binary for SNMP SET
            $egressBin = $this->hexToBinaryString($egressHex);
            $untagBin = $this->hexToBinaryString($untagHex);

            // SET EgressPorts
            $oid1 = $this->zteOids['dot1qVlanStaticEgressPorts'] . '.' . $vlanId;
            $ok1 = $this->snmpSet($oid1, 'x', $egressHex);
            if (!$ok1) {
                throw new Exception("Gagal SET EgressPorts untuk VLAN {$vlanId}");
            }

            // SET UntaggedPorts
            $oid2 = $this->zteOids['dot1qVlanStaticUntaggedPorts'] . '.' . $vlanId;
            $ok2 = $this->snmpSet($oid2, 'x', $untagHex);
            if (!$ok2) {
                throw new Exception("Gagal SET UntaggedPorts untuk VLAN {$vlanId}");
            }

            $result['success'] = true;
            $result['message'] = "Port membership VLAN {$vlanId} berhasil diubah via SNMP";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE updateVlanPortsViaSnmp error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Convert hex string ("E0 00 00 ...") to packed hex for SNMP SET type 'x'.
     */
    protected function hexToBinaryString(string $hexSpaced): string
    {
        return str_replace(' ', '', $hexSpaced);
    }

    /**
     * Create VLAN via SNMP SET (dot1qVlanStaticRowStatus = createAndGo(4)).
     */
    public function createVlanViaSnmp(int $vlanId, string $name = ''): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            if (!$this->supportsSnmpWrite()) {
                throw new Exception('SNMP RW community tidak dikonfigurasi');
            }

            // Create VLAN via RowStatus = 4 (createAndGo)
            $oid = $this->zteOids['dot1qVlanStaticRowStatus'] . '.' . $vlanId;
            $ok = $this->snmpSet($oid, 'i', '4');
            if (!$ok) {
                throw new Exception("Gagal membuat VLAN {$vlanId} via SNMP");
            }

            // Set name if provided
            if ($name) {
                $nameOid = $this->zteOids['dot1qVlanStaticName'] . '.' . $vlanId;
                $this->snmpSet($nameOid, 's', $name);
            }

            $result['success'] = true;
            $result['message'] = "VLAN {$vlanId} berhasil dibuat via SNMP";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE createVlanViaSnmp error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Delete VLAN via SNMP SET (dot1qVlanStaticRowStatus = destroy(6)).
     */
    public function deleteVlanViaSnmp(int $vlanId): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            if (!$this->supportsSnmpWrite()) {
                throw new Exception('SNMP RW community tidak dikonfigurasi');
            }

            $oid = $this->zteOids['dot1qVlanStaticRowStatus'] . '.' . $vlanId;
            $ok = $this->snmpSet($oid, 'i', '6');
            if (!$ok) {
                throw new Exception("Gagal menghapus VLAN {$vlanId} via SNMP");
            }

            $result['success'] = true;
            $result['message'] = "VLAN {$vlanId} berhasil dihapus via SNMP";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE deleteVlanViaSnmp error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Update VLAN name via SNMP SET.
     */
    public function renameVlanViaSnmp(int $vlanId, string $name): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            if (!$this->supportsSnmpWrite()) {
                throw new Exception('SNMP RW community tidak dikonfigurasi');
            }

            $oid = $this->zteOids['dot1qVlanStaticName'] . '.' . $vlanId;
            $ok = $this->snmpSet($oid, 's', $name);
            if (!$ok) {
                throw new Exception("Gagal rename VLAN {$vlanId} via SNMP");
            }

            $result['success'] = true;
            $result['message'] = "Nama VLAN {$vlanId} berhasil diubah via SNMP";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Set VLAN description via CLI (not available in Q-BRIDGE-MIB).
     */
    public function setVlanDescriptionViaCli(int $vlanId, string $description): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            $commands = [
                'configure terminal',
                "vlan {$vlanId}",
                "description {$description}",
                'exit',
                'exit',
                'write',
            ];

            $this->executeBatchCliCommands($commands);
            $result['success'] = true;
            $result['message'] = "Deskripsi VLAN {$vlanId} berhasil diubah";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE setVlanDescriptionViaCli error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Hybrid VLAN update: SNMP for ports/name, CLI for description.
     */
    public function updateVlanHybrid(int $vlanId, array $params): array
    {
        $result = ['success' => true, 'message' => '', 'details' => []];

        // Update port membership via SNMP
        if (isset($params['tagged_ports']) || isset($params['untagged_ports'])) {
            $tagged = $params['tagged_ports'] ?? [];
            $untagged = $params['untagged_ports'] ?? [];

            if ($this->supportsSnmpWrite()) {
                $portResult = $this->updateVlanPortsViaSnmp($vlanId, $tagged, $untagged);
            } else {
                // Fallback to CLI
                $portResult = $this->updateVlanPortsViaCli($vlanId, $tagged, $untagged);
            }

            $result['details'][] = $portResult;
            if (!$portResult['success']) {
                $result['success'] = false;
            }
        }

        // Update name via SNMP
        if (!empty($params['name'])) {
            if ($this->supportsSnmpWrite()) {
                $nameResult = $this->renameVlanViaSnmp($vlanId, $params['name']);
            } else {
                $nameResult = ['success' => true, 'message' => 'Name update via CLI not supported'];
            }
            $result['details'][] = $nameResult;
        }

        // Update description via CLI (not in Q-BRIDGE-MIB)
        if (isset($params['description'])) {
            $descResult = $this->setVlanDescriptionViaCli($vlanId, $params['description']);
            $result['details'][] = $descResult;
            if (!$descResult['success']) {
                $result['success'] = false;
            }
        }

        // Build summary message
        $messages = array_column($result['details'], 'message');
        $result['message'] = $result['success']
            ? 'VLAN ' . $vlanId . ' berhasil diperbarui'
            : implode('; ', array_filter($messages));

        return $result;
    }

    /**
     * Fallback: update VLAN port membership via CLI when SNMP RW is not available.
     */
    protected function updateVlanPortsViaCli(int $vlanId, array $taggedPorts, array $untaggedPorts): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            $commands = ['configure terminal'];

            // Get all uplink port names we know about
            $allPorts = array_keys($this->uplinkBitMap);

            foreach ($allPorts as $portName) {
                $isTagged = in_array($portName, $taggedPorts);
                $isUntagged = in_array($portName, $untaggedPorts);

                if ($isTagged || $isUntagged) {
                    $commands[] = "interface {$portName}";
                    if ($isTagged) {
                        $commands[] = "switchport vlan {$vlanId} tag";
                    } else {
                        $commands[] = "switchport vlan {$vlanId} untag";
                    }
                    $commands[] = 'exit';
                } else {
                    // Remove from port
                    $commands[] = "interface {$portName}";
                    $commands[] = "no switchport vlan {$vlanId}";
                    $commands[] = 'exit';
                }
            }

            $commands[] = 'exit'; // exit configure
            $commands[] = 'write';

            $this->executeBatchCliCommands($commands);
            $result['success'] = true;
            $result['message'] = "Port membership VLAN {$vlanId} berhasil diubah via CLI";

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE updateVlanPortsViaCli error: " . $e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    //  GPON PROFILE MANAGEMENT (TCONT + Traffic/Gemport)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Read all TCONT (DBA) profiles from OLT via CLI.
     *
     * Returns array of:
     *   ['name' => str, 'type' => int, 'fbw' => int, 'abw' => int, 'mbw' => int]
     */
    public function getTcontProfiles(): array
    {
        $profiles = [];

        $output = $this->executeBatchCliCommands(['show gpon profile tcont']);

        // Parse blocks:
        // Profile name :NAME
        //  Type  FBW   ABW   MBW   PRIORITY  WEIGHT
        //  5     64    64    1048064  N/A     N/A
        $blocks = preg_split('/Profile name\s*:/i', $output);
        foreach (array_slice($blocks, 1) as $block) {
            $lines = array_values(array_filter(explode("\n", $block), fn($l) => trim($l) !== ''));
            if (empty($lines)) continue;

            $name = trim($lines[0]);
            // Skip header line (Type / FBW / ABW...)
            $dataLine = '';
            foreach (array_slice($lines, 1) as $line) {
                if (preg_match('/^\s*(\d)\s+([\d]+)\s+([\d]+)\s+([\d]+)/', $line, $m)) {
                    $dataLine = $line;
                    break;
                }
            }

            if ($dataLine && preg_match('/^\s*(\d)\s+([\d]+)\s+([\d]+)\s+([\d]+)/', $dataLine, $m)) {
                $profiles[] = [
                    'name' => $name,
                    'type' => (int) $m[1],
                    'fbw'  => (int) $m[2],
                    'abw'  => (int) $m[3],
                    'mbw'  => (int) $m[4],
                ];
            }
        }

        return $profiles;
    }

    /**
     * Read all Traffic (gemport downstream shaping) profiles from OLT via CLI.
     *
     * Returns array of:
     *   ['name' => str, 'sir' => int, 'pir' => int]
     */
    public function getTrafficProfiles(): array
    {
        $profiles = [];

        $output = $this->executeBatchCliCommands(['show gpon profile traffic']);

        // Parse blocks:
        // Profile name  :NAME
        //   SIR(kbps)  PIR(kbps)  CBS  PBS
        //   1048064    1048064    default  default
        $blocks = preg_split('/Profile name\s*:/i', $output);
        foreach (array_slice($blocks, 1) as $block) {
            $lines = array_values(array_filter(explode("\n", $block), fn($l) => trim($l) !== ''));
            if (empty($lines)) continue;

            $name = trim($lines[0]);
            // Find first line with numeric kbps values
            $dataLine = '';
            foreach (array_slice($lines, 1) as $line) {
                if (preg_match('/^\s*([\d]+)\s+([\d]+)/', $line)) {
                    $dataLine = $line;
                    break;
                }
            }

            if ($dataLine && preg_match('/^\s*([\d]+)\s+([\d]+)/', $dataLine, $m)) {
                $profiles[] = [
                    'name' => $name,
                    'sir'  => (int) $m[1],
                    'pir'  => (int) $m[2],
                ];
            }
        }

        return $profiles;
    }

    /**
     * Create a TCONT (DBA) profile on the OLT.
     *
     * @param string $name  Profile name (alphanumeric + hyphen/underscore)
     * @param int    $type  DBA type: 1=Fixed, 2=Assured, 3=NonAssured, 4=BestEffort, 5=Hybrid
     * @param int    $fbw   Fixed bandwidth kbps  (types 1 & 5)
     * @param int    $abw   Assured bandwidth kbps (types 2, 3 & 5)
     * @param int    $mbw   Max bandwidth kbps     (types 3, 4 & 5)
     */
    public function createTcontProfile(string $name, int $type, int $fbw = 0, int $abw = 0, int $mbw = 0): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '', $name);
            if (empty($safeName)) {
                return ['success' => false, 'message' => 'Nama profile tidak valid', 'output' => ''];
            }

            // Build bandwidth arguments based on type
            $bwArgs = match ($type) {
                1 => "fix {$fbw}",
                2 => "assure {$abw}",
                3 => "assure {$abw} max {$mbw}",
                4 => "max {$mbw}",
                5 => "fix {$fbw} assure {$abw} max {$mbw}",
                default => throw new Exception("Tipe DBA tidak valid: {$type}. Gunakan 1-5.")
            };

            $commands = [
                'configure terminal',
                "gpon profile tcont {$safeName} type {$type} {$bwArgs}",
                'exit',
                'write',
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid|unknown command/i', $output)) {
                $result['message'] = 'Gagal membuat TCONT profile. Output: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "TCONT profile '{$safeName}' berhasil dibuat di OLT";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE createTcontProfile error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Delete a TCONT profile from the OLT.
     */
    public function deleteTcontProfile(string $name): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '', $name);

            $commands = [
                'configure terminal',
                "no gpon profile tcont {$safeName}",
                'exit',
                'write',
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid|unknown command/i', $output)) {
                $result['message'] = 'Gagal hapus TCONT profile. Output: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "TCONT profile '{$safeName}' berhasil dihapus dari OLT";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE deleteTcontProfile error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Create a Traffic (gemport downstream) profile on the OLT.
     *
     * @param string $name Profile name
     * @param int    $sir  Sustained Information Rate kbps
     * @param int    $pir  Peak Information Rate kbps
     */
    public function createTrafficProfile(string $name, int $sir, int $pir): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '', $name);
            if (empty($safeName)) {
                return ['success' => false, 'message' => 'Nama profile tidak valid', 'output' => ''];
            }

            if ($pir < $sir) {
                $pir = $sir; // PIR must be >= SIR
            }

            $commands = [
                'configure terminal',
                "gpon profile traffic {$safeName} sir {$sir} pir {$pir}",
                'exit',
                'write',
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid|unknown command/i', $output)) {
                $result['message'] = 'Gagal membuat Traffic profile. Output: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "Traffic profile '{$safeName}' berhasil dibuat di OLT";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE createTrafficProfile error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Delete a Traffic profile from the OLT.
     */
    public function deleteTrafficProfile(string $name): array
    {
        $result = ['success' => false, 'message' => '', 'output' => ''];

        try {
            $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '', $name);

            $commands = [
                'configure terminal',
                "no gpon profile traffic {$safeName}",
                'exit',
                'write',
            ];

            $output = $this->executeBatchCliCommands($commands);
            $result['output'] = $output;

            if (preg_match('/Error|fail|invalid|unknown command/i', $output)) {
                $result['message'] = 'Gagal hapus Traffic profile. Output: ' . trim(substr($output, 0, 300));
            } else {
                $result['success'] = true;
                $result['message'] = "Traffic profile '{$safeName}' berhasil dihapus dari OLT";
            }

        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error("ZTE deleteTrafficProfile error: " . $e->getMessage());
        }

        return $result;
    }
}
