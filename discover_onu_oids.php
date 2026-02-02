<?php
/**
 * Deep OID Discovery for VSOL V1600D
 * Mencari OID yang benar untuk ONU data
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();

if (!$olt) {
    die("No OLT found\n");
}

echo "=== OID Discovery for: {$olt->name} ({$olt->ip_address}) ===\n\n";

// Set SNMP options
snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$timeout = 10000000; // 10 seconds
$retries = 2;

// Key OID trees to investigate
$treesToCheck = [
    // PON Port table - this might be where the "4" came from (4 PON ports)
    'PON Port Status (.5.1.1.3)' => '.1.3.6.1.4.1.37950.1.1.5.1.1.3',
    'PON Port Index (.5.1.1.1)' => '.1.3.6.1.4.1.37950.1.1.5.1.1.1',
    
    // V1600D ONU table - should have real ONUs
    'ONU Status V1600D (.5.2.1.5)' => '.1.3.6.1.4.1.37950.1.1.5.2.1.5',
    'ONU MAC V1600D (.5.2.1.4)' => '.1.3.6.1.4.1.37950.1.1.5.2.1.4',
    'ONU LLID V1600D (.5.2.1.3)' => '.1.3.6.1.4.1.37950.1.1.5.2.1.3',
    
    // Legacy/alternate ONU table (.5.12)
    'Legacy Col 2 (.5.12.1.1.1.2)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2',
    'Legacy Col 9 (.5.12.1.1.1.9)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1.9',
    
    // Try different table structures
    'Table .5.3 (.5.3)' => '.1.3.6.1.4.1.37950.1.1.5.3',
    'Table .5.4 (.5.4)' => '.1.3.6.1.4.1.37950.1.1.5.4',
    'Table .5.5 (.5.5)' => '.1.3.6.1.4.1.37950.1.1.5.5',
    'Table .5.6 (.5.6)' => '.1.3.6.1.4.1.37950.1.1.5.6',
    'Table .5.7 (.5.7)' => '.1.3.6.1.4.1.37950.1.1.5.7',
    'Table .5.8 (.5.8)' => '.1.3.6.1.4.1.37950.1.1.5.8',
    'Table .5.9 (.5.9)' => '.1.3.6.1.4.1.37950.1.1.5.9',
    'Table .5.10 (.5.10)' => '.1.3.6.1.4.1.37950.1.1.5.10',
    'Table .5.11 (.5.11)' => '.1.3.6.1.4.1.37950.1.1.5.11',
    
    // NMS EPON OLT PON specific (based on MIB reference)
    'OLT PON ONU Table (.5.12.1)' => '.1.3.6.1.4.1.37950.1.1.5.12.1',
    
    // H3C EPON (common on some VSOL)
    'H3C EPON ONU (.25506.2.104.1.2.1.1)' => '.1.3.6.1.4.1.25506.2.104.1.2.1.1',
];

echo "Scanning OID trees...\n";
echo str_repeat("=", 80) . "\n\n";

$results = [];

foreach ($treesToCheck as $name => $oid) {
    echo "Checking: {$name}\n";
    $startTime = microtime(true);
    
    $result = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    if ($result !== false && !empty($result)) {
        $count = count($result);
        echo "  ✓ Found {$count} entries ({$elapsed}ms)\n";
        
        // Show first 5 entries
        $i = 0;
        foreach ($result as $k => $v) {
            $displayVal = strlen($v) > 60 ? substr($v, 0, 60) . '...' : $v;
            echo "    {$k} = {$displayVal}\n";
            if (++$i >= 5) {
                if ($count > 5) echo "    ... and " . ($count - 5) . " more\n";
                break;
            }
        }
        
        $results[$name] = [
            'oid' => $oid,
            'count' => $count,
            'sample' => array_slice($result, 0, 5, true),
        ];
    } else {
        echo "  ✗ No data ({$elapsed}ms)\n";
    }
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 80) . "\n\n";

// Identify potential ONU tables (more than 4 entries usually indicates ONU data)
echo "Potential ONU data (>4 entries or has MAC-like values):\n";
foreach ($results as $name => $data) {
    $indicator = "";
    if ($data['count'] > 4) {
        $indicator = " [LIKELY ONU DATA - count > 4]";
    } elseif ($data['count'] == 4) {
        $indicator = " [Might be PON ports]";
    }
    echo "  - {$name}: {$data['count']} entries{$indicator}\n";
}

// Now try to walk entire .5 subtree to find all available tables
echo "\n\nWalking entire .1.3.6.1.4.1.37950.1.1.5 to find all tables...\n";
echo "(This may take a while)\n\n";

$fullResult = @snmp2_walk($olt->ip_address, $olt->snmp_community, '.1.3.6.1.4.1.37950.1.1.5', 60000000, 3);

if ($fullResult !== false && !empty($fullResult)) {
    // Group by table
    $tables = [];
    foreach ($fullResult as $oid => $val) {
        // Parse table identifier: .1.3.6.1.4.1.37950.1.1.5.X.Y.Z...
        if (preg_match('/\.1\.3\.6\.1\.4\.1\.37950\.1\.1\.5\.(\d+)/', $oid, $m)) {
            $tableId = $m[1];
            if (!isset($tables[$tableId])) {
                $tables[$tableId] = [];
            }
            $tables[$tableId][$oid] = $val;
        }
    }
    
    echo "Found " . count($tables) . " sub-tables:\n";
    foreach ($tables as $tableId => $entries) {
        $count = count($entries);
        echo "\n  Table .5.{$tableId}: {$count} OIDs\n";
        $i = 0;
        foreach ($entries as $oid => $val) {
            $displayVal = strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val;
            echo "    {$oid} = {$displayVal}\n";
            if (++$i >= 3) break;
        }
    }
    
    echo "\n\nTotal OIDs found: " . count($fullResult) . "\n";
} else {
    echo "Failed to walk .5 subtree\n";
}

echo "\nDone.\n";
