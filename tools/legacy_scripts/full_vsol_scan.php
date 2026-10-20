<?php
/**
 * Scan semua subtree VSOL enterprise untuk mencari string description
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== FULL VSOL ENTERPRISE SCAN ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Walk entire VSOL enterprise (.37950)
echo "Walking VSOL enterprise tree (1.3.6.1.4.1.37950)...\n";
echo "This may take a while...\n\n";

$result = @snmpwalkoid($oltIp, $community, '1.3.6.1.4.1.37950', 120000000, 5);

if ($result) {
    echo "Total entries found: " . count($result) . "\n\n";
    
    // Look for text that could be ONU descriptions
    $potentialDescs = [];
    
    // Known names from screenshot: Hoho-35b, Gustam, Hanif, Hari-35b, Bandang, etc.
    $knownNames = ['Hoho', 'Gustam', 'Hanif', 'Hari', 'Bandang', 'AriB', 'Gardu', 'Agus'];
    
    foreach ($result as $oid => $value) {
        if (is_string($value) && strlen($value) >= 3) {
            // Check if it matches known names
            foreach ($knownNames as $name) {
                if (stripos($value, $name) !== false) {
                    $potentialDescs[$oid] = $value;
                    break;
                }
            }
            
            // Also check for any alphanumeric text that's not MAC/hex
            if (!isset($potentialDescs[$oid])) {
                if (preg_match('/[a-zA-Z]{4,}/', $value) && 
                    !preg_match('/^[0-9a-fA-F:.\-]+$/', $value) &&
                    !in_array(strtolower($value), ['auth success', 'auth fail', 'online', 'offline', 'loid', 'loidpw'])) {
                    $potentialDescs[$oid] = $value;
                }
            }
        }
    }
    
    echo "Potential descriptions found: " . count($potentialDescs) . "\n\n";
    
    if (count($potentialDescs) > 0) {
        echo "Values:\n";
        $i = 0;
        foreach ($potentialDescs as $oid => $val) {
            if ($i++ >= 50) {
                echo "... and more\n";
                break;
            }
            $shortOid = preg_replace('/^.*?37950\./', '.37950.', $oid);
            echo "  {$shortOid} = \"{$val}\"\n";
        }
    }
    
    // Group all OIDs by their major subtree
    echo "\n\n=== SUBTREE SUMMARY ===\n";
    $subtrees = [];
    foreach ($result as $oid => $val) {
        // Get first few levels of OID after 37950
        if (preg_match('/37950\.(\d+\.\d+\.\d+)/', $oid, $m)) {
            $tree = $m[1];
            if (!isset($subtrees[$tree])) {
                $subtrees[$tree] = 0;
            }
            $subtrees[$tree]++;
        }
    }
    
    ksort($subtrees);
    foreach ($subtrees as $tree => $count) {
        echo "  .37950.{$tree} : {$count} entries\n";
    }
    
} else {
    echo "Failed to walk VSOL tree.\n";
}

echo "\nDone.\n";
