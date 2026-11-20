# Panduan Deploy Produksi SIAP-Wisata

Panduan ini hanya memuat langkah yang berlaku untuk kode repo ini. Bagian yang bergantung server Anda (nama domain, panel hosting) ditandai `<...>`.
Semua langkah **belum diuji di server nyata** oleh penyusunnya; jalankan di lingkungan percobaan terlebih dahulu.

## 1. Prasyarat
- PHP 8.2 atau lebih baru dengan ekstensi bawaan Laravel (mbstring, openssl, pdo_mysql, fileinfo, ctype, json, tokenizer, xml).
- MariaDB atau MySQL. Migrasi repo ini memakai `ALTER TABLE ... ADD CONSTRAINT ... CHECK` dan menurut dokumen repo diuji pada MariaDB 10.4.32. MySQL belum diverifikasi. SQLite **tidak** didukung oleh migrasi ini.
- Composer 2.
- Sertifikat HTTPS untuk domain resmi.

## 2. Berkas dan konfigurasi
1. Letakkan kode di luar folder yang dilayani web, misalnya `/var/www/siap-wisata`.
2. `composer install --no-dev --optimize-autoloader`
3. `cp .env.production.example .env`, isi semua nilai `<...>`, lalu `php artisan key:generate`.
4. Pastikan `.env` berisi `APP_ENV=production`, `APP_DEBUG=false`, `SIAP_DATABASE_ACCOUNTS=true`, `SIAP_DATABASE_BUSINESS=true`, `SESSION_SECURE_COOKIE=true`.
5. Buat database dan **pengguna database khusus aplikasi** (bukan `root`) dengan hak SELECT, INSERT, UPDATE, DELETE, serta CREATE/ALTER/INDEX/REFERENCES hanya saat migrasi.
6. `php artisan migrate --force`
7. **Data master awal (lokasi, tarif, jenis kegiatan, jenis persyaratan) belum punya jalur resmi di produksi.** Perintah `siap:siapkan-demo-db` sengaja menolak berjalan di luar `local/testing`, dan migrasi tidak mengisi data. Lokasi tambahan dapat dibuat dengan `php artisan siap:tambah-lokasi-wisata`, tetapi jenis kegiatan, persyaratan, dan tarif harus diisi lewat SQL/seeder yang disusun dari data resmi dinas (angka tarif di data contoh hanya contoh). Tanpa data ini form Permohonan Baru tidak dapat dipakai. Ini dicatat sebagai pekerjaan Tahap berikutnya.
8. `php artisan siap:buat-admin` untuk membuat administrator awal. Perintah ini tidak menampilkan kata sandi; simpan kata sandi di pengelola kata sandi.
9. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## 3. Web server
- **Document root harus `public/`.** Jangan arahkan ke root proyek.
- Blokir akses HTTP langsung ke `.env`, `.git`, `composer.*`, `storage/`, `tests/`, `docs/`, `database/`.
- Matikan daftar isi direktori. Sembunyikan versi server dan PHP (`server_tokens off`, `expose_php=Off`).
- Alihkan HTTP ke HTTPS. Aplikasi mengirim header HSTS sendiri bila permintaan terdeteksi HTTPS.
- Bila di balik reverse proxy atau load balancer, isi `SIAP_TRUSTED_PROXIES` dengan IP proxy (atau `*` bila server hanya dapat dijangkau lewat proxy), agar HTTPS terdeteksi dan cookie bertanda Secure.

## 4. Izin file
- Hanya `storage/` dan `bootstrap/cache/` yang dapat ditulis oleh pengguna web server. Kode lain cukup dibaca.
- Web server tidak berjalan sebagai root.
- Berkas unggahan disimpan di `storage/app/demo-uploads/` (di luar `public/`) dan hanya dilayani lewat controller.

## 5. Content-Security-Policy
Aplikasi mengirim CSP dalam mode `report-only` secara bawaan (`SIAP_CSP=report-only`). Langkah beralih ke penegakan:
1. Buka setiap halaman utama dengan DevTools > Console terbuka (login, antrean, detail, form permohonan, penetapan, kalender, laporan, arsip, admin, pratinjau dokumen).
2. Catat pesan "Content Security Policy" yang muncul. Perbaiki sumbernya, atau sesuaikan `SecurityHeaders::POLICY` bila memang sah (misalnya penyedia font tambahan).
3. Bila bersih di semua halaman, ubah `SIAP_CSP=enforce`, lalu `php artisan config:cache`.
4. Ulangi pemeriksaan sekali lagi dengan mode enforce. Bila ada yang rusak, kembalikan ke `report-only`.

## 6. Pencadangan dan pemeliharaan
- Cadangkan database secara terjadwal dan **uji pemulihannya**. Cadangan metadata JSON di menu Arsip bukan pengganti cadangan database.
- Cadangkan juga `storage/app/demo-uploads/` (berkas unggahan).
- Putar log (`LOG_CHANNEL=daily`) dan simpan di luar `public/`.
- Jalankan `composer audit` secara berkala dan perbarui paket yang rentan.
