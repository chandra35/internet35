<?php
// Run as: php artisan eval:run or just php test_pppoe_config.php from project root
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$g = new \App\Services\GenieAcsService();
$device = $g->findDeviceBySerial('HWTC0EDD2AAF');
echo "Device found: " . ($device ? $device['device_id'] : 'NOT FOUND') . "\n";

if ($device) {
    $onu = \App\Models\Onu::where('serial_number', 'HWTC0EDD2AAF')->first();
    echo "wan_ip: " . ($onu ? ($onu->wan_ip ?? 'NULL') : 'ONU not found') . "\n";
    echo "pppoe_username: " . ($onu ? ($onu->pppoe_username ?? 'NULL') : 'ONU not found') . "\n";
    echo "device_id contains %: " . (str_contains($device['device_id'], '%') ? 'YES' : 'NO') . "\n\n";

    // Use the URL-safe helper for this ONU type
    $helper = new \App\Services\HuaweiHG8145X6GenieAcsService();
    $result = $helper->configureWanPppoe($device['device_id'], [
        'username' => 'tes@huawei',
        'password' => 'test123',
        'vlan' => 335,
    ]);
    echo "Result:\n";
    print_r($result);
}
