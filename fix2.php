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

// Queue tasks WITHOUT connection_request (akan jalan saat ONU Inform)
$tasks = [
    [
        'label' => 'Set CR auth credentials + Periodic Inform',
        'body' => [
            'name' => 'setParameterValues',
            'parameterValues' => [
                ['InternetGatewayDevice.ManagementServer.ConnectionRequestUsername', 'kosong', 'xsd:string'],
                ['InternetGatewayDevice.ManagementServer.ConnectionRequestPassword', 'kosong', 'xsd:string'],
                ['InternetGatewayDevice.ManagementServer.PeriodicInformEnable', true, 'xsd:boolean'],
                ['InternetGatewayDevice.ManagementServer.PeriodicInformInterval', 300, 'xsd:unsignedInt'],
            ],
        ],
    ],
    [
        'label' => 'AddObject WANConnectionDevice (utk PPPoE WAN)',
        'body' => ['name' => 'addObject', 'objectName' => 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice'],
    ],
];

foreach ($tasks as $t) {
    $r = Http::timeout(10)->asJson()->post("$nbi/devices/$id/tasks", $t['body']);
    echo "  ".$t['label']." -> ".$r->status()." task=".($r->json('_id')??'?')."\n";
}

// Reboot ONU via OLT untuk fresh inform
echo "\nReboot ONU...\n";
$onu = App\Models\Onu::where('serial_number', $serial)->first();
$rb = App\Helpers\Olt\OltFactory::make($onu->olt)->rebootOnu($onu->slot, $onu->port, $onu->onu_id);
echo "  ".json_encode($rb)."\n";

echo "\nTunggu 1-2 menit, lalu jalankan: php audit2.php\n";
