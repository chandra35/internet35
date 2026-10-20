<?php
/**
 * Test Hioso Traffic Stats via getInterfaceStats
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

echo "Testing Hioso Traffic Stats\n";
echo str_repeat("=", 80) . "\n\n";

$olt = Olt::where('brand', 'hioso')->first();
echo "OLT: {$olt->name} ({$olt->ip_address})\n\n";

$helper = OltFactory::make($olt);
$stats = $helper->getInterfaceStats();

// PON Ports
$ponPorts = array_filter($stats, fn($s) => $s['type'] === 'pon');
echo "PON Ports Traffic:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-15s %-10s %-20s %-20s %s\n", 'Port', 'Status', 'Download', 'Upload', 'ONUs');
echo str_repeat("-", 80) . "\n";
foreach ($ponPorts as $port) {
    echo sprintf("%-15s %-10s %-20s %-20s %s\n",
        $port['name'],
        $port['status'],
        $port['in_bytes_formatted'],
        $port['out_bytes_formatted'],
        $port['onu_count'] ?? 0
    );
}

echo "\n\n";

// Uplink Ports
$uplinkPorts = array_filter($stats, fn($s) => $s['type'] === 'uplink');
echo "Uplink Ports Traffic:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-15s %-10s %-20s %-20s\n", 'Port', 'Status', 'Download', 'Upload');
echo str_repeat("-", 80) . "\n";
foreach ($uplinkPorts as $port) {
    echo sprintf("%-15s %-10s %-20s %-20s\n",
        $port['name'],
        $port['status'],
        $port['in_bytes_formatted'],
        $port['out_bytes_formatted']
    );
}

echo "\nDone!\n";
