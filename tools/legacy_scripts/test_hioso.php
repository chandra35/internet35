<?php
/**
 * Test Hioso OID Reference
 * Based on user reference: .1.3.6.1.4.1.17409.2.3.6.3.1.2
 */
error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = '172.16.16.4';
$community = 'public';

echo "=== Test Hioso OID Reference ===\n";
echo "IP: $ip\n\n";

// Test dengan Enterprise 17409 (standar Hioso)
echo "=== Enterprise 17409 OIDs ===\n";
$oids17409 = [
    'ONU Status (.2.3.6.3.1.2)' => '1.3.6.1.4.1.17409.2.3.6.3.1.2',
    'ONU Table (.2.3.5.1.1.1)' => '1.3.6.1.4.1.17409.2.3.5.1.1.1',
    'ONU Serial (.2.3.5.1.1.1.1.2)' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
    'ONU Optical (.2.3.5.1.4.1)' => '1.3.6.1.4.1.17409.2.3.5.1.4.1',
    'PON Port (.2.3.4.1.1.1)' => '1.3.6.1.4.1.17409.2.3.4.1.1.1',
    'Base .2.3.6' => '1.3.6.1.4.1.17409.2.3.6',
    'Base .2.3' => '1.3.6.1.4.1.17409.2.3',
];

foreach ($oids17409 as $name => $oid) {
    echo "$name: ";
    $r = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
    if ($r && count($r) > 0) {
        echo count($r) . " entries found!\n";
        $i = 0;
        foreach ($r as $o => $v) {
            $so = str_replace('iso.3.6.1.4.1.17409', '.17409', $o);
            echo "  $so = $v\n";
            if (++$i >= 5) { echo "  ...\n"; break; }
        }
    } else {
        echo "empty\n";
    }
}

// Test dengan Enterprise 25355 (OLT kamu)
echo "\n=== Enterprise 25355 OIDs (sama struktur) ===\n";
$oids25355 = [
    'ONU Status (.2.3.6.3.1.2)' => '1.3.6.1.4.1.25355.2.3.6.3.1.2',
    'ONU Table (.2.3.5.1.1.1)' => '1.3.6.1.4.1.25355.2.3.5.1.1.1',
    'ONU Serial (.2.3.5.1.1.1.1.2)' => '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.2',
    'ONU Optical (.2.3.5.1.4.1)' => '1.3.6.1.4.1.25355.2.3.5.1.4.1',
    'PON Port (.2.3.4.1.1.1)' => '1.3.6.1.4.1.25355.2.3.4.1.1.1',
    'Base .2.3.6' => '1.3.6.1.4.1.25355.2.3.6',
    'Base .2.3' => '1.3.6.1.4.1.25355.2.3',
];

foreach ($oids25355 as $name => $oid) {
    echo "$name: ";
    $r = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
    if ($r && count($r) > 0) {
        echo count($r) . " entries found!\n";
        $i = 0;
        foreach ($r as $o => $v) {
            $so = str_replace('iso.3.6.1.4.1.25355', '.25355', $o);
            echo "  $so = $v\n";
            if (++$i >= 5) { echo "  ...\n"; break; }
        }
    } else {
        echo "empty\n";
    }
}

// Scan sub-trees under 25355
echo "\n=== Scanning 25355 sub-trees ===\n";
for ($i = 1; $i <= 10; $i++) {
    $oid = "1.3.6.1.4.1.25355.$i";
    echo ".$i: ";
    $r = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
    if ($r && count($r) > 0) {
        echo count($r) . " OIDs\n";
        foreach (array_slice($r, 0, 3, true) as $o => $v) {
            $so = str_replace('iso.3.6.1.4.1.25355', '.25355', $o);
            echo "  $so = $v\n";
        }
    } else {
        echo "empty\n";
    }
}

echo "\nDone!\n";
