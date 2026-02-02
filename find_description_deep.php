<?php
/**
 * Explore deeper into VSOL OLT SNMP tree for description
 */

require_once __DIR__ . '/vendor/autoload.php';

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== MENCARI DESCRIPTION ONU VSOL ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Dari screenshot, di PON3 LLID 1 ada "Hoho-35b", LLID 2 ada "Gustam"
// Mari cari di berbagai subtree

$searchTrees = [
    // ONU management subtrees
    '12.1.1' => '1.3.6.1.4.1.37950.1.1.5.12.1.1',   // authModeTable
    '12.1.2' => '1.3.6.1.4.1.37950.1.1.5.12.1.2',   // onuCfgTable
    '12.1.3' => '1.3.6.1.4.1.37950.1.1.5.12.1.3',   // onuMngTable
    '12.1.4' => '1.3.6.1.4.1.37950.1.1.5.12.1.4',   
    '12.1.5' => '1.3.6.1.4.1.37950.1.1.5.12.1.5',   
    '12.1.6' => '1.3.6.1.4.1.37950.1.1.5.12.1.6',   
    '12.1.7' => '1.3.6.1.4.1.37950.1.1.5.12.1.7',   
    '12.1.8' => '1.3.6.1.4.1.37950.1.1.5.12.1.8',
];

foreach ($searchTrees as $label => $oid) {
    echo "=== Scanning {$label} ===\n";
    
    $result = @snmpwalkoid($oltIp, $community, $oid, 10000000, 1);
    
    if ($result && count($result) > 0) {
        echo "Found " . count($result) . " entries\n";
        
        // Look for entries that contain text like names
        $textEntries = [];
        foreach ($result as $fullOid => $value) {
            // Check for text values (letters, not just MAC or hex)
            if (is_string($value) && strlen($value) > 2) {
                // Skip MAC addresses and pure hex
                if (!preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) {
                    $textEntries[$fullOid] = $value;
                }
            }
        }
        
        if (count($textEntries) > 0) {
            echo "📝 Found " . count($textEntries) . " text entries:\n";
            $i = 0;
            foreach ($textEntries as $oid => $val) {
                if ($i++ >= 10) {
                    echo "  ... and more\n";
                    break;
                }
                // Extract just the last part of OID
                $shortOid = preg_replace('/^.*?37950\./', '37950.', $oid);
                echo "  {$shortOid} = \"{$val}\"\n";
            }
        } else {
            echo "No text entries (only numbers/MAC)\n";
        }
    } else {
        echo "No data\n";
    }
    echo "\n";
}

// Also try to find by searching for known names
echo "\n=== Cari di seluruh tree VSOL untuk 'Hoho' atau 'Gustam' ===\n";
echo "Walking entire .12 subtree (might take a while)...\n";

$fullResult = @snmpwalkoid($oltIp, $community, '1.3.6.1.4.1.37950.1.1.5.12', 60000000, 5);

if ($fullResult) {
    echo "Total entries in .12 tree: " . count($fullResult) . "\n\n";
    
    // Search for description-like values
    $descFound = [];
    foreach ($fullResult as $oid => $value) {
        if (is_string($value) && strlen($value) >= 3) {
            // Check if looks like a name (contains letters but not MAC/hex)
            if (preg_match('/[a-zA-Z]{3,}/', $value) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) {
                // Skip known non-description values
                if (!in_array($value, ['auth success', 'auth fail', 'deregister', 'online', 'offline'])) {
                    $descFound[$oid] = $value;
                }
            }
        }
    }
    
    echo "Text values found (possible descriptions):\n";
    $shown = 0;
    foreach ($descFound as $oid => $val) {
        if ($shown++ >= 30) {
            echo "  ... and " . (count($descFound) - 30) . " more\n";
            break;
        }
        $shortOid = preg_replace('/^.*?1\.1\.5\./', '.5.', $oid);
        echo "  {$shortOid} = \"{$val}\"\n";
    }
} else {
    echo "Could not walk tree\n";
}

echo "\nDone.\n";
