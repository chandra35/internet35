<?php
/**
 * Debug ONU Index parsing
 */

require_once __DIR__ . '/vendor/autoload.php';

$ip = '172.16.16.3';
$community = 'private';

echo "=== DEBUG ONU LLID (ONU ID) PARSING ===\n\n";

// Get raw SNMP data
$baseOid = '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1';

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Get llid (ONU ID)
echo "Getting LLID values...\n";
$llids = @snmpwalkoid($ip, $community, $baseOid . '.3', 5000000, 2);
echo "Found " . count($llids) . " LLID entries\n\n";

echo "First 20 entries:\n";
$count = 0;
foreach ($llids as $oid => $val) {
    if ($count >= 20) break;
    // Extract index from OID
    preg_match('/\.(\d+)$/', $oid, $m);
    $index = $m[1] ?? 'N/A';
    echo "  Index={$index}, OID={$oid}, Value={$val}\n";
    $count++;
}

// Get MACs for same indexes
echo "\n\nGetting MAC for same indexes...\n";
$macs = @snmpwalkoid($ip, $community, $baseOid . '.5', 5000000, 2);
echo "Found " . count($macs) . " MAC entries\n\n";

echo "First 20 entries:\n";
$count = 0;
foreach ($macs as $oid => $val) {
    if ($count >= 20) break;
    preg_match('/\.(\d+)$/', $oid, $m);
    $index = $m[1] ?? 'N/A';
    echo "  Index={$index}, OID={$oid}, MAC={$val}\n";
    $count++;
}

// Now get the ponId
echo "\n\nGetting PON Port IDs...\n";
$ponPorts = @snmpwalkoid($ip, $community, $baseOid . '.2', 5000000, 2);
echo "Found " . count($ponPorts) . " PON port entries\n\n";

echo "First 20 entries:\n";
$count = 0;
foreach ($ponPorts as $oid => $val) {
    if ($count >= 20) break;
    preg_match('/\.(\d+)$/', $oid, $m);
    $index = $m[1] ?? 'N/A';
    echo "  Index={$index}, OID={$oid}, PonPort={$val}\n";
    $count++;
}

// Check alignment
echo "\n\n=== ALIGNMENT CHECK ===\n";
echo "Comparing first 10 entries across all tables:\n";

$macKeys = array_keys($macs);
$llidKeys = array_keys($llids);
$ponKeys = array_keys($ponPorts);

for ($i = 0; $i < 10 && $i < count($macKeys); $i++) {
    $macOid = $macKeys[$i];
    $llidOid = $llidKeys[$i] ?? 'N/A';
    $ponOid = $ponKeys[$i] ?? 'N/A';
    
    preg_match('/\.(\d+)$/', $macOid, $m);
    $macIndex = $m[1] ?? 'N/A';
    
    preg_match('/\.(\d+)$/', $llidOid, $m);
    $llidIndex = $m[1] ?? 'N/A';
    
    echo "Entry {$i}:\n";
    echo "  MAC index={$macIndex}, MAC={$macs[$macOid]}\n";
    echo "  LLID index={$llidIndex}, LLID={$llids[$llidOid]}\n";
    echo "  PON index=" . ($ponKeys[$i] ? preg_match('/\.(\d+)$/', $ponOid, $m) ? $m[1] : 'N/A' : 'N/A') . ", PON={$ponPorts[$ponOid]}\n";
    echo "\n";
}

echo "=== DONE ===\n";
