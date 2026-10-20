<?php
// Quick Hioso OID Test dengan timeout pendek
require_once __DIR__ . '/vendor/autoload.php';

$ip = '172.16.16.4';
$community = 'public';

echo "=== Quick Hioso OID Test ===\n";
echo "IP: $ip\n\n";

// Set timeout sangat pendek
snmp_set_quick_print(true);
snmp_set_oid_numeric_print(true);

// Test OID dari referensi user
$testOids = [
    // Dari referensi: 1.3.6.1.4.1.17409.2.3.6.3.1.2 untuk ONU status
    'ONU Status (17409)' => '1.3.6.1.4.1.17409.2.3.6.3.1.2',
    'ONU Status (25355)' => '1.3.6.1.4.1.25355.2.3.6.3.1.2',
    
    // sysObjectID prefix test
    'sysObjectID base' => '1.3.6.1.4.1.25355.4',
    'Enterprise 25355 root' => '1.3.6.1.4.1.25355',
    
    // Mungkin OID langsung di bawah enterprise
    '25355.1' => '1.3.6.1.4.1.25355.1',
    '25355.2' => '1.3.6.1.4.1.25355.2',
    '25355.3' => '1.3.6.1.4.1.25355.3',
    '25355.4.1' => '1.3.6.1.4.1.25355.4.1',
    '25355.4.2' => '1.3.6.1.4.1.25355.4.2',
    '25355.4.3.1' => '1.3.6.1.4.1.25355.4.3.1',
];

foreach ($testOids as $name => $oid) {
    echo "$name: ";
    try {
        $result = @snmpget($ip, $community, $oid, 1000000, 1); // 1 detik timeout, 1 retry
        if ($result !== false) {
            echo $result . "\n";
        } else {
            echo "no response\n";
        }
    } catch (Exception $e) {
        echo "error\n";
    }
}

echo "\n=== Single Walk Test (timeout 2s) ===\n";
// Walk hanya 1 level dengan timeout pendek
$walkOids = [
    '25355.4.3' => '1.3.6.1.4.1.25355.4.3',
];

foreach ($walkOids as $name => $oid) {
    echo "Walk $name: ";
    try {
        $result = @snmprealwalk($ip, $community, $oid, 2000000, 1);
        if ($result !== false && !empty($result)) {
            echo "\n";
            foreach (array_slice($result, 0, 10) as $k => $v) {
                echo "  $k => $v\n";
            }
            if (count($result) > 10) {
                echo "  ... (" . (count($result) - 10) . " more)\n";
            }
        } else {
            echo "empty\n";
        }
    } catch (Exception $e) {
        echo "error\n";
    }
}

echo "\n=== LLDP/CDP Check ===\n";
// Cek apakah ada LLDP/CDP yang bisa memberikan info
$lldpOids = [
    'lldpRemTable' => '1.0.8802.1.1.2.1.4.1',
    'lldpLocPortTable' => '1.0.8802.1.1.2.1.3.7',
];

foreach ($lldpOids as $name => $oid) {
    echo "$name: ";
    try {
        $result = @snmpget($ip, $community, $oid, 1000000, 1);
        echo ($result !== false) ? $result : "no\n";
    } catch (Exception $e) {
        echo "no\n";
    }
}

echo "\nDone!\n";
