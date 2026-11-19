# Cetak dan pencatatan tanda tangan basah

- Petugas Layanan: Cetak Draft pada editor, memakai versi tersimpan dan penanda DRAFT. Simpan suntingan sebelum mencetak.
- Saat status `ttd`, pembayaran sudah tercatat, dan surat tersedia: Petugas Layanan/Kadis dapat membuka Cetak untuk Tanda Tangan Basah dari Detail Permohonan; Kadis juga dari antrean Tanda Tangan.
- Cetak tidak mengubah status. Tidak ada klaim TTE pada cetakan manual; label demo tetap ada.
- Petugas Layanan mengunggah PDF maksimal 10 MB, memilih penandatangan dari akun berperan Kadis, memasukkan tanggal dan alasan, serta mengonfirmasi kecocokan dokumen fisik.
- Server memvalidasi status, peran, versi surat, jenis file, dan tanggal. Dokumen, riwayat, serta status `serah` disimpan melalui transaksi BusinessStore. File baru dibersihkan jika transaksi gagal.
- `tanda_tangan_dokumen.metode=unggahan_manual`; `pengguna_id` adalah Kadis. Pengunggah dicatat pada `versi_dokumen.dibuat_oleh` dan riwayat disposisi.
- PDF manual menjadi versi dokumen final. Pratinjau, arsip dan serah terima membuka file tersebut, bukan meregenerasi surat tanpa tanda tangan.
- Tidak menambah migration: struktur database yang ada sudah mendukung metode unggahan_manual dan versi dokumen unggahan.

Batas: kecocokan tanda tangan basah diperiksa petugas; aplikasi tidak memverifikasi keaslian tanda tangan pada PDF secara otomatis. Penomoran halaman tetap mengikuti panjang isi A4, belum dipaksa sama dengan pembagian halaman referensi.

Pengujian: `tests/manual_signature_smoke.py` pada mode sesi terisolasi (20 permintaan HTTP kernel) dan `tests/manual_signature_database.php` pada SQLite in-memory. Meliputi pembatasan role/status, token versi lama, unggahan ulang, perpindahan antrean, identitas pengunggah/penandatangan dan kestabilan versi PDF setelah serah terima. Pratinjau/cetak juga diperiksa melalui browser headless.
