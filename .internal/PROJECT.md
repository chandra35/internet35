# Internet35 — Panduan Proyek

> File ini adalah referensi internal. **Jangan di-push ke GitHub.**
> Terakhir diupdate: 15 April 2026

---

## 1. Overview

**Internet35** adalah sistem billing & manajemen ISP berbasis Laravel 12 untuk mengelola pelanggan internet fiber (FTTH).

| Item | Detail |
|------|--------|
| Framework | Laravel 12, PHP ^8.2 |
| PHP Lokal | 8.5.1 |
| PHP Hosting | 8.3.30 |
| Database | SQLite (dev) / MySQL (production) |
| Frontend | AdminLTE 3 + Bootstrap 4 (admin), Bootstrap 5 (landing) |
| GitHub | `https://github.com/chandra35/internet35.git` (branch: `main`) |
| Hosting | Shared hosting `manmetr1@wifi35.net` (cPanel) |
| App Path | `/home/manmetr1/internet35-app/` |
| Public Path | `/home/manmetr1/wifi35.net/` |
| Deploy | `ssh manmetr1@wifi35.net "cd /home/manmetr1/internet35-app && bash deploy/update.sh"` |

---

## 2. Arsitektur Multi-POP

Sistem mendukung multiple POP (Point of Presence). Setiap POP adalah `User` dengan role `admin-pop`.

```
SuperAdmin
├── POP A (User admin-pop)
│   ├── Staff 1, Staff 2
│   ├── Router Mikrotik A
│   ├── OLT ZTE/Huawei
│   ├── Customers POP A
│   └── PopSetting (invoice, integrasi, notifikasi)
├── POP B (User admin-pop)
│   ├── ...
│   └── PopSetting
└── ...
```

Setiap POP punya konfigurasi sendiri via `PopSetting`:
- Info ISP (nama, alamat, logo)
- Template invoice & pajak
- Integrasi Mikrotik (auto-sync, isolir profile)
- Notifikasi (email, WhatsApp, Telegram)
- Payment gateway

---

## 3. Fitur Utama

### Manajemen Pelanggan
- CRUD pelanggan dengan data PPPoE (username, password, profile)
- Import/export Excel
- Bulk sync ke Mikrotik
- Auto-isolir (suspend) & buka isolir
- Link ke ODP/ONU untuk tracking fiber

### Billing & Invoice
- Generate invoice otomatis (scheduled task)
- Multi payment gateway (Midtrans sandbox/production)
- PDF invoice dengan template per POP
- Reminder otomatis via WhatsApp/Email/Telegram
- Portal pelanggan untuk bayar tagihan

### Integrasi Mikrotik (RouterOS API)
- CRUD PPP Profile & IP Pool
- Sync pelanggan sebagai PPP Secret
- Isolir: ubah profile + disconnect
- Comment markers standar:
  - `[billing] Nama - CustID` — PPP Secret
  - `[billing-profile] nama` — PPP Profile
  - `[billing-pool] nama` — IP Pool
  - `[billing-isolir]` — Isolir PPP Profile
  - `[billing-isolir-pool]` — Isolir IP Pool
  - `[billing-isolir-block]` — Firewall filter block
  - `[billing-isolir-redirect]` — NAT redirect ke halaman isolir

### Infrastruktur Isolir (Suspend)
Saat pelanggan di-isolir, di Mikrotik:
1. PPP Secret diubah profile ke `isolir`
2. Pelanggan dapat IP dari `pool-isolir` (subnet terpisah)
3. Bandwidth dibatasi (128k/128k default)
4. Firewall filter: block semua kecuali DNS, HTTP, HTTPS
5. NAT redirect: HTTP → server billing → halaman `/isolir`
6. Pelanggan bayar → auto buka isolir via scheduler

### Jaringan Fiber (FTTH)
- Manajemen OLT (ZTE/Huawei/VSOL/Hioso) via SNMP
- ONU discovery, register, monitoring signal
- ODC → ODP hierarchical (splitter ratio, optical power)
- Network Map (peta jaringan)

### Data Kependudukan
- Import data warga dari Excel
- Assign akses data per POP (per kelurahan)
- Digunakan untuk validasi saat registrasi pelanggan

---

## 4. Struktur File Penting

