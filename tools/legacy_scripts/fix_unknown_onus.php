<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$total = DB::table('onus')->where('status', 'unknown')->count();
echo "Unknown ONUs total: {$total}" . PHP_EOL;

$withSignal = DB::table('onus')->where('status', 'unknown')->whereNotNull('olt_rx_power')->count();
echo "Unknown ONUs with signal: {$withSignal}" . PHP_EOL;

// Fix: mark as online ONUs that have valid signal (rx > -35 dBm means active)
$fixed = DB::table('onus')
    ->where('status', 'unknown')
    ->whereNotNull('olt_rx_power')
    ->where('olt_rx_power', '>', -35)
    ->update(['status' => 'online']);
echo "Fixed to online: {$fixed}" . PHP_EOL;

// Show remaining unknown
$remaining = DB::table('onus')->where('status', 'unknown')->count();
echo "Remaining unknown: {$remaining}" . PHP_EOL;
