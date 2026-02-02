<?php
/**
 * Explore VSOL .12.1.20 OID tree (3401 entries found!)
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();
echo "OLT: {$olt->ip_address}\n\n";

snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

$timeout = 30000000; // 30 seconds  
$retries = 2;

// The big table - .12.1.20
$oid = '.1.3.6.1.4.1.37950.1.1.5.12.1.20';

echo "Walking OID: {$oid}\n";
echo "This may take a while (3401 entries)...\n\n";

$result = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);

if ($result !== false && !empty($result)) {
    echo "Total entries: " . count($result) . "\n\n";
    
    // Group by sub-table structure
    // OID format: .1.3.6.1.4.1.37950.1.1.5.12.1.20.{subtable}.{entry}.{column}.{index...}
    $subtables = [];
    
    foreach ($result as $fullOid => $val) {
        // Parse OID after .12.1.20
        if (preg_match('/\.1\.3\.6\.1\.4\.1\.37950\.1\.1\.5\.12\.1\.20\.(\d+)/', $fullOid, $m)) {
            $subtable = $m[1];
            if (!isset($subtables[$subtable])) {
                $subtables[$subtable] = [];
            }
            $subtables[$subtable][$fullOid] = $val;
        }
    }
    
    echo "Found " . count($subtables) . " sub-tables:\n";
    echo str_repeat("-", 80) . "\n\n";
    
    ksort($subtables);
    foreach ($subtables as $subtableId => $entries) {
        echo "Sub-table .12.1.20.{$subtableId}: " . count($entries) . " entries\n";
        
        // Show first 5 entries
        $i = 0;
        foreach ($entries as $oid => $val) {
            // Truncate display
            $shortOid = str_replace('.1.3.6.1.4.1.37950.1.1.5.12.1.20.', '.20.', $oid);
            $displayVal = strlen($val) > 40 ? substr($val, 0, 40) . '...' : $val;
            
            // Check if value looks like MAC
            if (preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i', $val)) {
                $displayVal = "MAC: {$val}";
            }
            
            echo "  {$shortOid} = {$displayVal}\n";
            if (++$i >= 5) break;
        }
        if (count($entries) > 5) echo "  ... and " . (count($entries) - 5) . " more\n";
        echo "\n";
    }
    
    // Look for MAC addresses in the data
    echo str_repeat("=", 80) . "\n";
    echo "Searching for MAC addresses...\n\n";
    
    $macs = [];
    foreach ($result as $oid => $val) {
        // Look for MAC format
        if (preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i', $val) && $val !== '00:00:00:00:00:00') {
            $macs[$oid] = $val;
        }
        // Look for hex string that could be MAC
        if (preg_match('/^[0-9a-f]{12}$/i', $val)) {
            $formatted = implode(':', str_split($val, 2));
            $macs[$oid] = "HEX->MAC: {$formatted}";
        }
    }
    
    if (!empty($macs)) {
        echo "Found " . count($macs) . " MAC addresses:\n";
        foreach ($macs as $oid => $mac) {
            $shortOid = str_replace('.1.3.6.1.4.1.37950.1.1.5.12.1.20.', '.20.', $oid);
            echo "  {$shortOid} = {$mac}\n";
        }
    } else {
        echo "No MAC addresses found in standard format.\n";
    }
    
} else {
    echo "FAILED to walk OID!\n";
}

echo "\nDone.\n";
