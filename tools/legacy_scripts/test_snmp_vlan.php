<?php
// Test SNMP VLAN OIDs on ZTE C320
$host = '136.1.1.100';
$community_ro = 'combro';
$community_rw = 'combrow';

echo "=== ZTE C320 SNMP VLAN Test ===\n\n";

// Standard Q-BRIDGE-MIB OIDs for VLAN
$oids = [
    'dot1qVlanStaticName'          => '1.3.6.1.2.1.17.7.1.4.3.1.1',
    'dot1qVlanStaticEgressPorts'   => '1.3.6.1.2.1.17.7.1.4.3.1.2',
    'dot1qVlanForbiddenEgressPorts'=> '1.3.6.1.2.1.17.7.1.4.3.1.3',
    'dot1qVlanStaticUntaggedPorts' => '1.3.6.1.2.1.17.7.1.4.3.1.4',
    'dot1qVlanStaticRowStatus'     => '1.3.6.1.2.1.17.7.1.4.3.1.5',
    'dot1qVlanCurrentTable'        => '1.3.6.1.2.1.17.7.1.4.2.1',
    'dot1qNumVlans'                => '1.3.6.1.2.1.17.7.1.1.4.0',
    'dot1qMaxVlanId'               => '1.3.6.1.2.1.17.7.1.1.3.0',
];

// ZTE private VLAN MIB candidates under enterprise 3902
$zte_oids = [
    'zteVlan_1015'        => '1.3.6.1.4.1.3902.1015.2.1.9',
    'zteVlan_1082_1'      => '1.3.6.1.4.1.3902.1082.500.1.2',
    'zteVlan_1082_2'      => '1.3.6.1.4.1.3902.1082.500.1.3',
    'zteVlan_1082_svcport'=> '1.3.6.1.4.1.3902.1082.500.20',
    'zteBridge'           => '1.3.6.1.4.1.3902.1015.2.1.4',
];

echo "--- Testing Q-BRIDGE-MIB (Standard VLAN OIDs) ---\n";
foreach ($oids as $name => $oid) {
    echo "\n[$name] OID: $oid\n";
    $result = @snmpwalk($host, $community_ro, $oid, 2000000, 0);
    if ($result === false) {
        // Try snmp2_walk
        $result = @snmp2_walk($host, $community_ro, $oid, 2000000, 0);
    }
    if ($result === false) {
        echo "  => NOT AVAILABLE / No response\n";
    } else {
        $count = count($result);
        echo "  => $count entries\n";
        // Show first 10
        foreach (array_slice($result, 0, 10) as $k => $v) {
            echo "  [$k] $v\n";
        }
        if ($count > 10) echo "  ... ($count total)\n";
    }
}

echo "\n\n--- Testing ZTE Private VLAN OIDs ---\n";
foreach ($zte_oids as $name => $oid) {
    echo "\n[$name] OID: $oid\n";
    $result = @snmp2_walk($host, $community_ro, $oid, 2000000, 0);
    if ($result === false) {
        echo "  => NOT AVAILABLE / No response\n";
    } else {
        $count = count($result);
        echo "  => $count entries\n";
        foreach (array_slice($result, 0, 10) as $k => $v) {
            echo "  [$k] $v\n";
        }
        if ($count > 10) echo "  ... ($count total)\n";
    }
}

echo "\n\n--- Testing SNMP SET (RW) with dot1qVlanStaticRowStatus ---\n";
echo "Community RW: $community_rw\n";
// Just test if RW community responds (GET, not SET)
$result = @snmp2_get($host, $community_rw, '1.3.6.1.2.1.1.1.0', 2000000, 0);
if ($result === false) {
    echo "  => RW community NOT responding\n";
} else {
    echo "  => RW community works! sysDescr = $result\n";
}

echo "\nDone.\n";
