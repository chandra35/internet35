<?php
/**
 * Test Optical Power Feature - New Version
 */

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

// Get Hioso OLT (172.16.16.4)
$olt = Olt::where('ip_address', '172.16.16.4')->first();
if (!$olt) {
    die("Hioso OLT not found\n");
}

echo "Testing optical power for OLT: {$olt->name} ({$olt->ip_address})\n\n";

// Get helper
$helper = OltFactory::make($olt);

echo "=== getPonOpticalPower() ===\n";
$opticalData = $helper->getPonOpticalPower();
print_r($opticalData);

echo "\n=== getOpticalPowerSummary() ===\n";
$summary = $helper->getOpticalPowerSummary();
print_r($summary['summary']);

echo "\n=== getTrafficSummary() (with optical) ===\n";
$traffic = $helper->getTrafficSummary();
if (isset($traffic['optical_power'])) {
    echo "Optical power data included!\n";
    echo "Total ONUs: " . ($traffic['optical_power']['summary']['total_onus'] ?? 0) . "\n";
    echo "Online ONUs: " . ($traffic['optical_power']['summary']['online_onus'] ?? 0) . "\n";
    echo "Overall RX Avg: " . ($traffic['optical_power']['summary']['overall_rx_power_formatted'] ?? '-') . "\n";
} else {
    echo "No optical power data in response\n";
}

echo "\nDone!\n";
