<?php

namespace App\Helpers\Olt;

use App\Models\Olt;
use App\Models\Onu;
use App\Models\OltPonPort;
use App\Models\OnuSignalHistory;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Base class for OLT helpers
 * Contains common functionality shared across all OLT brands
 */
abstract class BaseOltHelper implements OltInterface
{
    protected Olt $olt;
    protected ?object $snmpConnection = null;
    protected ?object $telnetConnection = null;
    protected ?object $sshConnection = null;

    protected int $snmpTimeout = 2;
    protected int $snmpRetries = 0;
    protected int $telnetTimeout = 10;
    protected int $sshTimeout = 10;

    /**
     * Common OID definitions
     */
    protected array $commonOids = [
        'sysDescr' => '1.3.6.1.2.1.1.1.0',
        'sysObjectID' => '1.3.6.1.2.1.1.2.0',
        'sysUpTime' => '1.3.6.1.2.1.1.3.0',
        'sysContact' => '1.3.6.1.2.1.1.4.0',
        'sysName' => '1.3.6.1.2.1.1.5.0',
        'sysLocation' => '1.3.6.1.2.1.1.6.0',
        'ifNumber' => '1.3.6.1.2.1.2.1.0',
    ];

    public function setOlt(Olt $olt): self
    {
        $this->olt = $olt;
        return $this;
    }

    public function supportsSnmp(): bool
    {
        return !empty($this->olt->snmp_community) && !empty($this->olt->ip_address);
    }

    public function supportsTelnet(): bool
    {
        return $this->olt->telnet_enabled && !empty($this->olt->telnet_username);
    }

    public function supportsSsh(): bool
    {
        return $this->olt->ssh_enabled && !empty($this->olt->ssh_username);
    }

    public function supportsApi(): bool
    {
        return $this->olt->api_enabled && !empty($this->olt->api_url);
    }

    /**
     * Execute SNMP GET
     */
    protected function snmpGet(string $oid): mixed
    {
        if (!$this->supportsSnmp()) {
            throw new Exception('SNMP is not configured for this OLT');
        }

        snmp_set_quick_print(true);
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
        
        $result = @snmpget(
            $this->olt->ip_address,
            $this->olt->snmp_community,
            $oid,
            $this->snmpTimeout * 1000000,
            $this->snmpRetries
        );

        if ($result === false) {
            Log::warning("SNMP GET failed for OID: {$oid} on OLT: {$this->olt->name}");
            return null;
        }

        return $result;
    }

    /**
     * Execute SNMP WALK
     */
    protected function snmpWalk(string $oid): array
    {
        if (!$this->supportsSnmp()) {
            throw new Exception('SNMP is not configured for this OLT');
        }

        snmp_set_quick_print(true);
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
        
        $result = @snmpwalkoid(
            $this->olt->ip_address,
            $this->olt->snmp_community,
            $oid,
            $this->snmpTimeout * 1000000,
            $this->snmpRetries
        );

        if ($result === false) {
            Log::warning("SNMP WALK failed for OID: {$oid} on OLT: {$this->olt->name}");
            return [];
        }

        return $result;
    }

    /**
     * Execute SNMP SET
     */
    protected function snmpSet(string $oid, string $type, mixed $value): bool
    {
        if (!$this->supportsSnmp()) {
            throw new Exception('SNMP is not configured for this OLT');
        }

        $result = @snmpset(
            $this->olt->ip_address,
            $this->olt->snmp_community,
            $oid,
            $type,
            $value,
            $this->snmpTimeout * 1000000,
            $this->snmpRetries
        );

        return $result !== false;
    }

