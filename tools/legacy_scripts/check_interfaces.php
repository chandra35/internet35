<?php
require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL & ~E_WARNING);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = $argv[1] ?? '172.16.16.4';
$community = $argv[2] ?? 'public';

echo "=== Interface Descriptions for $ip ===\n\n";

$result = @snmpwalkoid($ip, $community, '1.3.6.1.2.1.2.2.1.2', 5000000, 2);

if ($result && count($result) > 0) {
    $ponCount = 0;
    foreach ($result as $oid => $val) {
        preg_match('/\.(\d+)$/', $oid, $matches);
        $idx = $matches[1] ?? '?';
        
        $valLower = strtolower($val);
        $isPon = (strpos($valLower, 'pon') !== false || 
                  strpos($valLower, 'epon') !== false || 
                  strpos($valLower, 'gpon') !== false);
        
        $marker = $isPon ? ' [PON]' : '';
        if ($isPon) $ponCount++;
        
        echo "[$idx] $val$marker\n";
    }
    echo "\nTotal interfaces: " . count($result) . "\n";
    echo "PON ports detected: $ponCount\n";
} else {
    echo "No interfaces found or SNMP error.\n";
}
