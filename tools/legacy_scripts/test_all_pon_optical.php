<?php
/**
 * Test PON Optical Power for both OLTs
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

echo "Testing PON Optical Power for All OLTs\n";
echo str_repeat("=", 70) . "\n\n";

$olts = Olt::all();

foreach ($olts as $olt) {
    echo "OLT: {$olt->name} ({$olt->brand})\n";
    echo str_repeat("-", 70) . "\n";
    
    try {
        $helper = OltFactory::make($olt);
        $opticalData = $helper->getPonOpticalPower();
        
        if (empty($opticalData)) {
            echo "  No optical power data available\n";
        } else {
            echo sprintf("  %-15s %-12s %-12s %-12s %-12s %s\n", 
                'Port', 'TX Power', 'Temp', 'Voltage', 'TX Bias', 'Quality');
            echo "  " . str_repeat("-", 65) . "\n";
            
            foreach ($opticalData as $port) {
                echo sprintf("  %-15s %-12s %-12s %-12s %-12s %s\n",
                    $port['name'],
                    $port['tx_power_formatted'],
                    $port['temperature_formatted'],
                    $port['voltage_formatted'],
                    $port['tx_bias_formatted'],
                    $port['signal_quality']
                );
            }
        }
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Done!\n";
