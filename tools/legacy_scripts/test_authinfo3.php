<?php
/**
 * Test onuAuthInfo3Table yang mungkin berisi description
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== TEST onuAuthInfo3Table ===\n\n";

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// onuAuthInfo3Table columns:
// .1 = authInfoPonNo3 (PON port)
// .2 = authInfoOnuNo3 (ONU ID)
// .3 = onuAuthInfo3Details (might be description!)

$oids = [
    'authInfoPonNo3' => '1.3.6.1.4.1.37950.1.1.5.12.1.31.1.1',
    'authInfoOnuNo3' => '1.3.6.1.4.1.37950.1.1.5.12.1.31.1.2',
    'onuAuthInfo3Details' => '1.3.6.1.4.1.37950.1.1.5.12.1.31.1.3',
];

foreach ($oids as $name => $oid) {
    echo "Testing {$name}...\n";
    $result = @snmpwalkoid($oltIp, $community, $oid, 15000000, 2);
    
    if ($result && count($result) > 0) {
        echo "  ✅ Found " . count($result) . " entries\n";
        
        // Show first 5
        $i = 0;
        foreach ($result as $fullOid => $val) {
            if ($i++ >= 5) {
                echo "    ... and more\n";
                break;
            }
            $short = preg_replace('/^.*\.31\.1\.\d+\./', '', $fullOid);
            echo "    [{$short}] = {$val}\n";
        }
    } else {
        echo "  ❌ No data\n";
    }
    echo "\n";
}

// Also try onuAuthInfo2Table (.30) and onuAuthInfoTable 
echo "\n=== Trying other auth info tables ===\n\n";

$otherTables = [
    'onuAuthInfoTable (.12.1.21)' => '1.3.6.1.4.1.37950.1.1.5.12.1.21',
    'onuAuthInfo2Table (.12.1.30)' => '1.3.6.1.4.1.37950.1.1.5.12.1.30',
];

foreach ($otherTables as $name => $oid) {
    echo "Testing {$name}...\n";
    $result = @snmpwalkoid($oltIp, $community, $oid, 15000000, 2);
    
    if ($result && count($result) > 0) {
        echo "  ✅ Found " . count($result) . " entries\n";
        
        // Check for text values
        $textVals = [];
        foreach ($result as $fullOid => $val) {
            if (is_string($val) && preg_match('/[a-zA-Z]{3,}/', $val) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $val)) {
                if (!in_array(strtolower($val), ['auth success', 'auth fail'])) {
                    $textVals[$fullOid] = $val;
                }
            }
        }
        
        if (count($textVals) > 0) {
            echo "  📝 Text values found:\n";
            $i = 0;
            foreach ($textVals as $oid => $val) {
                if ($i++ >= 10) break;
                $short = preg_replace('/^.*?\.12\.1\./', '.12.1.', $oid);
                echo "    {$short} = \"{$val}\"\n";
            }
        }
    } else {
        echo "  ❌ No data\n";
    }
    echo "\n";
}

echo "Done.\n";
