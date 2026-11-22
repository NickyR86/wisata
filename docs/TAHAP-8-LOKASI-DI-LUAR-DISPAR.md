# Tahap 8: Lokasi di luar yang dikelola Dispar (input manual)

Dasar: Data Master hanya memuat lokasi yang dikelola Dispar; kegiatan di tempat lain tetap perlu dicatat dan disetujui. Konsep diambil dari cabang biru ("Lainnya") dan disesuaikan dengan kode hanlie.

## Asumsi (belum dikonfirmasi pemilik, mudah diubah)
- Untuk lokasi di luar Dispar **tidak ada retribusi**: tidak ada SKRD, STS, STBP, maupun bukti transfer. Dispar hanya memproses persetujuan kegiatan. Alur sama dengan bebas biaya (Tahap 5): setelah surat dirilis, status langsung menunggu tanda tangan.
- Keringanan atau bebas biaya tidak berlaku (tidak ada retribusi yang bisa diringankan).
- Rekomendasi DLH tidak diwajibkan, karena penandanya per lokasi Dispar.
- Pemeriksaan bentrok jadwal tidak berlaku (lokasi bukan milik Dispar).

## Perilaku
1. Pada formulir Permohonan Baru, dropdown lokasi memiliki pilihan terakhir **"Lainnya (di luar lokasi Dispar)"**. Memilihnya menampilkan dua isian wajib: **Nama lokasi kegiatan** dan **Alamat lokasi**.
2. Nama dan alamat tersimpan pada permohonan dan tampil di semua tempat yang menampilkan lokasi: antrean, detail, kalender, dashboard, arsip, pencarian, lembar periksa, dan Surat Persetujuan (nama beserta alamat).
3. Setelah Kabid merilis surat, status langsung `ttd`; serah terima hanya mensyaratkan Surat Persetujuan. Penetapan retribusi, template, dan unggahan dokumen keuangan ditolak server untuk permohonan ini.
4. Lembar periksa menandai Bukti TF, SKRD, STS, dan STBP "- (lokasi di luar Dispar)".
5. Data Master menyembunyikan lokasi bawaan ini; di mode database admin tidak dapat mengubah atau menghapusnya.

## Perubahan
| Berkas | Perubahan |
|---|---|
| `database/migrations/2026_11_23_000001_add_other_location_support.php` (baru) | Kolom `lokasi.lainnya`, `permohonan.lokasi_lain_nama`/`lokasi_lain_alamat`; satu baris lokasi bawaan "Lainnya" |
| `app/Support/DemoData.php` | `OTHER_LOCATION_ID`, lokasi bawaan pada data demo, `isOther()`, `noFee()`, `noFeeReason()`, `locationName()`; bentrok jadwal mengabaikan lokasi lain |
| `app/Http/Controllers/DemoController.php` | Validasi nama dan alamat; alur tanpa retribusi (`release`, `sign`, `handover`, penetapan); penolakan template/unggahan dokumen keuangan; pencarian memakai nama manual |
| `app/Services/BusinessStore.php` | Membaca dan menyimpan nama/alamat manual; penanda `other`; perlindungan lokasi bawaan; serah terima 1 dokumen |
| `app/Support/ApprovalLetter.php`, `ManualSignature.php`, `PermitRequirements.php` | Surat memakai nama dan alamat manual; tanda tangan basah dan lembar periksa mengikuti jalur tanpa retribusi |
| `app/Console/Commands/PrepareBusinessDemo.php` | Tidak menyalin lokasi bawaan sebagai lokasi biasa |
| `resources/views/**`, `public/js/app.js` | Isian manual di formulir; nama lokasi lewat satu fungsi pada 10 tampilan; teks "Lokasi di luar Dispar" pada panel dan arsip |
| `tests/demo_smoke.py` | Skenario lokasi di luar Dispar sampai selesai, penolakan tanpa nama, penolakan keringanan, dan tidak ada pemeriksaan bentrok |

## Dampak dan catatan
- Wajib `php artisan migrate`.
- Sesi demo yang sudah berjalan sebelum perubahan ini belum memiliki lokasi "Lainnya"; gunakan tombol reset demo atau sesi baru.
- Ekspor CSV tidak memuat kolom lokasi sama sekali (sudah demikian sebelumnya); belum ditambahkan di tahap ini.
- Laporan "Rekap Lokasi" menampilkan baris "Lainnya" untuk seluruh kegiatan di luar Dispar.

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. Jalankan `tests/demo_smoke.py` (mode demo).
2. Mode database setelah `php artisan migrate`: tabel `lokasi` berisi satu baris `lainnya=1`; Data Master tidak menampilkannya.
3. Daftarkan permohonan dengan "Lainnya", isi nama dan alamat; proses sampai serah terima: tidak ada tahap tagihan/pembayaran, dan serah terima hanya Surat Persetujuan.
4. Buka Surat Persetujuan: tempat kegiatan memuat nama dan alamat manual.
5. Coba daftar tanpa nama/alamat: ditolak. Coba daftar dengan keringanan di "Lainnya": ditolak.

## Membatalkan
`git revert <hash Tahap 8>`, lalu `php artisan migrate:rollback --step=1` (hanya berhasil bila belum ada permohonan yang memakai lokasi bawaan).
