<?php
/**
 * Deep search for ONU-related OIDs on Hioso OLT
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 2000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Deep ONU OID Search on Hioso ===\n";
echo "Host: $host\n\n";

// Walk MIB-II first
echo "=== 1. Walking MIB-II Standard ===\n";
$mib2 = @snmpwalkoid($host, $community, '1.3.6.1.2.1', $timeout * 3, $retries);
$mib2Count = $mib2 ? count($mib2) : 0;
echo "Found $mib2Count OIDs in MIB-II\n\n";

// Search for ONU-related in MIB-II
$onuRelated = [];
if ($mib2) {
    foreach ($mib2 as $oid => $value) {
        $valueStr = strtolower((string)$value);
        if (preg_match('/(onu|epon|gpon|llid|optical|0x[0-9a-f]{12})/i', $valueStr) ||
            preg_match('/([0-9a-f]{2}:){5}[0-9a-f]{2}/i', $valueStr)) {
            $onuRelated[$oid] = $value;
        }
    }
}

if (!empty($onuRelated)) {
    echo "ONU-related in MIB-II:\n";
    foreach ($onuRelated as $oid => $value) {
        echo "  $oid = $value\n";
    }
} else {
    echo "No ONU-related values in MIB-II\n";
}

// Try enterprise 25355
echo "\n=== 2. Enterprise 25355 ===\n";
$ent = @snmpwalkoid($host, $community, '1.3.6.1.4.1.25355', $timeout * 3, $retries);
if ($ent && count($ent) > 0) {
    echo "Found " . count($ent) . " OIDs!\n";
    foreach ($ent as $oid => $value) {
        echo "  $oid = $value\n";
    }
} else {
    echo "No response from enterprise 25355\n";
}

// Check interface count - maybe ONUs have their own interface indexes
echo "\n=== 3. Interface Index Check ===\n";
$ifIndex = @snmpwalkoid($host, $community, '1.3.6.1.2.1.2.2.1.1', $timeout, $retries);
if ($ifIndex) {
    $indexes = array_values($ifIndex);
    echo "Interface indexes: " . implode(', ', $indexes) . "\n";
    echo "Max index: " . max($indexes) . "\n";
    
    if (max($indexes) > 8) {
        echo "\nHigh indexes found - checking for ONU interfaces:\n";
        for ($i = 9; $i <= min(max($indexes), 500); $i++) {
            $descr = @snmpget($host, $community, ".1.3.6.1.2.1.2.2.1.2.$i", 500000, 0);
            if ($descr !== false) {
                echo "  [$i] $descr\n";
            }
        }
    }
}

// Bridge MIB - MAC table
echo "\n=== 4. Bridge MIB (MAC addresses) ===\n";
$macTable = @snmpwalkoid($host, $community, '1.3.6.1.2.1.17.4.3.1.1', $timeout, $retries);
if ($macTable && count($macTable) > 0) {
    echo "Found " . count($macTable) . " MAC entries!\n";
    $i = 0;
    foreach ($macTable as $oid => $value) {
        // Try to format MAC
        if (strlen($value) >= 6) {
            $hex = bin2hex(substr($value, 0, 6));
            $mac = implode(':', str_split($hex, 2));
        } else {
            $mac = $value;
        }
        echo "  $oid = $mac\n";
        if (++$i >= 30) {
            echo "  ... (" . count($macTable) . " total)\n";
            break;
        }
    }
} else {
    echo "No MAC table data\n";
}

// Q-Bridge MIB
echo "\n=== 5. Q-Bridge (802.1Q VLAN) ===\n";
$qbridge = @snmpwalkoid($host, $community, '1.3.6.1.2.1.17.7', $timeout, $retries);
if ($qbridge && count($qbridge) > 0) {
    echo "Found " . count($qbridge) . " OIDs\n";
    $i = 0;
    foreach ($qbridge as $oid => $value) {
        echo "  $oid = $value\n";
        if (++$i >= 20) {
            echo "  ... (" . count($qbridge) . " total)\n";
            break;
        }
    }
} else {
    echo "No Q-Bridge data\n";
}

// Entity MIB
echo "\n=== 6. Entity MIB (Physical entities) ===\n";
$entity = @snmpwalkoid($host, $community, '1.3.6.1.2.1.47', $timeout, $retries);
if ($entity && count($entity) > 0) {
    echo "Found " . count($entity) . " OIDs\n";
    foreach ($entity as $oid => $value) {
        echo "  $oid = $value\n";
    }
} else {
    echo "No Entity MIB data\n";
}

// RMON/Ethernet Statistics
echo "\n=== 7. RMON Statistics ===\n";
$rmon = @snmpwalkoid($host, $community, '1.3.6.1.2.1.16', $timeout, $retries);
if ($rmon && count($rmon) > 0) {
    echo "Found " . count($rmon) . " OIDs\n";
    $i = 0;
    foreach ($rmon as $oid => $value) {
        echo "  $oid = $value\n";
        if (++$i >= 15) {
            echo "  ... (" . count($rmon) . " total)\n";
            break;
        }
    }
} else {
    echo "No RMON data\n";
}

echo "\n=== Summary ===\n";
echo "MIB-II: $mib2Count OIDs\n";
echo "Enterprise 25355: " . ($ent ? count($ent) : 0) . " OIDs\n";
echo "MAC Table: " . ($macTable ? count($macTable) : 0) . " entries\n";
echo "\n=== DONE ===\n";
