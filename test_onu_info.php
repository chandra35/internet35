<?php
// Check more OIDs for ONU info

$ip = '172.16.16.3';
$community = 'private';

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Check onuListTable description (.6)
echo "=== onuListTable description (.6) ===\n";
$walk = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.6', 1000000, 3);
if ($walk) {
    $count = 0;
    foreach ($walk as $oid => $value) {
        echo "$oid = $value\n";
        $count++;
        if ($count >= 15) break;
    }
}

// Check distance from opmDiagInfoTable
echo "\n\n=== opmDiagInfoTable structure ===\n";
// .1 = ponId, .2 = onuId, .3-? = various diag info

// Try .3 to .10
for ($i = 1; $i <= 12; $i++) {
    $oid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.$i.1.1";
    $val = @snmpget($ip, $community, $oid, 1000000, 1);
    echo ".12.2.1.8.1.$i.1.1 = $val\n";
}

echo "\n\n=== Check onuAuthInfoRtt (.13) for distance ===\n";
$walk2 = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.13', 1000000, 3);
if ($walk2) {
    $count = 0;
    foreach ($walk2 as $oid => $value) {
        echo "$oid = $value\n";
        $count++;
        if ($count >= 10) break;
    }
}
