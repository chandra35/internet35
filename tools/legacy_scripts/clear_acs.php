<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$serial = 'HWTCD38C26AA';
$nbi = 'http://172.10.10.254:7557';
$svc = new App\Services\GenieAcsService();
$id = $svc->findDeviceBySerial($serial)['device_id'];

echo "Device: $id\n";

// Cancel all pending tasks
foreach ($svc->getDeviceTasks($id) as $t) {
    $r = Http::delete("$nbi/tasks/{$t['_id']}");
    echo "DEL task ".$t['_id']." -> ".$r->status()."\n";
}

// Delete faults
$fr = Http::get("$nbi/faults", ['query' => json_encode(['device' => $id])]);
foreach (($fr->json() ?: []) as $f) {
    $fid = urlencode($f['_id']);
    $r = Http::delete("$nbi/faults/$fid");
    echo "DEL fault ".$f['_id']." -> ".$r->status()."\n";
}

echo "Done\n";
