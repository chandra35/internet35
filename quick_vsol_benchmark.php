<?php
/**
 * Quick benchmark for VSOL only
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

$olt = Olt::where('brand', 'vsol')->first();
echo "Testing VSOL: {$olt->name}\n";

$helper = OltFactory::make($olt);

$start = microtime(true);
$data = $helper->getTrafficSummary();
$elapsed = round((microtime(true) - $start) * 1000);

echo "Time: {$elapsed}ms\n";
echo "PON Ports: " . count($data['pon_ports']['ports']) . "\n";
echo "Uplinks: " . count($data['uplink_ports']['ports']) . "\n";
echo "Optical: " . count($data['optical_power']['pon_ports']) . "\n";

// Show sample data
if (!empty($data['pon_ports']['ports'])) {
    echo "\nSample PON Port:\n";
    $port = $data['pon_ports']['ports'][0];
    echo "  Index: " . ($port['index'] ?? 'N/A') . "\n";
    echo "  Name: " . $port['name'] . "\n";
    echo "  Download: " . $port['in_bytes_formatted'] . "\n";
    echo "  Upload: " . $port['out_bytes_formatted'] . "\n";
}

if (!empty($data['optical_power']['pon_ports'])) {
    echo "\nSample Optical:\n";
    $opt = $data['optical_power']['pon_ports'][0];
    echo "  Port: " . ($opt['port'] ?? 'N/A') . "\n";
    echo "  TX Power: " . $opt['tx_power_formatted'] . "\n";
    echo "  Temp: " . $opt['temperature_formatted'] . "\n";
}
