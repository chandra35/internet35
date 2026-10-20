<?php
/**
 * Simple Test - Hioso Telnet ONU Parser
 * Test parsing logic dari output Telnet
 */

$host = '172.16.16.4';
$username = 'admin';
$password = 'admin';

echo "=== Hioso Telnet ONU Parser Test ===\n\n";

/**
 * Parse Hioso uptime string
 */
function parseHiosoUptime(string $uptime): int {
    $seconds = 0;
    if (preg_match('/(\d+)D/i', $uptime, $m)) $seconds += (int)$m[1] * 86400;
    if (preg_match('/(\d+)H/i', $uptime, $m)) $seconds += (int)$m[1] * 3600;
    if (preg_match('/(\d+)M/i', $uptime, $m)) $seconds += (int)$m[1] * 60;
    if (preg_match('/(\d+)S/i', $uptime, $m)) $seconds += (int)$m[1];
    return $seconds;
}

/**
 * Parse ONU info output
 */
function parseOnuInfoOutput(string $output): array {
    $onus = [];
    $lines = explode("\n", $output);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, 'OnuId') === 0 || strpos($line, '===') === 0) continue;
        if (strpos($line, 'EPON') === 0) continue;
        if (strpos($line, '--- Enter') !== false) continue;
        
        // Pattern: slot/port:onuId MAC Status Firmware ChipId Ge Fe Pots CtcStatus CtcVer Activate Uptime Name
        // Example: 0/1:1  08:5c:1b:de:39:dd Down    3230     6301   4  2  1    --             30     Yes      0H0M0S           Jani-28
        if (preg_match('/^(\d+)\/(\d+):(\d+)\s+([0-9a-f:]+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\s+(\w+)\s+(\S+)\s*(.*)?$/i', $line, $m)) {
            $onus[] = [
                'slot' => (int)$m[1],
                'port' => (int)$m[2],
                'onu_id' => (int)$m[3],
                'mac_address' => strtoupper($m[4]),
                'serial_number' => str_replace(':', '', strtoupper($m[4])),
                'status' => strtolower($m[5]) === 'up' ? 'online' : 'offline',
                'firmware' => $m[6],
                'chip_id' => $m[7],
                'ge_ports' => (int)$m[8],
                'fe_ports' => (int)$m[9],
                'pots_ports' => (int)$m[10],
                'ctc_status' => $m[11],
                'uptime_seconds' => parseHiosoUptime($m[14]),
                'description' => trim($m[15] ?? ''),
            ];
        }
    }
    
    return $onus;
}

// Connect and get data
$sock = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$sock) die("Connection failed: $errstr\n");

stream_set_timeout($sock, 30);

function rd($sock, $wait = 3) {
    $buf = '';
    $end = time() + $wait;
    while (time() < $end) {
        $c = @fread($sock, 1024);
        if ($c) $buf .= $c;
        else usleep(100000);
        if (preg_match('/EPON[#>]\s*$/', $buf)) break;
    }
    return $buf;
}

// Login
rd($sock, 5);
fwrite($sock, "$username\r\n"); usleep(500000);
rd($sock, 2);
fwrite($sock, "$password\r\n"); usleep(500000);
rd($sock, 3);
fwrite($sock, "enable\r\n"); usleep(500000);
rd($sock, 2);

echo "Connected and logged in.\n\n";

// Get ONU info from each port
$allOnus = [];

for ($port = 1; $port <= 4; $port++) {
    echo "Querying port 0/$port...\n";
    
    fwrite($sock, "show onu info epon 0/$port all\r\n");
    usleep(1000000);
    
    $out = '';
    $end = time() + 15;
    while (time() < $end) {
        $c = @fread($sock, 4096);
        if ($c) {
            $out .= $c;
            // Handle pagination
            if (strpos($out, '--More--') !== false || strpos($out, '--- Enter Key') !== false) {
                fwrite($sock, " ");
                $out = preg_replace('/--More--|--- Enter Key.*----/', '', $out);
            }
            if (preg_match('/EPON#\s*$/', $out)) break;
        }
        usleep(100000);
    }
    
    // Clean output
    $out = str_replace("\r", "", $out);
    
    // Parse
    $onus = parseOnuInfoOutput($out);
    echo "  Found " . count($onus) . " ONUs\n";
    
    $allOnus = array_merge($allOnus, $onus);
}

// Close
fwrite($sock, "exit\r\n");
fclose($sock);

// Summary
echo "\n=== Summary ===\n";
echo "Total ONUs: " . count($allOnus) . "\n\n";

// Group by port and status
$byPort = [];
$online = 0;
$offline = 0;

foreach ($allOnus as $onu) {
    $key = "{$onu['slot']}/{$onu['port']}";
    if (!isset($byPort[$key])) $byPort[$key] = ['online' => 0, 'offline' => 0, 'onus' => []];
    $byPort[$key]['onus'][] = $onu;
    
    if ($onu['status'] === 'online') {
        $byPort[$key]['online']++;
        $online++;
    } else {
        $byPort[$key]['offline']++;
        $offline++;
    }
}

echo "By Port:\n";
foreach ($byPort as $port => $data) {
    echo "  Port $port: " . count($data['onus']) . " ONUs ";
    echo "(Online: {$data['online']}, Offline: {$data['offline']})\n";
}

echo "\nTotal Online: $online\n";
echo "Total Offline: $offline\n";

// Show sample ONUs
echo "\n=== Sample ONUs (First 5) ===\n";
foreach (array_slice($allOnus, 0, 5) as $onu) {
    echo "{$onu['slot']}/{$onu['port']}:{$onu['onu_id']} ";
    echo "MAC:{$onu['mac_address']} ";
    echo "Status:{$onu['status']} ";
    echo "Uptime:" . gmdate("H\Hi\Ms\S", $onu['uptime_seconds']) . " ";
    echo "Name:{$onu['description']}\n";
}

echo "\nDone!\n";
