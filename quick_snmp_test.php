<?php
/**
 * Quick SNMP Test - Check specific OIDs
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();
echo "OLT: {$olt->ip_address}\n\n";

snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

// Only test the most critical OIDs with short timeout
$timeout = 5000000; // 5 seconds
$retries = 1;

$tests = [
    'PON Port Status (should be 4)' => '.1.3.6.1.4.1.37950.1.1.5.1.1.3',
    'Legacy Col 2 (suspected PON not ONU)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2',
];

foreach ($tests as $name => $oid) {
    echo "=== {$name} ===\n";
    echo "OID: {$oid}\n";
    $r = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);
    if ($r && !empty($r)) {
        echo "Count: " . count($r) . "\n";
        foreach ($r as $k => $v) {
            echo "  {$k} = {$v}\n";
        }
    } else {
        echo "FAILED\n";
    }
    echo "\n";
}

// Try to walk .5.12 completely to see structure
echo "=== Walking .5.12 subtree ===\n";
$r = @snmp2_walk($olt->ip_address, $olt->snmp_community, '.1.3.6.1.4.1.37950.1.1.5.12', 30000000, 2);
if ($r && !empty($r)) {
    echo "Total OIDs: " . count($r) . "\n\n";
    
    // Group by column
    $cols = [];
    foreach ($r as $oid => $val) {
        // Parse: .1.3.6.1.4.1.37950.1.1.5.12.1.1.1.{col}.{port}.{onuid}
        if (preg_match('/\.1\.3\.6\.1\.4\.1\.37950\.1\.1\.5\.12\.1\.1\.1\.(\d+)\.(\d+)(?:\.(\d+))?/', $oid, $m)) {
            $col = $m[1];
            $port = $m[2];
            $onuid = $m[3] ?? 'N/A';
            
            if (!isset($cols[$col])) $cols[$col] = [];
            $cols[$col][] = [
                'oid' => $oid,
                'port' => $port,
                'onuid' => $onuid,
                'value' => $val
            ];
        }
    }
    
    ksort($cols);
    foreach ($cols as $col => $entries) {
        echo "Column {$col}: " . count($entries) . " entries\n";
        foreach (array_slice($entries, 0, 3) as $e) {
            $displayVal = strlen($e['value']) > 40 ? substr($e['value'], 0, 40) . '...' : $e['value'];
            echo "  Port={$e['port']}, OnuId={$e['onuid']}, Value={$displayVal}\n";
        }
        if (count($entries) > 3) echo "  ... and " . (count($entries) - 3) . " more\n";
        echo "\n";
    }
} else {
    echo "FAILED\n";
}
