<?php
// Reboot ONT ONU 19 via OLT

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->firstOrFail();
$helper = App\Helpers\Olt\OltFactory::make($olt);
$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

echo "Rebooting ONT 1/1/1:19...\n";
$out = $ref->invoke($helper, ['reboot ont gpon-onu_1/1/1:19 1']);
echo $out . "\n";
