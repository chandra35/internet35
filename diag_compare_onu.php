<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$h = App\Helpers\Olt\OltFactory::make($olt);
$ref = new ReflectionClass($h);
$exec = $ref->getMethod('executeBatchCliCommands');
$exec->setAccessible(true);

// Compare ONU 16 (broken) vs all other registered ONUs to find the working pattern
$cmds = [
    "show gpon onu detail-info gpon-onu_1/1/1:16",
    "show gpon onu detail-info gpon-onu_1/1/1:17",
    "show gpon onu by-name 1/1/1",
    "show running-config interface gpon-onu_1/1/1:16",
    "show running-config interface gpon-onu_1/1/1:17",
];
echo $exec->invoke($h, $cmds);