```
app/
├── Helpers/
│   ├── ActivityLogger.php          — Static logging helper
│   └── Mikrotik/
│       └── MikrotikService.php     — Low-level RouterOS API wrapper (dipakai controllers)
├── Http/Controllers/
│   ├── Admin/                      — 33 controllers admin
│   ├── Pelanggan/                  — 3 controllers portal pelanggan
│   ├── Api/WebhookController.php   — Payment webhook
│   └── LandingController.php       — Landing page + halaman isolir
├── Models/                         — 35 models (Eloquent)
├── Services/
│   ├── MikrotikService.php         — Model-aware Mikrotik service
│   ├── CustomerUnsuspendService.php — Auto buka isolir
│   ├── InvoicePdfService.php       — Generate PDF invoice
│   ├── NotificationService.php     — Email/WA/Telegram
│   ├── PaymentGatewayService.php   — Midtrans/DOKU integration
│   ├── RadiusService.php           — RADIUS integration
│   └── PermissionScannerService.php — Auto-discover permissions
└── Imports/
    └── ResidentImport.php          — Import data warga dari Excel
```

### Dua MikrotikService (PENTING!)
- `app/Helpers/Mikrotik/MikrotikService.php` — Low-level API wrapper. **Dipakai oleh semua controllers.**
- `app/Services/MikrotikService.php` — Higher-level, model-aware. Dipakai untuk beberapa operasi.

---

## 5. Deployment

### Shared Hosting (wifi35.net)
```bash
# Quick update
ssh manmetr1@wifi35.net "cd /home/manmetr1/internet35-app && bash deploy/update.sh"

# Script: deploy/update.sh
# - git pull origin main
# - composer install --no-dev
# - php artisan migrate --force
# - php artisan config:cache
# - php artisan route:cache
# - php artisan view:cache
# - rsync public/ ke document root
```

### Local Development
```bash
cd d:\projek\internet35
php artisan serve        # http://localhost:8000
php artisan migrate
php artisan db:seed
```

---

## 6. Scheduled Tasks (Cron)

Dikelola via admin → Scheduler. Task yang tersedia:
- **Generate Invoice** — Buat tagihan bulanan otomatis
- **Auto Isolir** — Isolir pelanggan yang lewat jatuh tempo
- **Auto Buka Isolir** — Buka isolir setelah pembayaran dikonfirmasi
- **Kirim Reminder** — Reminder tagihan via WA/Email/Telegram
- **Sync Mikrotik** — Sync data pelanggan ke router

---

## 7. Password & Enkripsi

- User password: bcrypt (Laravel default)
- PPPoE password pelanggan: `Crypt::encryptString()` di DB
- Akses plaintext: `$customer->decrypted_pppoe_password` (accessor)
- User plain password: `$user->decrypted_password` (accessor)
- **PENTING**: Saat sync ke Mikrotik, gunakan `decrypted_pppoe_password`, BUKAN `pppoe_password`

---

## 8. Tracking Username PPPoE

- Field `previous_pppoe_username` di customers table
- Saat username diubah, old username disimpan
- `syncMikrotik()` cari secret by name, fallback ke previous_pppoe_username
- Jika ditemukan via previous, rename secret di router

---

## 9. Key URLs

| URL | Fungsi |
|-----|--------|
| `/` | Landing page publik |
| `/isolir` | Halaman isolir (pelanggan yang di-suspend diarahkan ke sini) |
| `/login` | Login admin & pelanggan |
| `/admin/dashboard` | Dashboard admin |
| `/pelanggan` | Portal pelanggan (bayar tagihan, lihat koneksi) |

---

## 10. Tech Stack Summary

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Database | SQLite (dev), MySQL (prod) |
| Admin UI | AdminLTE 3 + Bootstrap 4 |
| Landing | Bootstrap 5 + AOS animation |
| PDF | DomPDF (barryvdh/laravel-dompdf) |
| Excel | Maatwebsite/Excel 3 |
| Permission | Spatie/Laravel-Permission 6 |
| Region Data | Laravolt/Indonesia |
| Mikrotik API | Custom (RouterOS API via socket) |
| SNMP | PHP SNMP functions |
| Payment | Midtrans, DOKU (via PaymentGatewayService) |
| Notification | Email (SMTP), WhatsApp (API), Telegram (Bot API) |
