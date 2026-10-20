<?php
/**
 * Test Interface Traffic Stats via SNMP
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

echo "=== Test Interface Traffic Stats ===\n\n";

// Get Hioso OLT
$olt = Olt::where('ip_address', '172.16.16.4')->first();

if (!$olt) {
    die("OLT not found!\n");
}

echo "OLT: {$olt->name} ({$olt->ip_address})\n\n";

$helper = OltFactory::make($olt);

// Test 1: Get all interface stats
echo "=== 1. All Interface Stats ===\n";
$stats = $helper->getInterfaceStats();

if (empty($stats)) {
    echo "No interface stats available\n";
} else {
    echo "Found " . count($stats) . " interfaces:\n\n";
    
    echo str_pad("Name", 12) . str_pad("Type", 8) . str_pad("Status", 8) . 
         str_pad("Speed", 10) . str_pad("In", 15) . str_pad("Out", 15) . 
         str_pad("Errors", 10) . "\n";
    echo str_repeat("-", 78) . "\n";
    
    foreach ($stats as $stat) {
        echo str_pad($stat['name'], 12);
        echo str_pad($stat['type'], 8);
        echo str_pad($stat['status'], 8);
        echo str_pad($stat['speed_mbps'] . " Mbps", 10);
        echo str_pad($stat['in_bytes_formatted'], 15);
        echo str_pad($stat['out_bytes_formatted'], 15);
        echo str_pad($stat['in_errors'] . "/" . $stat['out_errors'], 10);
        echo "\n";
    }
}

// Test 2: PON Traffic only
echo "\n=== 2. PON Port Traffic ===\n";
$ponStats = $helper->getPonTrafficStats();
foreach ($ponStats as $stat) {
    echo "  {$stat['name']}: In={$stat['in_bytes_formatted']}, Out={$stat['out_bytes_formatted']} ({$stat['status']})\n";
}

// Test 3: Uplink Traffic only
echo "\n=== 3. Uplink Traffic ===\n";
$uplinkStats = $helper->getUplinkTrafficStats();
foreach ($uplinkStats as $stat) {
    echo "  {$stat['name']}: In={$stat['in_bytes_formatted']}, Out={$stat['out_bytes_formatted']} ({$stat['status']})\n";
}

// Test 4: Traffic Summary
echo "\n=== 4. Traffic Summary ===\n";
$summary = $helper->getTrafficSummary();

echo "PON Ports:\n";
echo "  Total: {$summary['pon_ports']['total']} ({$summary['pon_ports']['up']} up, {$summary['pon_ports']['down']} down)\n";
echo "  Traffic In:  {$summary['pon_ports']['in_formatted']}\n";
echo "  Traffic Out: {$summary['pon_ports']['out_formatted']}\n";

echo "\nUplink Ports:\n";
echo "  Total: {$summary['uplink_ports']['total']} ({$summary['uplink_ports']['up']} up, {$summary['uplink_ports']['down']} down)\n";
echo "  Traffic In:  {$summary['uplink_ports']['in_formatted']}\n";
echo "  Traffic Out: {$summary['uplink_ports']['out_formatted']}\n";

echo "\nCollected at: {$summary['collected_at']}\n";

echo "\n=== DONE ===\n";
