# Penetapan sebagai sumber nominal SKRD

Penetapan Retribusi menjadi satu tempat untuk mengubah tarif sebelum penerbitan. Tombol Simpan penetapan menyimpan tarif dan dasar penyesuaian, tanpa menerbitkan tagihan. Ringkasan menghitung tarif × jumlah hari, potongan yang disetujui, dan total. Draft tersimpan dalam pengaturan dokumen SKRD pada tabel pengaturan_dokumen_permohonan; tidak membutuhkan migrasi.

Editor surat mengambil nominal dari perhitungan yang sama, sementara kode rekening, uraian, tanggal, dan identitas dokumen tetap dapat diedit. Perubahan nominal lewat POST template ditolak di server. Nilai lama pada rincian template yang berbeda dari penetapan tidak lagi menjadi sumber nominal; uraian pertama dipertahankan jika rincian lama perlu dinormalisasi. Simpan penetapan tetap diperlukan sebelum membuka editor surat untuk melihat hasil terbaru.

Saat penerbitan, perhitungan disimpan sebagai tagihan dan snapshot SKRD. Nominal terbit dikunci; tahap ini tidak menyediakan koreksi nominal tagihan terbit. Revisi informasi surat tetap tersedia dengan alasan dan versi. Dokumen lama tidak ditulis ulang otomatis.

Tahap ini mempertahankan formula keringanan yang sudah ada (25% untuk persetujuan pada lokasi yang memenuhi syarat), format cetak, dan perilaku STS/STBP. Belum menambahkan aturan denda, pembebasan penuh, atau alur keputusan baru.

Validasi: assessment_sync_smoke.py menguji jalur HTTP simpan/buka ulang, guard nominal, penerbitan, role, dan kunci tagihan. assessment_sync_database.php memakai SQLite in-memory untuk penyimpanan draft, potongan, nominal template lama, penerbitan, dan snapshot; tidak mereset database development.
