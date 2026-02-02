<?php
/**
 * Test VSOL V1600D OIDs from oid-base.com reference
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();
echo "OLT: {$olt->ip_address}\n\n";

snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

$timeout = 15000000; // 15 seconds  
$retries = 2;

// OIDs to test based on oid-base.com VSOL V1600D MIB structure
$testOids = [
    // From user's link - profile related
    'profileTypeShow (.12.9.7.1.1.2)' => '.1.3.6.1.4.1.37950.1.1.5.12.9.7.1.1.2',
    
    // ONU Auth tables
    'authModeTable (.12.1.1.1)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1',
    'whiteMacPonNo (.12.1.2.1)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.2.1',
    'whiteMacValue (.12.1.2.2)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.2.2',
    
    // Try higher sibling numbers in onuAuth (possible ONU tables)
    'onuAuth.3 (.12.1.3)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.3',
    'onuAuth.4 (.12.1.4)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.4',
    'onuAuth.5 (.12.1.5)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.5',
    'onuAuth.10 (.12.1.10)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.10',
    'onuAuth.20 (.12.1.20)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.20',
    'onuAuth.30 (.12.1.30)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.30',
    
    // ONU Port table
    'onuPort (.12.5)' => '.1.3.6.1.4.1.37950.1.1.5.12.5',
    
    // Current working OID (status)
    'WORKING: Legacy Status (.12.1.1.1.2)' => '.1.3.6.1.4.1.37950.1.1.5.12.1.1.1.2',
    
    // Other potential ONU tables in .12
    'v1600dOnuMgmt.3 (.12.3)' => '.1.3.6.1.4.1.37950.1.1.5.12.3',
    'v1600dOnuMgmt.4 (.12.4)' => '.1.3.6.1.4.1.37950.1.1.5.12.4',
];

echo "Testing VSOL V1600D OIDs...\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($testOids as $name => $oid) {
    echo "Testing: {$name}\n";
    echo "OID: {$oid}\n";
    
    $start = microtime(true);
    $result = @snmp2_walk($olt->ip_address, $olt->snmp_community, $oid, $timeout, $retries);
    $elapsed = round((microtime(true) - $start) * 1000);
    
    if ($result !== false && !empty($result)) {
        $count = count($result);
        echo "✓ Found {$count} entries ({$elapsed}ms)\n";
        
        // Show first 3 entries
        $i = 0;
        foreach ($result as $k => $v) {
            $displayVal = strlen($v) > 50 ? substr($v, 0, 50) . '...' : $v;
            echo "  {$k} = {$displayVal}\n";
            if (++$i >= 3) break;
        }
        if ($count > 3) echo "  ... and " . ($count - 3) . " more\n";
    } else {
        echo "✗ No data ({$elapsed}ms)\n";
    }
    
    echo "\n";
}

echo "Done.\n";
