<?php
/**
 * Simple SNMP test
 */

$host = '172.16.16.4';
$community = 'public';

echo "Test SNMP pada Hioso OLT\n";
echo "========================\n\n";

// Test basic SNMP
$sysDescr = @snmpget($host, $community, '1.3.6.1.2.1.1.1.0', 3000000, 1);
echo "sysDescr: " . ($sysDescr ?: 'NO RESPONSE') . "\n";

$sysObjectID = @snmpget($host, $community, '1.3.6.1.2.1.1.2.0', 3000000, 1);
echo "sysObjectID: " . ($sysObjectID ?: 'NO RESPONSE') . "\n";

// Test interfaces
echo "\nInterfaces:\n";
$interfaces = @snmpwalkoid($host, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 1);
if ($interfaces) {
    foreach ($interfaces as $oid => $value) {
        $idx = substr($oid, strrpos($oid, '.') + 1);
        echo "  [$idx] $value\n";
    }
} else {
    echo "  NO RESPONSE\n";
}

// Test enterprise 25355
echo "\nEnterprise 25355:\n";
$ent = @snmpwalkoid($host, $community, '1.3.6.1.4.1.25355', 5000000, 1);
if ($ent && count($ent) > 0) {
    echo "  FOUND: " . count($ent) . " OIDs!\n";
    $i = 0;
    foreach ($ent as $oid => $value) {
        echo "  $oid = $value\n";
        if (++$i >= 20) break;
    }
} else {
    echo "  NO RESPONSE\n";
}

// Test enterprise 17409 (Hioso standard)
echo "\nEnterprise 17409 (Hioso standard):\n";
$ent17409 = @snmpwalkoid($host, $community, '1.3.6.1.4.1.17409', 5000000, 1);
if ($ent17409 && count($ent17409) > 0) {
    echo "  FOUND: " . count($ent17409) . " OIDs!\n";
    $i = 0;
    foreach ($ent17409 as $oid => $value) {
        echo "  $oid = $value\n";
        if (++$i >= 20) break;
    }
} else {
    echo "  NO RESPONSE\n";
}

echo "\nDONE.\n";
