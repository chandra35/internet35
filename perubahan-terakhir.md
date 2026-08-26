# Perubahan Terakhir

Tanggal: 27 Agustus 2026 (WIB)

## Navbar

- Menambahkan jam real-time WIB di navbar atas untuk layout admin dan portal pelanggan.
- Jam dihitung di browser dengan zona waktu `Asia/Jakarta` dan diperbarui setiap detik.

## Scheduler: Auto Isolir MikroTik

- Menambahkan panel **Auto Isolir Pelanggan ke MikroTik** pada `Admin > Scheduler`.
- Panel dapat membuat atau memperbarui task global `billing:auto-suspend`, menentukan frekuensi eksekusi, serta mengaktifkan atau menonaktifkannya.
- Task global mengevaluasi data setiap pelanggan: hanya pelanggan `active` dengan `auto_isolir=true`, invoice pending/overdue yang melewati grace period, router aktif, dan PPP secret yang ada yang dapat diproses.
- Ketika memenuhi syarat, aplikasi mengubah profile PPP secret menjadi profile isolir lalu memutus sesi PPP aktif agar pelanggan reconnect ke profile isolir. PPP secret tidak di-disable.
- Status pelanggan dan invoice hanya diperbarui setelah perintah perubahan profile di MikroTik sukses. Bila koneksi router/secret/perintah gagal, task mencatat kegagalan dan tidak membuat pelanggan menjadi suspended hanya di database.
- Perbaikan kompatibilitas database: autoisolir tidak lagi menyimpan nilai `mikrotik_status=isolated` yang tidak didukung enum. Karena secret tetap enabled, status MikroTik disimpan sebagai `enabled`.
- Service isolir/buka isolir kini memeriksa hasil update profile dan enable secret sebelum menyatakan operasi berhasil.

## Verifikasi sebelum deploy

Berhasil dijalankan pada workspace:

```text
php -l app/Http/Controllers/Admin/SchedulerController.php
php -l app/Console/Commands/BillingAutoSuspend.php
php -l app/Services/CustomerUnsuspendService.php
php artisan route:list --name=admin.scheduler.auto-suspend.configure
php artisan view:cache
git diff --check
```

## Setelah deploy

1. Login sebagai superadmin, buka `Admin > Scheduler`, pilih jadwal Auto Isolir lalu simpan/aktifkan.
2. Pastikan cron server menjalankan `php artisan schedule:run` setiap menit dan queue worker hidup.
3. Pastikan profile isolir tersedia pada setiap MikroTik yang akan dipakai.
4. Aktifkan flag `auto_isolir` hanya pada pelanggan yang telah disetujui.
5. Jalankan `php artisan billing:auto-suspend --dry-run` dahulu dan cek log sebelum menunggu jadwal otomatis.

## Catatan keamanan

`INTERNET.md` adalah pedoman internal yang memuat kredensial/topologi. File tersebut sengaja diabaikan Git dan tidak termasuk commit/push. Gunakan hanya dari workspace atau penyimpanan internal terenkripsi.

