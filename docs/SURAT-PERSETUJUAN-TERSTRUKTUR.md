# Editor surat persetujuan terstruktur

Referensi bentuk: surat Reuni IKAK Babel yang diberikan pengguna. Layout: kop, judul/nomor, Tentang, Dasar a–h, penerima, Untuk, ketentuan bernomor, penandatangan, dan tembusan. Tidak menyalin tanda tangan, QR, atau klaim sertifikasi dokumen contoh.

## Perilaku
- Sekretariat mengisi alamat, nama acara, jabatan PIC, nomor/tanggal surat pemohon, dan jam kegiatan pada formulir permohonan. Nama acara berbeda dari kategori kegiatan.
- Data tambahan tersimpan di `permohonan.data_surat`. Kolom nullable menjaga kompatibilitas data lama.
- Sekretariat dapat melengkapi data lama melalui Detail Permohonan sebelum review (status masuk/draft), dengan alasan koreksi tercatat pada riwayat.
- Petugas menyunting Tentang, Untuk, rujukan pemohon, daftar dasar hukum, ketentuan dan tembusan. Daftar bisa ditambah, dihapus, dan diurutkan; penomoran otomatis.
- Variabel `{kegiatan}`, `{lokasi}`, `{tanggal}`, `{durasi}`, `{jam_mulai}`, `{jam_selesai}`, `{pemohon}`, `{nomor_permohonan}`, `{tanggal_permohonan}` diisi dari sumber server. Klien tidak dapat mengganti snapshot sumber langsung.
- `draft_surat.struktur_surat` menyimpan redaksi, daftar, dan salinan sumber per versi. Opsi Ambil data permohonan terbaru memperbarui sumber saat disimpan; tidak menimpa redaksi.
- Kabid meninjau atau mengembalikan draft, bukan menyuntingnya. Koreksi sumber ditolak setelah masuk review sampai draft dikembalikan.
- Narasi lama tetap tersimpan dalam bagian tambahan. Data sumber lama yang belum tersedia diberi label Belum diisi, bukan dibuatkan identitas fiktif secara otomatis.

## Batas tahap ini
- Daftar dasar hukum awal ditranskripsi dari contoh pengguna, bukan katalog peraturan yang sudah diverifikasi dinas. Pemilihan acuan otomatis per jenis kegiatan belum diterapkan.
- Kop dan penandatangan memakai template demo; tanda tangan elektronik asli belum diintegrasikan.
- Field sumber tambahan masih opsional untuk kompatibilitas demo lama; kelengkapan perlu diperiksa sebelum penerbitan resmi.
- Tidak mengubah aturan DLH, tarif, dan keringanan.

## Instalasi dan verifikasi
Jalankan `php artisan migrate` setelah mengambil perubahan. Migrasi hanya menambahkan dua kolom nullable, tanpa reset database.

Pengujian: `tests/approval_reference_smoke.py` (mode sesi terisolasi), `tests/approval_reference_database.php` (SQLite in-memory), dan `tests/structured_letter_smoke.py`. Pemeriksaan headless mencakup substitusi variabel, tambah/urut/hapus butir, tampilan dan cetak.

## Pratinjau A4
Pratinjau surat di editor dan halaman cetak ditampilkan dalam lembar 210 × 297 mm, margin 12 mm. Pemisahan isi menggunakan fragmentasi kolom browser; penomoran daftar tetap berlanjut dan blok tanda tangan dijaga utuh. Perubahan isi editor menghitung ulang halaman. Layar kecil hanya mengubah skala tampilan, bukan ukuran kertas. Cetak menggunakan lembar yang sama, tanpa menambahkan lembar kosong terakhir. Browser sebaiknya memakai ukuran A4, skala 100%, dan header/footer bawaan dimatikan.

Verifikasi headless: ukuran tiap lembar, dua halaman surat contoh, pertambahan halaman setelah penambahan 35 butir panjang, skala layar 390 px, dan hasil PDF A4 tanpa halaman kosong. Surat keuangan tidak diubah.
