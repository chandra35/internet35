<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

// Find global service-port entries
echo "=== show service-port all ===\n";
$sp = $ref->invoke($helper, ['show service-port all']);
echo $sp . "\n";
