# Perubahan Terakhir

Tanggal: 27 Agustus 2026 (WIB)

## Perpindahan VM

- VM aplikasi dipindahkan dari `172.10.10.253` ke `172.16.2.4`.
- Referensi host aplikasi pada extension GenieACS, script SNMP trap ZTE, dan panduan debug CLI diperbarui ke alamat baru.
- Detail akses VM tersimpan hanya pada `INTERNET.md` (dokumen internal yang diabaikan Git).

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

## Buka Isolir Setelah Pembayaran

- Buka isolir sekarang menggunakan `packages.mikrotik_profile_name`; nama paket hanya fallback untuk data lama.
- Untuk pelanggan yang memiliki router dan PPPoE, status database hanya kembali `active` bila profile MikroTik berhasil dipulihkan dan secret berhasil di-enable. Bila router/secret/profile gagal, pelanggan tetap `suspended` agar status aplikasi tidak berbeda dengan layanan aktual.
- Mapping paket produksi `10M_AB` untuk pelanggan `POP35153240` diselaraskan ke profile MikroTik `10M-35-AB`. Pelanggan tetap suspended sampai invoice ditandai lunas.

## Pembayaran Manual dan Reset Go-Live

- Pembayaran manual yang sudah ada pada detail Invoice dikoreksi: notifikasi aktivasi hanya dikirim bila buka-isolir MikroTik berhasil. Bila gagal, invoice tetap tercatat lunas tetapi pelanggan tetap suspended dan admin menerima pesan yang sesuai.
- Menambahkan **Tools > Reset Data Transaksi (Siap Operasional)** untuk superadmin. Aksi ini menghapus invoice, pembayaran, log notifikasi, dan log scheduler; mereset counter task serta marker tanggal billing.
- Reset gabungan tidak menghapus data pelanggan, user, paket, router, OLT/ONU, jaringan, PPP secret MikroTik, ataupun status layanan pelanggan.

## Invoice Otomatis

- Menambahkan panel **Invoice Otomatis** pada `Sistem > Scheduler`.
- Superadmin dapat membuat, mengaktifkan/nonaktifkan, dan memilih jadwal task `billing:generate` tanpa membuat task generik secara manual.
- Jadwal operasi yang dipilih: setiap hari pukul 08:00 WIB. Command membuat invoice berdasarkan `billing_day` masing-masing pelanggan dan mencegah invoice ganda untuk periode yang sama.

## Daftar Pelanggan

- Daftar pelanggan menggunakan pemuatan tabel AJAX: pencarian berjalan otomatis setelah jeda singkat saat mengetik, filter dan pagination juga dimuat tanpa submit atau refresh halaman.
- Menambahkan kolom **Jatuh Tempo**. Nilainya diambil dari invoice aktif yang paling awal belum lunas (`pending`, `partial`, atau `overdue`), bukan field tanggal pelanggan lama, sehingga sama dengan tanggal penagihan yang dipakai auto-isolir.

## Navigasi Sidebar

- Menata menu berdasarkan fungsi: **Pelanggan & Tagihan** menjadi submenu di dalam **Layanan**, dengan tautan Pelanggan dan Invoice.
- Menu **Router** (Daftar Router, PPP Profiles, dan IP Pools) dipindahkan ke dalam **Jaringan**.
- **Pelanggan & Tagihan** sekarang merupakan modul tersendiri dengan fitur Pelanggan, Invoice, dan Pembayaran.

## Modul Pembayaran

