# Tahap 5: Bebas biaya dan keringanan 25%

Dasar: temuan T-3 (keringanan buntu), B-4/B-5/B-6 dari cabang biru, dan penjelasan pemilik: keringanan bergantung dokumen yang diserahkan di awal, kasusnya jarang, sedangkan **bebas biaya lebih sering** dan tidak memerlukan SKRD, STS, maupun bukti transfer.

## Asumsi yang dipakai (belum dikonfirmasi pemilik, mudah diubah)
- Pengambil keputusan keringanan adalah pemilik tahap peninjauan awal (`masuk`), yaitu Kabid. Peran ini mengikuti `DemoData::OWNER['masuk']`.
- Pada bebas biaya, **STBP juga tidak diterbitkan** (tidak ada penerimaan uang). Pemilik hanya menyebut SKRD, STS, dan bukti transfer.
- Dasar bebas biaya/keringanan dibuktikan lewat satu dokumen pendukung dan alasan tertulis. Daftar kategori resmi belum ada, jadi alasan ditulis bebas.

## Alur baru
1. **Pendaftaran (Sekretariat):** pilihan "Keringanan retribusi": Tidak ada, Keringanan 25%, atau Bebas biaya (hanya lokasi dikelola Dispar). Bila memilih salah satu, alasan (min. 5 karakter) dan **dokumen pendukung** (PDF/JPG/PNG, maks. 2 MB) wajib.
2. **Peninjauan awal (Kabid, status `masuk`):** panel menampilkan jenis permohonan, alasan, dan dokumen pendukung di daftar berkas. Kabid memilih Setujui atau Tolak keringanan. Penolakan keringanan wajib catatan (min. 5 karakter) dan permohonan tetap berjalan dengan tarif normal. Menolak seluruh permohonan tetap lewat tombol "Tolak permohonan".
3. **Keputusan tersimpan:** status, persentase (25 atau 100), catatan, pejabat pemutus, dan waktunya (kolom yang sudah ada di tabel `permohonan`).
4. **Jalur 25% disetujui:** alur tagihan seperti biasa; SKRD memakai potongan 25%. **Jalur ditolak:** tarif normal.
5. **Jalur bebas biaya disetujui:** setelah Kabid merilis surat, status langsung `ttd` (tahap tagihan dan pembayaran dilewati). Tidak ada SKRD, STS, STBP, maupun bukti transfer. Setelah Kadis menandatangani, serah terima hanya mensyaratkan Surat Persetujuan.

Tindakan penetapan/SKRD pada permohonan bebas biaya ditolak server (403). Status keringanan lama `diajukan` yang tertinggal di data lama tetap memblokir penerbitan SKRD.

## Perubahan
| Berkas | Perubahan |
|---|---|
| `app/Support/DemoData.php` | `RELIEF_REQUIREMENT`, `isFree()`, `reliefName()` |
| `app/Http/Controllers/DemoController.php` | Form: `discount` = `25`/`free`, alasan wajib, dokumen pendukung wajib; `approve` di `masuk` memutuskan keringanan; `release` ke `ttd` bila bebas biaya; `sign` dan `handover` mengikuti jalur gratis; penetapan ditolak untuk bebas biaya |
| `app/Services/BusinessStore.php` | Membaca/menyimpan `jenis_keringanan`, keputusan, catatan, pejabat, waktu; dokumen pendukung sebagai persyaratan `REQ-5`; serah terima bebas biaya hanya 1 dokumen |
| `app/Support/ManualSignature.php`, `FinanceDocument.php` | Tanda tangan basah sah tanpa pembayaran bila bebas biaya; persentase 100 untuk pembebasan |
| `database/migrations/2026_11_21_000001_add_relief_requirement_catalog.php` (baru) | Katalog `REQ-5` "Dokumen pendukung keringanan" |
| `resources/views/permohonan/create.blade.php`, `public/js/app.js` | Pilihan keringanan dan baris unggah dokumen pendukung |
| `resources/views/permohonan/action.blade.php`, `show.blade.php`, `work.blade.php`, `arsip/index.blade.php` | Panel keputusan, status dan teks "bebas biaya", penanda "tidak berlaku" untuk SKRD/STS/STBP |
| `tests/demo_smoke.py` | Tes lama "keringanan pending memblokir SKRD" diganti tiga skenario: 25% disetujui, bebas biaya sampai selesai, keringanan ditolak |

Tidak ada perubahan skema kolom: tabel `permohonan` sudah memiliki `jenis_keringanan` (`normal`/`pengurangan`/`pembebasan`), `status_keringanan`, `persentase_disetujui`, `catatan_keputusan_keringanan`, `keringanan_diputuskan_oleh/pada`.

## Dampak
- Wajib `php artisan migrate` (menambah katalog REQ-5).
- Alur 25% yang sebelumnya buntu kini dapat diselesaikan.
- Tiga temuan sebelumnya tertutup: keringanan buntu (T-3), jalur nihil tidak bisa diserahterimakan (B-4/B-5), dan retribusi nihil lewat tarif 0 (B-6) tidak berlaku lagi karena tidak ada jalur "tarif 0".

## Belum mencakup
- Daftar kategori/dasar hukum bebas biaya, bila dinas memiliki daftar resmi.
- Pengaturan siapa pemutus selain Kabid.
- Laporan khusus jumlah permohonan bebas biaya dan keringanan.

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. Jalankan `tests/demo_smoke.py` (mode demo): tiga skenario baru harus lulus; skenario lain tidak boleh regresi.
2. Manual (mode database setelah `migrate`): daftarkan permohonan "Bebas biaya" dengan dokumen pendukung; Kabid menyetujui; proses sampai surat dirilis; status menjadi "Menunggu tanda tangan" tanpa melewati tagihan; Kadis menandatangani; Sekretariat menyerahkan; permohonan selesai tanpa SKRD, STS, STBP.
3. Pastikan di tabel `permohonan` kolom `status_keringanan='disetujui'`, `persentase_disetujui=100`, `keringanan_diputuskan_oleh` dan `_pada` terisi; di tabel `tagihan_retribusi` dan `pembayaran` tidak ada baris untuk permohonan itu.
4. Daftarkan permohonan keringanan tanpa dokumen pendukung: ditolak validasi.

## Membatalkan
`git revert <hash Tahap 5>`, lalu `php artisan migrate:rollback --step=1`.
