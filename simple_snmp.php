<?php
/**
 * Super simple SNMP test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '172.16.16.4';
$community = 'public';

echo "Starting SNMP test...\n";

// Suppress MIB warnings
@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "1. Testing basic sysDescr...\n";
$result = @snmpget($host, $community, '.1.3.6.1.2.1.1.1.0', 2000000, 1);
echo "   Result: " . ($result ?: 'NULL') . "\n";

echo "2. Testing sysObjectID...\n";
$result = @snmpget($host, $community, '.1.3.6.1.2.1.1.2.0', 2000000, 1);
echo "   Result: " . ($result ?: 'NULL') . "\n";

echo "3. Testing interface walk (limit)...\n";
$result = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.2', 3000000, 1);
if ($result) {
    echo "   Found " . count($result) . " interfaces:\n";
    foreach ($result as $oid => $value) {
        echo "   - $value\n";
    }
} else {
    echo "   No result\n";
}

echo "4. Testing Enterprise 25355...\n";
$result = @snmpwalkoid($host, $community, '.1.3.6.1.4.1.25355', 5000000, 1);
if ($result && count($result) > 0) {
    echo "   Found " . count($result) . " OIDs!\n";
    $i = 0;
    foreach ($result as $oid => $value) {
        echo "   $oid = $value\n";
        if (++$i > 10) break;
    }
} else {
    echo "   No enterprise 25355 OIDs\n";
}

echo "5. Testing IF-MIB extensions (ifXTable)...\n";
$result = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.31.1.1.1', 5000000, 1);
if ($result && count($result) > 0) {
    echo "   Found " . count($result) . " OIDs!\n";
    $i = 0;
    foreach ($result as $oid => $value) {
        echo "   $oid = $value\n";
        if (++$i > 15) {
            echo "   ... (" . count($result) . " total)\n";
            break;
        }
    }
} else {
    echo "   No IF-MIB extensions\n";
}

echo "\nDONE\n";
