<?php
require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL & ~E_WARNING);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = $argv[1] ?? '172.16.16.4';
$community = $argv[2] ?? 'public';

echo "=== Exploring OLT $ip for Device Model ===\n\n";

// Walk enterprise 25355
echo "=== Enterprise 25355 (first 100 entries) ===\n";
$result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.25355', 10000000, 3);
if ($result && count($result) > 0) {
    echo "Total entries: " . count($result) . "\n\n";
    $i = 0;
    foreach ($result as $oid => $val) {
        $shortOid = str_replace('iso.3.6.1.4.1.25355', '.25355', $oid);
        $valDisplay = is_string($val) && strlen($val) > 60 ? substr($val, 0, 60) . '...' : $val;
        echo "$shortOid = $valDisplay\n";
        if (++$i >= 100) {
            echo "...\n";
            break;
        }
    }
} else {
    echo "Empty or failed. Trying standard MIB OIDs...\n";
}

// Try standard MIB OIDs that often have model info
echo "\n=== Standard MIB OIDs ===\n";
$standardOids = [
    'sysDescr' => '1.3.6.1.2.1.1.1.0',
    'sysObjectID' => '1.3.6.1.2.1.1.2.0',
    'sysName' => '1.3.6.1.2.1.1.5.0',
    'sysLocation' => '1.3.6.1.2.1.1.6.0',
    'entPhysicalDescr.1' => '1.3.6.1.2.1.47.1.1.1.1.2.1',
    'entPhysicalName.1' => '1.3.6.1.2.1.47.1.1.1.1.7.1',
    'entPhysicalModelName.1' => '1.3.6.1.2.1.47.1.1.1.1.13.1',
    'entPhysicalSerialNum.1' => '1.3.6.1.2.1.47.1.1.1.1.11.1',
    'entPhysicalSoftwareRev.1' => '1.3.6.1.2.1.47.1.1.1.1.10.1',
    'entPhysicalHardwareRev.1' => '1.3.6.1.2.1.47.1.1.1.1.8.1',
];

foreach ($standardOids as $name => $oid) {
    $val = @snmpget($ip, $community, $oid, 3000000, 2);
    if ($val !== false) {
        echo "$name: $val\n";
    }
}

// Walk Entity MIB for physical entities
echo "\n=== Entity Physical Table (entPhysicalTable) ===\n";
$entPhys = @snmpwalkoid($ip, $community, '1.3.6.1.2.1.47.1.1.1.1', 5000000, 2);
if ($entPhys && count($entPhys) > 0) {
    echo "Total entries: " . count($entPhys) . "\n";
    $i = 0;
    foreach ($entPhys as $oid => $val) {
        $shortOid = str_replace('iso.3.6.1.2.1.47.1.1.1.1', '.47.1.1.1.1', $oid);
        echo "$shortOid = $val\n";
        if (++$i >= 50) {
            echo "...\n";
            break;
        }
    }
} else {
    echo "Entity MIB not available\n";
}
