<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// List all OLTs
$olts = App\Models\Olt::all(['id', 'name', 'ip_address', 'brand', 'status', 'snmp_community']);

echo "=== OLT List ===\n";
foreach ($olts as $olt) {
    echo "{$olt->name} ({$olt->brand}) - {$olt->ip_address} [{$olt->status}]\n";
}

// Check for Hioso
$hioso = App\Models\Olt::where('brand', 'hioso')->first();
if ($hioso) {
    echo "\n=== Testing Hioso OLT: {$hioso->name} ===\n";
    $helper = (new App\Helpers\Olt\HiosoHelper())->setOlt($hioso);
    
    echo "Testing SNMP connection...\n";
    $onus = $helper->getAllOnus();
    echo "ONUs found: " . count($onus) . "\n";
    
    if (count($onus) > 0) {
        echo "\nFirst 5 ONUs:\n";
        foreach (array_slice($onus, 0, 5) as $onu) {
            echo "  {$onu['slot']}/{$onu['port']}:{$onu['onu_id']} - {$onu['serial_number']} [{$onu['status']}]\n";
        }
    }
} else {
    echo "\nNo Hioso OLT found in database.\n";
    echo "Please add a Hioso OLT via admin panel first.\n";
}
