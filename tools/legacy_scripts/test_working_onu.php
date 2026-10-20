<?php
// Check a working ONU config from SmartOLT for comparison
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

// Find a working ONU (one provisioned by SmartOLT that has PPPoE working)
// First check ONU 1 on port 1/1 (likely SmartOLT-provisioned)
for ($i = 1; $i <= 5; $i++) {
    echo "=== ONU 1/1/$i pon-onu-mng ===\n";
    $cfg = $ref->invoke($helper, ["show onu running config gpon-onu_1/1/1:$i"]);
    if (stripos($cfg, 'pppoe') !== false) {
        echo $cfg . "\n\n";
        break;
    } else {
        echo "(no pppoe config, skipping)\n\n";
    }
}
