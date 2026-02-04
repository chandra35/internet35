<?php
/**
 * Comprehensive SNMP Interface Statistics Test
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 3000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== SNMP Interface & Traffic Statistics ===\n";
echo "Host: $host\n\n";

// Test sections
$tests = [
    // Basic interface info
    'ifDescr' => '.1.3.6.1.2.1.2.2.1.2',
    'ifType' => '.1.3.6.1.2.1.2.2.1.3',
    'ifSpeed' => '.1.3.6.1.2.1.2.2.1.5',
    'ifPhysAddress' => '.1.3.6.1.2.1.2.2.1.6',
    'ifAdminStatus' => '.1.3.6.1.2.1.2.2.1.7',
    'ifOperStatus' => '.1.3.6.1.2.1.2.2.1.8',
    
    // Traffic counters
    'ifInOctets' => '.1.3.6.1.2.1.2.2.1.10',
    'ifInErrors' => '.1.3.6.1.2.1.2.2.1.14',
    'ifOutOctets' => '.1.3.6.1.2.1.2.2.1.16',
    'ifOutErrors' => '.1.3.6.1.2.1.2.2.1.20',
    
    // IF-MIB Extensions (64-bit counters)
    'ifName' => '.1.3.6.1.2.1.31.1.1.1.1',
    'ifHCInOctets' => '.1.3.6.1.2.1.31.1.1.1.6',
    'ifHCOutOctets' => '.1.3.6.1.2.1.31.1.1.1.10',
    'ifAlias' => '.1.3.6.1.2.1.31.1.1.1.18',
    
    // Entity MIB (hardware info)
    'entPhysicalDescr' => '.1.3.6.1.2.1.47.1.1.1.1.2',
    'entPhysicalClass' => '.1.3.6.1.2.1.47.1.1.1.1.5',
    'entPhysicalName' => '.1.3.6.1.2.1.47.1.1.1.1.7',
    'entPhysicalSerialNum' => '.1.3.6.1.2.1.47.1.1.1.1.11',
];

foreach ($tests as $name => $oid) {
    echo "--- $name ($oid) ---\n";
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result && count($result) > 0) {
        echo "Found " . count($result) . " values:\n";
        foreach ($result as $fullOid => $value) {
            $idx = substr($fullOid, strrpos($fullOid, '.') + 1);
            $shortVal = is_string($value) ? substr($value, 0, 50) : $value;
            echo "  [$idx] $shortVal\n";
        }
    } else {
        echo "  No data\n";
    }
    echo "\n";
}

// Look for PON-specific OIDs in standard locations
echo "=== Searching for PON/EPON Standard MIBs ===\n";
$eponTests = [
    'dot3EponMauType' => '.1.3.6.1.2.1.158',  // IEEE 802.3ah EPON MIB
    'dot3MpcpControl' => '.1.3.6.1.2.1.155',  // MPCP MIB
];

foreach ($eponTests as $name => $oid) {
    echo "--- $name ($oid) ---\n";
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result && count($result) > 0) {
        echo "Found " . count($result) . " values!\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            echo "  $fullOid = $value\n";
            if (++$i >= 10) {
                echo "  ... (" . count($result) . " total)\n";
                break;
            }
        }
    } else {
        echo "  No data\n";
    }
    echo "\n";
}

echo "=== DONE ===\n";
