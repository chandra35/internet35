<?php
// Quick test: ZTE C320 SNMP RW capability + key OID categories
$host = '136.1.1.100';
$ro = 'combro';
$rw = 'combrow';

echo "=== ZTE C320 SNMP Full Capability Test ===\n\n";

// 1. Test RW community
echo "[1] RW Community Test (GET with RW community):\n";
$r = @snmp2_get($host, $rw, '1.3.6.1.2.1.1.1.0', 3000000, 1);
echo $r ? "  => WORKS: $r\n" : "  => FAILED\n";

// 2. Key OID categories - just count entries
$tests = [
    // System
    'sysDescr'       => ['1.3.6.1.2.1.1.1.0', 'get'],
    'sysUpTime'      => ['1.3.6.1.2.1.1.3.0', 'get'],
    
    // Interfaces (IF-MIB)
    'ifDescr'        => ['1.3.6.1.2.1.2.2.1.2', 'walk'],
    'ifOperStatus'   => ['1.3.6.1.2.1.2.2.1.8', 'walk'],
    'ifInOctets'     => ['1.3.6.1.2.1.2.2.1.10', 'walk'],
    'ifOutOctets'    => ['1.3.6.1.2.1.2.2.1.16', 'walk'],
    'ifHCInOctets'   => ['1.3.6.1.2.1.31.1.1.1.6', 'walk'],
    'ifAlias'        => ['1.3.6.1.2.1.31.1.1.1.18', 'walk'],
    
    // Q-BRIDGE (VLAN)
    'dot1qVlanStaticName'   => ['1.3.6.1.2.1.17.7.1.4.3.1.1', 'walk'],
    'dot1qNumVlans'         => ['1.3.6.1.2.1.17.7.1.1.4.0', 'get'],
    
    // BRIDGE-MIB (MAC)
    'dot1dTpFdbAddress'     => ['1.3.6.1.2.1.17.4.3.1.1', 'walk'],
    'dot1dTpFdbPort'        => ['1.3.6.1.2.1.17.4.3.1.2', 'walk'],
    
    // ENTITY-MIB (cards/hardware)
    'entPhysicalDescr'      => ['1.3.6.1.2.1.47.1.1.1.1.2', 'walk'],
    'entPhysicalName'       => ['1.3.6.1.2.1.47.1.1.1.1.7', 'walk'],
    'entPhysicalSerialNum'  => ['1.3.6.1.2.1.47.1.1.1.1.11', 'walk'],
    
    // ZTE GPON ONU (common ZTE private OIDs)
    'zteOnuIndex'           => ['1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.1', 'walk'],
    'zteOnuType'            => ['1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.2', 'walk'],
    'zteOnuSN'              => ['1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.3', 'walk'],
    'zteOnuStatus'          => ['1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.5', 'walk'],
    'zteOnuDesc'            => ['1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.6', 'walk'],
    
    // ZTE PON optical
    'ztePonRxPower'         => ['1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.4', 'walk'],
    'ztePonTxPower'         => ['1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.3', 'walk'],
    
    // ZTE Service Port
    'zteServicePort'        => ['1.3.6.1.4.1.3902.1082.500.1.2', 'walk'],
    
    // ZTE VLAN private
    'zteVlanConfig'         => ['1.3.6.1.4.1.3902.1082.500.20', 'walk'],
    
    // ZTE ONU distance/config
    'zteOnuDistance'         => ['1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.7', 'walk'],
    'zteOnuRxPower'         => ['1.3.6.1.4.1.3902.1082.500.10.2.3.1.1.4', 'walk'],
    'zteOnuTxPower'         => ['1.3.6.1.4.1.3902.1082.500.10.2.3.1.1.3', 'walk'],
    
    // ZTE Profile/template
    'zteDbaProfile'         => ['1.3.6.1.4.1.3902.1082.500.10.2.1.1.1.1', 'walk'],
    'zteLineProfile'        => ['1.3.6.1.4.1.3902.1082.500.10.2.1.2.1.1', 'walk'],
    'zteSrvProfile'         => ['1.3.6.1.4.1.3902.1082.500.10.2.1.3.1.1', 'walk'],
];

echo "\n[2] OID Capability Scan:\n";
echo str_pad("OID Name", 30) . str_pad("Type", 6) . str_pad("Result", 15) . "Sample\n";
echo str_repeat("-", 90) . "\n";

foreach ($tests as $name => [$oid, $type]) {
    if ($type === 'get') {
        $r = @snmp2_get($host, $ro, $oid, 3000000, 1);
        if ($r !== false) {
            $sample = substr($r, 0, 60);
            echo str_pad($name, 30) . str_pad($type, 6) . str_pad("OK", 15) . "$sample\n";
        } else {
            echo str_pad($name, 30) . str_pad($type, 6) . str_pad("FAIL", 15) . "\n";
        }
    } else {
        $r = @snmp2_walk($host, $ro, $oid, 3000000, 1);
        if ($r !== false && count($r) > 0) {
            $c = count($r);
            $sample = substr($r[0], 0, 60);
            echo str_pad($name, 30) . str_pad($type, 6) . str_pad("$c entries", 15) . "$sample\n";
        } else {
            echo str_pad($name, 30) . str_pad($type, 6) . str_pad("FAIL/EMPTY", 15) . "\n";
        }
    }
}

// 3. Test SNMP SET (non-destructive: try to SET sysContact which is usually writable)
echo "\n[3] SNMP SET Test (sysContact):\n";
$current = @snmp2_get($host, $ro, '1.3.6.1.2.1.1.4.0', 3000000, 1);
echo "  Current sysContact: $current\n";

// Try SET with RW community
$result = @snmp2_set($host, $rw, '1.3.6.1.2.1.1.4.0', 's', 'test-rw-access', 3000000, 1);
if ($result) {
    echo "  => SET SUCCEEDED! SNMP RW is fully functional.\n";
    // Restore original
    @snmp2_set($host, $rw, '1.3.6.1.2.1.1.4.0', 's', str_replace('"', '', $current), 3000000, 1);
    echo "  => Restored original value.\n";
} else {
    echo "  => SET FAILED. Error: " . error_get_last()['message'] . "\n";
}

echo "\nDone.\n";
