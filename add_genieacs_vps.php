<?php
/**
 * Script: add_genieacs_vps.php
 * Buat/update VirtualParameters baru di GenieACS via NBI API.
 *
 * VirtualParameters yang ditambah:
 *   1. getTxPower  — TX optical power dari ONU (Huawei/ZTE specific)
 *   2. getWanStatus — status koneksi WAN PPPoE
 *
 * Jalankan 1x saja: php add_genieacs_vps.php
 */
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GenieAcsService;

$g = new GenieAcsService();

if (!$g->isAvailable()) {
    echo "ERROR: GenieACS NBI tidak tersedia. Pastikan server GenieACS aktif." . PHP_EOL;
    exit(1);
}

// -------------------------------------------------------------------
// 1. getTxPower — TX optical power ONU
//    Mencoba path ZTE dulu, lalu Huawei, lalu FiberHome.
//    Return: float dBm string, atau "N/A"
// -------------------------------------------------------------------
$getTxPowerScript = <<<'JS'
// TX Optical Power
let m = "N/A";
let zte = declare("InternetGatewayDevice.WANDevice.*.X_ZTE-COM_WANPONInterfaceConfig.TXPower", {value: Date.now()});
let huawei = declare("InternetGatewayDevice.WANDevice.*.X_GponInterafceConfig.TXPower", {value: Date.now()});
let fiberhome = declare("InternetGatewayDevice.WANDevice.*.X_FH_GponInterfaceConfig.TXPower", {value: Date.now()});
if (zte.size) {
  let val = zte.value[0];
  if (typeof val !== "undefined" && val !== "") m = val;
} else if (huawei.size) {
  for (let p of huawei) {
    if (p.value[0]) { m = p.value[0]; break; }
  }
} else if (fiberhome.size) {
  for (let p of fiberhome) {
    if (p.value[0]) { m = p.value[0]; break; }
  }
}
return {writable: false, value: [m, "xsd:string"]};
JS;

// -------------------------------------------------------------------
// 2. getWanStatus — status koneksi WAN PPPoE
//    Return: "Connected" | "Disconnected" | "Unknown"
// -------------------------------------------------------------------
$getWanStatusScript = <<<'JS'
// WAN PPPoE Connection Status
let result = "Unknown";
let keys = [
  "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus",
  "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ConnectionStatus",
  "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.ConnectionStatus"
];
for (let i = 0; i < keys.length; i++) {
  let d = declare(keys[i], {value: Date.now()});
  if (d.size) {
    for (let p of d) {
      if (p.value && p.value[0]) {
        result = p.value[0];
        break;
      }
    }
    if (result !== "Unknown") break;
  }
}
return {writable: false, value: [result, "xsd:string"]};
JS;

// -------------------------------------------------------------------
// Cek existing VPs dulu
// -------------------------------------------------------------------
echo "Mengambil daftar VirtualParameters yang ada..." . PHP_EOL;
$existing = [];
try {
    $r = \Illuminate\Support\Facades\Http::timeout(10)->get(
        config('services.genieacs.nbi_url', 'http://172.10.10.254:7557') . '/virtual_parameters'
    );
    if ($r->ok()) {
        foreach ($r->json() as $vp) {
            $existing[] = $vp['_id'] ?? $vp['name'] ?? '';
        }
        echo "Existing VPs: " . implode(', ', $existing) . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "WARNING: Gagal ambil daftar VP: " . $e->getMessage() . PHP_EOL;
}

// -------------------------------------------------------------------
// Create / update VPs
// -------------------------------------------------------------------
$vps = [
    'getTxPower'   => $getTxPowerScript,
    'getWanStatus' => $getWanStatusScript,
];

foreach ($vps as $name => $script) {
    $action = in_array($name, $existing) ? 'UPDATE' : 'CREATE';
    echo "{$action} VirtualParameter '{$name}'... ";
    $ok = $g->createVirtualParameter($name, $script);
    echo ($ok ? 'OK' : 'GAGAL') . PHP_EOL;
}

echo PHP_EOL . "Selesai. Restart GenieACS tidak diperlukan — VP aktif di inform berikutnya." . PHP_EOL;
echo "Cek hasilnya: curl http://172.10.10.254:7557/virtual_parameters" . PHP_EOL;
