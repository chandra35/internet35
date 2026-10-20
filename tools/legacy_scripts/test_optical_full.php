<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

// Get Hioso OLT
$olt = Olt::where('ip_address', '172.16.16.4')->first();

if (!$olt) {
    die("OLT not found\n");
}

echo "Testing Hioso Optical Power via Telnet\n";
echo "=====================================\n";
echo "OLT: {$olt->name} ({$olt->ip_address})\n\n";

try {
    $helper = OltFactory::make($olt);
    
    // Test getting all ONUs (with optical for online ones)
    echo "1. Getting all ONUs via getAllOnus()...\n";
    $onus = $helper->getAllOnus();
    
    echo "   Found " . count($onus) . " ONUs\n\n";
    
    // Group by status
    $online = array_filter($onus, fn($o) => ($o['status'] ?? '') === 'online');
    $offline = array_filter($onus, fn($o) => ($o['status'] ?? '') !== 'online');
    
    echo "   Online: " . count($online) . ", Offline: " . count($offline) . "\n\n";
    
    // Show first 5 online ONUs with optical data
    echo "2. Sample online ONUs with optical data:\n";
    $count = 0;
    foreach ($online as $onu) {
        echo "   - ONU {$onu['slot']}/{$onu['port']}:{$onu['onu_id']} ({$onu['name']})\n";
        echo "     Status: {$onu['status']}\n";
        echo "     Tx Power: " . ($onu['tx_power'] ?? 'N/A') . " dBm\n";
        echo "     Rx Power: " . ($onu['rx_power'] ?? 'N/A') . " dBm\n";
        echo "     Temperature: " . ($onu['temperature'] ?? 'N/A') . " C\n";
        echo "\n";
        
        if (++$count >= 5) break;
    }
    
    // Test single ONU optical
    echo "3. Testing single ONU optical info...\n";
    $testOnu = reset($online);
    if ($testOnu) {
        $opticalInfo = $helper->getOnuOpticalInfo(
            $testOnu['slot'],
            $testOnu['port'],
            $testOnu['onu_id']
        );
        
        echo "   ONU {$testOnu['slot']}/{$testOnu['port']}:{$testOnu['onu_id']}\n";
        echo "   Optical Info: " . json_encode($opticalInfo, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\nDone!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