    /**
     * Execute Telnet command
     */
    protected function telnetCommand(string $command, int $waitTime = 1): string
    {
        if (!$this->supportsTelnet()) {
            throw new Exception('Telnet is not configured for this OLT');
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

        // Wait for login prompt and send username
        $this->telnetWaitFor($fp, ['Username:', 'login:', '>']);
        fwrite($fp, $this->olt->telnet_username . "\r\n");

        // Wait for password prompt and send password
        $this->telnetWaitFor($fp, ['Password:', 'password:']);
        fwrite($fp, $this->olt->telnet_password . "\r\n");

        // Wait for prompt
        sleep(1);
        $this->telnetWaitFor($fp, ['>', '#', '$']);

        // Send command
        fwrite($fp, $command . "\r\n");
        sleep($waitTime);

        // Read response
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($line === false) break;
            $response .= $line;
            
            // Check if we hit the prompt again
            if (preg_match('/[>#\$]\s*$/', $line)) {
                break;
            }
        }

        fclose($fp);

        return $response;
    }

    /**
     * Wait for specific strings in telnet
     */
    protected function telnetWaitFor($fp, array $waitFor): string
    {
        $buffer = '';
        $timeout = time() + $this->telnetTimeout;

        while (time() < $timeout) {
            $char = fgetc($fp);
            if ($char === false) {
                usleep(100000);
                continue;
            }
            $buffer .= $char;

            foreach ($waitFor as $string) {
                if (str_contains($buffer, $string)) {
                    return $buffer;
                }
            }
        }

        return $buffer;
    }

    /**
     * Execute SSH command
     */
    protected function sshCommand(string $command): string
    {
        if (!$this->supportsSsh()) {
            throw new Exception('SSH is not configured for this OLT');
        }

        $connection = @ssh2_connect(
            $this->olt->ip_address,
            $this->olt->ssh_port ?? 22
        );

        if (!$connection) {
            throw new Exception('SSH connection failed');
        }

        // Authenticate
        if (!empty($this->olt->ssh_key)) {
            // Key-based auth
            $pubKeyFile = tempnam(sys_get_temp_dir(), 'ssh_pub_');
            $privKeyFile = tempnam(sys_get_temp_dir(), 'ssh_priv_');
            file_put_contents($privKeyFile, $this->olt->ssh_key);
            
            $auth = @ssh2_auth_pubkey_file(
                $connection,
                $this->olt->ssh_username,
                $pubKeyFile,
                $privKeyFile
            );
            
            @unlink($pubKeyFile);
            @unlink($privKeyFile);
        } else {
            // Password auth
            $auth = @ssh2_auth_password(
                $connection,
                $this->olt->ssh_username,
                $this->olt->ssh_password
            );
        }

        if (!$auth) {
            throw new Exception('SSH authentication failed');
        }

        $stream = ssh2_exec($connection, $command);
        if (!$stream) {
            throw new Exception('SSH command execution failed');
        }

        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        fclose($stream);

        return $output;
    }

