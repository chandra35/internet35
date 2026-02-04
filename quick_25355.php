<?php
/**
 * Quick SNMP scan untuk OLT 172.16.16.4
 */
putenv('MIBS=');

$host = '172.16.16.4';
$community = 'telecom';

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Quick SNMP Scan ===\n\n";

// Test ifDescr first - should work
echo "--- ifDescr (interfaces) ---\n";
$result = @snmpwalkoid($host, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 1);
if ($result) {
    foreach ($result as $oid => $val) {
        echo "  " . basename($oid) . " = {$val}\n";
    }
} else {
    echo "No data or timeout\n";
}

echo "\n--- Testing ONU OIDs ---\n";

// Test both enterprise OIDs for ONU Serial
$oids = [
    '17409' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
    '25355' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.2',
];

foreach ($oids as $ent => $oid) {
    echo "Enterprise {$ent} ONU Serial: ";
    $result = @snmpwalkoid($host, $community, $oid, 3000000, 1);
    if ($result && count($result) > 0) {
        echo count($result) . " ONUs found\n";
        foreach (array_slice($result, 0, 3, true) as $fullOid => $val) {
            echo "  {$fullOid} = {$val}\n";
        }
    } else {
        echo "No data\n";
    }
}

echo "\n--- Walk Enterprise 25355 base ---\n";
$result = @snmpwalkoid($host, $community, '1.3.6.1.4.1.25355', 10000000, 1);
if ($result && count($result) > 0) {
    echo "Found " . count($result) . " OIDs\n";
    $i = 0;
    foreach ($result as $oid => $val) {
        if ($i < 20) {
            $displayVal = strlen($val) > 40 ? substr($val, 0, 37) . '...' : $val;
            echo "  {$oid} = {$displayVal}\n";
        }
        $i++;
    }
    if ($i > 20) echo "  ... (" . ($i - 20) . " more)\n";
} else {
    echo "No data from Enterprise 25355\n";
}

echo "\nDone.\n";
