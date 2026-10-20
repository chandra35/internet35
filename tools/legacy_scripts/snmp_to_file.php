<?php
/**
 * Full SNMP Walk - save to file directly
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 5000000;
$retries = 1;
$outputFile = __DIR__ . '/snmp_output.txt';

$output = [];
$output[] = "=== Full SNMP Walk on Hioso OLT ===";
$output[] = "Host: $host";
$output[] = "Time: " . date('Y-m-d H:i:s');
$output[] = "";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Walk from root
$result = @snmpwalkoid($host, $community, '1.3.6.1', $timeout, $retries);

if (!$result) {
    $output[] = "No response!";
    file_put_contents($outputFile, implode("\n", $output));
    echo "Saved to $outputFile\n";
    exit;
}

$output[] = "Total OIDs found: " . count($result);
$output[] = "";

// Group by prefix
$groups = [];
foreach ($result as $oid => $value) {
    // Get first 5 levels of OID
    $parts = explode('.', $oid);
    $prefix = implode('.', array_slice($parts, 0, 7));
    if (!isset($groups[$prefix])) {
        $groups[$prefix] = [];
    }
    $groups[$prefix][$oid] = $value;
}

// Output grouped
foreach ($groups as $prefix => $oids) {
    $output[] = "=== $prefix (" . count($oids) . " OIDs) ===";
    $i = 0;
    foreach ($oids as $oid => $value) {
        $shortValue = is_string($value) ? substr($value, 0, 100) : $value;
        $output[] = "  $oid = $shortValue";
        if (++$i >= 15) {
            if (count($oids) > 15) {
                $output[] = "  ... (" . count($oids) . " total)";
            }
            break;
        }
    }
    $output[] = "";
}

// Enterprise specific
$output[] = "";
$output[] = "=== ALL ENTERPRISE OIDs ===";
foreach ($result as $oid => $value) {
    if (strpos($oid, '1.3.6.1.4.1') === 0) {
        $output[] = "  $oid = $value";
    }
}

// PON/ONU related values
$output[] = "";
$output[] = "=== PON/ONU RELATED VALUES ===";
foreach ($result as $oid => $value) {
    $valueStr = strtolower((string)$value);
    if (strpos($valueStr, 'pon') !== false || 
        strpos($valueStr, 'onu') !== false ||
        strpos($valueStr, 'epon') !== false) {
        $output[] = "  $oid = $value";
    }
}

$output[] = "";
$output[] = "=== DONE ===";

file_put_contents($outputFile, implode("\n", $output));
echo "Saved to $outputFile (" . count($result) . " OIDs)\n";
