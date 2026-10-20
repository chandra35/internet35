# Internet35 — Sistem Operasi & Billing ISP (FTTH)

Aplikasi manajemen operasional dan billing ISP berbasis **Laravel 12** yang dirancang untuk penyedia layanan internet fiber-to-the-home (FTTH) dengan arsitektur **Multi-POP (Point of Presence)**.

---

## 🚀 Fitur Utama

### 1. Manajemen Multi-POP & Pelanggan
- Multi-tenant POP dengan isolasi data per wilayah.
- Manajemen siklus hidup pelanggan (Status: *Pending*, *Active*, *Suspended*, *Terminated*).
- Integrasi data kependudukan (NIK/wilayah) dan pembatasan akses data per POP.
- Pemetaan fisik pelanggan ke ODP, ODC, port splitter, dan ONU/CPE.

### 2. Integrasi MikroTik (RouterOS)
- Pengelolaan PPP Secret, PPP Profile, dan IP Pool via RouterOS API.
- **Auto-Isolir (Suspend)**: Mengubah profile PPP secret ke profile isolir dan memutuskan sesi aktif tanpa mematikan (*disable*) secret.
- **Auto-Buka-Isolir (Unsuspend)**: Otomatis memulihkan profile paket layanan begitu tagihan lunas.
- Pencarian dan pengaitan (*assign*) PPP Secret existing di MikroTik tanpa duplikasi data.

### 3. Operasi Jaringan GPON & Multi-Vendor OLT
- Mendukung integrasi OLT multi-merek: **ZTE C320**, **Huawei**, **FiberHome**, **VSOL**, **Hioso**, dan **HSGQ**.
- Protokol: SNMP v2c/v3, Telnet CLI, dan SSH.
- Fitur OLT: Auto-scan unconfigured ONU, registrasi ONU, monitoring redaman optik (Rx/Tx power), T-CONT, GEM Port, Service-Port, dan OMCI `pon-onu-mng`.

### 4. Provisioning CPE / TR-069 (GenieACS)
- Integrasi Northbound Interface (NBI REST API) GenieACS.
- Provisioning otomatis WAN PPPoE, WiFi (SSID, enkripsi, password, channel), dan remote management.
- Queue background push (`PushPppoeToGenieAcs`) dengan retry berkala hingga perangkat online.
- Fallback WebUI untuk perangkat FiberHome.

### 5. Billing & Finansial
- Penjadwalan pembuatan invoice otomatis (*scheduled billing*).
- Fleksibilitas perlakuan PPN (Global POP vs Override per-pelanggan) dengan pemisahan subtotal dan PPN dari harga paket.
- Dukungan *partial payment* dan pencatatan riwayat pembayaran.
- Integrasi multi-gateway (Midtrans, DOKU, Tripay, Xendit, Duitku, iPaymu) serta transfer manual.
- Generator cetak invoice PDF (DomPDF) dengan template per POP.

### 6. Portal Pelanggan & Notifikasi
- Portal mandiri (`/pelanggan`): cek tagihan, pembayaran online, cek status koneksi, dan ubah pengaturan WiFi.
- Notifikasi multi-kanal otomatis: WhatsApp (Fonnte/WABA), Telegram Bot, dan Email SMTP.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.2+ (PHP 8.3 recommended), Laravel 12.x
- **Database**: MySQL / MariaDB (Production), SQLite support
- **Queue & Cache**: Database driver
- **Frontend Admin**: AdminLTE 3 + Bootstrap 4, Vite
- **Frontend Portal**: Bootstrap 5 + Responsive Layout
- **Networking**: MikroTik RouterOS API, PHP SNMP, Socket Telnet/SSH, TR-069 / GenieACS

---

## 📋 Panduan Instalasi Lokal

```bash
# 1. Clone repository
git clone https://github.com/chandra35/internet35.git
cd internet35

# 2. Install dependensi
composer install
npm install

# 3. Konfigurasi Environment
cp .env.example .env
php artisan key:generate

# 4. Migrasi Database (CATATAN: Jangan jalankan migrate:fresh pada data yang sudah ada)
php artisan migrate

# 5. Build asset frontend
npm run build

# 6. Jalankan server lokal
php artisan serve
```

---

## ⚙️ Scheduled Tasks (Cron)

Pastikan Laravel Scheduler berjalan setiap menit di server:

```bash
* * * * * cd /path/to/internet35 && php artisan schedule:run >> /dev/null 2>&1
```

Task utama yang dikelola via `Admin > Scheduler`:
- `billing:generate` — Generate invoice bulanan
- `billing:reminder` — Kirim pengingat tagihan
- `billing:auto-suspend` — Auto-isolir pelanggan jatuh tempo
- `billing:auto-unsuspend` — Auto-buka isolir setelah pelunasan
- `onus:scan-new` — Pemindaian berkala ONU baru pada OLT
- `onu:sync-power` — Sinkronisasi berkala redaman optik ONU

---

## 🔒 Catatan Keamanan
- File kredensial jaringan, dump database, dan konfigurasi server rahasia dikelola secara terisolasi dan **dilarang di-commit ke repositori publik**.
- Password PPPoE dan kredensial sensitif disimpan terenkripsi menggunakan Laravel Application Key.

---

## 📄 Lisensi
Hak Cipta © 2026 Internet35. All rights reserved.
