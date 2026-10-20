<?php
/**
 * Discover SNMP OIDs for Hioso OLT with Enterprise ID 25355
 */

// Suppress MIB warnings
putenv('MIBS=');
error_reporting(E_ERROR);

$host = '172.16.16.4';
$community = 'telecom';

echo "=== Discovering OIDs on Hioso OLT (Enterprise 25355) ===\n\n";

// Test base OIDs for both Enterprise IDs
$testOids = [
    // Standard Hioso (17409)
    '17409 - ONU Serial' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
    '17409 - ONU Status' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.4',
    '17409 - PON Port Admin' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.2',
    
    // Haishuo (25355)
    '25355 - ONU Serial' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.2',
    '25355 - ONU Status' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.4',
    '25355 - PON Port Admin' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.2',
    
    // Try some base enterprise walks
    '25355 Base' => '1.3.6.1.4.1.25355',
    '17409 Base' => '1.3.6.1.4.1.17409',
];

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

foreach ($testOids as $name => $oid) {
    echo "--- {$name} ---\n";
    echo "OID: {$oid}\n";
    
    // Try walk first
    $result = @snmpwalkoid($host, $community, $oid, 5000000, 2);
    
    if ($result === false || empty($result)) {
        echo "Result: No data\n\n";
    } else {
        echo "Found " . count($result) . " entries:\n";
        $i = 0;
        foreach ($result as $fullOid => $val) {
            if ($i < 10) {
                // Truncate long values
                $displayVal = strlen($val) > 50 ? substr($val, 0, 47) . '...' : $val;
                echo "  {$fullOid} = {$displayVal}\n";
            }
            $i++;
        }
        if ($i > 10) {
            echo "  ... and " . ($i - 10) . " more\n";
        }
        echo "\n";
    }
}

// Also check ifDescr for PON port names
echo "=== Interface Descriptions (ifDescr) ===\n";
$ifDescrs = @snmpwalkoid($host, $community, '1.3.6.1.2.1.2.2.1.2', 5000000, 2);
if ($ifDescrs) {
    foreach ($ifDescrs as $oid => $val) {
        echo "  {$oid} = {$val}\n";
    }
}

// Check for any ONU-related tables in common EPON MIB paths
echo "\n=== Searching common EPON ONU OIDs ===\n";
$commonPaths = [
    'EPON ONU Table (2.5.1)' => '1.3.6.1.4.1.25355.2.5.1',
    'EPON ONU Table (2.3.5)' => '1.3.6.1.4.1.25355.2.3.5',
    'Common EPON (.2.3)' => '1.3.6.1.4.1.25355.2.3',
    'Common EPON (.2.5)' => '1.3.6.1.4.1.25355.2.5',
    'Try .3' => '1.3.6.1.4.1.25355.3',
    'Try .4' => '1.3.6.1.4.1.25355.4',
];

foreach ($commonPaths as $name => $oid) {
    echo "--- {$name} ({$oid}) ---\n";
    $result = @snmpwalkoid($host, $community, $oid, 10000000, 2);
    if ($result === false || empty($result)) {
        echo "No data\n\n";
    } else {
        echo "Found " . count($result) . " entries\n";
        $i = 0;
        foreach ($result as $fullOid => $val) {
            if ($i < 5) {
                $displayVal = strlen($val) > 50 ? substr($val, 0, 47) . '...' : $val;
                echo "  {$fullOid} = {$displayVal}\n";
            }
            $i++;
        }
        if ($i > 5) {
            echo "  ... and " . ($i - 5) . " more\n";
        }
        echo "\n";
    }
}

echo "\n=== Done ===\n";
