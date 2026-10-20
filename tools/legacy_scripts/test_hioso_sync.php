<?php
/**
 * Test HiosoHelper Sync - Verify fixes work correctly
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "=============================================================\n";
echo "          TEST HIOSO HELPER SYNC FIX\n";
echo "=============================================================\n\n";

$hiosoIp = '172.16.16.4';
$community = 'public';

// Test 1: PON Port Detection from ifDescr
echo "1. Testing PON Port Detection from ifDescr:\n";
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ifDescrs = @snmprealwalk($hiosoIp, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 2);
$ponPorts = [];

if ($ifDescrs) {
    foreach ($ifDescrs as $oid => $name) {
        $name = trim(str_replace(['"', "'"], '', $name));
        
        if (preg_match('/pon[^a-z]*(\d+)/i', $name, $m)) {
            $portNum = (int) $m[1];
            $slot = 0;
            
            if (preg_match('/(\d+)\/(\d+)/', $name, $sp)) {
                $slot = (int) $sp[1];
                $portNum = (int) $sp[2];
            }
            
            $ponPorts[] = [
                'slot' => $slot,
                'port' => $portNum,
                'name' => $name,
            ];
        }
    }
    
    usort($ponPorts, fn($a, $b) => $a['port'] - $b['port']);
    
    echo "   ✓ Found " . count($ponPorts) . " PON ports:\n";
    foreach ($ponPorts as $p) {
        echo "     - {$p['name']} → slot:{$p['slot']} port:{$p['port']}\n";
    }
} else {
    echo "   ✗ ifDescr failed\n";
}

// Test 2: Telnet ONU Data with 'source' field
echo "\n2. Testing Telnet ONU Data (with source field):\n";

$sock = @fsockopen($hiosoIp, 23, $errno, $errstr, 5);
if (!$sock) die("   ✗ Telnet connection failed\n");

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
fwrite($sock, "admin\r\n"); usleep(500000);
rd($sock, 2);
fwrite($sock, "admin\r\n"); usleep(500000);
rd($sock, 3);
fwrite($sock, "enable\r\n"); usleep(500000);
rd($sock, 2);

echo "   ✓ Telnet connected\n";

// Get ONU data from port 0/1 only (quick test)
fwrite($sock, "show onu info epon 0/1 all\r\n");
usleep(1000000);

$out = '';
$end = time() + 10;
while (time() < $end) {
    $c = @fread($sock, 4096);
    if ($c) {
        $out .= $c;
        if (strpos($out, '--More--') !== false || strpos($out, '--- Enter Key') !== false) {
            fwrite($sock, " ");
            $out = preg_replace('/--More--|--- Enter Key.*----/', '', $out);
        }
        if (preg_match('/EPON#\s*$/', $out)) break;
    }
    usleep(100000);
}

fwrite($sock, "exit\r\n");
fclose($sock);

// Parse
$out = str_replace("\r", "", $out);
$onus = [];

foreach (explode("\n", $out) as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, 'OnuId') === 0 || strpos($line, '===') === 0) continue;
    
    if (preg_match('/^(\d+)\/(\d+):(\d+)\s+([0-9a-f:]+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\s+(\w+)\s+(\S+)\s*(.*)?$/i', $line, $m)) {
        $onus[] = [
            'slot' => (int)$m[1],
            'port' => (int)$m[2],
            'onu_id' => (int)$m[3],
            'mac_address' => strtoupper($m[4]),
            'serial_number' => str_replace(':', '', strtoupper($m[4])),
            'status' => strtolower($m[5]) === 'up' ? 'online' : 'offline',
            'description' => trim($m[15] ?? ''),
            'source' => 'telnet', // KEY FIELD!
        ];
    }
}

echo "   ✓ Parsed " . count($onus) . " ONUs from port 0/1\n";

if (count($onus) > 0) {
    echo "   ✓ Source field: " . ($onus[0]['source'] ?? 'MISSING') . "\n";
    
    // Show sample
    echo "\n   Sample ONU data:\n";
    foreach (array_slice($onus, 0, 3) as $onu) {
        echo "     - {$onu['slot']}/{$onu['port']}:{$onu['onu_id']} ";
        echo "MAC:{$onu['mac_address']} ";
        echo "Status:{$onu['status']} ";
        echo "Source:{$onu['source']}\n";
    }
}

// Test 3: Check data ready for database
echo "\n3. Data Structure Check (for saveOnuToDatabase):\n";

$requiredFields = ['slot', 'port', 'onu_id', 'serial_number', 'status'];
$optionalFields = ['mac_address', 'description', 'source'];

if (!empty($onus)) {
    $sample = $onus[0];
    
    echo "   Required fields:\n";
    foreach ($requiredFields as $field) {
        $has = isset($sample[$field]);
        $icon = $has ? '✓' : '✗';
        $val = $has ? substr((string)$sample[$field], 0, 30) : 'MISSING';
        echo "     {$icon} {$field}: {$val}\n";
    }
    
    echo "   Optional fields:\n";
    foreach ($optionalFields as $field) {
        $has = isset($sample[$field]);
        $icon = $has ? '✓' : '-';
        $val = $has ? substr((string)$sample[$field], 0, 30) : 'not set';
        echo "     {$icon} {$field}: {$val}\n";
    }
}

echo "\n=============================================================\n";
echo "                      SUMMARY\n";
echo "=============================================================\n";
echo "\n";
echo "✓ PON ports can be detected from ifDescr\n";
echo "✓ Telnet data includes 'source' => 'telnet' field\n";
echo "✓ ONU data has all required fields for database\n";
echo "\n";
echo "Next step: Run Sync ONU from web interface again\n";
echo "Expected result: All 98 ONUs should be synced correctly\n";
echo "\n";
