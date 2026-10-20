<?php
/**
 * Final comprehensive check - what OIDs actually exist?
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 2000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Final OID Discovery ===\n";
echo "Host: $host\n\n";

// First confirm sysObjectID
echo "=== System Info ===\n";
$sysDescr = @snmpget($host, $community, '.1.3.6.1.2.1.1.1.0', $timeout, $retries);
$sysObjectID = @snmpget($host, $community, '.1.3.6.1.2.1.1.2.0', $timeout, $retries);
$sysName = @snmpget($host, $community, '.1.3.6.1.2.1.1.5.0', $timeout, $retries);

echo "sysDescr: $sysDescr\n";
echo "sysObjectID: $sysObjectID\n";
echo "sysName: $sysName\n";

// Extract enterprise ID from sysObjectID
if (preg_match('/\.1\.3\.6\.1\.4\.1\.(\d+)/', $sysObjectID, $m)) {
    $enterpriseId = $m[1];
    echo "\nEnterprise ID: $enterpriseId\n";
} else {
    $enterpriseId = '25355';
}

// Walk enterprise tree with VERY long timeout
echo "\n=== Walking Enterprise $enterpriseId ===\n";
echo "Timeout: 30 seconds...\n\n";

$entOid = ".1.3.6.1.4.1.$enterpriseId";
$result = @snmpwalkoid($host, $community, $entOid, 30000000, 3);

if ($result && count($result) > 0) {
    echo "*** SUCCESS! Found " . count($result) . " OIDs! ***\n\n";
    foreach ($result as $oid => $value) {
        echo "$oid = $value\n";
    }
} else {
    echo "No OIDs in enterprise tree $enterpriseId\n";
    echo "\nThis OLT does NOT implement enterprise MIB.\n";
    echo "Only standard MIB-II is available.\n";
}

// List what IS available
echo "\n=== What IS Available (MIB-II) ===\n";
$available = [
    'sysDescr' => '.1.3.6.1.2.1.1.1.0',
    'sysUpTime' => '.1.3.6.1.2.1.1.3.0',
    'ifNumber' => '.1.3.6.1.2.1.2.1.0',
];

foreach ($available as $name => $oid) {
    $val = @snmpget($host, $community, $oid, $timeout, $retries);
    echo "$name: $val\n";
}

echo "\nAvailable Interface Data:\n";
$ifData = [
    'ifDescr' => '.1.3.6.1.2.1.2.2.1.2',
    'ifOperStatus' => '.1.3.6.1.2.1.2.2.1.8',
    'ifInOctets' => '.1.3.6.1.2.1.2.2.1.10',
    'ifOutOctets' => '.1.3.6.1.2.1.2.2.1.16',
];

foreach ($ifData as $name => $oid) {
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result) {
        echo "  $name: " . count($result) . " entries ✓\n";
    }
}

echo "\n=== Conclusion ===\n";
echo "This Hioso OLT (Enterprise $enterpriseId) only supports:\n";
echo "  ✓ Standard MIB-II (system info, interface stats)\n";
echo "  ✗ Enterprise MIB (ONU data, optical power)\n";
echo "\nFor ONU management, use Telnet which is already implemented.\n";
