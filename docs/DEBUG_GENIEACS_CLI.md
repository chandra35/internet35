# Debug GenieACS Read/Write via PHP CLI

Cara tercepat untuk test apakah read/write ke GenieACS berfungsi **tanpa perlu buka browser**.
Langsung jalankan PHP di server — hasilnya langsung kelihatan benar/salah.

---

## Setup: Buat script di lokal, upload ke server, jalankan

```powershell
# Di PowerShell lokal (d:\projek\internet35)

$script = @'
[ISI SCRIPT PHP DI SINI]
'@

$script | Out-File -FilePath "debug_temp.php" -Encoding UTF8
pscp -pw kosongkosong debug_temp.php root@172.10.10.253:/www/wwwroot/internet35/
plink -ssh root@172.10.10.253 -pw kosongkosong -batch "cd /www/wwwroot/internet35 && /www/server/php/83/bin/php debug_temp.php 2>&1"

# Setelah selesai, hapus file
plink -ssh root@172.10.10.253 -pw kosongkosong -batch "rm /www/wwwroot/internet35/debug_temp.php"
Remove-Item debug_temp.php -ErrorAction SilentlyContinue
```

---

## Template boilerplate PHP untuk semua script debug

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// --- tulis kode debug di sini ---
$s = new App\Services\GenieAcsService();
```

---

## Script siap pakai

### 1. Test READ Security Info

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$result = $s->getSecurityInfo('00259E-HG8245H-485754436ED42F9A');

echo "KEYS: " . json_encode(array_keys($result)) . "\n\n";
echo "ACL:\n";
foreach ($result['acl'] ?? [] as $k => $v) {
    echo "  $k: " . var_export($v, true) . "\n";
}
echo "\nCLI:\n";
foreach ($result['cli'] ?? [] as $k => $v) {
    echo "  $k: " . var_export($v, true) . "\n";
}
echo "\nWeb User: " . json_encode($result['web_user'] ?? []) . "\n";
echo "Web Admin: " . json_encode($result['web_admin'] ?? []) . "\n";
echo "Firewall Level: " . ($result['firewall_level'] ?? 'N/A') . "\n";
```

**Output yang diharapkan:**
```
KEYS: ["firewall_level","default_gateway","dns_servers","acs","acl","cli","web_user","web_admin"]

ACL:
  ftp_lan: false
  ftp_wan: false
  http_lan: true
  http_wan: true
  ...
```

---

### 2. Test WRITE Security Settings

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();

$result = $s->setSecuritySettings('00259E-HG8245H-485754436ED42F9A', [
    'acl_http_lan' => 1,   // 1 = enable, 0 = disable
    'acl_http_wan' => 1,
    'acl_ftp_lan'  => 0,
    'acl_ftp_wan'  => 0,
]);

echo "WRITE RESULT: " . json_encode($result) . "\n";
// Harapan: {"success":true,"task_id":"...","status":200}
// status 200 = langsung diterapkan, 202 = ditambah ke antrian
```

---

### 3. Test WRITE + VERIFY (write lalu baca balik)

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$deviceId = '00259E-HG8245H-485754436ED42F9A';

// --- WRITE ---
$writeResult = $s->setSecuritySettings($deviceId, [
    'acl_http_lan' => 1,
    'acl_http_wan' => 1,
    'acl_icmp_echo' => 1,
]);
echo "WRITE: " . json_encode($writeResult) . "\n";

// Tunggu sebentar agar GenieACS cache terupdate
sleep(3);

// --- READ BALIK ---
$readResult = $s->getSecurityInfo($deviceId);
echo "VERIFY http_lan: " . var_export($readResult['acl']['http_lan'] ?? 'N/A', true) . "\n";
echo "VERIFY http_wan: " . var_export($readResult['acl']['http_wan'] ?? 'N/A', true) . "\n";
echo "VERIFY icmp:     " . var_export($readResult['acl']['icmp_echo'] ?? 'N/A', true) . "\n";
```

> **Catatan:** Jika `status=200` tapi verify masih nilai lama, artinya GenieACS cache belum update —
> device belum kirim Inform terbaru. Tunggu lebih lama atau trigger refresh dulu.

---

### 4. Test READ WiFi Info

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$wifis = $s->getWifiInfo('00259E-HG8245H-485754436ED42F9A');

foreach ($wifis as $i => $w) {
    echo "WiFi #$i: SSID={$w['ssid']} Enable={$w['enable']} Band={$w['standard']}\n";
}
```

---

### 5. Test READ WAN Info

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$wans = $s->getWanInfo('00259E-HG8245H-485754436ED42F9A');

foreach ($wans as $i => $w) {
    echo "WAN #$i: IP={$w['ip']} Status={$w['status']} Type={$w['type']}\n";
}
```

---

### 6. Test setParameterValues langsung (raw)

Untuk test OID baru yang belum ada di service:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$deviceId = '00259E-HG8245H-485754436ED42F9A';

$result = $s->setParameterValues($deviceId, [
    'InternetGatewayDevice.X_HW_Security.AclServices.HTTPLanEnable' => [true, 'xsd:boolean'],
    'InternetGatewayDevice.X_HW_Security.AclServices.HTTPWanEnable' => [true, 'xsd:boolean'],
], true); // true = with connection_request

echo json_encode($result) . "\n";
```

---

### 7. Cek pending tasks device

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$tasks = $s->getDeviceTasks('00259E-HG8245H-485754436ED42F9A');

echo "Pending tasks: " . count($tasks) . "\n";
foreach ($tasks as $t) {
    echo "  [{$t['_id']}] {$t['name']}\n";
}
```

---

### 8. Clear semua pending tasks

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Services\GenieAcsService();
$cleared = $s->clearDeviceTasks('00259E-HG8245H-485754436ED42F9A');
// Atau hanya jenis tertentu:
// $cleared = $s->clearDeviceTasks('00259E-HG8245H-485754436ED42F9A', 'getParameterValues');

echo "Cleared $cleared tasks\n";
```

---

## Cara baca response GenieACS

| `status` | Arti |
|---------|------|
| `200` | Task langsung diselesaikan — device online & connection_request berhasil |
| `202` | Task masuk antrian — device offline, akan dijalankan saat Inform berikutnya |
| `4xx` | Error request — cek format body JSON |
| `500` | Error server GenieACS |

## Cara baca cache GenieACS

GenieACS **tidak real-time**. Cache di MongoDB diupdate hanya saat:
1. Device kirim `Inform` (periodic, default setiap 300 detik)
2. Ada task `getParameterValues` yang completed (connection_request berhasil)

Jadi setelah write, kalau langsung baca balik, **nilainya bisa masih lama**.
Solusi: trigger `smartRefresh()` dulu, tunggu ~5-10 detik, baru baca.

```php
$s->smartRefresh($deviceId);   // kirim getParameterValues ke device
sleep(8);                       // tunggu device respond
$result = $s->getSecurityInfo($deviceId); // baca cache yang sudah fresh
```

---

## Device IDs yang diketahui

| Device | Serial | GenieACS Device ID |
|--------|--------|-------------------|
| omah-tes (HG8245H) | HWTC6ED42F9A | `00259E-HG8245H-485754436ED42F9A` |

> Untuk cari device ID lain: `$s->findDeviceBySerial('serialnumber')['device_id']`
