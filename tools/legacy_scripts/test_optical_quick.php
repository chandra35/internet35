<?php
// Quick test 3 ONUs only

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

$olt = Olt::where('ip_address', '172.16.16.4')->first();
if (!$olt) die("OLT not found\n");

echo "Quick Optical Test (3 ONUs only)\n";
echo "================================\n\n";

$helper = OltFactory::make($olt);

// Test getOnuOpticalInfo directly
echo "Testing getOnuOpticalInfo() for 3 ONUs:\n\n";

$testOnus = [
    [0, 1, 3],
    [0, 1, 4],
    [0, 2, 1],
];

foreach ($testOnus as $onu) {
    [$slot, $port, $onuId] = $onu;
    echo "ONU {$slot}/{$port}:{$onuId}:\n";
    
    $optical = $helper->getOnuOpticalInfo($slot, $port, $onuId);
    echo "  Tx Power: " . ($optical['tx_power'] ?? 'N/A') . " dBm\n";
    echo "  Rx Power: " . ($optical['rx_power'] ?? 'N/A') . " dBm\n";
    echo "  Temperature: " . ($optical['temperature'] ?? 'N/A') . " C\n";
    echo "\n";
}

echo "Done!\n";
