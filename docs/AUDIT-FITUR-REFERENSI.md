# Audit HTML referensi dan implementasi Blade — 18 Oktober 2026

Acuan: HTML SIPTR Dispar yang diberikan pengguna, empat screenshot terbaru, serta lima foto dalam ZIP Template Retribusi, SKRD, STS, dan STBP. Foto hanya dibaca sebagai referensi; foto, identitas pejabat, rekening asli, dan tanda tangan asli tidak dipaketkan sebagai aset aplikasi.

| Area | Temuan dan perubahan |
|---|---|
| Detail | Kode ada tetapi tersembunyi oleh gaya eyebrow. Sekarang jelas di bawah nama, DPR konsisten mengikuti referensi demo; bukan nomor resmi yang ditetapkan Dispar. |
| Persyaratan | Judul sekarang Berkas Persyaratan (jumlah). Berkas contoh tetap dijelaskan sebagai metadata; unggahan nyata punya tautan buka. |
| Proses | Node hijau selesai, jingga aktif, kosong berikutnya, garis penghubung horizontal; mobile vertikal. Revisi mengikuti tahap terkini, detail kronologi ada di riwayat. |
| Retribusi | Tiga kartu SKRD/STS/STBP, status terbit, editor uraian per permohonan, pratinjau langsung, simpan, pemilihan template sistem atau berkas manual. |
| Format dokumen | SKRD memiliki kop dua kolom, rincian penetapan, keringanan, tanda terima. STS memiliki rincian setoran dan dua area tanda tangan. STBP memiliki asal pembayaran, bank/rekening, rincian penerimaan dan area Bendahara. Semuanya bertanda DEMO. |
| Unggah manual | PDF/JPG/PNG maksimal 2 MB, validasi server, penyimpanan privat lokal, akses melalui sesi/peran. Berkas yang dipilih menjadi sumber dokumen; simpan template mengaktifkan sumber sistem kembali. Tidak mengubah tahap secara otomatis. |
| Pembayaran | Unggah dan buka bukti; petugas memeriksa nominal sendiri. Simulasi kirim ulang dicatat tanpa mengirim WhatsApp/email. Checkbox bukti contoh tetap tersedia agar demo dapat berjalan tanpa berkas. |
| Permohonan baru | Bisa mengunggah empat persyaratan atau memakai paket berkas contoh. Estimasi nominal di form Sekretariat dihapus. |
| Visibilitas nominal | Ringkasan keuangan dan panel nominal detail hanya untuk Bendahara/Kadis/Admin. Sekretariat tetap dapat membuka dokumen saat diperlukan untuk serah terima. Ini penyajian sesuai tugas, bukan klaim pemisahan data rahasia. |
| Area tugas | Pencarian pemohon/kode ditambahkan pada daftar tugas. |
| Serah terima | Dokumen final dan tautan pratinjau/cetak; daftar Baru Diserahkan beserta penerima. STS tetap internal. |
| Arsip | Filter, detail dan cetak yang sudah tersedia dipertahankan; cadangan metadata JSON ditambahkan dan diberi label sesuai isi, bukan backup berkas lengkap. |
| User | Tambah, edit nama/NIP/peran, aktif/nonaktif profil demo. Bukan pengelolaan kredensial autentikasi produksi. |
| Hapus dan log | Hapus sementara dengan alasan + konfirmasi kode, tab Riwayat Hapus dan pemulihan. Sengaja tidak menyalin hard delete referensi agar riwayat tidak hilang. |
| Dashboard | Ringkasan terpisah dari Antrean sesuai keputusan pengguna. Filter, statistik antrean, kalender dan laporan dipertahankan. |

## Perbedaan yang sengaja dipertahankan

- Potongan 25% harus disetujui dan lokasi dikelola Dispar; tidak menyalin potongan 50% otomatis dari HTML.
- Validasi proses dilakukan di server; tidak menyalin perubahan status hanya di JavaScript.
- SLA sejak pengajuan, bukan dihitung ulang per tahap. Kalender tidak memasukkan permohonan ditolak.
- Tidak mengklaim bukti bayar sudah dibaca otomatis, tagihan sudah dikirim, atau tanda tangan tersertifikasi.
- SKRD bukan bukti lunas. STS internal; proses setoran nyata masih perlu backend tersendiri. Simulasi pembayaran saat ini tetap menghasilkan contoh STBP dan STS seperti alur demo sebelumnya.
- Editor adalah penyesuaian isi template untuk satu permohonan, bukan pengaturan template global atau editor bebas format Word.
- Unggah DOC/DOCX belum disediakan; PDF/JPG/PNG dapat langsung dibuka browser. Dokumen Word perlu diekspor ke PDF sebelum diunggah.
- Cadangan JSON memuat metadata, bukan lampiran biner. Pemulihan backup belum diimplementasikan.
- Tampilan laporan/master masih versi Blade; tidak diklaim identik piksel atau setara seluruh detail grafik referensi. Autentikasi nyata, akun, database, notifikasi eksternal, dan TTE tetap pekerjaan backend.

## Keputusan terbaru: informasi retribusi di antrean

Informasi retribusi ditampilkan untuk semua peran di antrean dan detail. Sebelum SKRD: Belum ditetapkan, tanpa nominal estimasi. Setelah SKRD: nominal tetap dari penetapan, disertai Menunggu pembayaran atau Lunas. STS tidak menjadi syarat munculnya nominal maupun status lunas. Ringkasan agregat keuangan tetap khusus Bendahara/Kadis/Admin. Ketentuan ini menggantikan catatan sebelumnya tentang menyembunyikan nominal pada detail Sekretariat.
