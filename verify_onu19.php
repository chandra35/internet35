<?php
// Verify ONU 19 current config after fix

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->firstOrFail();
$helper = App\Helpers\Olt\OltFactory::make($olt);
$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

echo "=== gpon-onu_1/1/1:19 running-config ===\n";
echo $ref->invoke($helper, ['show running-config interface gpon-onu_1/1/1:19']);
