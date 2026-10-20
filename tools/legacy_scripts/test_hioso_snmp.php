<?php
/**
 * Test Hioso OLT dengan OID Enterprise 17409
 */
error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$ip = '172.16.16.4';
$community = 'public';

echo "=== Test Hioso OLT - Enterprise 17409 OIDs ===\n\n";

// System info
echo "sysObjectID: ";
$val = @snmpget($ip, $community, '1.3.6.1.2.1.1.2.0', 3000000, 2);
echo ($val ?: "timeout") . "\n";

echo "sysDescr: ";
$val = @snmpget($ip, $community, '1.3.6.1.2.1.1.1.0', 3000000, 2);
echo ($val ?: "timeout") . "\n\n";

// Test ONU OIDs dengan Enterprise 17409
echo "=== Testing Enterprise 17409 OIDs ===\n";

$oids17409 = [
    'ONU Serial' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
    'ONU MAC' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.3',
    'ONU Status' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.4',
    'ONU Distance' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.7',
    'ONU Rx Power' => '1.3.6.1.4.1.17409.2.3.5.1.4.1.1.3',
    'PON Port Status' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.3',
];

foreach ($oids17409 as $name => $oid) {
    echo "\n$name ($oid):\n";
    $result = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
    if ($result && count($result) > 0) {
        echo "  Found " . count($result) . " entries:\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            $shortOid = preg_replace('/^iso\.3\.6\.1\.4\.1\.17409\./', '.17409.', $fullOid);
            echo "  $shortOid => $value\n";
            if (++$i >= 10) {
                echo "  ... (showing first 10)\n";
                break;
            }
        }
    } else {
        echo "  No data or timeout\n";
    }
}

echo "\n\nDone!\n";
echo "=== Try PON Port OIDs ===\n";

$oids = [
    'ponPortAdmin (17409.2.3.4.1.1.1.1.2)' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.2',
    'ponPortOper (17409.2.3.4.1.1.1.1.3)' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1.3',
    'ponPortTable base (17409.2.3.4.1.1.1)' => '1.3.6.1.4.1.17409.2.3.4.1.1.1',
    'ponPortTable (17409.2.3.4.1.1)' => '1.3.6.1.4.1.17409.2.3.4.1.1',
    'ponMgmt (17409.2.3.4)' => '1.3.6.1.4.1.17409.2.3.4',
];

foreach ($oids as $name => $oid) {
    $result = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
    if ($result && count($result) > 0) {
        echo "$name: " . count($result) . " entries\n";
        $i = 0;
        foreach ($result as $o => $v) {
            echo "  $o = $v\n";
            if (++$i >= 5) {
                echo "  ...\n";
                break;
            }
        }
    } else {
        echo "$name: empty/failed\n";
    }
}

echo "\n=== Try ONU Table ===\n";
$onuOids = [
    'onuSerial (17409.2.3.5.1.1.1.1.2)' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2',
    'onuStatus (17409.2.3.5.1.1.1.1.4)' => '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.4',
    'onuTable base (17409.2.3.5.1.1.1)' => '1.3.6.1.4.1.17409.2.3.5.1.1.1',
    'onuMgmt (17409.2.3.5.1)' => '1.3.6.1.4.1.17409.2.3.5.1',
    'onuBase (17409.2.3.5)' => '1.3.6.1.4.1.17409.2.3.5',
];

foreach ($onuOids as $name => $oid) {
    $result = @snmpwalkoid($ip, $community, $oid, 5000000, 2);
    if ($result && count($result) > 0) {
        echo "$name: " . count($result) . " entries\n";
        $i = 0;
        foreach ($result as $o => $v) {
            echo "  $o = $v\n";
            if (++$i >= 5) {
                echo "  ...\n";
                break;
            }
        }
    } else {
        echo "$name: empty/failed\n";
    }
}

echo "\n=== Walk Enterprise 17409 base ===\n";
$result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.17409', 5000000, 2);
if ($result && count($result) > 0) {
    echo "Total entries: " . count($result) . "\n";
    $i = 0;
    foreach ($result as $o => $v) {
        echo "$o = $v\n";
        if (++$i >= 20) {
            echo "...\n";
            break;
        }
    }
} else {
    echo "Empty or failed - trying broader walk\n";
    
    // Try walking from iso.enterprises
    echo "\n=== Walk all enterprises (1.3.6.1.4.1) - first 50 ===\n";
    $result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1', 5000000, 2);
    if ($result && count($result) > 0) {
        echo "Total entries: " . count($result) . "\n";
        $i = 0;
        foreach ($result as $o => $v) {
            echo "$o = $v\n";
            if (++$i >= 50) {
                echo "...\n";
                break;
            }
        }
    } else {
        echo "No enterprise data found\n";
    }
}
