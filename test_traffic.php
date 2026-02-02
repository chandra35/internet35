<?php
// Test ONU Traffic OIDs for VSOL V1600D

$ip = '172.16.16.3';
$community = 'private';

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Base OID for onuStatisticsTable
$baseOid = '1.3.6.1.4.1.37950.1.1.5.12.1.20.1';

// Test PON 1, ONU 1
$ponId = 1;
$onuId = 1;

echo "Testing ONU Traffic for PON $ponId / ONU $onuId\n";
echo str_repeat('-', 50) . "\n";

// Try different OIDs
$oids = [
    'statRxGoodOctets' => '.3',
    'statTxGoodOctets' => '.10',
    'statRxUcastFrames' => '.4',
    'statTxUcastFrames' => '.11',
];

foreach ($oids as $name => $suffix) {
    $oid = "$baseOid$suffix.$ponId.$onuId";
    $result = @snmpget($ip, $community, $oid, 1000000, 1);
    echo "$name: $result\n";
}

echo "\n\nSNMP Walk first 10 entries of onuStatisticsTable:\n";
$walk = @snmpwalkoid($ip, $community, $baseOid, 1000000, 3);
if ($walk) {
    $count = 0;
    foreach ($walk as $oid => $value) {
        echo "$oid = $value\n";
        $count++;
        if ($count >= 20) break;
    }
}
