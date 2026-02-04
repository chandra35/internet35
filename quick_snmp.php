<?php
/**
 * Quick SNMP check with shorter timeout
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 1000000; // 1 sec only
$retries = 0;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Quick SNMP Check ===\n";
echo "Host: $host | Timeout: 1s\n\n";

function quickWalk($host, $community, $oid, $timeout, $retries) {
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    return $result ?: [];
}

$checks = [
    'ifDescr' => '.1.3.6.1.2.1.2.2.1.2',
    'ifOperStatus' => '.1.3.6.1.2.1.2.2.1.8',
    'ifInOctets' => '.1.3.6.1.2.1.2.2.1.10',
    'ifOutOctets' => '.1.3.6.1.2.1.2.2.1.16',
    'ifName' => '.1.3.6.1.2.1.31.1.1.1.1',
    'ifHCInOctets' => '.1.3.6.1.2.1.31.1.1.1.6',
    'ifHCOutOctets' => '.1.3.6.1.2.1.31.1.1.1.10',
];

$allResults = [];

foreach ($checks as $name => $oid) {
    echo "Checking $name... ";
    $start = microtime(true);
    $result = quickWalk($host, $community, $oid, $timeout, $retries);
    $elapsed = round((microtime(true) - $start) * 1000);
    
    if (count($result) > 0) {
        echo "OK (" . count($result) . " values, {$elapsed}ms)\n";
        $allResults[$name] = $result;
    } else {
        echo "No data ({$elapsed}ms)\n";
    }
}

echo "\n=== Interface Summary ===\n";
if (isset($allResults['ifDescr'])) {
    foreach ($allResults['ifDescr'] as $oid => $name) {
        $idx = substr($oid, strrpos($oid, '.') + 1);
        $status = isset($allResults['ifOperStatus'][$oid]) ? 
            ($allResults['ifOperStatus'][$oid] == 1 ? 'UP' : 'DOWN') : '?';
        $inOctets = $allResults['ifInOctets'][".1.3.6.1.2.1.2.2.1.10.$idx"] ?? 
                    $allResults['ifHCInOctets'][".1.3.6.1.2.1.31.1.1.1.6.$idx"] ?? 0;
        $outOctets = $allResults['ifOutOctets'][".1.3.6.1.2.1.2.2.1.16.$idx"] ?? 
                     $allResults['ifHCOutOctets'][".1.3.6.1.2.1.31.1.1.1.10.$idx"] ?? 0;
        
        echo sprintf("  [%d] %-12s %s  In: %s  Out: %s\n", 
            $idx, $name, $status, 
            number_format($inOctets), 
            number_format($outOctets)
        );
    }
}

echo "\n=== DONE ===\n";
