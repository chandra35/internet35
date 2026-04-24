<?php
/**
 * Script: add_genieacs_vps.php
 * Buat VirtualParameters baru di GenieACS langsung via MongoDB
 * (karena NBI API menolak script dengan object literal return).
 *
 * Jalankan di VM GenieACS (172.10.10.254):
 *   php add_genieacs_vps.php
 * atau dari VM app:
 *   scp ke 254 dulu
 */

// Bisa dijalankan standalone tanpa Laravel
$mongoHost = getenv('GENIEACS_MONGO_HOST') ?: '127.0.0.1';
$mongoPort = getenv('GENIEACS_MONGO_PORT') ?: 27017;
$mongoDb   = 'genieacs';

// -------------------------------------------------------------------
// VirtualParameters scripts (format yang sama dgn VP existing)
// -------------------------------------------------------------------
$vps = [
    'getTxPower' => [
        'script' => implode("\n", [
            '// TX Optical Power',
            'let m = "N/A";',
            'let zte = declare("InternetGatewayDevice.WANDevice.*.X_ZTE-COM_WANPONInterfaceConfig.TXPower", {value: Date.now()});',
            'let huawei = declare("InternetGatewayDevice.WANDevice.*.X_GponInterafceConfig.TXPower", {value: Date.now()});',
            'let fiberhome = declare("InternetGatewayDevice.WANDevice.*.X_FH_GponInterfaceConfig.TXPower", {value: Date.now()});',
            'if (zte.size) {',
            '  let val = zte.value[0];',
            '  if (typeof val !== "undefined" && val !== "") m = val;',
            '} else if (huawei.size) {',
            '  for (let p of huawei) {',
            '    if (p.value[0]) { m = p.value[0]; break; }',
            '  }',
            '} else if (fiberhome.size) {',
            '  for (let p of fiberhome) {',
            '    if (p.value[0]) { m = p.value[0]; break; }',
            '  }',
            '}',
            'return {writable: false, value: [m, "xsd:string"]};',
        ]),
    ],
    'getWanStatus' => [
        'script' => implode("\n", [
            '// WAN PPPoE Connection Status',
            'let result = "Unknown";',
            'let keys = [',
            '  "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus",',
            '  "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ConnectionStatus"',
            '];',
            'for (let i = 0; i < keys.length; i++) {',
            '  let d = declare(keys[i], {value: Date.now()});',
            '  if (d.size) {',
            '    for (let p of d) {',
            '      if (p.value && p.value[0]) { result = p.value[0]; break; }',
            '    }',
            '    if (result !== "Unknown") break;',
            '  }',
            '}',
            'return {writable: false, value: [result, "xsd:string"]};',
        ]),
    ],
];

// -------------------------------------------------------------------
// Check if running in environment with MongoDB extension
// -------------------------------------------------------------------
if (!extension_loaded('mongodb') && !class_exists('MongoDB\Client')) {
    // Fallback: generate mongo shell commands to run manually
    echo "MongoDB extension not available. Generating mongo shell commands..." . PHP_EOL . PHP_EOL;
    
    foreach ($vps as $name => $data) {
        $script = addslashes($data['script']);
        echo "# Insert VP: {$name}" . PHP_EOL;
        echo "# Run on VM 254:" . PHP_EOL;
        echo "mongo genieacs --eval 'db.virtualParameters.updateOne({_id:\"" . $name . "\"},{\\$set:{script:\"" . $script . "\"}},{upsert:true})'" . PHP_EOL . PHP_EOL;
    }
    
    echo PHP_EOL . "Atau jalankan script berikut di VM GenieACS:" . PHP_EOL;
    echo "ssh root@172.10.10.254 'mongo genieacs --eval \"...\"'" . PHP_EOL;
    exit(0);
}

// Use MongoDB PHP extension
try {
    $client = new MongoDB\Client("mongodb://{$mongoHost}:{$mongoPort}");
    $collection = $client->selectCollection($mongoDb, 'virtualParameters');
    
    foreach ($vps as $name => $data) {
        $existing = $collection->findOne(['_id' => $name]);
        $action = $existing ? 'UPDATE' : 'CREATE';
        
        echo "{$action} VP '{$name}'... ";
        
        $result = $collection->updateOne(
            ['_id' => $name],
            ['$set' => ['script' => $data['script']]],
            ['upsert' => true]
        );
        
        echo "OK (matched={$result->getMatchedCount()}, upserted={$result->getUpsertedCount()})" . PHP_EOL;
    }
    
    echo PHP_EOL . "Selesai. VP akan aktif di inform berikutnya (dalam " . PHP_EOL;
    echo "~5 menit atau saat device menginform ke ACS)." . PHP_EOL;
    
} catch (\Exception $e) {
    echo "ERROR MongoDB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

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
    if ($ok) {
        echo "OK" . PHP_EOL;
    } else {
        echo "GAGAL" . PHP_EOL;
        // Debug: show raw response
        try {
            $r = \Illuminate\Support\Facades\Http::timeout(10)
                ->asJson()
                ->put(
                    config('services.genieacs.nbi_url', 'http://172.10.10.254:7557') . "/virtual_parameters/{$name}",
                    ['script' => $script]
                );
            echo "  HTTP {$r->status()}: " . substr($r->body(), 0, 200) . PHP_EOL;
        } catch (\Exception $debugEx) {
            echo "  Exception: " . $debugEx->getMessage() . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Selesai. Restart GenieACS tidak diperlukan — VP aktif di inform berikutnya." . PHP_EOL;
echo "Cek hasilnya: curl http://172.10.10.254:7557/virtual_parameters" . PHP_EOL;
