<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$h = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionClass($h);
$m = $ref->getMethod('executeBatchCliCommands');
$m->setAccessible(true);

// Try several diagnostic commands the OLT may understand
$cmds = [
    "show gpon onu detail-info gpon-onu_1/1/1:16",
    "show gpon remote-onu state gpon-onu_1/1/1:16",
    "show pon-onu-mng gpon-onu_1/1/1:16",
    "show running-config-pon-onu-mng gpon-onu_1/1/1:16",
    "show gpon onu detail-info gpon-onu_1/1/1:16 onu-info",
];
foreach ($cmds as $c) {
    echo "\n=== $c ===\n";
    echo $m->invoke($h, [$c]);
}
