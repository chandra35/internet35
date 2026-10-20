<?php
// Set CR creds satu per satu (atomic transaction issue di Huawei)
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$serial = 'HWTCD38C26AA';
$nbi = 'http://172.10.10.254:7557';
$svc = new App\Services\GenieAcsService();
$id  = $svc->findDeviceBySerial($serial)['device_id'];
$enc = urlencode($id);

echo "Device: $id\n\n";

// Clear pending + faults
foreach ($svc->getDeviceTasks($id) as $t) Http::delete("$nbi/tasks/{$t['_id']}");
$fr = Http::get("$nbi/faults", ['query' => json_encode(['device' => $id])]);
foreach (($fr->json() ?: []) as $f) Http::delete("$nbi/faults/" . urlencode($f['_id']));

// Two SEPARATE tasks (not in same SetParameterValues atomic call)
$pwd = $argv[1] ?? 'acs@internet35';
$tests = [
    ['CR Username "acs"',     'ConnectionRequestUsername', 'acs'],
    ['CR Password',           'ConnectionRequestPassword', $pwd],
];
foreach ($tests as [$label, $param, $val]) {
    $r = Http::timeout(10)->asJson()->post("$nbi/devices/$id/tasks", [
        'name' => 'setParameterValues',
        'parameterValues' => [["InternetGatewayDevice.ManagementServer.$param", $val, 'xsd:string']],
    ]);
    echo "Q: $label='$val' -> ".$r->status()." task=".($r->json('_id')??'?')."\n";
}

// Trigger CR via NBI (config saat ini = AUTH("kosong","kosong") match ONU saat ini)
$cr = Http::timeout(20)->post("$nbi/devices/$enc/tasks?connection_request", [
    'name' => 'refreshObject',
    'objectName' => 'InternetGatewayDevice.ManagementServer',
]);
echo "\nCR refresh -> ".$cr->status()."\n";
