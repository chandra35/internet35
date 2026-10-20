<?php
// Test ONU Type/Model OIDs for VSOL V1600D

$ip = '172.16.16.3';
$community = 'private';

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// onuAuthInfoTable OIDs
// Base: 1.3.6.1.4.1.37950.1.1.5.12.1.12.1
// .7 = onutype
// .10 = onuAuthInfoDescription

echo "=== Testing ONU Type from onuAuthInfoTable ===\n\n";

// Walk onutype
$walk = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.7', 1000000, 3);
if ($walk) {
    echo "onutype (.7) - Found " . count($walk) . " entries:\n";
    $count = 0;
    foreach ($walk as $oid => $value) {
        echo "  $oid = $value\n";
        $count++;
        if ($count >= 10) break;
    }
}

echo "\n\n=== Testing onuAuthInfoDescription (.10) ===\n";
$walk2 = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.10', 1000000, 3);
if ($walk2) {
    echo "onuAuthInfoDescription (.10) - Found " . count($walk2) . " entries:\n";
    $count = 0;
    foreach ($walk2 as $oid => $value) {
        echo "  $oid = $value\n";
        $count++;
        if ($count >= 10) break;
    }
}

echo "\n\n=== Testing onuType2 (.14) ===\n";
$walk3 = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.14', 1000000, 3);
if ($walk3) {
    echo "onuType2 (.14) - Found " . count($walk3) . " entries:\n";
    $count = 0;
    foreach ($walk3 as $oid => $value) {
        echo "  $oid = $value\n";
        $count++;
        if ($count >= 10) break;
    }
}

// Test distance OID
echo "\n\n=== Testing Distance (opmDiagInfoTable) ===\n";
// Check OID .12.2.1.8.1.x for distance
$walkDist = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1', 1000000, 3);
if ($walkDist) {
    echo "opmDiagInfoTable - Found " . count($walkDist) . " entries:\n";
    $count = 0;
    foreach ($walkDist as $oid => $value) {
        echo "  $oid = $value\n";
        $count++;
        if ($count >= 20) break;
    }
}
