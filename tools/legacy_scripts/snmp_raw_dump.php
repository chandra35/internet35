<?php
/**
 * Debug SNMP values - dump raw data
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 2000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Raw SNMP Dump ===\n";
echo "Host: $host\n\n";

$tables = [
    'ifDescr' => '.1.3.6.1.2.1.2.2.1.2',
    'ifOperStatus' => '.1.3.6.1.2.1.2.2.1.8',
    'ifSpeed' => '.1.3.6.1.2.1.2.2.1.5',
    'ifInOctets' => '.1.3.6.1.2.1.2.2.1.10',
    'ifOutOctets' => '.1.3.6.1.2.1.2.2.1.16',
];

foreach ($tables as $name => $oid) {
    echo "--- $name ---\n";
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result) {
        foreach ($result as $fullOid => $value) {
            echo "  $fullOid = $value\n";
        }
    } else {
        echo "  No data\n";
    }
    echo "\n";
}

echo "=== DONE ===\n";
