<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$serial = 'HWTCD38C26AA';
$nbi = 'http://172.10.10.254:7557';
$svc = new App\Services\GenieAcsService();
$dev = $svc->findDeviceBySerial($serial);
if (!$dev) { echo "NOT FOUND\n"; exit; }
$id = $dev['device_id'];
echo "Device: $id\n";
echo "Last inform: ".($dev['last_inform']??'?')."\n\n";

echo "=== PENDING TASKS ===\n";
foreach ($svc->getDeviceTasks($id) as $t) {
    echo "- ".($t['name']??'?')." | id=".($t['_id']??'?')."\n";
    if (isset($t['parameterValues'])) foreach ($t['parameterValues'] as $pv) echo "    ".$pv[0]." = ".json_encode($pv[1])."\n";
    if (isset($t['parameterNames'])) echo "    names=".json_encode($t['parameterNames'])."\n";
    if (isset($t['objectName'])) echo "    object=".$t['objectName']."\n";
    if (isset($t['fault'])) echo "    FAULT=".json_encode($t['fault'])."\n";
}

echo "\n=== FAULTS ===\n";
$fr = Http::timeout(10)->get("$nbi/faults", ['query' => json_encode(['device' => $id])]);
foreach (($fr->json() ?: []) as $f) {
    echo "- ".($f['_id']??'?')." code=".($f['code']??'?')."\n";
    echo "    msg=".($f['message']??'?')."\n";
    echo "    ts=".($f['timestamp']??'?')."\n";
    if (isset($f['detail'])) echo "    detail=".json_encode($f['detail'])."\n";
}

echo "\n=== ACS Mgmt ===\n";
$r = Http::timeout(10)->get("$nbi/devices", [
    'query' => json_encode(['_id'=>$id]),
    'projection' => 'InternetGatewayDevice.ManagementServer',
]);
$ms = $r->json()[0]['InternetGatewayDevice']['ManagementServer'] ?? [];
foreach (['ConnectionRequestURL','PeriodicInformEnable','PeriodicInformInterval','URL','Username'] as $k) {
    echo "  $k = ".json_encode($ms[$k]['_value']??null)."\n";
}

echo "\n=== WANConnectionDevice instances ===\n";
$r = Http::timeout(10)->get("$nbi/devices", [
    'query' => json_encode(['_id'=>$id]),
    'projection' => 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice',
]);
$wcd = $r->json()[0]['InternetGatewayDevice']['WANDevice']['1']['WANConnectionDevice'] ?? [];
foreach ($wcd as $idx => $val) {
    if (!is_array($val) || str_starts_with($idx,'_')) continue;
    echo "WCD.$idx:\n";
    foreach (['WANPPPConnection','WANIPConnection'] as $type) {
        $sub = $val[$type] ?? [];
        foreach ($sub as $j => $v) {
            if (!is_array($v) || str_starts_with($j,'_')) continue;
            $name = $v['Name']['_value']??'-';
            $en = $v['Enable']['_value']??'-';
            $ip = $v['ExternalIPAddress']['_value']??'-';
            $sl = $v['X_HW_SERVICELIST']['_value']??'-';
            $vl = $v['X_HW_VLAN']['_value']??'-';
            $u  = $v['Username']['_value']??'-';
            echo "  $type.$j  name=$name en=$en ip=$ip vlan=$vl SL=$sl user=$u\n";
        }
    }
}
