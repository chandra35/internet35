<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

// Try different service-port commands
$cmds = [
    'show service-port',
    'show running-config interface gpon-olt_1/1/1',
    'show gpon onu detail-info gpon-onu_1/1/1:1',
    'show gpon onu detail-info gpon-onu_1/1/1:21',
];

foreach ($cmds as $cmd) {
    echo "=== $cmd ===\n";
    $out = $ref->invoke($helper, [$cmd]);
    // Only show first 40 lines
    $lines = explode("\n", $out);
    echo implode("\n", array_slice($lines, 0, 40));
    if (count($lines) > 40) echo "\n... (" . count($lines) . " lines total)";
    echo "\n\n";
}
