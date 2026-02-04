<?php
/**
 * Full SNMP Walk - discover ALL available OIDs on Hioso OLT
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 5000000; // 5 sec
$retries = 1;

echo "=== Full SNMP Walk on Hioso OLT ===\n";
echo "Host: $host\n";
echo "This will take a while...\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Walk from root of MIB tree
$startOid = '1.3.6.1';

echo "Walking from $startOid...\n\n";

$result = @snmpwalkoid($host, $community, $startOid, $timeout, $retries);

if (!$result) {
    echo "No response!\n";
    exit;
}

echo "Total OIDs found: " . count($result) . "\n\n";

// Group OIDs by major sections
$groups = [
    '1.3.6.1.2.1.1' => 'System (MIB-II)',
    '1.3.6.1.2.1.2' => 'Interfaces',
    '1.3.6.1.2.1.3' => 'Address Translation',
    '1.3.6.1.2.1.4' => 'IP',
    '1.3.6.1.2.1.5' => 'ICMP',
    '1.3.6.1.2.1.6' => 'TCP',
    '1.3.6.1.2.1.7' => 'UDP',
    '1.3.6.1.2.1.10' => 'Transmission',
    '1.3.6.1.2.1.11' => 'SNMP Statistics',
    '1.3.6.1.2.1.17' => 'Bridge MIB',
    '1.3.6.1.2.1.31' => 'IF-MIB Extensions',
    '1.3.6.1.2.1.47' => 'Entity MIB',
    '1.3.6.1.2.1.99' => 'Entity Sensor MIB',
    '1.3.6.1.2.1.155' => 'EPON MIB',
    '1.3.6.1.4.1' => 'Enterprise (Private)',
];

$categorized = [];
$uncategorized = [];

foreach ($result as $oid => $value) {
    $found = false;
    foreach ($groups as $prefix => $name) {
        if (strpos($oid, $prefix) === 0) {
            if (!isset($categorized[$name])) {
                $categorized[$name] = [];
            }
            $categorized[$name][$oid] = $value;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $uncategorized[$oid] = $value;
    }
}

// Print categorized results
foreach ($categorized as $category => $oids) {
    echo "=== $category (" . count($oids) . " OIDs) ===\n";
    $i = 0;
    foreach ($oids as $oid => $value) {
        $shortValue = is_string($value) ? substr($value, 0, 80) : $value;
        echo "  $oid = $shortValue\n";
        if (++$i >= 10) {
            if (count($oids) > 10) {
                echo "  ... (" . count($oids) . " total)\n";
            }
            break;
        }
    }
    echo "\n";
}

// Print uncategorized
if (!empty($uncategorized)) {
    echo "=== Uncategorized (" . count($uncategorized) . " OIDs) ===\n";
    foreach ($uncategorized as $oid => $value) {
        $shortValue = is_string($value) ? substr($value, 0, 80) : $value;
        echo "  $oid = $shortValue\n";
    }
}

// Special focus on Enterprise OIDs
echo "\n\n=== Enterprise OIDs Detail ===\n";
$enterpriseOids = array_filter($result, function($oid) {
    return strpos($oid, '1.3.6.1.4.1') === 0;
}, ARRAY_FILTER_USE_KEY);

if (!empty($enterpriseOids)) {
    echo "Found " . count($enterpriseOids) . " enterprise OIDs:\n";
    foreach ($enterpriseOids as $oid => $value) {
        echo "  $oid = $value\n";
    }
} else {
    echo "No enterprise OIDs found\n";
}

// Look for anything related to PON/ONU
echo "\n\n=== Search for PON/ONU related ===\n";
foreach ($result as $oid => $value) {
    $valueStr = strtolower((string)$value);
    if (strpos($valueStr, 'pon') !== false || 
        strpos($valueStr, 'onu') !== false ||
        strpos($valueStr, 'epon') !== false ||
        strpos($valueStr, 'optical') !== false) {
        echo "  $oid = $value\n";
    }
}

echo "\n=== DONE ===\n";
