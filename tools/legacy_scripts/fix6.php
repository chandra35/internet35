<?php
// Test berbagai kombinasi user/pwd untuk CR Huawei EG8141H5
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$serial = 'HWTCD38C26AA';
$nbi = 'http://172.10.10.254:7557';
$svc = new App\Services\GenieAcsService();
$id  = $svc->findDeviceBySerial($serial)['device_id'];

function clear($id, $svc, $nbi) {
    foreach ($svc->getDeviceTasks($id) as $t) Http::delete("$nbi/tasks/{$t['_id']}");
    $fr = Http::get("$nbi/faults", ['query' => json_encode(['device' => $id])]);
    foreach (($fr->json() ?: []) as $f) Http::delete("$nbi/faults/" . urlencode($f['_id']));
}

clear($id, $svc, $nbi);

$user = $argv[1] ?? 'acs';
$pwd  = $argv[2] ?? 'Acs12345';
echo "Testing: user=$user pwd=$pwd (len=" . strlen($pwd) . ")\n";

// SatuPI task, dua param atomic
$r = Http::timeout(10)->asJson()->post("$nbi/devices/$id/tasks", [
    'name' => 'setParameterValues',
    'parameterValues' => [
        ['InternetGatewayDevice.ManagementServer.ConnectionRequestUsername', $user, 'xsd:string'],
        ['InternetGatewayDevice.ManagementServer.ConnectionRequestPassword', $pwd,  'xsd:string'],
    ],
]);
echo "Q -> ".$r->status()." task=".$r->json('_id')."\n";
echo "Tunggu next periodic inform (~5min) lalu cek fault\n";