    /**
     * Test connection to OLT
     */
    public function testConnection(): array
    {
        $results = [
            'success' => false,
            'methods' => [],
            'message' => '',
        ];

        // Test SNMP
        if ($this->supportsSnmp()) {
            try {
                $sysDescr = $this->snmpGet($this->commonOids['sysDescr']);
                $results['methods']['snmp'] = [
                    'success' => $sysDescr !== null,
                    'message' => $sysDescr !== null ? 'Connected' : 'Failed',
                    'data' => $sysDescr,
                ];
            } catch (Exception $e) {
                $results['methods']['snmp'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Test Telnet
        if ($this->supportsTelnet()) {
            try {
                $fp = @fsockopen(
                    $this->olt->ip_address,
                    $this->olt->telnet_port ?? 23,
                    $errno,
                    $errstr,
                    5
                );
                if ($fp) {
                    fclose($fp);
                    $results['methods']['telnet'] = [
                        'success' => true,
                        'message' => 'Port reachable',
                    ];
                } else {
                    $results['methods']['telnet'] = [
                        'success' => false,
                        'message' => $errstr,
                    ];
                }
            } catch (Exception $e) {
                $results['methods']['telnet'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Test SSH
        if ($this->supportsSsh() && function_exists('ssh2_connect')) {
            try {
                $connection = @ssh2_connect(
                    $this->olt->ip_address,
                    $this->olt->ssh_port ?? 22
                );
                $results['methods']['ssh'] = [
                    'success' => $connection !== false,
                    'message' => $connection ? 'Connected' : 'Failed',
                ];
                if ($connection) {
                    // Close connection (no explicit close function in ssh2)
                    unset($connection);
                }
            } catch (Exception $e) {
                $results['methods']['ssh'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Overall success if any method works
        $results['success'] = collect($results['methods'])
            ->contains(fn($m) => $m['success'] ?? false);

        $results['message'] = $results['success'] 
            ? 'Connection successful' 
            : 'All connection methods failed';

        return $results;
    }

    /**
     * Get basic system info via SNMP
     */
    public function getSystemInfo(): array
    {
        return [
            'description' => $this->snmpGet($this->commonOids['sysDescr']),
            'object_id' => $this->snmpGet($this->commonOids['sysObjectID']),
            'uptime' => $this->snmpGet($this->commonOids['sysUpTime']),
            'contact' => $this->snmpGet($this->commonOids['sysContact']),
            'name' => $this->snmpGet($this->commonOids['sysName']),
            'location' => $this->snmpGet($this->commonOids['sysLocation']),
        ];
    }

    /**
     * Save ONU data to database
     * Uses olt_id + slot + port + onu_id as unique key (matches DB constraint)
     * Falls back to serial_number if slot/port/onu_id not available
     */
    protected function saveOnuToDatabase(array $onuData): Onu
    {
        // Determine unique key based on available data
        // Prefer slot/port/onu_id (matches database unique constraint)
        if (isset($onuData['slot']) && isset($onuData['port']) && isset($onuData['onu_id'])) {
            $uniqueKey = [
                'olt_id' => $this->olt->id,
                'slot' => $onuData['slot'],
                'port' => $onuData['port'],
                'onu_id' => $onuData['onu_id'],
            ];
        } else {
            // Fallback to serial_number for OLTs without slot/port/onu_id
            $uniqueKey = [
                'olt_id' => $this->olt->id,
                'serial_number' => $onuData['serial_number'],
            ];
        }

        return Onu::updateOrCreate(
            $uniqueKey,
            array_merge($onuData, [
                'last_sync_at' => now(),
            ])
        );
    }

    /**
     * Save signal history
     */
    protected function saveSignalHistory(Onu $onu, array $signalData): void
    {
        OnuSignalHistory::create([
            'onu_id' => $onu->id,
            'olt_id' => $this->olt->id,
            'rx_power' => $signalData['rx_power'] ?? null,
            'tx_power' => $signalData['tx_power'] ?? null,
            'olt_rx_power' => $signalData['olt_rx_power'] ?? null,
            'temperature' => $signalData['temperature'] ?? null,
            'voltage' => $signalData['voltage'] ?? null,
            'bias_current' => $signalData['bias_current'] ?? null,
            'status' => $signalData['status'] ?? null,
            'distance' => $signalData['distance'] ?? null,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Update PON port stats
     */
    protected function updatePonPort(int $slot, int $port, array $data): OltPonPort
    {
        return OltPonPort::updateOrCreate(
            [
                'olt_id' => $this->olt->id,
                'slot' => $slot,
                'port' => $port,
            ],
            array_merge($data, [
                'last_sync_at' => now(),
            ])
        );
    }

    /**
     * Parse serial number from different formats
     */
    protected function parseSerialNumber(string $raw): string
    {
        // Remove common prefixes
        $sn = preg_replace('/^(GPON|EPON|SN:|SN=)/i', '', trim($raw));
        
        // Convert hex to ASCII if needed (some OLTs return hex format)
        if (preg_match('/^[0-9A-F]{16}$/i', $sn)) {
            // Check if it looks like hex-encoded ASCII
            $decoded = hex2bin($sn);
            if ($decoded && ctype_print($decoded)) {
                return $decoded;
            }
        }

        return strtoupper(trim($sn));
    }

    /**
     * Convert optical power from different formats
     */
    protected function parseOpticalPower(mixed $value): ?float
    {
        if (is_null($value) || $value === '' || $value === 'N/A') {
            return null;
        }

        // If already a number
        if (is_numeric($value)) {
            $power = (float) $value;
            
            // Some OLTs return value * 100 or * 1000
            if ($power > 100) {
                $power = $power / 100;
            }
            if ($power > 10) {
                // Value in 0.01 dBm
                $power = $power / 10;
            }
            
            // Convert from positive to negative if needed (some OLTs)
            if ($power > 0 && $power < 50) {
                $power = -$power;
            }
            
            return round($power, 2);
        }

        // Try to extract number from string
        if (preg_match('/-?[\d.]+/', $value, $matches)) {
            return round((float) $matches[0], 2);
        }

        return null;
    }

    /**
     * Parse distance value
     */
    protected function parseDistance(mixed $value): ?float
    {
        if (is_null($value) || $value === '' || $value === 'N/A') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (preg_match('/([\d.]+)\s*(km|m)?/i', $value, $matches)) {
            $distance = (float) $matches[1];
            $unit = strtolower($matches[2] ?? 'm');
            
            if ($unit === 'km') {
                $distance *= 1000;
            }
            
            return $distance;
        }

        return null;
    }

    /**
     * Map ONU status from OLT-specific values
     */
    protected function mapOnuStatus(mixed $status): string
    {
        $status = strtolower(trim((string) $status));
        
        $statusMap = [
            '1' => 'online',
            '2' => 'offline',
            'online' => 'online',
            'offline' => 'offline',
            'working' => 'online',
            'up' => 'online',
            'down' => 'offline',
            'los' => 'los',
            'loss of signal' => 'los',
            'losi' => 'los',
            'dyinggasp' => 'dying_gasp',
            'dying-gasp' => 'dying_gasp',
            'dying gasp' => 'dying_gasp',
            'dg' => 'dying_gasp',
            'poweroff' => 'power_off',
            'power-off' => 'power_off',
            'power off' => 'power_off',
        ];

        return $statusMap[$status] ?? 'unknown';
    }

    /**
     * Get interface traffic statistics via SNMP (MIB-II standard)
     * This works on most SNMP-enabled OLTs using standard MIB-II OIDs
     * Individual helpers can override this for brand-specific implementation
     */
    public function getInterfaceStats(): array
    {
        $interfaces = [];
        
        try {
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
            
            // Get interface operational status
            $ifOperStatus = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.8',
                $timeout,
                $retries
            ) ?: [];
            
            // Get interface speed
            $ifSpeed = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.5',
                $timeout,
                $retries
            ) ?: [];
            
            // Get incoming octets
            $ifInOctets = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.10',
                $timeout,
                $retries
            ) ?: [];
            
            // Get outgoing octets
            $ifOutOctets = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.16',
                $timeout,
                $retries
            ) ?: [];
            
            // Get input errors
            $ifInErrors = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.14',
                $timeout,
                $retries
            ) ?: [];
            
            // Get output errors
            $ifOutErrors = @snmpwalkoid(
                $this->olt->ip_address,
                $this->olt->snmp_community ?? 'public',
                '1.3.6.1.2.1.2.2.1.20',
                $timeout,
                $retries
            ) ?: [];
            
            foreach ($ifDescr as $oid => $name) {
                // Extract interface index from OID
                preg_match('/\.(\d+)$/', $oid, $matches);
                $ifIndex = $matches[1] ?? null;
                
                if (!$ifIndex) continue;
                
                // Clean name
                $name = trim(str_replace(['"', "'"], '', $name));
                
                // Skip loopback and internal interfaces
                if (preg_match('/^(lo|Loopback|Null|Internal)/i', $name)) {
                    continue;
                }
                
                // Determine interface type
                $type = 'other';
                if (preg_match('/pon|epon|gpon/i', $name)) {
                    $type = 'pon';
                } elseif (preg_match('/^(G|GE|ETH|Ethernet|Uplink|Trunk|SFP)/i', $name)) {
                    $type = 'uplink';
                }
                
                // Get status
                $statusOid = "1.3.6.1.2.1.2.2.1.8.{$ifIndex}";
                $statusValue = $ifOperStatus[$statusOid] ?? null;
                $status = ($statusValue == 1) ? 'up' : 'down';
                
                // Get speed
                $speedOid = "1.3.6.1.2.1.2.2.1.5.{$ifIndex}";
                $speedValue = (int)($ifSpeed[$speedOid] ?? 0);
                $speedMbps = $speedValue / 1000000;
                
                // Get traffic counters
                $inOctets = (int)($ifInOctets["1.3.6.1.2.1.2.2.1.10.{$ifIndex}"] ?? 0);
                $outOctets = (int)($ifOutOctets["1.3.6.1.2.1.2.2.1.16.{$ifIndex}"] ?? 0);
                $inErrors = (int)($ifInErrors["1.3.6.1.2.1.2.2.1.14.{$ifIndex}"] ?? 0);
                $outErrors = (int)($ifOutErrors["1.3.6.1.2.1.2.2.1.20.{$ifIndex}"] ?? 0);
                
                $interfaces[] = [
                    'index' => $ifIndex,
                    'name' => $name,
                    'type' => $type,
                    'status' => $status,
                    'speed_bps' => $speedValue,
                    'speed_mbps' => $speedMbps,
                    'in_octets' => $inOctets,
                    'out_octets' => $outOctets,
                    'in_bytes_formatted' => $this->formatBytes($inOctets),
                    'out_bytes_formatted' => $this->formatBytes($outOctets),
                    'in_errors' => $inErrors,
                    'out_errors' => $outErrors,
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to get interface stats: " . $e->getMessage());
        }
        
        return $interfaces;
    }
    
    /**
     * Get PON port traffic only
     */
    public function getPonTrafficStats(): array
    {
        return array_filter(
            $this->getInterfaceStats(),
            fn($iface) => $iface['type'] === 'pon'
        );
    }
    
    /**
     * Get Uplink port traffic only
     */
    public function getUplinkTrafficStats(): array
    {
        return array_filter(
            $this->getInterfaceStats(),
            fn($iface) => $iface['type'] === 'uplink'
        );
    }
    
    /**
     * Get traffic summary for dashboard
     */
    public function getTrafficSummary(): array
    {
        // Cache traffic data for 30 seconds to avoid repeated slow SNMP queries
        $cacheKey = "olt_traffic_{$this->olt->id}";
        $cacheTtl = 30; // seconds
        
        return Cache::remember($cacheKey, $cacheTtl, function () {
            $stats = $this->getInterfaceStats();
            
            $ponPorts = array_filter($stats, fn($s) => $s['type'] === 'pon');
            $uplinkPorts = array_filter($stats, fn($s) => $s['type'] === 'uplink');
            
            $ponIn = array_sum(array_column($ponPorts, 'in_octets'));
            $ponOut = array_sum(array_column($ponPorts, 'out_octets'));
            $uplinkIn = array_sum(array_column($uplinkPorts, 'in_octets'));
            $uplinkOut = array_sum(array_column($uplinkPorts, 'out_octets'));
            
            // Get optical power data
            $opticalData = $this->getOpticalPowerSummary();
            
            // Add port index for frontend mapping
            $ponPortsWithIndex = array_values(array_map(function($p) {
                // Ensure index is set for mapping optical data
                if (!isset($p['index'])) {
                    // Try to extract port number from name
                    if (preg_match('/(\d+)$/', $p['name'] ?? '', $m)) {
                        $p['index'] = (int)$m[1];
                    }
                }
                return $p;
            }, $ponPorts));
            
            return [
                'pon_ports' => [
                    'total' => count($ponPorts),
                    'up' => count(array_filter($ponPorts, fn($p) => $p['status'] === 'up')),
                    'down' => count(array_filter($ponPorts, fn($p) => $p['status'] === 'down')),
                    'in_octets' => $ponIn,
                    'out_octets' => $ponOut,
                    'in_formatted' => $this->formatBytes($ponIn),
                    'out_formatted' => $this->formatBytes($ponOut),
                    'ports' => $ponPortsWithIndex,
                ],
                'uplink_ports' => [
                    'total' => count($uplinkPorts),
                    'up' => count(array_filter($uplinkPorts, fn($p) => $p['status'] === 'up')),
                    'down' => count(array_filter($uplinkPorts, fn($p) => $p['status'] === 'down')),
                    'in_octets' => $uplinkIn,
                    'out_octets' => $uplinkOut,
                    'in_formatted' => $this->formatBytes($uplinkIn),
                    'out_formatted' => $this->formatBytes($uplinkOut),
                    'ports' => array_values($uplinkPorts),
                ],
                'optical_power' => $opticalData,
                'collected_at' => now()->toIso8601String(),
            ];
        });
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Get PON port optical power statistics from OLT SFP transceivers
     * This is the TX power (output power) of the OLT's PON port modules
     * 
     * Note: This base implementation returns empty array.
     * Each OLT brand should override this to implement actual data collection.
     * 
     * @return array Array of PON ports with optical power data (tx_power, temperature, etc.)
     */
    public function getPonOpticalPower(): array
    {
        // Base implementation returns empty - override in brand-specific helpers
        return [];
    }
    
    /**
     * Get optical power summary for all PON ports
     * Includes overall OLT optical health
     */
    public function getOpticalPowerSummary(): array
    {
        $ponPorts = $this->getPonOpticalPower();
        
        // Calculate overall TX power stats
        $allTxPowers = array_filter(array_column($ponPorts, 'tx_power'));
        $overallTxAvg = !empty($allTxPowers) ? round(array_sum($allTxPowers) / count($allTxPowers), 2) : null;
        $overallTxMin = !empty($allTxPowers) ? min($allTxPowers) : null;
        $overallTxMax = !empty($allTxPowers) ? max($allTxPowers) : null;
        
        // Count by signal quality
        $qualityCounts = [
            'excellent' => 0,
            'good' => 0,
            'acceptable' => 0,
            'warning' => 0,
            'unknown' => 0,
        ];
        foreach ($ponPorts as $port) {
            $quality = $port['signal_quality'] ?? 'unknown';
            $qualityCounts[$quality] = ($qualityCounts[$quality] ?? 0) + 1;
        }
        
        return [
            'pon_ports' => $ponPorts,
            'summary' => [
                'total_pon_ports' => count($ponPorts),
                'ports_with_data' => count($allTxPowers),
                'overall_tx_power_avg' => $overallTxAvg,
                'overall_tx_power_min' => $overallTxMin,
                'overall_tx_power_max' => $overallTxMax,
                'overall_tx_power_formatted' => $overallTxAvg !== null ? $overallTxAvg . ' dBm' : '-',
                'signal_quality_counts' => $qualityCounts,
            ],
            'collected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Abstract methods that each brand must implement
     */
    abstract public function getPonPorts(): array;
    abstract public function getPonPortInfo(int $slot, int $port): array;
    abstract public function getAllOnus(): array;
    abstract public function getOnusByPort(int $slot, int $port): array;
    abstract public function getOnuInfo(int $slot, int $port, int $onuId): array;
    abstract public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array;
    abstract public function getOnuBySerial(string $serialNumber): ?array;
    abstract public function getUnregisteredOnus(): array;
    abstract public function registerOnu(array $params): array;
    abstract public function unregisterOnu(int $slot, int $port, int $onuId): array;
    abstract public function rebootOnu(int $slot, int $port, int $onuId): array;
    abstract public function getOnuTraffic(int $slot, int $port, int $onuId): array;
    abstract public function getProfiles(string $type = 'all'): array;
    abstract public function applyServiceToOnu(int $slot, int $port, int $onuId, array $serviceConfig): array;
    abstract public function getUplinkPorts(): array;
    abstract public function syncAll(): array;
}
