<?php
putenv('MIBS=');
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$host = '172.16.16.4';
$community = 'telecom';

echo "Testing SNMP to {$host}...\n";

// Test sysDescr with long timeout
$result = @snmpget($host, $community, '1.3.6.1.2.1.1.1.0', 10000000, 3);
if ($result !== false) {
    echo "sysDescr: {$result}\n";
} else {
    echo "SNMP GET failed for sysDescr\n";
    exit(1);
}

// Test sysName
$result = @snmpget($host, $community, '1.3.6.1.2.1.1.5.0', 10000000, 3);
echo "sysName: " . ($result ?: 'N/A') . "\n";

// Test sysObjectID
$result = @snmpget($host, $community, '1.3.6.1.2.1.1.2.0', 10000000, 3);
echo "sysObjectID: " . ($result ?: 'N/A') . "\n";

// Now try ifDescr walk
echo "\nifDescr walk...\n";
$result = @snmpwalkoid($host, $community, '1.3.6.1.2.1.2.2.1.2', 10000000, 2);
if ($result) {
    echo "Found " . count($result) . " interfaces:\n";
    foreach ($result as $oid => $val) {
        echo "  {$val}\n";
    }
} else {
    echo "ifDescr walk failed\n";
}
