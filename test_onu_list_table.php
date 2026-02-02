<?php
/**
 * Test ONU List Table - VSOL V1600D
 * 
 * berdasarkan struktur MIB dari oid-base.com:
 * .1.3.6.1.4.1.37950.1.1.5.12.1.9 = onuListTable
 * .1.3.6.1.4.1.37950.1.1.5.12.1.9.1 = onuListEntry
 * 
 * Columns:
 * .1 = onuIndex
 * .2 = ponId
 * .3 = llid
 * .4 = status
 * .5 = macAddress
 */

require_once __DIR__ . '/vendor/autoload.php';

$oltIp = '172.16.16.3';
$community = 'public';

// Base OIDs
$baseOid = '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1';

$columns = [
    '1' => 'onuIndex',
    '2' => 'ponId',
    '3' => 'llid',
    '4' => 'status',
    '5' => 'macAddress',
];

echo "=== TEST ONU LIST TABLE - VSOL V1600D ===\n";
echo "OLT: {$oltIp}\n";
echo "Base OID: {$baseOid}\n\n";

// Collect data per column
$data = [];

foreach ($columns as $colId => $colName) {
    $oid = $baseOid . '.' . $colId;
    echo "Testing {$colName} ({$oid})...\n";
    
    $startTime = microtime(true);
    $result = @snmpwalk($oltIp, $community, $oid, 60000000, 5); // 60s timeout, 5 retries
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    if ($result === false) {
        echo "  ❌ FAILED (timeout or error) [{$elapsed}ms]\n";
        continue;
    }
    
    echo "  ✅ Found " . count($result) . " entries [{$elapsed}ms]\n";
    
    // Show first 10 entries
    foreach (array_slice($result, 0, 10) as $idx => $value) {
        // Format MAC if this is macAddress column
        if ($colName === 'macAddress' && is_string($value)) {
            // Handle hex string format
            if (preg_match('/^Hex-STRING: (.+)$/i', $value, $m)) {
                $hex = trim($m[1]);
                $formatted = str_replace(' ', ':', $hex);
                echo "    [{$idx}] {$formatted} (from: {$value})\n";
            } else {
                echo "    [{$idx}] {$value}\n";
            }
        } else {
            echo "    [{$idx}] {$value}\n";
        }
    }
    
    if (count($result) > 10) {
        echo "    ... and " . (count($result) - 10) . " more entries\n";
    }
    
    // Store data
    foreach ($result as $idx => $value) {
        $data[$idx][$colName] = $value;
    }
    
    echo "\n";
}

// If we got ponId and macAddress, try to correlate
if (!empty($data)) {
    echo "=== COMBINED ONU DATA ===\n";
    
    // Reorganize by index
    $onus = [];
    $idx = 0;
    while ($idx < 20) { // Check first 20 indices
        $entry = [];
        foreach ($columns as $colId => $colName) {
            if (isset($data[$idx][$colName])) {
                $entry[$colName] = $data[$idx][$colName];
            }
        }
        if (!empty($entry)) {
            $onus[$idx] = $entry;
        }
        $idx++;
    }
    
    if (!empty($onus)) {
        foreach ($onus as $idx => $onu) {
            echo "ONU #{$idx}:\n";
            foreach ($onu as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
            echo "\n";
        }
    } else {
        echo "No ONU data found\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
