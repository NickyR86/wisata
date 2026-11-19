# Revisi demo 18 Oktober 2026

- Area cetak diberi class print-area sesuai aturan CSS referensi; ukuran A4 dan overflow cetak diperbaiki. Dialog cetak native belum dapat diperiksa penuh melalui browser otomatis.
- Serah terima: panel daftar lebih rapat; dokumen mendahului konfirmasi. Pratinjau mengingat asal halaman dan tetap menyediakan Detail Permohonan.
- Dua contoh untuk setiap tahap utama (24 permohonan), narasi surat lengkap, metadata dokumen fiktif, kalender bulan berjalan dengan status tentatif/disetujui.
- Sesi lama diperkaya tanpa menghapus pekerjaan: kasus baru dan metadata yang belum ada ditambahkan satu kali. Jadwal/data lama tidak ditimpa.
- Kadis: surat sebelum tanda tangan, konfirmasi simulasi, nama pejabat fiktif, waktu, referensi simulasi, hasil pada surat final. Tidak menggunakan sertifikat atau tanda tangan orang nyata.
- Bendahara: masa, tanggal, NPWR, alamat, uraian, bank/rekening. Tarif per hari dan alasan dapat diubah saat penetapan; total dihitung server dengan potongan yang disetujui. Nilai setelah penerbitan tetap dikunci.
- Arsip: penerima/tanggal, akses langsung dokumen; periode berdasarkan tanggal selesai. Riwayat menampilkan waktu, peran, tindakan dan catatan.
- Laporan: kinerja 3 hari kerja serta tagihan, penerimaan dan belum dibayar per lokasi.
- Proses STBP dan STS tetap gabungan pada simulasi pembayaran. Belum merupakan backend produksi.

Validasi: 88 pemeriksaan alur dan 49 pemeriksaan dokumen/unggah lulus. 18 pemeriksaan revisi khusus juga lulus (tests/revision_smoke.py).
