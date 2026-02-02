<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();

if (!$olt) {
    echo "No OLT found in database\n";
    exit;
}

echo "=== OLT Configuration ===\n";
echo "Name: {$olt->name}\n";
echo "Brand: {$olt->brand}\n";
echo "Model: {$olt->model}\n";
echo "IP: {$olt->ip_address}\n";
echo "SNMP Community: {$olt->snmp_community}\n";
echo "SNMP Version: {$olt->snmp_version}\n";
echo "Telnet User: {$olt->telnet_username}\n";
echo "Telnet Port: {$olt->telnet_port}\n";
echo "\n";

// Try SNMP test
snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

echo "=== Testing SNMP ===\n";
$result = @snmp2_get($olt->ip_address, $olt->snmp_community, '.1.3.6.1.2.1.1.5.0', 3000000, 3);
echo "System Name: " . ($result !== false ? $result : "FAILED") . "\n";

$result = @snmp2_get($olt->ip_address, $olt->snmp_community, '.1.3.6.1.2.1.1.1.0', 3000000, 3);
echo "System Descr: " . ($result !== false ? substr($result, 0, 80) : "FAILED") . "\n";

// Walk entire VSOL enterprise tree 
echo "\n=== Walking VSOL Enterprise OID ===\n";
$result = @snmp2_walk($olt->ip_address, $olt->snmp_community, '.1.3.6.1.4.1.37950', 10000000, 5);
if ($result !== false && !empty($result)) {
    $count = 0;
    foreach ($result as $oid => $val) {
        echo "$oid => " . substr($val, 0, 60) . "\n";
        if (++$count > 100) {
            echo "... (" . (count($result) - 100) . " more entries) ...\n";
            break;
        }
    }
    echo "\nTotal entries: " . count($result) . "\n";
} else {
    echo "FAILED or EMPTY\n";
    
    // Try to find what OIDs are available
    echo "\nTrying alternative discovery...\n";
    $result = @snmp2_walk($olt->ip_address, $olt->snmp_community, '.1.3.6.1.4.1', 15000000, 5);
    if ($result !== false && !empty($result)) {
        $enterprises = [];
        foreach ($result as $oid => $val) {
            if (preg_match('/\.1\.3\.6\.1\.4\.1\.(\d+)/', $oid, $m)) {
                $enterprises[$m[1]] = true;
            }
        }
        echo "Found enterprise IDs: " . implode(', ', array_keys($enterprises)) . "\n";
    }
}
