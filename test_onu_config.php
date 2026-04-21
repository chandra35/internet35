<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

// Get full ONU info
echo "=== ONU Detail from OLT ===\n";
$info = $helper->getOnuInfo(1, 1, 21);
echo json_encode($info, JSON_PRETTY_PRINT) . "\n\n";

// Check running config via CLI
$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

echo "=== Running Config: gpon-onu_1/1/1:21 ===\n";
$cfg = $ref->invoke($helper, ['show running-config interface gpon-onu_1/1/1:21']);
echo $cfg . "\n\n";

echo "=== ONU Running Config (pon) ===\n";
$ponCfg = $ref->invoke($helper, ['show onu running config gpon-onu_1/1/1:21']);
echo $ponCfg . "\n\n";

echo "=== Service Port ===\n";
$sp = $ref->invoke($helper, ['show service-port port gpon-onu_1/1/1:21']);
echo $sp . "\n";
