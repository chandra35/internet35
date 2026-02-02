<?php
/**
 * Simple test - just walk the entire 12.2 subtree
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "Checking 12.2 subtree...\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$oid = '1.3.6.1.4.1.37950.1.1.5.12.2';
$result = @snmprealwalk($oltIp, $community, $oid, 30000000, 3);

if ($result) {
    echo "Found " . count($result) . " entries:\n";
    foreach ($result as $k => $v) {
        $short = preg_replace('/^.*?37950/', '37950', $k);
        echo "  {$short} = {$v}\n";
    }
} else {
    echo "No data or timeout.\n";
}

echo "\n\nNow checking 12.3...\n";
$oid = '1.3.6.1.4.1.37950.1.1.5.12.3';
$result = @snmprealwalk($oltIp, $community, $oid, 30000000, 3);

if ($result) {
    echo "Found " . count($result) . " entries:\n";
    foreach ($result as $k => $v) {
        $short = preg_replace('/^.*?37950/', '37950', $k);
        echo "  {$short} = {$v}\n";
    }
} else {
    echo "No data or timeout.\n";
}

echo "\nDone.\n";
