# Tahap 2: Opsi "berkas contoh" hanya untuk lokal

Dasar: temuan T-11 pada `LAPORAN-PENGUJIAN-WHITEBOX-BLACKBOX.md`.

## Masalah
Form Permohonan Baru memiliki kotak centang "Gunakan seluruh berkas contoh". Bila `files=1` terkirim, `DemoController::store()` melewati validasi unggah wajib dan mendaftarkan permohonan dengan berkas tiruan. Di produksi ini berarti Sekretariat (atau siapa pun yang dapat mengirim permintaan itu) dapat mendaftarkan permohonan tanpa berkas persyaratan sungguhan, bahkan bila kotak centang disembunyikan, karena server tetap menerima nilai `files=1`.

## Perubahan
| Berkas | Perubahan |
|---|---|
| `app/Http/Controllers/DemoController.php` (`store`) | Opsi berkas contoh hanya dihormati bila `APP_ENV` adalah `local` atau `testing`. Di lingkungan lain unggahan wajib selalu divalidasi. |
| `resources/views/permohonan/create.blade.php` | Kotak centang dan kalimat "atau gunakan seluruh berkas contoh untuk demo" hanya tampil di `local/testing`. |

`public/js/app.js` tidak diubah: kode yang membaca kotak centang memakai optional chaining (`sampleFiles?.checked`), jadi aman bila elemennya tidak ada.

## Dampak
- Lokal/demo: tidak ada perubahan perilaku; tes yang ada tetap berjalan.
- Produksi: pendaftaran selalu mensyaratkan unggahan PDF/JPG/PNG (maks 2 MB) untuk setiap persyaratan yang berlaku.

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. Lokal: buka `/permohonan/baru`; kotak centang berkas contoh masih tampil; mendaftar dengan kotak dicentang berhasil.
2. Mode database dengan `APP_ENV=production` (setelah akun database aktif): halaman `/permohonan/baru` tidak menampilkan kotak centang.
3. Kirim POST ke `/permohonan/baru` dengan `files=1` tanpa berkas (misalnya lewat curl dengan token CSRF dan cookie sesi): harus ditolak 302 dengan pesan validasi, dan permohonan tidak tercatat.

## Membatalkan
`git revert <hash commit Tahap 2>`.
