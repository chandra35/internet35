<?php
// Test the fixed getUnregisteredOnus
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = \App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = \App\Helpers\Olt\OltFactory::make($olt);

$uncfg = $helper->getUnregisteredOnus();
echo "Found " . count($uncfg) . " uncfg ONUs:\n";
foreach ($uncfg as $u) {
    echo json_encode($u, JSON_PRETTY_PRINT) . "\n";
}
