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

echo "Device: $id\n\n";

// Clear pending + fault
foreach ($svc->getDeviceTasks($id) as $t) {
    Http::delete("$nbi/tasks/{$t['_id']}");
    echo "DEL task ".$t['_id']."\n";
}
$fr = Http::get("$nbi/faults", ['query' => json_encode(['device' => $id])]);
foreach (($fr->json() ?: []) as $f) {
    Http::delete("$nbi/faults/" . urlencode($f['_id']));
    echo "DEL fault ".$f['_id']."\n";
}

// Test individual parameters one-by-one
$tests = [
    ['Inform interval 300',
        [['InternetGatewayDevice.ManagementServer.PeriodicInformInterval', 300, 'xsd:unsignedInt']]],
    ['CR Username "kosong"',
        [['InternetGatewayDevice.ManagementServer.ConnectionRequestUsername', 'kosong', 'xsd:string']]],
    ['CR Password "kosong"',
        [['InternetGatewayDevice.ManagementServer.ConnectionRequestPassword', 'kosong', 'xsd:string']]],
];

foreach ($tests as [$label, $pv]) {
    $r = Http::timeout(10)->asJson()->post("$nbi/devices/$id/tasks", [
        'name' => 'setParameterValues',
        'parameterValues' => $pv,
    ]);
    echo "Q: $label -> ".$r->status()." task=".($r->json('_id')??'?')."\n";
}

// Trigger inform via reboot
$onu = App\Models\Onu::where('serial_number', $serial)->first();
$rb = App\Helpers\Olt\OltFactory::make($onu->olt)->rebootOnu($onu->slot, $onu->port, $onu->onu_id);
echo "\nReboot: ".json_encode($rb)."\n";
