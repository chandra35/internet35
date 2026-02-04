<?php
/**
 * Benchmark traffic API response time
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

echo "Traffic API Benchmark\n";
echo str_repeat("=", 60) . "\n\n";

$olts = Olt::all();

foreach ($olts as $olt) {
    echo "OLT: {$olt->name} ({$olt->brand})\n";
    
    $helper = OltFactory::make($olt);
    
    $start = microtime(true);
    $data = $helper->getTrafficSummary();
    $elapsed = round((microtime(true) - $start) * 1000);
    
    echo "  Time: {$elapsed}ms\n";
    echo "  PON Ports: " . count($data['pon_ports']['ports']) . "\n";
    echo "  Uplinks: " . count($data['uplink_ports']['ports']) . "\n";
    echo "  Optical: " . count($data['optical_power']['pon_ports']) . "\n";
    echo "\n";
}

echo "Done!\n";
