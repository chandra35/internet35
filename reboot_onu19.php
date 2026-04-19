<?php
// Reboot ONT ONU 19 via OLT

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->firstOrFail();
$helper = App\Helpers\Olt\OltFactory::make($olt);

echo "Rebooting ONT 1/1/1:19...\n";
$result = $helper->rebootOnu(1, 1, 19);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
