# Integrasi database — tahap akun

6 November 2026. Tahap pertama integrasi; belum migrasi seluruh CRUD.

## Yang tersedia

- Model Pengguna memakai PK pengguna_id dan role melalui model_has_roles.
- Mode SIAP_DATABASE_ACCOUNTS=true memakai NIP/username dan password hash; role yang dikirim klien tidak dipakai untuk memilih akses.
- Middleware membaca ulang status akun dan role tiap request serta membatalkan sesi setelah password berubah atau akun dinonaktifkan.
- Pengelolaan akun Admin menyimpan tambah/edit/nonaktifkan akun dalam transaksi; tidak menghapus akun historis. Administrator aktif terakhir dilindungi.
- Audit mencatat metadata perubahan tanpa password. Permission granular belum diintegrasikan; otorisasi fitur masih whitelist role controller.
- Mode demo lama tetap tersedia ketika flag false. Jangan aktifkan mode demo pada deployment publik.

## Mengaktifkan setelah peninjauan

1. Pastikan MySQL berjalan dan migration sudah diterapkan.
2. Jalankan `php artisan siap:buat-admin` dengan PHP 8.2+ dan pdo_mysql. Isi identitas serta password sendiri; password disembunyikan saat input. Perintah hanya untuk database tanpa akun.
3. Set SIAP_DATABASE_ACCOUNTS=true pada .env lalu `php artisan config:clear`.
4. Login memakai akun itu; menu Admin mengelola akun berikutnya.

Flag sengaja masih false; belum membuat kredensial permanen tanpa pemilik. Flag ini hanya mengubah akun, bukan penyimpanan bisnis. Permohonan, lokasi/tarif, draft, dokumen, pembayaran, arsip dan laporan masih berbasis demo. Jangan menganggap mode ini siap produksi.

## Verifikasi dan pekerjaan selanjutnya

`php tests/account_database_smoke.php` lulus pada MariaDB lokal: create/update akun, hash, login benar/salah, pivot role, akun nonaktif, benturan NIP/username, audit tanpa rahasia. Fixture dibatalkan melalui rollback transaksi. Blade view:cache dan registrasi perintah CLI berhasil. Belum diuji visual browser atau seluruh siklus login lewat HTTP.

Selanjutnya: master lokasi/jenis kegiatan/tarif, intake dan persyaratan, versi draft/review, keuangan, dokumen/serah terima, kemudian pembacaan arsip/kalender/laporan. Validasi lintas tabel dan otorisasi harus ikut setiap tahap; jangan mengganti penyimpanan dengan satu kolom JSON seluruh permohonan.
