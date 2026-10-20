<?php
/**
 * Targeted ONU search - check MAC table and try different community strings
 */

$host = '172.16.16.4';
$timeout = 2000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Targeted ONU Search ===\n";
echo "Host: $host\n\n";

// Test different community strings
$communities = ['public', 'private', 'telecom', 'admin', 'hioso'];

echo "=== 1. Testing Community Strings ===\n";
foreach ($communities as $comm) {
    $result = @snmpget($host, $comm, '.1.3.6.1.2.1.1.1.0', 1000000, 0);
    if ($result !== false) {
        echo "  [$comm] WORKS - $result\n";
    } else {
        echo "  [$comm] No response\n";
    }
}

$community = 'public'; // Use public for rest of tests

// Quick targeted checks
echo "\n=== 2. MAC/FDB Table ===\n";
$macOids = [
    'dot1dTpFdbAddress' => '1.3.6.1.2.1.17.4.3.1.1',
    'dot1dTpFdbPort' => '1.3.6.1.2.1.17.4.3.1.2',
    'dot1dTpFdbStatus' => '1.3.6.1.2.1.17.4.3.1.3',
];

foreach ($macOids as $name => $oid) {
    echo "[$name] ";
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result && count($result) > 0) {
        echo count($result) . " entries\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            if ($name == 'dot1dTpFdbAddress' && strlen($value) == 6) {
                $value = strtoupper(bin2hex($value));
                $value = implode(':', str_split($value, 2));
            }
            echo "  $fullOid = $value\n";
            if (++$i >= 10) {
                echo "  ... (" . count($result) . " total)\n";
                break;
            }
        }
    } else {
        echo "No data\n";
    }
    echo "\n";
}

// Interface table summary
echo "=== 3. Interface Count ===\n";
$ifNumber = @snmpget($host, $community, '.1.3.6.1.2.1.2.1.0', $timeout, $retries);
echo "ifNumber: $ifNumber\n";

// Check for high interface indexes (ONUs sometimes appear as interfaces)
echo "\n=== 4. Checking for ONU Interfaces ===\n";
$ifDescr = @snmpwalkoid($host, $community, '1.3.6.1.2.1.2.2.1.2', $timeout, $retries);
if ($ifDescr) {
    echo "Interface descriptions:\n";
    foreach ($ifDescr as $oid => $value) {
        $idx = substr($oid, strrpos($oid, '.') + 1);
        $lower = strtolower($value);
        $marker = (strpos($lower, 'onu') !== false || strpos($lower, 'epon') !== false) ? ' *** ONU!' : '';
        echo "  [$idx] $value$marker\n";
    }
}

// Try enterprise with longer timeout
echo "\n=== 5. Enterprise 25355 (long timeout) ===\n";
$entResult = @snmpwalkoid($host, $community, '1.3.6.1.4.1.25355', 10000000, 2);
if ($entResult && count($entResult) > 0) {
    echo "SUCCESS! Found " . count($entResult) . " OIDs:\n";
    foreach ($entResult as $oid => $value) {
        echo "  $oid = $value\n";
    }
} else {
    echo "No enterprise OIDs available\n";
}

// Try some common Hioso OID patterns (from other Hioso models)
echo "\n=== 6. Testing Known Hioso OID Patterns ===\n";
$hiosoPatterns = [
    '1.3.6.1.4.1.17409.2.3.1.1.1' => 'PON Port Table',
    '1.3.6.1.4.1.17409.2.3.4.1.1' => 'ONU Table',
    '1.3.6.1.4.1.17409.2.3.5.1.1' => 'ONU Serial',
    '1.3.6.1.4.1.25355.2.3.4.1' => 'ONU Table (25355)',
    '1.3.6.1.4.1.25355.2.3.5.1' => 'ONU Serial (25355)',
];

foreach ($hiosoPatterns as $oid => $name) {
    echo "[$name] $oid: ";
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result && count($result) > 0) {
        echo count($result) . " OIDs!\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            echo "  $fullOid = $value\n";
            if (++$i >= 5) break;
        }
    } else {
        echo "No data\n";
    }
}

echo "\n=== DONE ===\n";
