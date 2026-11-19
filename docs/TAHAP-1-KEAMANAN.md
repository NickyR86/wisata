# Tahap 1: Fondasi Keamanan

Commit: lihat `git log` (pesan diawali "Tahap 1").
Dasar: temuan T-1, T-4 (sebagian), T-6, T-7, T-9, T-10 pada `LAPORAN-PENGUJIAN-WHITEBOX-BLACKBOX.md`.

## Perubahan

| # | Perubahan | Berkas | Temuan |
|---|---|---|---|
| 1 | Login mode demo (tanpa kata sandi) ditolak di luar `APP_ENV=local/testing`; sesi demo lama di luar lokal diarahkan ke login; isian otomatis `demo1234` hanya di lokal; halaman login menampilkan peringatan | `DemoController::enter`, `DemoSession`, `auth/login.blade.php` | T-1 |
| 2 | Header keamanan: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, HSTS (HTTPS non-lokal). CSP mode report-only, hanya pada respons HTML | `SecurityHeaders` (baru), `bootstrap/app.php`, `config/siap.php` | Checklist publikasi |
| 3 | Proxy tepercaya dari konfigurasi (`SIAP_TRUSTED_PROXIES`) | `TrustProxiesFromConfig` (baru) | T-6 |
| 4 | Rate limit login ditambah per IP (20/menit) selain per akun+IP; waktu respons seragam untuk akun yang tidak ada | `DemoController::enter`, `AccountService::authenticate` | T-9 |
| 5 | Penetralan CSV ditambah awalan tab dan carriage return | `DemoController::export` | T-10 |
| 6 | Pendaftaran: cek keringanan non-Dispar dipindah sebelum penulisan berkas; berkas unggahan dihapus bila pendaftaran gagal | `DemoController::store` | T-7 |
| 7 | Dua atribut `onchange` inline diganti `data-autosubmit` (siap untuk CSP enforce) | `kalender/index`, `permohonan/filters`, `public/js/app.js` | CSP |
| 8 | Contoh `.env` produksi dan panduan deploy | `.env.production.example`, `docs/DEPLOY-PRODUKSI.md` | Checklist publikasi |

Tidak diubah di tahap ini (menunggu keputusan atau tahap berikutnya): pembatasan role ekspor (T-4), migrasi MariaDB-only (T-2), alur keringanan (T-3), performa mode database (T-5).

## Efek yang perlu diketahui
- Mode demo hanya jalan bila `APP_ENV=local` atau `testing`. Pastikan `.env` hasil salinan `.env.example` (berisi `APP_ENV=local`) di komputer pengembang. Skrip `JALANKAN-DEMO.ps1` sudah melakukannya.
- Di server dengan `APP_ENV=production` dan `SIAP_DATABASE_ACCOUNTS=false`, aplikasi hanya menampilkan halaman login dengan peringatan. Ini disengaja.
- CSP tidak menegakkan apa pun sampai `SIAP_CSP=enforce` diaktifkan.

## Cara memverifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)

1. Lokal (mode demo):
   ```sh
   cp .env.example .env && php artisan key:generate
   php artisan serve --port=8017
   python3 tests/security_baseline_smoke.py http://127.0.0.1:8017
   python3 tests/demo_smoke.py http://127.0.0.1:8017      # tes lama harus tetap lulus
   ```
2. Simulasi produksi tanpa akun database (harus menolak login demo):
   ```sh
   APP_ENV=production php artisan serve --port=8018
   EXPECT_ENV=production python3 tests/security_baseline_smoke.py http://127.0.0.1:8018
   ```
3. Periksa di browser: halaman login, antrean (ubah urutan), kalender (ubah lokasi): pengiriman otomatis harus tetap bekerja; DevTools > Console tidak menampilkan error JavaScript.
4. Daftarkan permohonan dengan lokasi non-Dispar dan keringanan 25%: ditolak, dan tidak ada berkas baru di `storage/app/demo-uploads/`.

## Membatalkan tahap ini
`git revert <hash commit Tahap 1>` mengembalikan seluruh perubahan di atas tanpa menyentuh tahap lain.
