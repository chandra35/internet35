<?php
/**
 * Scan PON Port Optical Power OIDs - Simple version
 */

echo "Starting scan...\n";

// Hioso OLT
$hiosoIp = '172.16.16.4';
$vsolIp = '172.16.16.3';
$community = 'tahsin';

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "\n=== HIOSO OLT (172.16.16.4) ===\n";

// Hioso PON Port OIDs (Enterprise 25355)
$hiosoOids = [
    'PON Port Admin' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.2',
    'PON Port Oper' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.3',
    'PON Port TX Power' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.4',
    'PON Port RX Power' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1.5',
    'PON Optical Table' => '1.3.6.1.4.1.25355.2.3.4.1.2.1',
    'PON SFP Table' => '1.3.6.1.4.1.25355.2.3.4.1.3.1',
    'PON Diag' => '1.3.6.1.4.1.25355.2.3.4.1.4.1',
    'PON Table Full' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1',
];

foreach ($hiosoOids as $name => $oid) {
    echo "\n$name ($oid):\n";
    $result = @snmpwalkoid($hiosoIp, $community, $oid, 2000000, 2);
    if ($result && count($result) > 0) {
        $c = 0;
        foreach ($result as $k => $v) {
            $k = str_replace('iso.3.6.1', '1.3.6.1', $k);
            echo "  $k = $v\n";
            if (++$c >= 10) { echo "  ... more\n"; break; }
        }
    } else {
        echo "  (empty)\n";
    }
}

// Let's walk the entire PON port table
echo "\n--- Walking PON Table (.4.1.1.1.1) ---\n";
$result = @snmpwalkoid($hiosoIp, $community, '1.3.6.1.4.1.25355.2.3.4.1.1.1.1', 2000000, 2);
if ($result) {
    foreach ($result as $k => $v) {
        $k = str_replace('iso.3.6.1', '1.3.6.1', $k);
        echo "  $k = $v\n";
    }
}

echo "\n=== VSOL OLT (172.16.16.3) ===\n";

$vsolOids = [
    'PON Port Table' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1',
    'PON Optical' => '1.3.6.1.4.1.37950.1.1.5.11.1.2.1',
    'PON SFP' => '1.3.6.1.4.1.37950.1.1.5.11.2.1',
    'PON Diag' => '1.3.6.1.4.1.37950.1.1.5.13',
];

foreach ($vsolOids as $name => $oid) {
    echo "\n$name ($oid):\n";
    $result = @snmpwalkoid($vsolIp, $community, $oid, 2000000, 2);
    if ($result && count($result) > 0) {
        $c = 0;
        foreach ($result as $k => $v) {
            $k = str_replace('iso.3.6.1', '1.3.6.1', $k);
            echo "  $k = $v\n";
            if (++$c >= 15) { echo "  ... more\n"; break; }
        }
    } else {
        echo "  (empty)\n";
    }
}

echo "\nDone!\n";
