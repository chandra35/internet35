<?php
/**
 * Deep SNMP OID Discovery for Hioso OLT
 * Try all possible OID patterns to find what works
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 3000000; // 3 sec
$retries = 1;

echo "=== Deep SNMP Discovery for Hioso OLT ===\n";
echo "Host: $host\n";
echo "Timeout: 3s\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

function tryGet($host, $community, $oid, $timeout, $retries) {
    $result = @snmpget($host, $community, $oid, $timeout, $retries);
    return $result !== false ? $result : null;
}

function tryWalk($host, $community, $oid, $timeout, $retries, $limit = 10) {
    $result = @snmpwalkoid($host, $community, $oid, $timeout, $retries);
    if ($result === false || empty($result)) return null;
    return $result;
}

// 1. Standard OIDs (should work)
echo "=== 1. Standard MIB-II ===\n";
$standard = [
    'sysDescr' => '1.3.6.1.2.1.1.1.0',
    'sysObjectID' => '1.3.6.1.2.1.1.2.0',
    'sysUpTime' => '1.3.6.1.2.1.1.3.0',
    'sysName' => '1.3.6.1.2.1.1.5.0',
    'sysContact' => '1.3.6.1.2.1.1.4.0',
    'sysLocation' => '1.3.6.1.2.1.1.6.0',
];
foreach ($standard as $name => $oid) {
    $val = tryGet($host, $community, $oid, $timeout, $retries);
    echo "$name: " . ($val ?? 'NO RESPONSE') . "\n";
}

// 2. Interface discovery
echo "\n=== 2. Interface Table (ifDescr) ===\n";
$interfaces = tryWalk($host, $community, '1.3.6.1.2.1.2.2.1.2', $timeout, $retries);
if ($interfaces) {
    echo "Found " . count($interfaces) . " interfaces:\n";
    foreach ($interfaces as $oid => $value) {
        $idx = substr($oid, strrpos($oid, '.') + 1);
        echo "  [$idx] $value\n";
    }
}

// 3. Try enterprise 25355 with different sub-trees
echo "\n=== 3. Enterprise 25355 Sub-trees ===\n";
$subtrees = [
    '1.3.6.1.4.1.25355.1' => 'System info?',
    '1.3.6.1.4.1.25355.2' => 'EPON?',
    '1.3.6.1.4.1.25355.2.1' => 'Level 2.1',
    '1.3.6.1.4.1.25355.2.2' => 'Level 2.2',
    '1.3.6.1.4.1.25355.2.3' => 'Level 2.3',
    '1.3.6.1.4.1.25355.3' => 'Level 3',
    '1.3.6.1.4.1.25355.4' => 'Level 4',
];

foreach ($subtrees as $oid => $desc) {
    echo "\n[$desc] Walking $oid...\n";
    $result = tryWalk($host, $community, $oid, $timeout * 2, $retries);
    if ($result) {
        $count = count($result);
        echo "  Found $count OIDs!\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            $shortValue = is_string($value) ? substr($value, 0, 50) : $value;
            echo "  $fullOid = $shortValue\n";
            if (++$i >= 5) {
                if ($count > 5) echo "  ... ($count total)\n";
                break;
            }
        }
    } else {
        echo "  No response\n";
    }
}

// 4. Try walking full enterprise tree
echo "\n=== 4. Full Enterprise 25355 Walk ===\n";
echo "Walking 1.3.6.1.4.1.25355 (may take time)...\n";
$fullWalk = tryWalk($host, $community, '1.3.6.1.4.1.25355', $timeout * 5, $retries);
if ($fullWalk) {
    echo "SUCCESS! Found " . count($fullWalk) . " OIDs\n\n";
    
    // Group by sub-tree
    $groups = [];
    foreach ($fullWalk as $oid => $value) {
        // Extract first 2 levels after enterprise
        if (preg_match('/1\.3\.6\.1\.4\.1\.25355\.(\d+)\.?(\d+)?/', $oid, $m)) {
            $key = $m[1] . (isset($m[2]) ? '.' . $m[2] : '');
            if (!isset($groups[$key])) $groups[$key] = [];
            $groups[$key][$oid] = $value;
        }
    }
    
    foreach ($groups as $prefix => $oids) {
        echo "--- Sub-tree 25355.$prefix (" . count($oids) . " OIDs) ---\n";
        $i = 0;
        foreach ($oids as $oid => $value) {
            $shortValue = is_string($value) ? substr($value, 0, 60) : $value;
            echo "  $oid = $shortValue\n";
            if (++$i >= 3) {
                if (count($oids) > 3) echo "  ... (" . count($oids) . " total)\n";
                break;
            }
        }
        echo "\n";
    }
} else {
    echo "No response from enterprise 25355\n";
}

// 5. Try other enterprises that might be used
echo "\n=== 5. Try Other Enterprise Numbers ===\n";
$otherEnterprises = [
    '17409' => 'Hioso standard',
    '3902' => 'ZTE',
    '2011' => 'Huawei',
    '6296' => 'BDCOM',
    '31656' => 'VSOL',
    '45121' => 'C-Data',
];

foreach ($otherEnterprises as $ent => $desc) {
    echo "[$desc] Enterprise $ent: ";
    $result = tryWalk($host, $community, "1.3.6.1.4.1.$ent", $timeout, $retries);
    if ($result) {
        echo count($result) . " OIDs found!\n";
    } else {
        echo "No response\n";
    }
}

// 6. Look for EPON/GPON specific MIBs
echo "\n=== 6. EPON/GPON Standard MIBs ===\n";
$eponOids = [
    'dot3MpcpMIB' => '1.3.6.1.2.1.10.127.1', // MPCP MIB
    'dot3EponMIB' => '1.3.6.1.2.1.155', // EPON MIB
    'entityPhysical' => '1.3.6.1.2.1.47.1.1.1', // Entity MIB
];

foreach ($eponOids as $name => $oid) {
    echo "[$name] $oid: ";
    $result = tryWalk($host, $community, $oid, $timeout, $retries);
    if ($result) {
        echo count($result) . " OIDs\n";
        $i = 0;
        foreach ($result as $fullOid => $value) {
            echo "  $fullOid = $value\n";
            if (++$i >= 3) break;
        }
    } else {
        echo "No response\n";
    }
}

echo "\n=== DONE ===\n";
