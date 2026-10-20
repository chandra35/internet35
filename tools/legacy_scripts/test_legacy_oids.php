<?php
/**
 * Test Legacy VSOL OIDs
 * Quick test untuk mapping OID yang benar
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();

if (!$olt) {
    die("No OLT found\n");
}

echo "=== Testing OLT: {$olt->name} ({$olt->ip_address}) ===\n\n";

// Set SNMP options
snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
snmp_set_enum_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$timeout = 10000000; // 10 seconds
$retries = 2;

// Test OIDs - legacy tree columns (1-20)
$legacyColumns = [];
for ($col = 1; $col <= 25; $col++) {
    $legacyColumns[$col] = ".1.3.6.1.4.1.37950.1.1.5.12.1.1.1.{$col}";
}

echo "Testing legacy ONU table columns:\n";
echo str_repeat("-", 80) . "\n";

foreach ($legacyColumns as $col => $oid) {
    $startTime = microtime(true);
    $result = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    if ($result !== false && !empty($result)) {
        $count = count($result);
        $firstKey = array_key_first($result);
        $firstVal = $result[$firstKey];
        
        // Determine data type
        $valType = 'unknown';
        if (is_numeric($firstVal)) {
            $valType = 'integer';
        } elseif (preg_match('/^[0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5}$/', $firstVal)) {
            $valType = 'MAC';
        } elseif (preg_match('/^[0-9a-fA-F]+$/', $firstVal) && strlen($firstVal) >= 8) {
            $valType = 'hex-string';
        } else {
            $valType = 'string';
        }
        
        // Truncate value for display
        $displayVal = strlen($firstVal) > 40 ? substr($firstVal, 0, 40) . '...' : $firstVal;
        
        echo sprintf(
            "Column %2d: ✓ %d entries (%dms) - Type: %-10s - Sample: %s\n",
            $col, $count, $elapsed, $valType, $displayVal
        );
    } else {
        echo sprintf("Column %2d: ✗ FAILED (%dms)\n", $col, $elapsed);
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Testing alternative ONU tables:\n";
echo str_repeat("-", 80) . "\n";

// Test V1600D specific tables
$v1600dTables = [
    'v1600d_status' => '.1.3.6.1.4.1.37950.1.1.5.2.1.5',
    'v1600d_mac' => '.1.3.6.1.4.1.37950.1.1.5.2.1.4',
    'v1600d_llid' => '.1.3.6.1.4.1.37950.1.1.5.2.1.3',
    'v1600d_ponport' => '.1.3.6.1.4.1.37950.1.1.5.2.1.2',
];

foreach ($v1600dTables as $name => $oid) {
    $startTime = microtime(true);
    $result = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    if ($result !== false && !empty($result)) {
        $count = count($result);
        $firstKey = array_key_first($result);
        $firstVal = $result[$firstKey];
        echo sprintf("%-15s: ✓ %d entries (%dms) - Sample: %s\n", $name, $count, $elapsed, substr($firstVal, 0, 40));
    } else {
        echo sprintf("%-15s: ✗ FAILED (%dms)\n", $name, $elapsed);
    }
}

echo "\nDone.\n";
