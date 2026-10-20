<?php
/**
 * Quick SNMP sections check - test each MIB section separately
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 3000000;
$retries = 1;
$outputFile = __DIR__ . '/snmp_sections.txt';

$out = [];
$out[] = "=== SNMP Sections Check on Hioso OLT ===";
$out[] = "Host: $host";
$out[] = "Time: " . date('Y-m-d H:i:s');
$out[] = "";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

function walkSection($host, $community, $oid, $timeout, $retries) {
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    return $result ?: [];
}

// Test each major MIB section
$sections = [
    '1.3.6.1.2.1.1' => 'System MIB',
    '1.3.6.1.2.1.2' => 'Interfaces MIB',
    '1.3.6.1.2.1.4' => 'IP MIB',
    '1.3.6.1.2.1.6' => 'TCP MIB',
    '1.3.6.1.2.1.7' => 'UDP MIB',
    '1.3.6.1.2.1.11' => 'SNMP MIB',
    '1.3.6.1.2.1.17' => 'Bridge MIB',
    '1.3.6.1.2.1.31' => 'IF-MIB Extensions',
    '1.3.6.1.2.1.47' => 'Entity MIB',
    '1.3.6.1.2.1.99' => 'Entity Sensor MIB',
    '1.3.6.1.2.1.155' => 'EPON MIB (dot3EponMIB)',
    '1.3.6.1.4.1.25355' => 'Enterprise 25355 (Haishuo)',
    '1.3.6.1.4.1.17409' => 'Enterprise 17409 (Hioso)',
];

$totalOids = 0;

foreach ($sections as $oid => $name) {
    $out[] = "--- $name ($oid) ---";
    $result = walkSection($host, $community, $oid, $timeout, $retries);
    $count = count($result);
    $totalOids += $count;
    
    if ($count == 0) {
        $out[] = "  NO RESPONSE";
    } else {
        $out[] = "  Found $count OIDs:";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            $shortValue = is_string($value) ? substr($value, 0, 80) : $value;
            $out[] = "    $fullOid = $shortValue";
            if (++$i >= 20) {
                if ($count > 20) $out[] = "    ... ($count total)";
                break;
            }
        }
    }
    $out[] = "";
}

$out[] = "=== Summary ===";
$out[] = "Total OIDs found: $totalOids";
$out[] = "";
$out[] = "=== DONE ===";

file_put_contents($outputFile, implode("\n", $out));
echo "Saved to $outputFile\n";
echo "Total OIDs: $totalOids\n";
