<?php
require_once __DIR__ . '/vendor/autoload.php';

// Suppress MIB warnings
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = '172.16.16.4';
$community = 'public';

echo "=== Deep SNMP Scan OLT $ip ===\n\n";

// System Info
echo "=== System MIB ===\n";
$sysOids = [
    'sysDescr' => '1.3.6.1.2.1.1.1.0',
    'sysObjectID' => '1.3.6.1.2.1.1.2.0',
    'sysUpTime' => '1.3.6.1.2.1.1.3.0',
    'sysContact' => '1.3.6.1.2.1.1.4.0',
    'sysName' => '1.3.6.1.2.1.1.5.0',
    'sysLocation' => '1.3.6.1.2.1.1.6.0',
];

foreach ($sysOids as $name => $oid) {
    $val = @snmpget($ip, $community, $oid, 3000000, 1);
    echo "$name: " . ($val !== false ? $val : 'N/A') . "\n";
}

// Try Enterprise 25355 (detected from sysObjectID)
echo "\n=== Walk Enterprise 25355 ===\n";
$result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.25355', 5000000, 2);
if ($result && count($result) > 0) {
    echo "Total entries: " . count($result) . "\n";
    $i = 0;
    foreach ($result as $o => $v) {
        // Shorten display
        $shortOid = str_replace('iso.3.6.1.4.1.25355', '.25355', $o);
        $shortVal = is_string($v) && strlen($v) > 50 ? substr($v, 0, 50) . '...' : $v;
        echo "$shortOid = $shortVal\n";
        if (++$i >= 100) {
            echo "... (truncated)\n";
            break;
        }
    }
} else {
    echo "Empty or failed\n";
}

// Try interfaces
echo "\n=== Interfaces (ifDescr) ===\n";
$result = @snmpwalkoid($ip, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 2);
if ($result && count($result) > 0) {
    foreach ($result as $o => $v) {
        preg_match('/\.(\d+)$/', $o, $m);
        $idx = $m[1] ?? '?';
        echo "[$idx] $v\n";
    }
} else {
    echo "No interfaces found\n";
}
