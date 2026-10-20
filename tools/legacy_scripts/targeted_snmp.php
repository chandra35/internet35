<?php
/**
 * Targeted SNMP Test - Check the exact OID that found "4" entries
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();
echo "OLT: {$olt->ip_address}, Community: {$olt->snmp_community}\n\n";

snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

$timeout = 15000000; // 15 seconds
$retries = 2;

// The OID that was found to return data: .1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2
$oid = '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2';
echo "Testing OID: {$oid}\n";
echo "(Status OID from legacy table)\n\n";

$r = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);

if ($r && !empty($r)) {
    echo "Found " . count($r) . " entries:\n\n";
    
    foreach ($r as $fullOid => $val) {
        // Parse: .1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2.{port}.{onuid}
        if (preg_match('/\.1\.3\.6\.1\.4\.1\.37950\.1\.1\.5\.12\.1\.1\.1\.2\.(\d+)\.(\d+)/', $fullOid, $m)) {
            $port = $m[1];
            $onuid = $m[2];
            echo "Full OID: {$fullOid}\n";
            echo "  Parsed: Port={$port}, ONU_ID={$onuid}\n";
            echo "  Value: {$val}\n";
            echo "\n";
        } else {
            echo "Could not parse: {$fullOid} = {$val}\n\n";
        }
    }
    
    echo "\n=== Analysis ===\n";
    // Count unique ports
    $ports = [];
    $onusPerPort = [];
    foreach ($r as $fullOid => $val) {
        if (preg_match('/\.(\d+)\.(\d+)$/', $fullOid, $m)) {
            $port = $m[1];
            $onuid = $m[2];
            $ports[$port] = true;
            if (!isset($onusPerPort[$port])) $onusPerPort[$port] = [];
            $onusPerPort[$port][] = $onuid;
        }
    }
    
    echo "Unique PON Ports with ONUs: " . count($ports) . "\n";
    foreach ($onusPerPort as $port => $onus) {
        echo "  Port {$port}: " . count($onus) . " ONU(s) - IDs: " . implode(', ', $onus) . "\n";
    }
    
} else {
    echo "FAILED to get data!\n";
    echo "Last SNMP error: " . snmp_get_last_error_msg() . "\n";
}
