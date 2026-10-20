<?php

$ip = '192.168.18.3';
$community = 'public';
$timeout = 10000000;
$retries = 5;

snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

echo "=== Testing VSOL V1600D4 SNMP OIDs ===\n\n";

// System info
echo "1. System Name (1.3.6.1.2.1.1.5.0):\n";
$result = @snmp2_get($ip, $community, '.1.3.6.1.2.1.1.5.0', $timeout, $retries);
echo "   " . ($result !== false ? $result : "FAILED") . "\n\n";

echo "2. System Description (1.3.6.1.2.1.1.1.0):\n";
$result = @snmp2_get($ip, $community, '.1.3.6.1.2.1.1.1.0', $timeout, $retries);
echo "   " . ($result !== false ? $result : "FAILED") . "\n\n";

// PON Port Table
echo "3. PON Port Status (1.3.6.1.4.1.37950.1.1.5.1.1.3):\n";
$result = @snmp2_walk($ip, $community, '.1.3.6.1.4.1.37950.1.1.5.1.1.3', $timeout, $retries);
if ($result !== false && !empty($result)) {
    foreach ($result as $oid => $val) {
        echo "   $oid => $val\n";
    }
} else {
    echo "   FAILED or EMPTY\n";
}
echo "\n";

// ONU Status Table (the key OID)
echo "4. ONU Status (1.3.6.1.4.1.37950.1.1.5.2.1.5):\n";
$result = @snmp2_walk($ip, $community, '.1.3.6.1.4.1.37950.1.1.5.2.1.5', $timeout, $retries);
if ($result !== false && !empty($result)) {
    foreach ($result as $oid => $val) {
        echo "   $oid => $val\n";
    }
} else {
    echo "   FAILED or EMPTY\n";
}
echo "\n";

// ONU MAC Address
echo "5. ONU MAC Address (1.3.6.1.4.1.37950.1.1.5.2.1.4):\n";
$result = @snmp2_walk($ip, $community, '.1.3.6.1.4.1.37950.1.1.5.2.1.4', $timeout, $retries);
if ($result !== false && !empty($result)) {
    foreach ($result as $oid => $val) {
        echo "   $oid => $val\n";
    }
} else {
    echo "   FAILED or EMPTY\n";
}
echo "\n";

// Try higher level OID walk to discover structure
echo "6. Full Enterprise OID walk (1.3.6.1.4.1.37950) - first 50:\n";
$result = @snmp2_walk($ip, $community, '.1.3.6.1.4.1.37950', $timeout, $retries);
if ($result !== false && !empty($result)) {
    $count = 0;
    foreach ($result as $oid => $val) {
        echo "   $oid => " . substr($val, 0, 60) . "\n";
        if (++$count > 50) {
            echo "   ... more data exists ...\n";
            break;
        }
    }
} else {
    echo "   FAILED or EMPTY\n";
}
echo "\n";

// CPU/Memory
echo "7. CPU Usage (1.3.6.1.4.1.37950.1.1.1.1.0):\n";
$result = @snmp2_get($ip, $community, '.1.3.6.1.4.1.37950.1.1.1.1.0', $timeout, $retries);
echo "   " . ($result !== false ? $result : "FAILED") . "\n\n";

echo "8. Memory Usage (1.3.6.1.4.1.37950.1.1.1.2.0):\n";
$result = @snmp2_get($ip, $community, '.1.3.6.1.4.1.37950.1.1.1.2.0', $timeout, $retries);
echo "   " . ($result !== false ? $result : "FAILED") . "\n\n";

// Try alternative OIDs
echo "9. Legacy ONU Status (1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2):\n";
$result = @snmp2_walk($ip, $community, '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2', $timeout, $retries);
if ($result !== false && !empty($result)) {
    foreach ($result as $oid => $val) {
        echo "   $oid => $val\n";
    }
} else {
    echo "   FAILED or EMPTY\n";
}
echo "\n";

echo "=== SNMP Test Complete ===\n";
