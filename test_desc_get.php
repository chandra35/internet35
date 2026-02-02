<?php
/**
 * Cari description dengan snmpget spesifik
 * 
 * Dari screenshot: EPON0/3:1 = "Hoho-35b"
 * Ini PON port 3, LLID 1
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== TEST SNMPGET UNTUK DESCRIPTION ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Known ONU: PON 3, LLID 1, expected description "Hoho-35b"
// We need to find what OID stores this

// Try many possible OID patterns for description
// Index pattern: .ponId.llid or .globalIndex

$patterns = [
    // authModeTable columns beyond column 2
    "12.1.1.1.3.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.3.3.1",
    "12.1.1.1.4.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.4.3.1",
    "12.1.1.1.5.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.5.3.1",
    "12.1.1.1.6.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.6.3.1",
    "12.1.1.1.7.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.7.3.1",
    "12.1.1.1.8.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.8.3.1",
    
    // Try different index patterns .ponId.0 (if LLID starts from 0)
    "12.1.1.1.3.3.0" => "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.3.3.0",
    
    // onuCfgTable columns
    "12.1.2.1.1.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.2.1.1.3.1",
    "12.1.2.1.2.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.2.1.2.3.1",
    "12.1.2.1.3.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.2.1.3.3.1",
    
    // onuListTable if it has more columns
    "12.1.9.1.6.95" => "1.3.6.1.4.1.37950.1.1.5.12.1.9.1.6.95",  // 95 is around PON3:1 index
    "12.1.9.1.7.95" => "1.3.6.1.4.1.37950.1.1.5.12.1.9.1.7.95",
    "12.1.9.1.8.95" => "1.3.6.1.4.1.37950.1.1.5.12.1.9.1.8.95",
    
    // Maybe a separate description table
    "12.1.3.1.1.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.3.1.1.3.1",
    "12.1.4.1.1.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.4.1.1.3.1",
    "12.1.5.1.1.3.1" => "1.3.6.1.4.1.37950.1.1.5.12.1.5.1.1.3.1",
];

echo "Testing specific OIDs for PON3:LLID1 (expected: Hoho-35b)...\n\n";

foreach ($patterns as $label => $oid) {
    $result = @snmpget($oltIp, $community, $oid, 2000000);
    if ($result !== false && $result !== null && $result !== '') {
        echo "  ✅ {$label} = {$result}\n";
    }
}

// Let's try getnext from different bases
echo "\n\nTrying SNMP getnext to discover OIDs...\n";

$discoveryBases = [
    '12.1.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.1',
    '12.1.2' => '1.3.6.1.4.1.37950.1.1.5.12.1.2',
    '12.1.3' => '1.3.6.1.4.1.37950.1.1.5.12.1.3',
    '12.1.4' => '1.3.6.1.4.1.37950.1.1.5.12.1.4',
    '12.1.5' => '1.3.6.1.4.1.37950.1.1.5.12.1.5',
];

foreach ($discoveryBases as $label => $baseOid) {
    echo "\nStarting from {$label}:\n";
    
    $current = $baseOid;
    for ($i = 0; $i < 10; $i++) {
        $result = @snmpgetnext($oltIp, $community, $current, 2000000);
        
        if ($result === false || strpos($result, 'No Such') !== false) {
            break;
        }
        
        // Get the OID
        $oidInfo = @snmprealwalk($oltIp, $community, $current, 2000000);
        if ($oidInfo && count($oidInfo) > 0) {
            foreach ($oidInfo as $oid => $val) {
                // Check if still under our base
                if (strpos($oid, '37950.1.1.5.' . str_replace('.', '.', substr($label, 0, 4))) === false) {
                    break 2;
                }
                $shortOid = preg_replace('/^.*?\.12\./', '.12.', $oid);
                echo "  {$shortOid} = {$val}\n";
                $current = $oid;
            }
        }
    }
}

echo "\n\nDone.\n";
