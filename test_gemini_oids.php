<?php
/**
 * Test OID 25355 yang dikasih Gemini
 * Root: .1.3.6.1.4.1.25355
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 3000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Test OID 25355 dari Gemini ===\n";
echo "Host: $host\n\n";

// OID yang dikasih Gemini
$oids = [
    // Data ONU
    'MAC Address ONU' => '.1.3.6.1.4.1.25355.2.6.1.1.1.1.6',
    'Status ONU' => '.1.3.6.1.4.1.25355.2.6.1.1.1.1.8',
    'Distance' => '.1.3.6.1.4.1.25355.2.6.1.1.1.1.14',
    'ONU Description' => '.1.3.6.1.4.1.25355.2.6.1.1.1.1.17',
    
    // Optical Power
    'Rx Power (Signal)' => '.1.3.6.1.4.1.25355.2.6.1.1.1.1.13',
    'Voltage' => '.1.3.6.1.4.1.25355.2.6.1.1.1.1.12',
    
    // System
    'Save Config' => '.1.3.6.1.4.1.25355.1.1.3.0',
    
    // Try parent OIDs
    'ONU Table Root' => '.1.3.6.1.4.1.25355.2.6.1.1.1',
    'ONU Table Parent' => '.1.3.6.1.4.1.25355.2.6.1.1',
    'ONU Root' => '.1.3.6.1.4.1.25355.2.6.1',
    'ONU Parent' => '.1.3.6.1.4.1.25355.2.6',
    'Level 2' => '.1.3.6.1.4.1.25355.2',
    'Level 1' => '.1.3.6.1.4.1.25355.1',
    'Root 25355' => '.1.3.6.1.4.1.25355',
];

foreach ($oids as $name => $oid) {
    echo "--- $name ---\n";
    echo "OID: $oid\n";
    
    // Try walk first
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    
    if ($result && count($result) > 0) {
        echo "SUCCESS! Found " . count($result) . " values:\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            // Format MAC if it looks like binary
            if ($name == 'MAC Address ONU' && strlen($value) == 6) {
                $value = strtoupper(implode(':', str_split(bin2hex($value), 2)));
            }
            // Format Rx Power
            if ($name == 'Rx Power (Signal)' && is_numeric($value)) {
                $dbm = $value / 10;
                $value = "$value (= {$dbm} dBm)";
            }
            
            echo "  $fullOid = $value\n";
            if (++$i >= 15) {
                echo "  ... (" . count($result) . " total)\n";
                break;
            }
        }
    } else {
        // Try single get
        $single = @snmpget($host, $community, $oid, $timeout, $retries);
        if ($single !== false) {
            echo "Single value: $single\n";
        } else {
            echo "No response\n";
        }
    }
    echo "\n";
}

// Also try full walk of 25355 root
echo "=== Full Walk of Root 25355 ===\n";
echo "Walking .1.3.6.1.4.1.25355 (timeout 10s)...\n";
$fullWalk = @snmpwalkoid($host, $community, '.1.3.6.1.4.1.25355', 10000000, 2);
if ($fullWalk && count($fullWalk) > 0) {
    echo "SUCCESS! Found " . count($fullWalk) . " OIDs:\n";
    foreach ($fullWalk as $oid => $value) {
        echo "  $oid = $value\n";
    }
} else {
    echo "No response from root 25355\n";
}

echo "\n=== DONE ===\n";
