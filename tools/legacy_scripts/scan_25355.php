<?php
/**
 * Scan OID tree untuk Hioso OLT Enterprise 25355
 */
error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = '172.16.16.4';
$community = 'public';

echo "=== Hioso OLT 25355 Full Scan ===\n";
echo "IP: $ip, Community: $community\n\n";

// Walk enterprise 25355
echo "Walking 1.3.6.1.4.1.25355...\n";
$start = microtime(true);
$result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.25355', 30000000, 3);
$elapsed = round(microtime(true) - $start, 1);

if ($result && count($result) > 0) {
    echo "Found " . count($result) . " OIDs ({$elapsed}s)\n\n";
    
    foreach ($result as $oid => $value) {
        $shortOid = str_replace('iso.3.6.1.4.1.25355', '.25355', $oid);
        $shortVal = strlen($value) > 60 ? substr($value, 0, 57) . '...' : $value;
        echo "$shortOid = $shortVal\n";
    }
} else {
    echo "No data ({$elapsed}s). Trying sub-OIDs...\n\n";
    
    for ($i = 1; $i <= 10; $i++) {
        $oid = "1.3.6.1.4.1.25355.$i";
        echo "Checking .$i... ";
        $r = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
        if ($r && count($r) > 0) {
            echo count($r) . " OIDs found!\n";
            foreach (array_slice($r, 0, 5, true) as $o => $v) {
                $so = str_replace('iso.3.6.1.4.1.25355', '.25355', $o);
                echo "  $so = $v\n";
            }
        } else {
            echo "empty\n";
        }
    }
}

echo "\nDone!\n";
