<?php
/**
 * Check specific ONU for description via different OIDs
 * 
 * Dari screenshot:
 * - EPON0/3:1 = Hoho-35b (PON 3, LLID 1)  
 * - EPON0/3:2 = Gustam (PON 3, LLID 2)
 */

require_once __DIR__ . '/vendor/autoload.php';

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== CARI DESCRIPTION UNTUK ONU SPESIFIK ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Try GET for specific ONU indices
// PON 3 = ponId 3, LLID 1 should have "Hoho-35b"

// Possible OID patterns untuk description:
// 1. .authModeTable.<column>.<ponId>.<llid>
// 2. .onuCfgTable.<column>.<ponId>.<llid>
// 3. .onuDescTable.<column>.<index>

$baseOids = [
    // Try different base tables
    '12.1.1.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1',  // authModeTable columns
    '12.1.2.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.2.1',  // onuCfgTable columns
    '12.1.4.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.4.1',  
    '12.1.9.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1',  // onuListTable
    // Other possible tables
    '12.2' => '1.3.6.1.4.1.37950.1.1.5.12.2',
    '12.3' => '1.3.6.1.4.1.37950.1.1.5.12.3',
    '12.4' => '1.3.6.1.4.1.37950.1.1.5.12.4',
    '12.5' => '1.3.6.1.4.1.37950.1.1.5.12.5',
];

// First, let's walk authModeTable columns looking for one that has description
echo "Checking authModeTable columns...\n";
for ($col = 1; $col <= 20; $col++) {
    $oid = "1.3.6.1.4.1.37950.1.1.5.12.1.1.1.{$col}";
    $result = @snmpwalkoid($oltIp, $community, $oid, 3000000, 1);
    
    if ($result && count($result) > 0) {
        // Check first few values
        $hasText = false;
        $sample = '';
        foreach (array_slice($result, 0, 5, true) as $fullOid => $value) {
            if (is_string($value) && preg_match('/[a-zA-Z]{3,}/', $value) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) {
                if (!in_array($value, ['auth success', 'auth fail'])) {
                    $hasText = true;
                    $sample = $value;
                    break;
                }
            }
        }
        
        if ($hasText) {
            echo "  ✅ Column {$col}: " . count($result) . " entries - Sample: \"{$sample}\"\n";
        } else {
            echo "  Column {$col}: " . count($result) . " entries (numeric/mac)\n";
        }
    }
}

// Also check onuCfgTable
echo "\nChecking onuCfgTable columns...\n";
for ($col = 1; $col <= 20; $col++) {
    $oid = "1.3.6.1.4.1.37950.1.1.5.12.1.2.1.{$col}";
    $result = @snmpwalkoid($oltIp, $community, $oid, 3000000, 1);
    
    if ($result && count($result) > 0) {
        $hasText = false;
        $sample = '';
        foreach (array_slice($result, 0, 5, true) as $fullOid => $value) {
            if (is_string($value) && preg_match('/[a-zA-Z]{3,}/', $value) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) {
                $hasText = true;
                $sample = $value;
                break;
            }
        }
        
        if ($hasText) {
            echo "  ✅ Column {$col}: " . count($result) . " entries - Sample: \"{$sample}\"\n";
        } else {
            echo "  Column {$col}: " . count($result) . " entries\n";
        }
    }
}

// Try walking .12.2, .12.3, etc
echo "\nChecking other .12.x subtrees...\n";
for ($sub = 2; $sub <= 10; $sub++) {
    $oid = "1.3.6.1.4.1.37950.1.1.5.12.{$sub}";
    $result = @snmpwalkoid($oltIp, $community, $oid, 5000000, 1);
    
    if ($result && count($result) > 0) {
        $textCount = 0;
        $samples = [];
        foreach ($result as $fullOid => $value) {
            if (is_string($value) && preg_match('/[a-zA-Z]{3,}/', $value) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) {
                $textCount++;
                if (count($samples) < 3) {
                    $samples[] = $value;
                }
            }
        }
        
        if ($textCount > 0) {
            echo "  ✅ .12.{$sub}: " . count($result) . " total, {$textCount} text - Samples: " . implode(", ", $samples) . "\n";
        } else {
            echo "  .12.{$sub}: " . count($result) . " entries (numeric)\n";
        }
    }
}

// Try different enterprise subtrees - maybe description is stored separately
echo "\nChecking other enterprise subtrees (not under .12)...\n";
for ($sub = 1; $sub <= 15; $sub++) {
    if ($sub == 12) continue; // skip onuAuth, already checked
    
    $oid = "1.3.6.1.4.1.37950.1.1.5.{$sub}";
    $result = @snmpwalkoid($oltIp, $community, $oid, 5000000, 1);
    
    if ($result && count($result) > 0) {
        $textCount = 0;
        $samples = [];
        foreach ($result as $fullOid => $value) {
            if (is_string($value) && strlen($value) >= 3 && preg_match('/[a-zA-Z]{3,}/', $value) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) {
                $textCount++;
                if (count($samples) < 3 && strlen($value) < 30) {
                    $samples[] = $value;
                }
            }
        }
        
        if ($textCount > 0) {
            echo "  ✅ .5.{$sub}: " . count($result) . " total, {$textCount} text\n";
            if (count($samples) > 0) {
                echo "     Samples: " . implode(", ", $samples) . "\n";
            }
        } else {
            echo "  .5.{$sub}: " . count($result) . " entries\n";
        }
    }
}

echo "\nDone.\n";
