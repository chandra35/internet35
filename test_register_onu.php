<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = \App\Models\Olt::where('ip_address', '136.1.1.100')->first();
if (!$olt) { echo "ZTE OLT not found!\n"; exit(1); }
echo "OLT: {$olt->name} ({$olt->ip_address})\n";

$helper = (new \App\Helpers\Olt\ZteC320Helper())->setOlt($olt);

// First try to unregister ONU 19 if it exists from previous test
echo "=== Unregistering ONU 19 (cleanup) ===\n";
$unreg = $helper->unregisterOnu(1, 1, 19);
print_r($unreg);

// Wait for ONU to appear in uncfg list
sleep(5);

echo "\n=== Unregistered ONUs ===\n";
$uncfg = $helper->getUnregisteredOnus();
print_r($uncfg);

if (!empty($uncfg)) {
    $onu = $uncfg[0];
    echo "\n=== Registering ONU: {$onu['serial_number']} ===\n";
    
    $result = $helper->registerOnu([
        'slot' => $onu['slot'],
        'port' => $onu['port'],
        'serial_number' => $onu['serial_number'],
        'name' => 'Test-TR069-ONU',
        'line_profile' => 'SMARTOLT-1G-UP',
        'tcont_id' => 1,
        'gem_port' => 1,
        'service_id' => 1,
        'service_port_mode' => 'tag',
        'vlan' => 335,
        'mgmt_vlan' => 111,
    ]);
    
    echo "\n=== Registration Result ===\n";
    print_r($result);
} else {
    echo "No unregistered ONUs found\n";
}
