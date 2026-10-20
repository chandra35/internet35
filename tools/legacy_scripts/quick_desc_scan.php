<?php
/**
 * Quick scan for description OID di VSOL
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== QUICK SCAN DESCRIPTION OID ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Most likely locations for description:
$testOids = [
    // Known names from screenshot: "Hoho-35b" at PON3 LLID1
    // Try direct GET with different index patterns
    
    // Pattern 1: table.column.ponId.llid
    '12.1.1.1.3.3.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.3.3.1',
    '12.1.1.1.4.3.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.4.3.1',
    '12.1.1.1.5.3.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.5.3.1',
    
    // Pattern 2: Maybe under a different base
    '12.2.1.3.1' => '1.3.6.1.4.1.37950.1.1.5.12.2.1.3.1',
    '12.2.1.4.1' => '1.3.6.1.4.1.37950.1.1.5.12.2.1.4.1',
    
    // Pattern 3: onuMngTable
    '12.1.3.1.3.3.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.3.1.3.3.1',
    
    // Let's walk the full auth table entry for PON3
    '12.1.1.1.*.3.*' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1',
];

// First, let's just do a simple walk of authModeTable
echo "Walking authModeTable (1.3.6.1.4.1.37950.1.1.5.12.1.1)...\n";
$result = @snmpwalkoid($oltIp, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.1', 10000000, 1);
if ($result) {
    echo "Found " . count($result) . " entries:\n";
    foreach ($result as $oid => $val) {
        $shortOid = str_replace('iso.3.6.1.4.1.37950.1.1.5.12.1.1.', '.1.', $oid);
        $shortOid = str_replace('1.3.6.1.4.1.37950.1.1.5.12.1.1.', '.1.', $shortOid);
        echo "  {$shortOid} = {$val}\n";
    }
}

// Then walk onuListTable completely
echo "\n\nWalking onuListTable (1.3.6.1.4.1.37950.1.1.5.12.1.9.1)...\n";
$result = @snmpwalkoid($oltIp, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.9.1', 30000000, 1);
if ($result) {
    echo "Found " . count($result) . " entries\n\n";
    
    // Group by column
    $columns = [];
    foreach ($result as $oid => $val) {
        // Extract column and index
        preg_match('/\.12\.1\.9\.1\.(\d+)\.(\d+)$/', $oid, $m);
        if ($m) {
            $col = $m[1];
            $idx = $m[2];
            if (!isset($columns[$col])) {
                $columns[$col] = [];
            }
            $columns[$col][$idx] = $val;
        }
    }
    
    echo "Columns found: " . implode(", ", array_keys($columns)) . "\n\n";
    
    // Show sample from each column (first 3 entries)
    foreach ($columns as $col => $data) {
        echo "Column {$col} (" . count($data) . " entries):\n";
        $i = 0;
        foreach ($data as $idx => $val) {
            if ($i++ >= 3) break;
            echo "  [{$idx}] = {$val}\n";
        }
        echo "\n";
    }
}

// Try walking under onuAuth parent
echo "\n\nWalking 12.1 to see all tables...\n";
$result = @snmpwalkoid($oltIp, $community, '1.3.6.1.4.1.37950.1.1.5.12.1', 60000000, 3);
if ($result) {
    echo "Found " . count($result) . " total entries\n";
    
    // Group by table
    $tables = [];
    foreach ($result as $oid => $val) {
        preg_match('/\.12\.1\.(\d+)/', $oid, $m);
        if ($m) {
            $tableNum = $m[1];
            if (!isset($tables[$tableNum])) {
                $tables[$tableNum] = ['count' => 0, 'hasText' => false, 'sample' => ''];
            }
            $tables[$tableNum]['count']++;
            
            // Check for text
            if (is_string($val) && preg_match('/[a-zA-Z]{3,}/', $val) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $val)) {
                if (!in_array(strtolower($val), ['auth success', 'auth fail', 'loid', 'loidpw'])) {
                    $tables[$tableNum]['hasText'] = true;
                    if (empty($tables[$tableNum]['sample'])) {
                        $tables[$tableNum]['sample'] = $val;
                    }
                }
            }
        }
    }
    
    echo "\nTables summary:\n";
    foreach ($tables as $num => $info) {
        $textMarker = $info['hasText'] ? "📝 HAS TEXT" : "";
        $sample = $info['sample'] ? " - Sample: \"{$info['sample']}\"" : "";
        echo "  Table 12.1.{$num}: {$info['count']} entries {$textMarker}{$sample}\n";
    }
}

echo "\nDone.\n";
