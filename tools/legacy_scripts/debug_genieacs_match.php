<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GenieAcsService;

$g = new GenieAcsService();
$d = $g->getDevicesForSync();
$keys = array_keys($d);
echo 'Total devices: ' . count($keys) . PHP_EOL;
foreach(array_slice($keys, 0, 15) as $k) {
    $parts = explode('-', $k);
    $sHex = $d[$k]['serial_hex'] ?? 'null';
    $rx = $d[$k]['rx_power'] ?? 'null';
    echo "$k | parts=" . count($parts) . " | last=" . end($parts) . " | serial_hex=$sHex | rx=$rx" . PHP_EOL;
}

// Also show first few ONU serials from DB
echo PHP_EOL . "ONU serials in DB (first 15):" . PHP_EOL;
$onus = \App\Models\Onu::take(15)->pluck('serial_number');
foreach($onus as $sn) {
    echo $sn . PHP_EOL;
}
