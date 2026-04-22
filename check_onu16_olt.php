<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('name', 'OLT-ZTE-C320')->firstOrFail();
$helper = App\Helpers\Olt\OltFactory::make($olt);

echo "==== running-config ONU 16 ====\n";
echo $helper->getOnuRunningConfig(1, 1, 16) . "\n";

$ref = new ReflectionClass($helper);
$exec = $ref->getMethod('executeCommands');
$exec->setAccessible(true);

echo "\n==== service-port for onu_1/1/1:16 ====\n";
echo $exec->invoke($helper, ['terminal length 0','show service-port interface gpon-onu_1/1/1:16']) . "\n";

echo "\n==== mac learned on this onu (limit 30) ====\n";
echo $exec->invoke($helper, ['terminal length 0','show mac gpon onu gpon-onu_1/1/1:16']) . "\n";
