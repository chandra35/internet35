<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;

$serial = 'HWTCD38C26AA';
$nbi = 'http://172.10.10.254:7557';
$svc = new App\Services\GenieAcsService();
$id  = $svc->findDeviceBySerial($serial)['device_id'];

// Clear
foreach ($svc->getDeviceTasks($id) as $t) Http::delete("$nbi/tasks/{$t['_id']}");
$fr = Http::get("$nbi/faults", ['query'=>json_encode(['device'=>$id])]);
foreach (($fr->json()??[]) as $f) Http::delete("$nbi/faults/".urlencode($f['_id']));

// Test param yang harusnya WORK - ubah Interval 300 -> 600
$r = Http::asJson()->post("$nbi/devices/$id/tasks", [
    'name'=>'setParameterValues',
    'parameterValues'=>[
        ['InternetGatewayDevice.ManagementServer.PeriodicInformInterval', 600, 'xsd:unsignedInt'],
    ],
]);
echo "Q PeriodicInformInterval=600 -> ".$r->status()."\n";
echo "Tunggu inform berikutnya & cek apakah Internal di mongo berubah jadi 600\n";
