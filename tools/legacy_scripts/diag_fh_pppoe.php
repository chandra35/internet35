<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('name', 'OLT-ZTE-C320')->firstOrFail();
$helper = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionClass($helper);
$exec = $ref->getMethod('executeCommands');
$exec->setAccessible(true);

// Compare: ONU 16 (Fiberhome, broken) vs ONU 17 (working SmartOLT-style ACS)
echo "==== ONU 16 (Fiberhome tes) running config ====\n";
echo $exec->invoke($helper, ['terminal length 0','show running-config-pon-onu-mng gpon-onu_1/1/1:16']) . "\n";

echo "\n==== ONU 17 (working) running config ====\n";
echo $exec->invoke($helper, ['terminal length 0','show running-config-pon-onu-mng gpon-onu_1/1/1:17']) . "\n";

// Check actual onu state (auth status, online status)
echo "\n==== gpon onu state ONU 16 ====\n";
echo $exec->invoke($helper, ['terminal length 0','show gpon onu state gpon-onu_1/1/1:16']) . "\n";

// Check pppoe related stats per gemport
echo "\n==== gemport stats ONU 16 ====\n";
echo $exec->invoke($helper, ['terminal length 0','show gpon onu uncfg gpon-olt_1/1/1','show gpon onu interface gpon-onu_1/1/1:16']) . "\n";

// Show traffic on service-port 1 (vport 1, vlan 335 — the INTERNET service)
echo "\n==== service-port-vport stats ====\n";
echo $exec->invoke($helper, ['terminal length 0','show gpon onu service-port gpon-onu_1/1/1:16']) . "\n";
