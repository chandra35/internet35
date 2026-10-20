<?php
/**
 * Check DB records
 */

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\Onu;

$olt = Olt::where('ip_address', '172.16.16.3')->first();
echo "OLT ID: " . $olt->id . PHP_EOL;
echo "PON Ports: " . OltPonPort::where('olt_id', $olt->id)->count() . PHP_EOL;
echo "ONUs: " . Onu::where('olt_id', $olt->id)->count() . PHP_EOL;
echo "ONUs with olt_rx_power: " . Onu::where('olt_id', $olt->id)->whereNotNull('olt_rx_power')->count() . PHP_EOL;

// Check first few ONUs
$onus = Onu::where('olt_id', $olt->id)->take(5)->get(['id', 'slot', 'pon_port', 'onu_id', 'status', 'olt_rx_power', 'tx_power']);
echo "\nSample ONUs:\n";
foreach ($onus as $onu) {
    echo "  PON {$onu->slot}/{$onu->pon_port}:{$onu->onu_id} - Status: {$onu->status}, OLT RX: {$onu->olt_rx_power}, TX: {$onu->tx_power}\n";
}

// Check Hioso OLT
echo "\n--- Hioso OLT ---\n";
$hioso = Olt::where('ip_address', '172.16.16.4')->first();
if ($hioso) {
    echo "OLT ID: " . $hioso->id . PHP_EOL;
    echo "PON Ports: " . OltPonPort::where('olt_id', $hioso->id)->count() . PHP_EOL;
    echo "ONUs: " . Onu::where('olt_id', $hioso->id)->count() . PHP_EOL;
    echo "ONUs with olt_rx_power: " . Onu::where('olt_id', $hioso->id)->whereNotNull('olt_rx_power')->count() . PHP_EOL;
}
