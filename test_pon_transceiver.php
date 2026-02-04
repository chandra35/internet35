<?php
/**
 * Test VSOL PON Transceiver Table from LibreNMS MIB
 * OID: 1.3.6.1.4.1.37950.1.1.5.10.13.1 (ponTransceiverTable)
 */

$ip = '172.16.16.3';
$community = 'public';

echo "Testing VSOL PON Transceiver Table (from LibreNMS MIB)\n";
echo str_repeat("=", 60) . "\n\n";

// ponTransceiverTable at .37950.1.1.5.10.13.1.1.x
$baseOid = '1.3.6.1.4.1.37950.1.1.5.10.13.1.1';

$fields = [
    1 => 'transceiverPonIndex',
    2 => 'tempperature',
    3 => 'voltage',
    4 => 'biasCurrent',
    5 => 'transmitPower',
];

foreach ($fields as $idx => $name) {
    echo "$name (.$idx):\n";
    $result = @snmpwalk($ip, $community, "$baseOid.$idx", 2000000, 3);
    if ($result && !empty($result)) {
        foreach ($result as $val) {
            echo "  $val\n";
        }
    } else {
        echo "  (no data)\n";
    }
    echo "\n";
}

echo "\n\nFull walk of ponTransceiverTable:\n";
echo str_repeat("-", 60) . "\n";
$result = @snmprealwalk($ip, $community, '1.3.6.1.4.1.37950.1.1.5.10.13.1', 2000000, 3);
if ($result && !empty($result)) {
    foreach ($result as $oid => $val) {
        $short = preg_replace('/^.*37950/', '.37950', $oid);
        echo "$short => $val\n";
    }
} else {
    echo "(table not available on this firmware)\n";
}
