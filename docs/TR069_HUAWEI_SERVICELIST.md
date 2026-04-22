# TR-069 Huawei `X_HW_SERVICELIST` — Catatan Maintenance

> Dokumen ini menjelaskan kenapa **semua task ACS untuk ONU Huawei tiba-tiba menumpuk
> di "Pending Tasks"** padahal device terlihat Connected, dan bagaimana mendiagnosis
> serta memperbaikinya.

## TL;DR

Pada ONU Huawei (EG8141H5, HG8245H5, dll), setiap WAN connection punya parameter
`X_HW_SERVICELIST` yang menentukan service apa saja yang dilayani WAN tersebut.
Nilai yang umum:

| Nilai | Arti |
|---|---|
| `TR069` | WAN khusus management TR-069 (biasanya WANIPConnection di VLAN 111 / DHCP) |
| `INTERNET` | WAN khusus internet pelanggan (PPPoE / IP DHCP) |
| `TR069_INTERNET` | **GABUNGAN** — WAN melayani BOTH TR-069 dan internet |
| `VOIP`, `IPTV`, dll | Service lain |

**Bug umum:** WAN PPPoE pelanggan tercipta dengan default firmware
`X_HW_SERVICELIST = "TR069_INTERNET"`. Akibatnya Huawei pakai **IP PPPoE publik**
(misal `10.10.6.67`) sebagai `ConnectionRequestURL` ke GenieACS — bukan IP DHCP
mgmt VLAN 111 (`172.16.19.138`). GenieACS yang ada di network internal
(`172.10.10.254`) **tidak bisa reach IP PPPoE pelanggan**, jadi setiap kali kirim
task dengan `?connection_request`, request balik ke ONU **timeout**. Task tidak
gagal hard, hanya antri menunggu Periodic Inform. Kalau `PeriodicInformInterval`
juga default Huawei (`604800` = 7 hari) atau `PeriodicInformEnable=false`, task
**tidak akan pernah dieksekusi**.

## Gejala

Di UI ACS Management ONU:

- Banner kuning "Menunggu device check-in… Xs / 36s maks" terus-menerus
- "Pending Tasks" menumpuk: 1× `setParameterValues` + banyak `getParameterValues`
- Username PPPoE di tampilan tidak berubah walau sudah klik **Setup PPPoE WAN**
- Status header tetap "Connected" (last_inform lama, tidak refresh)
- Tab WiFi/LAN tidak ter-update walau Refresh diklik

## Cara diagnosa cepat

```php
// chdir ke project root, bootstrap app, lalu:
$svc = new App\Services\GenieAcsService();
$dev = $svc->findDeviceBySerial('HWTC...');
$id  = $dev['device_id'];

// 1) Cek ManagementServer params
GET http://172.10.10.254:7557/devices?query={"_id":"<id>"}&projection=InternetGatewayDevice.ManagementServer

// Periksa:
//   ConnectionRequestURL → harus IP mgmt VLAN (172.16.x), BUKAN IP PPPoE (10.x)
//   PeriodicInformEnable → harus true
//   PeriodicInformInterval → 60-300 detik

// 2) Cek X_HW_SERVICELIST tiap WAN
GET .../WANDevice.1.WANConnectionDevice.<n>.WAN<PPP|IP>Connection.<m>.X_HW_SERVICELIST

// PPPoE WAN HARUS = "INTERNET" (atau VOIP/IPTV/INTERNET kombinasi tanpa TR069)
// IP WAN mgmt HARUS = "TR069"
```

## Cara perbaiki secara manual (recovery)

Lihat `audit_acs_huawei.php` + `fix_acs_huawei.php` di root project (kalau masih
ada — kalau tidak, contoh di bawah).

```php
use Illuminate\Support\Facades\Http;
$nbi = 'http://172.10.10.254:7557';

// 1. Hapus task yang macet
foreach ($svc->getDeviceTasks($id) as $t) {
    Http::delete("$nbi/tasks/{$t['_id']}");
}

// 2. Queue fix tasks (TANPA ?connection_request — biarkan eksekusi saat Inform)
Http::asJson()->post("$nbi/devices/$id/tasks", [
    'name' => 'setParameterValues',
    'parameterValues' => [
        // Hapus TR069 dari ServiceList PPPoE
        ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.X_HW_SERVICELIST', 'INTERNET', 'xsd:string'],
        // Aktifkan Periodic Inform
        ['InternetGatewayDevice.ManagementServer.PeriodicInformEnable', true, 'xsd:boolean'],
        ['InternetGatewayDevice.ManagementServer.PeriodicInformInterval', 300, 'xsd:unsignedInt'],
    ],
]);

// 3. Reboot ONU via OLT untuk paksa re-register ConnectionRequestURL
$onu = App\Models\Onu::find(...);
App\Helpers\Olt\OltFactory::make($onu->olt)
    ->rebootOnu($onu->slot, $onu->port, $onu->onu_id);
```

Tunggu 1–3 menit. Verifikasi `ConnectionRequestURL` sekarang pakai IP `172.16.x`.

## Pencegahan di kode

`app/Services/GenieAcsService.php` → method `buildWanPppoeParams()` cabang
`case 'huawei':` selalu set:

```php
$params["{$wanPath}.X_HW_SERVICELIST"] = ['INTERNET', 'xsd:string'];
```

Ini memastikan setiap WAN PPPoE yang dibuat/diupdate via Setup PPPoE WAN punya
ServiceList bersih. WAN mgmt TR-069 (`WANConnectionDevice.2.WANIPConnection.1`)
tidak disentuh — sudah di-set OLT side dengan `X_HW_SERVICELIST="TR069"` melalui
`pon-onu-mng` profile (lihat `ZTE_C320_PROVISIONING.md` section 6).

## Kasus terkait

- Saat Periodic Inform = 7 hari + ConnectionRequestURL salah, ONU **tidak akan
  pernah** Inform lagi sampai di-reboot. Selalu reboot setelah fix ServiceList.
- Kalau ONU pelanggan dibehind double-NAT atau di WAN dengan IP private,
  ConnectionRequestURL bisa juga unreachable. Solusi: pastikan WAN mgmt VLAN 111
  dapat IP DHCP yang routable dari subnet GenieACS.
- Untuk Fiberhome, parameter ekuivalen adalah `X_FH_*` — belum dikonfirmasi pola
  serupa, tambahkan ke dokumen ini kalau ditemukan.

## Riwayat

- 2026-04-23 — Ditemukan pada ONU `HWTCD38C26AA` (EG8141H5) di OLT ZTE C320
  port `1/1:16`. Pre-fix: `X_HW_SERVICELIST="TR069_INTERNET"`,
  `PeriodicInformInterval=604800`, `PeriodicInformEnable=false`,
  `ConnectionRequestURL=http://10.10.6.67:7547/...`. 17 task macet.
  Setelah fix + reboot: ServiceList=INTERNET, Inform=300s, task tereksekusi
  normal.
