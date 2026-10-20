<?php
require_once __DIR__ . '/vendor/autoload.php';

// Suppress all warnings
error_reporting(0);

$ip = '172.16.16.4';
$community = 'public';
$output = [];

$output[] = "=== Testing OLT $ip ===";
$output[] = "";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// System Info
$output[] = "=== System MIB ===";
$sysOids = [
    'sysDescr' => '1.3.6.1.2.1.1.1.0',
    'sysObjectID' => '1.3.6.1.2.1.1.2.0',
    'sysName' => '1.3.6.1.2.1.1.5.0',
];

foreach ($sysOids as $name => $oid) {
    $val = @snmpget($ip, $community, $oid, 3000000, 1);
    $output[] = "$name: " . ($val !== false ? $val : 'N/A');
}

// Walk Enterprise 25355
$output[] = "";
$output[] = "=== Walk Enterprise 25355 ===";
$result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.25355', 5000000, 2);
if ($result && count($result) > 0) {
    $output[] = "Total entries: " . count($result);
    $i = 0;
    foreach ($result as $o => $v) {
        $shortOid = str_replace('iso.3.6.1.4.1.25355', '.25355', $o);
        $shortVal = is_string($v) && strlen($v) > 60 ? substr($v, 0, 60) . '...' : $v;
        $output[] = "$shortOid = $shortVal";
        if (++$i >= 100) {
            $output[] = "... (truncated)";
            break;
        }
    }
} else {
    $output[] = "Empty or failed - trying walk from base";
    
    // Try broader walk
    $result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1', 10000000, 2);
    if ($result && count($result) > 0) {
        $output[] = "Walk 1.3.6.1.4.1 - Total: " . count($result);
        $i = 0;
        foreach ($result as $o => $v) {
            $output[] = "$o = $v";
            if (++$i >= 100) {
                $output[] = "... (truncated)";
                break;
            }
        }
    }
}

// Try interfaces
$output[] = "";
$output[] = "=== Interfaces (ifDescr) ===";
$result = @snmpwalkoid($ip, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 2);
if ($result && count($result) > 0) {
    foreach ($result as $o => $v) {
        preg_match('/\.(\d+)$/', $o, $m);
        $idx = $m[1] ?? '?';
        $output[] = "[$idx] $v";
    }
} else {
    $output[] = "No interfaces found";
}

// Write to file
file_put_contents(__DIR__ . '/snmp_output.txt', implode("\n", $output));
echo "Output written to snmp_output.txt\n";
