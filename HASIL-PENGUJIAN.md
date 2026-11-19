# Hasil pengujian — 17 Oktober 2026

## Berhasil

- Laravel 12.70.0 terpasang dengan PHP 8.3.35 portabel; Bootstrap 5.3.8 disimpan lokal.
- Seluruh template Blade berhasil dikompilasi dengan `php artisan view:cache`.
- `php artisan route:list --except-vendor` berhasil menampilkan rute aplikasi.
- 88 pemeriksaan request melalui HTTP Kernel Laravel berhasil. Middleware, CSRF, sesi, routing, controller, validasi, redirect, dan render Blade tetap dijalankan. Transport pengujian menggunakan CLI tanpa koneksi TCP.
- Mencakup tujuh role; penolakan akses role yang salah; pendaftaran; catatan revisi wajib; penyusunan dan revisi draft; verifikasi; rilis; SKRD; pembayaran; tanda tangan simulasi; serah terima; arsip; empat preview dokumen; ekspor CSV; tarif snapshot; potongan 25%; serta penahanan tagihan ketika keputusan keringanan belum tersedia.
- Email opsional boleh tidak dikirim: data baru diberi nilai default agar halaman detail tidak error.

## Batas verifikasi

Pemeriksaan browser localhost berhasil: Ringkasan pada desktop dan mobile, Antrean desktop, editor draft Petugas, serta menu mobile Lainnya. Pengujian Kernel mencakup halaman awal ketujuh peran, Ringkasan setiap peran, filter SLA, dan Antrean tanpa banner besar. Belum dilakukan perbandingan pixel demi pixel seluruh halaman dengan Figma atau pengujian lintas seluruh browser.

Lingkungan sandbox juga tidak mendukung pemeriksaan writability/atomic rename cache Composer secara normal. Dependensi berhasil diunduh; manifest lokal untuk pengujian dihasilkan terpisah tanpa mengubah vendor. Cache lokal tersebut tidak disertakan dalam ZIP source. Pada komputer biasa, `composer install` menjalankan package discovery standar Laravel.

## Mengulang pengujian

Dengan server aktif dan Python tersedia:

```sh
python tests/demo_smoke.py http://127.0.0.1:8017
```

Tanpa server TCP, PowerShell dengan PHP 8.2+:

```powershell
$env:DEMO_TEST_PHP = (Get-Command php).Source
python tests/demo_smoke.py
```

Pengujian membuat sesi demonstrasi tersendiri dan mereset data sesi tersebut di akhir. `tests/kernel_request.php` hanya dapat dijalankan melalui CLI dan bukan endpoint publik.

Sebelum presentasi, buka `JALANKAN-DEMO.cmd` dan ikuti skenario dalam README untuk memeriksa tampilan pada browser komputer yang digunakan.

## Pembaruan 18 Oktober 2026

- 88 pemeriksaan alur utama berhasil setelah pembaruan template, unggah, dan fitur pendukung.
- 49 pemeriksaan tambahan berhasil: simpan template ketiga dokumen, escaping teks, unggah/baca berkas privat, penolakan jenis berkas dan role, pendaftaran dengan berkas, hapus/pulihkan, edit profil, serta cadangan metadata.
- 7 pemeriksaan terakhir berhasil untuk visibilitas retribusi Sekretariat: belum SKRD tanpa nominal, nominal setelah SKRD, dan status lunas.
- Browser: editor SKRD/live preview/simpan berhasil; unggah bukti PNG lokal berhasil. Detail node horizontal dan mobile vertikal diperiksa. Screenshot tersimpan di docs/screenshots.
- Setelah diskusi terakhir, localhost tidak menerima koneksi sehingga kolom retribusi terbaru diverifikasi lewat HTTP Kernel, belum diperiksa ulang secara visual. Jalankan JALANKAN-DEMO.cmd untuk membuka server.
- Template mengikuti struktur foto sebagai demonstrasi, tidak disertifikasi identik cetak dokumen resmi.
