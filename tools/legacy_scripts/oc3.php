<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$h = App\Helpers\Olt\OltFactory::make($olt);
$result = $h->applyPonOnuMng(1, 1, 16, [
    'vlan' => 335,
    'mgmt_vlan' => 111,
    'pppoe_username' => null,
    'pppoe_password' => null,
    'wait_seconds' => 0,
]);
echo json_encode($result, JSON_PRETTY_PRINT);