- Menambahkan halaman **Pelanggan & Tagihan > Pembayaran** untuk menemukan pelanggan yang memiliki invoice belum lunas.
- Admin dapat membuka pelanggan, melihat setiap bulan/periode tunggakan, memilih satu atau beberapa invoice, lalu mencatat pembayaran manual sekaligus dalam satu transaksi.
- Setiap invoice yang dipilih memperoleh riwayat pembayaran manual tersendiri; invoice dilunasi sesuai sisa tagihannya. Jika seluruh tunggakan telah lunas, sistem mencoba membuka isolir pelanggan dengan alur MikroTik yang sama seperti pembayaran dari halaman Invoice.
- Invoice dapat dicetak satuan atau sekaligus untuk invoice yang dipilih dari halaman pembayaran.
- Daftar tunggakan memakai DataTable AJAX server-side: pencarian, pagination, dan jumlah data dimuat tanpa refresh. Pada layar ponsel tabel berubah menjadi kartu per pelanggan agar setiap nilai tetap terbaca.
- Tampilan daftar Pembayaran diselaraskan dengan daftar Pelanggan: card header biru, filter bar pencarian penuh, header tabel ringkas, jarak baris lega, dan tombol aksi yang tidak terpotong.
- Halaman pembayaran dapat melengkapi periode invoice yang hilang setelah invoice terakhir hingga periode berjalan melalui aksi eksplisit **Lengkapi Bulan**. Aksi tidak berjalan saat halaman dibuka dan tidak membuat periode sebelum invoice pertama; aman untuk data transaksi yang telah di-reset saat go-live.
- Menambahkan filter **Bulan Tagihan** pada daftar Pembayaran. Default adalah bulan berjalan dan admin dapat memilih sampai 11 bulan sebelumnya. Filter menampilkan nama bulan saja agar ringkas, sementara tahun/periode lengkap tetap tersedia pada modul Invoice untuk riwayat. Daftar hanya memuat pelanggan dengan invoice belum lunas pada periode itu dan invoice periode yang dipilih otomatis dicentang saat halaman proses pembayaran dibuka.
- Memperbaiki endpoint DataTable Pembayaran yang sebelumnya gagal saat memuat data. Jika terjadi gangguan pemuatan berikutnya, aplikasi menampilkan notifikasi yang jelas tanpa alert bawaan browser.
- Generator invoice tetap membuat satu invoice untuk setiap periode bulanan. Pelanggan berstatus `suspended` tetap memperoleh invoice pada siklus berikutnya, sehingga tunggakan dicatat dan dibayar per bulan, bukan berhenti atau dihitung sebagai satu tagihan mundur.

## Normalisasi Scheduler Isolir untuk Go-Live

- Menambahkan konfigurasi **Auto Buka Isolir Pelanggan** pada `Sistem > Scheduler`. Task memeriksa pelanggan suspended tanpa invoice belum lunas, memulihkan profile PPP paket, memastikan secret enabled, dan memutus sesi agar pelanggan reconnect dengan layanan aktif.
- Auto buka isolir dan auto isolir menggunakan jadwal mandiri serta dapat diaktifkan/nonaktifkan terpisah. Keduanya disetel nonaktif sementara selama persiapan reset data go-live agar invoice data uji tidak memengaruhi MikroTik.
- Reset transaksi bawaan tetap hanya menghapus transaksi dan marker billing; status layanan/MikroTik sengaja tidak diubah. Sebelum reset go-live, pelanggan yang masih suspended harus dipulihkan melalui alur buka-isolir MikroTik yang berhasil, bukan hanya diubah status database-nya.

## Kebijakan Tagihan per POP

- `billing_day` setiap pelanggan menjadi tanggal jatuh tempo individual. Invoice otomatis dibuat berdasarkan tanggal tersebut, bukan serentak pada tanggal satu.
- Menambahkan pengaturan per POP pada `Pengaturan Invoice & Pajak`: **Invoice Muncul Sebelum Jatuh Tempo** (default H-3), **Masa Tenggang Isolir** (default 0 hari), serta **Jam Auto Isolir** (default 20:00 WIB).
- Dengan default baru: invoice dibuat H-3, reminder menggunakan konfigurasi notifikasi POP (default H-2 dan H-1), lalu pelanggan dengan `auto_isolir` aktif diisolir pukul 20:00 tepat pada hari jatuh tempo apabila belum melunasi tagihan.
- Auto-isolir memakai kebijakan POP dan membandingkan jatuh tempo secara inklusif, sehingga konfigurasi masa tenggang 0 benar-benar dapat memproses tagihan pada hari jatuh tempo, bukan baru hari berikutnya.
- Pada 28 Agustus 2026, `billing_day` POP 35 Wonosari dinormalisasi dari data pelanggan lama: 327 pelanggan diperbarui melalui pencocokan unik PPPoE/nama/telepon. Baris yang tidak cocok otomatis tidak diubah dan data transaksi belum di-reset.

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
