<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check _deviceId._SerialNumber format in GenieACS
$r = \Illuminate\Support\Facades\Http::timeout(15)->get('http://172.10.10.254:7557/devices', [
    'limit'      => 10,
    'projection' => '_id,_deviceId,_lastInform',
]);

echo "HTTP: " . $r->status() . PHP_EOL;
foreach ($r->json() as $d) {
    $sn  = $d['_deviceId']['_SerialNumber'] ?? '?';
    $oui = $d['_deviceId']['_OUI'] ?? '?';
    $pc  = $d['_deviceId']['_ProductClass'] ?? '?';
    echo $d['_id'] . " | SN=$sn | OUI=$oui | PC=$pc" . PHP_EOL;
}

// Also check a few ONU serial_numbers from DB
echo PHP_EOL . "ONU serials in DB (first 10):" . PHP_EOL;
$onus = \App\Models\Onu::take(10)->get(['serial_number', 'status']);
foreach ($onus as $o) {
    echo $o->serial_number . " (" . $o->status . ")" . PHP_EOL;
}
