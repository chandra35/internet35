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
//    Mencoba path Huawei dulu, lalu ZTE, lalu fallback ke N/A.
//    Format return: string dBm seperti "2.5" atau "-1.3"
// -------------------------------------------------------------------
$getTxPowerScript = <<<'JS'
let result = "N/A";
try {
  // Huawei ONT TX power via vendor-specific TR-069 path
  let hw = declare("InternetGatewayDevice.X_HW_DEBUG.GPON.TxOpticalPower", {value: 1});
  if (hw.value && hw.value[0] !== undefined && hw.value[0] !== "") {
    result = String(hw.value[0]);
  }
} catch(e1) {
  try {
    // ZTE ONT TX power via vendor-specific path
    let zte = declare("InternetGatewayDevice.X_ZTE-COM_GPON.TxPower", {value: 1});
    if (zte.value && zte.value[0] !== undefined && zte.value[0] !== "") {
      result = String(zte.value[0]);
    }
  } catch(e2) {
    result = "N/A";
  }
}
return [Date.now(), result];
JS;

// -------------------------------------------------------------------
// 2. getWanStatus — status koneksi WAN PPPoE
//    Membaca ConnectionStatus dari WANPPPConnection.
//    Return: "Connected" | "Disconnected" | "Unknown"
// -------------------------------------------------------------------
$getWanStatusScript = <<<'JS'
let result = "Unknown";
try {
  let x = declare(
    "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus",
    {value: 1}
  );
  if (x.value && x.value[0]) {
    result = x.value[0];
  }
} catch(e) {
  result = "Unknown";
}
return [Date.now(), result];
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
