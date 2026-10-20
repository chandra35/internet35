<?php
/**
 * Test untuk mencari OID yang menyimpan Description ONU
 */

require_once __DIR__ . '/vendor/autoload.php';

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== MENCARI OID DESCRIPTION ONU ===\n\n";

// Kemungkinan OID untuk description
$possibleOids = [
    'onuListTable.6' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.6',
    'onuListTable.7' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.7',
    'onuListTable.8' => '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.8',
    'authModeTable.3' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.3',
    'authModeTable.4' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.4',
    'authModeTable.5' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.5',
    'authModeTable.6' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.6',
    'authModeTable.7' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.7',
    'authModeTable.8' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.8',
    'authModeTable.9' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.9',
    'authModeTable.10' => '1.3.6.1.4.1.37950.1.1.5.12.1.1.1.10',
    'onuTypeCfg.3' => '1.3.6.1.4.1.37950.1.1.5.12.1.10.3',
    'onuTypeCfg.4' => '1.3.6.1.4.1.37950.1.1.5.12.1.10.4',
    // onuAuth sub-tables
    'onuAuth.10' => '1.3.6.1.4.1.37950.1.1.5.12.1.10',
    'onuAuth.11' => '1.3.6.1.4.1.37950.1.1.5.12.1.11',
    'onuAuth.12' => '1.3.6.1.4.1.37950.1.1.5.12.1.12',
    'onuAuth.13' => '1.3.6.1.4.1.37950.1.1.5.12.1.13',
    'onuAuth.14' => '1.3.6.1.4.1.37950.1.1.5.12.1.14',
    'onuAuth.15' => '1.3.6.1.4.1.37950.1.1.5.12.1.15',
];

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

foreach ($possibleOids as $name => $oid) {
    echo "Testing {$name} ({$oid})...\n";
    
    $start = microtime(true);
    $result = @snmpwalkoid($oltIp, $community, $oid, 3000000, 1);
    $elapsed = round((microtime(true) - $start) * 1000);
    
    if ($result && count($result) > 0) {
        echo "  ✅ Found " . count($result) . " entries [{$elapsed}ms]\n";
        
        // Show first 5 entries
        $count = 0;
        foreach ($result as $fullOid => $value) {
            if ($count >= 5) {
                echo "    ... and " . (count($result) - 5) . " more\n";
                break;
            }
            
            // Check if value looks like a description (string with letters)
            $valueStr = is_string($value) ? $value : print_r($value, true);
            $isText = preg_match('/[a-zA-Z]{2,}/', $valueStr);
            
            echo "    [{$count}] " . ($isText ? "📝 " : "") . substr($valueStr, 0, 60) . "\n";
            $count++;
        }
        echo "\n";
    } else {
        echo "  ❌ No data [{$elapsed}ms]\n";
    }
}

// Juga coba walk di sub-tree onuAuth untuk cari yang string
echo "\n=== WALK onuAuth untuk cari string description ===\n";
$onuAuthBase = '1.3.6.1.4.1.37950.1.1.5.12.1';

for ($i = 10; $i <= 20; $i++) {
    $oid = "{$onuAuthBase}.{$i}";
    echo "Testing .12.1.{$i}...\n";
    
    $result = @snmpwalkoid($oltIp, $community, $oid, 5000000, 1);
    
    if ($result && count($result) > 0) {
        // Check if any value contains text (like name)
        $hasText = false;
        $textSamples = [];
        
        foreach ($result as $fullOid => $value) {
            if (is_string($value) && preg_match('/[a-zA-Z]{3,}/', $value) && !preg_match('/^[0-9a-fA-F:]+$/', $value)) {
                $hasText = true;
                if (count($textSamples) < 3) {
                    $textSamples[] = $value;
                }
            }
        }
        
        if ($hasText) {
            echo "  ✅ Found " . count($result) . " entries with TEXT!\n";
            foreach ($textSamples as $sample) {
                echo "    📝 Sample: " . substr($sample, 0, 50) . "\n";
            }
        } else {
            echo "  Found " . count($result) . " entries (numeric/hex only)\n";
        }
    }
}

echo "\n=== DONE ===\n";
