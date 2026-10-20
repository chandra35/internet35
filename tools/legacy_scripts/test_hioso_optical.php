<?php
/**
 * Test Hioso PON Optical Power via Telnet
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('brand', 'hioso')->first();
echo "Testing Hioso OLT: {$olt->name} ({$olt->ip_address})\n\n";

$helper = App\Helpers\Olt\OltFactory::make($olt);
echo "Getting PON Optical Power via Telnet DDM...\n\n";

$data = $helper->getPonOpticalPower();

if (empty($data)) {
    echo "No data returned!\n";
} else {
    foreach ($data as $port) {
        echo sprintf("%-15s TX=%s Temp=%s Voltage=%s Quality=%s\n",
            $port['name'],
            $port['tx_power_formatted'],
            $port['temperature_formatted'],
            $port['voltage_formatted'],
            $port['signal_quality']
        );
    }
}

echo "\nDone!\n";
