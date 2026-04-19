<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

$olt = Olt::where('ip_address', '136.1.1.100')->first();
$helper = OltFactory::make($olt);

echo "=== ONU 19 running-config ===\n";
echo $helper->executeBatchCliCommands(['show running-config interface gpon-onu_1/1/1:19']);

echo "\n\n=== ONU 20 running-config ===\n";
echo $helper->executeBatchCliCommands(['show running-config interface gpon-onu_1/1/1:20']);

echo "\n\n=== ONU 19 pon-onu-mng ===\n";
echo $helper->executeBatchCliCommands(['show running-config interface gpon-onu-mng_1/1/1:19']);

echo "\n\n=== ONU 20 pon-onu-mng ===\n";
echo $helper->executeBatchCliCommands(['show running-config interface gpon-onu-mng_1/1/1:20']);
