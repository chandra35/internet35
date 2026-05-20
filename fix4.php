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

// Clear previous fault + task
foreach ($svc->getDeviceTasks($id) as $t) Http::delete("$nbi/tasks/{$t['_id']}");
$fr = Http::get("$nbi/faults", ['query' => json_encode(['device' => $id])]);
foreach (($fr->json() ?: []) as $f) Http::delete("$nbi/faults/" . urlencode($f['_id']));

// Set CR creds: user=acs, pwd=acs@internet35
$body = [
    'name' => 'setParameterValues',
    'parameterValues' => [
        ['InternetGatewayDevice.ManagementServer.ConnectionRequestUsername', 'acs', 'xsd:string'],
        ['InternetGatewayDevice.ManagementServer.ConnectionRequestPassword', 'acs@internet35', 'xsd:string'],
    ],
];
$r = Http::timeout(10)->asJson()->post("$nbi/devices/$id/tasks", $body);
echo "Q: CR user=acs pwd=acs@internet35 -> ".$r->status()." task=".$r->json('_id')."\n";

// Trigger inform
$onu = App\Models\Onu::where('serial_number', $serial)->first();
App\Helpers\Olt\OltFactory::make($onu->olt)->rebootOnu($onu->slot, $onu->port, $onu->onu_id);
echo "Rebooted, tunggu 90s lalu jalankan audit2.php\n";
