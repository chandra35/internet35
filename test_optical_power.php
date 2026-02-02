<?php
/**
 * Test VSOL V1600D Optical Power OIDs
 */

ini_set('display_errors', 0);
error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = '172.16.16.3';
$community = 'private';

echo "=== VSOL V1600D Optical Power Test ===\n\n";

// Test berbagai OID untuk optical power
$testOids = [
    // MIB opmDiagInfo (.12.2.1.8)
    'opmDiag.txPower' => '1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6',
    'opmDiag.rxPower' => '1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7',
    
    // onuOpmDiag (.12.2.1.13)
    'onuOpmDiag.txPower' => '1.3.6.1.4.1.37950.1.1.5.12.2.1.13.1.6',
    'onuOpmDiag.rxPower' => '1.3.6.1.4.1.37950.1.1.5.12.2.1.13.1.7',
    
    // Try onuStatisticsTable (.12.1.20)
    'onuStat.5' => '1.3.6.1.4.1.37950.1.1.5.12.1.20.1.5',
    'onuStat.6' => '1.3.6.1.4.1.37950.1.1.5.12.1.20.1.6',
    
    // PON Port
    'ponPort.10' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1.10',
    'ponPort.11' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1.11',
];

foreach ($testOids as $name => $oid) {
    $data = @snmpwalkoid($ip, $community, $oid, 2000000, 1);
    if ($data && count($data) > 0) {
        echo "OK $name: " . count($data) . " entries\n";
        $i = 0;
        foreach ($data as $o => $val) {
            if ($i++ < 2) {
                preg_match('/\.(\d+(?:\.\d+)*)$/', $o, $m);
                echo "   .{$m[1]} = $val\n";
            }
        }
    } else {
        echo "-- $name: No data\n";
    }
}

// Quick test parent
echo "\n=== Quick scan onuSla (.12.2) ===\n";
$data = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.2', 5000000, 1);
if ($data) {
    echo "Found " . count($data) . " entries under .12.2\n";
    $i = 0;
    foreach ($data as $o => $val) {
        if ($i++ < 10) {
            $short = preg_replace('/^.*37950\.1\.1\.5\./', '.', $o);
            echo "  $short = " . substr($val, 0, 40) . "\n";
        }
    }
} else {
    echo "No data under .12.2\n";
}

echo "\nDone.\n";
