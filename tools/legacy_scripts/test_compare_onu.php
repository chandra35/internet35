<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

// Compare INTERFACE config (not pon-onu-mng) between working ONU and ours
echo "=== INTERFACE CONFIG: SmartOLT ONU 1/1/1:1 (working) ===\n";
$cfg1 = $ref->invoke($helper, ['show running-config interface gpon-onu_1/1/1:1']);
echo $cfg1 . "\n\n";

echo "=== INTERFACE CONFIG: Our ONU 1/1/1:21 ===\n";
$cfg21 = $ref->invoke($helper, ['show running-config interface gpon-onu_1/1/1:21']);
echo $cfg21 . "\n\n";

// Check OLT-level service-port for both
echo "=== OLT SERVICE-PORT for ONU 1/1/1:1 ===\n";
$sp1 = $ref->invoke($helper, ['show service-port port gpon-onu_1/1/1:1']);
echo $sp1 . "\n\n";

echo "=== OLT SERVICE-PORT for gpon-olt_1/1/1 (all) ===\n";
$spAll = $ref->invoke($helper, ['show service-port port gpon-olt_1/1/1']);
echo $spAll . "\n";
