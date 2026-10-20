<?php
/**
 * Targeted scan untuk Description dengan timeout proper
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== TARGETED DESCRIPTION SCAN ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Walk per subtree dengan timeout terkelola
$subtrees = [
    '1.1.5.12.1' => '1.3.6.1.4.1.37950.1.1.5.12.1',  // onuAuth tables
    '1.1.5.12.2' => '1.3.6.1.4.1.37950.1.1.5.12.2',  // more onu tables
    '1.1.5.12.3' => '1.3.6.1.4.1.37950.1.1.5.12.3',
    '1.1.5.13' => '1.3.6.1.4.1.37950.1.1.5.13',      // other management
    '1.1.5.14' => '1.3.6.1.4.1.37950.1.1.5.14',
    '1.1.5.15' => '1.3.6.1.4.1.37950.1.1.5.15',
    '1.1.6' => '1.3.6.1.4.1.37950.1.1.6',            // other branches
    '1.1.7' => '1.3.6.1.4.1.37950.1.1.7',
    '1.2' => '1.3.6.1.4.1.37950.1.2',
    '1.3' => '1.3.6.1.4.1.37950.1.3',
];

$knownNames = ['Hoho', 'Gustam', 'Hanif', 'Hari', 'Bandang', 'AriB', 'Gardu'];
$allFoundDescs = [];

foreach ($subtrees as $label => $oid) {
    echo "Scanning .37950.{$label}... ";
    
    $start = microtime(true);
    $result = @snmpwalkoid($oltIp, $community, $oid, 15000000, 2);
    $elapsed = round(microtime(true) - $start, 1);
    
    if (!$result || count($result) == 0) {
        echo "empty [{$elapsed}s]\n";
        continue;
    }
    
    echo count($result) . " entries [{$elapsed}s]\n";
    
    // Find text values
    $textFound = 0;
    foreach ($result as $fullOid => $value) {
        if (!is_string($value) || strlen($value) < 3) continue;
        
        // Skip known non-description values
        $lower = strtolower($value);
        if (in_array($lower, ['auth success', 'auth fail', 'online', 'offline', 'loid', 'loidpw', 'onuv', 'onum', 'onutypevalue', 'onuvendorid', 'onumodelid', 'onutypeval'])) continue;
        
        // Skip MAC addresses
        if (preg_match('/^[0-9a-fA-F:.\-]+$/', $value)) continue;
        
        // Check for letters (actual text)
        if (preg_match('/[a-zA-Z]{3,}/', $value)) {
            $textFound++;
            
            // Check for known names
            foreach ($knownNames as $name) {
                if (stripos($value, $name) !== false) {
                    $shortOid = preg_replace('/^.*?37950\./', '.37950.', $fullOid);
                    $allFoundDescs[$shortOid] = $value;
                    echo "  📝 FOUND: {$shortOid} = \"{$value}\"\n";
                    break;
                }
            }
        }
    }
    
    if ($textFound > 0 && count($allFoundDescs) == 0) {
        echo "  ({$textFound} text values, but no known names)\n";
    }
}

echo "\n\n=== SUMMARY ===\n";
if (count($allFoundDescs) > 0) {
    echo "Description OIDs found:\n";
    foreach ($allFoundDescs as $oid => $val) {
        echo "  {$oid} = \"{$val}\"\n";
    }
} else {
    echo "No description OIDs found matching known ONU names.\n";
    echo "Description might NOT be available via SNMP on this VSOL OLT.\n";
    echo "\nPossible alternatives:\n";
    echo "1. Description stored only in web GUI config\n";
    echo "2. Need telnet/CLI access to retrieve\n";
    echo "3. Description stored in different OID structure\n";
}

echo "\nDone.\n";
