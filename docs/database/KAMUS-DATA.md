# Kamus data SIAP-Wisata revisi 1

Rancangan 5 November 2026, hasil tindak lanjut audit. Ini spesifikasi implementasi inti, belum migration dan belum database berjalan. Baca KEPUTUSAN-DAN-ALUR.md untuk constraint lintas tabel dan batas kebijakan.

Tipe bersifat logis untuk migration Laravel; bigint PK adalah ID positif auto increment. Semua kolom wajib kecuali ditandai NULL. PK gabungan tidak auto increment. Semua tabel non-pivot memiliki created_at dan updated_at datetime, kecuali riwayat_disposisi dan audit_aktivitas yang append-only memakai terjadi_pada. FK menggunakan RESTRICT pada penghapusan data bisnis dan pengguna. Master dinonaktifkan; permohonan soft delete. Uang decimal, bukan float. Waktu disimpan UTC dan ditampilkan Asia/Jakarta; date bisnis mengikuti tanggal lokal.

Aturan >=, rentang, nilai status dan pasangan nullable diterapkan sebagai constraint database bila didukung serta validasi server; aturan lintas tabel memerlukan transaksi. JSON hanya dipakai untuk format/snapshot/audit, bukan pengganti relasi transaksi.


## pengguna

Akun internal; dikelola tim teknologi, bukan akun pemohon.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| pengguna_id | bigint | PK | Identitas akun |
| nama | varchar(150) | NOT NULL | Nama pengguna |
| nip | varchar(20) | NULL UNIQUE | NIP sebagai teks |
| username | varchar(100) | NOT NULL UNIQUE | Alternatif login dan akun tanpa NIP |
| email | varchar(150) | NULL UNIQUE | Kontak akun |
| password | varchar(255) | NOT NULL | Hash password, tidak pernah plaintext |
| unit_kerja | varchar(150) | NULL | Unit organisasi |
| status_akun | varchar(20) | NOT NULL | aktif atau nonaktif |

Normalisasi username/NIP sebelum validasi. Username tidak boleh sama dengan NIP akun lain; cek lintas kolom dalam layanan pengelolaan akun. Akun historis dinonaktifkan, bukan dihapus. Role dari pivot, bukan kolom paralel.

## lokasi

Master lokasi kegiatan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| lokasi_id | bigint | PK | Identitas lokasi |
| nama_lokasi | varchar(150) | NOT NULL | Nama tempat |
| status_aktif | boolean | NOT NULL | Boleh dipilih untuk permohonan baru |
| dikelola_dispar | boolean | NOT NULL | Atribut sumber keputusan kelayakan yang sudah dibahas |



## jenis_kegiatan

Nama kegiatan konsisten antara permohonan dan tarif.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| jenis_kegiatan_id | bigint | PK | Identitas jenis kegiatan |
| nama | varchar(150) | NOT NULL UNIQUE | Nama kategori kegiatan |
| aktif | boolean | NOT NULL | Ketersediaan pilihan |



## tarif_retribusi

Tarif berdasarkan lokasi, jenis kegiatan dan masa berlaku.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| tarif_retribusi_id | bigint | PK | Identitas versi tarif |
| lokasi_id | bigint | FK lokasi | Lokasi |
| jenis_kegiatan_id | bigint | FK jenis_kegiatan | Jenis kegiatan |
| tarif | decimal(18,2) | NOT NULL >=0 | Tarif satuan |
| satuan | varchar(20) | NOT NULL | hari untuk lingkup sekarang |
| berlaku_mulai | date | NOT NULL | Awal berlaku |
| berlaku_sampai | date | NULL | Akhir berlaku inklusif |
| ditetapkan_oleh | bigint | FK pengguna | Admin penginput master |

UNIQUE(lokasi_id, jenis_kegiatan_id, berlaku_mulai). Akhir >= awal. Rentang untuk pasangan lokasi/kegiatan tidak boleh tumpang tindih: validasi transaksional dengan kunci pasangan. Tarif yang sudah dirujuk tidak ditimpa; buat versi baru.

## permohonan

Satu pengajuan; identitas pemohon disimpan per pengajuan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| permohonan_id | bigint | PK | Identitas permohonan |
| nomor_tracking | varchar(50) | NOT NULL UNIQUE | Nomor pencarian, bukan PK |
| nama_pemohon | varchar(150) | NOT NULL | Nama organisasi atau individu |
| penanggung_jawab | varchar(150) | NOT NULL | Orang yang mengurus kegiatan |
| kontak_pemohon | varchar(30) | NOT NULL | Telepon/WhatsApp berupa teks |
| email_pemohon | varchar(150) | NULL | Email pemohon |
| alamat_pemohon | text | NOT NULL | Alamat untuk dokumen |
| lokasi_id | bigint | FK lokasi | Lokasi kegiatan |
| jenis_kegiatan_id | bigint | FK jenis_kegiatan | Kategori kegiatan |
| tanggal_kegiatan | date | NOT NULL | Hari pertama |
| jumlah_hari | integer | NOT NULL >0 | Durasi kegiatan |
| diterima_pada | datetime | NOT NULL | Awal pencatatan layanan |
| status | varchar(40) | NOT NULL | Status proses dari daftar transisi |
| diinput_oleh | bigint | FK pengguna | Petugas pencatat |
| jenis_keringanan | varchar(20) | NOT NULL | normal, pengurangan, pembebasan |
| status_keringanan | varchar(20) | NOT NULL | tidak_diajukan, diajukan, disetujui, ditolak |
| kategori_keringanan | varchar(100) | NULL | Kategori dasar pengajuan |
| alasan_keringanan | text | NULL | Alasan pemohon |
| persentase_disetujui | decimal(5,2) | NOT NULL DEFAULT 0 | 0..100; 25 untuk pengurangan yang disetujui, 100 pembebasan |
| catatan_keputusan_keringanan | text | NULL | Dasar keputusan |
| keringanan_diputuskan_oleh | bigint | NULL FK pengguna | Akun pengambil keputusan |
| keringanan_diputuskan_pada | datetime | NULL | Waktu keputusan |
| deleted_at | datetime | NULL | Hapus sementara |
| deleted_by | bigint | NULL FK pengguna | Penghapus |
| alasan_hapus | text | NULL | Alasan penghapusan |

Tanggal selesai dihitung tanggal_kegiatan + jumlah_hari - 1. Tidak menyimpan total harga perkiraan di permohonan. Keputusan keringanan menyimpan pelaku/waktu berpasangan; keputusan sebelumnya tetap di audit. Hapus/pulihkan tidak mengubah status proses.

## jenis_persyaratan

Katalog dokumen dan kondisi kewajibannya.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| jenis_persyaratan_id | bigint | PK | Identitas persyaratan |
| kode | varchar(50) | NOT NULL UNIQUE | Kode stabil |
| nama | varchar(150) | NOT NULL | Nama dokumen |
| kondisi_wajib | varchar(40) | NOT NULL | selalu, jika_impresariat, jika_keringanan, opsional |
| aktif | boolean | NOT NULL | Aktif untuk pengajuan baru |

Kondisi dievaluasi server dengan konteks pemohon; bukan kode yang dieksekusi dari database. Perubahan katalog tidak mengubah persyaratan pengajuan lama.

## persyaratan_permohonan

Daftar persyaratan yang berlaku, termasuk yang belum diunggah.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| persyaratan_permohonan_id | bigint | PK | Identitas checklist |
| permohonan_id | bigint | FK permohonan | Pengajuan |
| jenis_persyaratan_id | bigint | FK jenis_persyaratan | Sumber aturan |
| nama_snapshot | varchar(150) | NOT NULL | Nama saat aturan diterapkan |
| wajib | boolean | NOT NULL | Hasil evaluasi kewajiban saat intake |
| alasan_penerapan | text | NULL | Contoh: jasa impresariat tidak digunakan |

UNIQUE(permohonan_id, jenis_persyaratan_id). Checklist terisi sebelum unggah. Kelengkapan dihitung dari dokumen terbaru yang diterima untuk semua item wajib.

## dokumen_persyaratan

Versi unggahan tiap item persyaratan; penggantian tidak menimpa file lama.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| dokumen_persyaratan_id | bigint | PK | Identitas versi berkas |
| persyaratan_permohonan_id | bigint | FK persyaratan_permohonan | Item checklist |
| versi | integer | NOT NULL >0 | Urutan unggahan |
| nama_asli | varchar(255) | NOT NULL | Nama untuk tampilan |
| disk | varchar(50) | NOT NULL | Penyimpanan privat |
| file_path | varchar(500) | NOT NULL | Key berkas, bukan path komputer pengguna |
| mime_type | varchar(100) | NOT NULL | Tipe hasil pemeriksaan server |
| ukuran_byte | bigint | NOT NULL >=0 | Ukuran file |
| sha256 | char(64) | NOT NULL | Sidik isi berkas |
| diunggah_oleh | bigint | FK pengguna | Pengunggah |
| diunggah_pada | datetime | NOT NULL | Waktu unggah |
| status_verifikasi | varchar(20) | NOT NULL | menunggu, diterima, ditolak |
| diverifikasi_oleh | bigint | NULL FK pengguna | Pemeriksa |
| diverifikasi_pada | datetime | NULL | Waktu pemeriksaan |
| catatan_verifikasi | text | NULL | Alasan koreksi |

UNIQUE(persyaratan_permohonan_id, versi). Yang terbaru adalah versi terbesar; tidak menggandakan flag aktif. Versi lama immutable setelah diganti. Penolakan berkas wajib memiliki catatan.

## template_surat

Versi format sistem untuk surat dan dokumen keuangan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| template_surat_id | bigint | PK | Identitas versi template |
| jenis | varchar(30) | NOT NULL | persetujuan, skrd, stbp, sts |
| versi | integer | NOT NULL >0 | Versi template |
| isi_template | json | NOT NULL | Struktur field dan teks; bukan kode PHP bebas |
| dibuat_oleh | bigint | FK pengguna | Admin pengelola |
| dipublikasikan_pada | datetime | NULL | NULL berarti belum boleh dipakai |

UNIQUE(jenis, versi). Versi terpublikasi immutable; pemilihan default menggunakan versi terpublikasi terakhir. Revisi membuat baris baru.

## draft_surat

Isi surat per versi; petugas menyusun, Kabid/Sekretaris meninjau.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| draft_surat_id | bigint | PK | Identitas versi draft |
| permohonan_id | bigint | FK permohonan | Pengajuan |
| template_surat_id | bigint | FK template_surat | Template persetujuan yang digunakan |
| versi | integer | NOT NULL >0 | Versi per permohonan |
| nomor_draft | varchar(150) | NULL | Nomor usulan sebelum nomor terbit |
| tanggal_surat | date | NOT NULL | Tanggal draft |
| perihal | varchar(200) | NOT NULL | Perihal |
| lampiran | varchar(200) | NOT NULL | Keterangan lampiran |
| pembuka | text | NOT NULL | Narasi pembuka |
| isi_surat | text | NOT NULL | Isi utama |
| ketentuan | text | NOT NULL | Syarat pelaksanaan |
| penutup | text | NOT NULL | Narasi penutup |
| status | varchar(30) | NOT NULL | disusun, diajukan, perlu_revisi, disetujui |
| alasan_revisi | text | NULL | Alasan versi pengganti |
| disusun_oleh | bigint | FK pengguna | Penulis |
| dikirim_pada | datetime | NULL | Waktu kirim review |

UNIQUE(permohonan_id, versi). Simpan perubahan membuat versi konten baru bila isi berubah; kirim/review mengacu ID versi. Kabid tidak mengedit isi. Versi yang sudah dikirim tidak ditimpa.

## surat_persetujuan

Hubungan surat yang dirilis dengan versi draft yang telah lolos review.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| surat_persetujuan_id | bigint | PK | Identitas surat |
| permohonan_id | bigint | FK permohonan UNIQUE | Satu surat logis per permohonan |
| draft_surat_id | bigint | FK draft_surat | Versi yang dirilis |
| dirilis_oleh | bigint | FK pengguna | Kabid yang merilis |
| dirilis_pada | datetime | NOT NULL | Waktu rilis |

Draft harus milik permohonan yang sama dan mempunyai persetujuan Kabid + verifikasi Sekretaris untuk ID versi itu. Nomor/tanggal/file final berada pada dokumen_terbit/versi_dokumen; tidak disalin ke dua sumber mutable.

## tagihan_retribusi

Satu penetapan per permohonan; rincian menjadi dasar hitung.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| tagihan_retribusi_id | bigint | PK | Identitas tagihan |
| permohonan_id | bigint | FK permohonan UNIQUE | Satu penetapan |
| periode_retribusi | date | NOT NULL | Tanggal pertama bulan masa retribusi |
| npwr | varchar(100) | NULL | Nomor wajib retribusi jika tersedia |
| tanggal_jatuh_tempo | date | NULL | Wajib ketika tagihan positif diterbitkan |
| keterangan_jatuh_tempo | varchar(200) | NULL | Teks penjelasan yang dicetak |
| subtotal | decimal(18,2) | NOT NULL >=0 | Jumlah rincian |
| persentase_potongan | decimal(5,2) | NOT NULL | 0..100 snapshot keputusan |
| nominal_potongan | decimal(18,2) | NOT NULL >=0 | Potongan hasil perhitungan |
| persentase_denda | decimal(5,2) | NOT NULL DEFAULT 0 | Disiapkan, perhitungan nonnol belum diaktifkan |
| nominal_denda | decimal(18,2) | NOT NULL DEFAULT 0 | Nilai sanksi |
| nominal_kenaikan | decimal(18,2) | NOT NULL DEFAULT 0 | Nilai kenaikan |
| dasar_sanksi | text | NULL | Wajib bila sanksi nonnol kelak diaktifkan |
| jumlah_tagihan | decimal(18,2) | NOT NULL >=0 | Total yang harus dibayar |
| status | varchar(25) | NOT NULL | draft, terbit, lunas, dibebaskan, dibatalkan |
| diterbitkan_oleh | bigint | NULL FK pengguna | Bendahara |
| diterbitkan_pada | datetime | NULL | NULL selama draft |
| catatan_penetapan | text | NULL | Alasan tarif khusus, bila ada |

Total = subtotal - potongan + denda + kenaikan. Potongan <= subtotal. Terbit membekukan nominal; koreksi nominal sesudah terbit/pembayaran bukan sekadar edit template dan di luar lingkup awal. Status lunas berasal dari pembayaran terverifikasi; dibebaskan tidak membuat pembayaran Rp0 fiktif.

## rincian_tagihan_retribusi

Komponen perhitungan dan baris uraian SKRD.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| rincian_tagihan_retribusi_id | bigint | PK | Identitas baris |
| tagihan_retribusi_id | bigint | FK tagihan_retribusi | Induk |
| urutan | integer | NOT NULL >0 | Urutan cetak |
| tarif_retribusi_id | bigint | NULL FK tarif_retribusi | Sumber master; NULL hanya tarif khusus beralasan |
| kode_rekening | varchar(100) | NOT NULL | Kode akun sebagai teks |
| uraian | text | NOT NULL | Uraian komponen |
| kuantitas | decimal(12,2) | NOT NULL >0 | Jumlah hari/unit ditagihkan |
| satuan | varchar(20) | NOT NULL | hari dalam lingkup saat ini |
| tarif_satuan | decimal(18,2) | NOT NULL >=0 | Snapshot tarif |
| jumlah | decimal(18,2) | NOT NULL >=0 | ROUND(kuantitas * tarif_satuan, 2) |
| alasan_tarif_khusus | text | NULL | Wajib jika menyimpang dari master |

UNIQUE(tagihan_retribusi_id, urutan). Master harus cocok dengan lokasi/jenis kegiatan sumber. Layanan penetapan menentukan tarif berlaku pada tanggal kegiatan; kebijakan ini pilihan rancangan yang harus disepakati sebelum data tarif operasional diisi.

## pembayaran

Pelunasan sekali per tagihan; tidak mengasumsikan cicilan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| pembayaran_id | bigint | PK | Identitas pembayaran |
| tagihan_retribusi_id | bigint | FK tagihan_retribusi UNIQUE | Tagihan yang dibayar |
| jumlah_bayar | decimal(18,2) | NOT NULL >0 | Harus sama dengan total tagihan pada pelunasan |
| tanggal_bayar | date | NOT NULL | Tanggal uang diterima |
| metode | varchar(20) | NOT NULL | tunai atau transfer |
| bank | varchar(100) | NULL | Bank penerima |
| nomor_rekening | varchar(100) | NULL | Rekening penerima berupa teks |
| referensi_transaksi | varchar(150) | NULL | Referensi pembayaran |
| bukti_disk | varchar(50) | NULL | Penyimpanan bukti |
| bukti_path | varchar(500) | NULL | Key bukti pembayaran |
| bukti_nama | varchar(255) | NULL | Nama file |
| bukti_mime | varchar(100) | NULL | Tipe berkas |
| bukti_ukuran_byte | bigint | NULL | Ukuran >=0 |
| bukti_sha256 | char(64) | NULL | Hash bukti |
| status | varchar(20) | NOT NULL | menunggu, terverifikasi, ditolak |
| diinput_oleh | bigint | FK pengguna | Pencatat |
| diverifikasi_oleh | bigint | NULL FK pengguna | Bendahara pemeriksa |
| diverifikasi_pada | datetime | NULL | Waktu konfirmasi |
| catatan | text | NULL | Catatan pemeriksaan |

Satu rekaman pembayaran operasional dapat dikoreksi sebelum verifikasi dengan audit; transaksi terverifikasi dikunci. Pembatalan/refund dan percobaan cicilan tidak termasuk lingkup awal. Transfer membutuhkan referensi atau bukti sesuai prosedur.

## dokumen_terbit

Identitas dokumen logis; pemilik transaksi menggunakan FK eksplisit.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| dokumen_terbit_id | bigint | PK | Identitas dokumen |
| jenis | varchar(30) | NOT NULL | persetujuan, skrd, stbp, sts |
| surat_persetujuan_id | bigint | NULL FK surat_persetujuan UNIQUE | Pemilik surat persetujuan |
| tagihan_retribusi_id | bigint | NULL FK tagihan_retribusi UNIQUE | Pemilik SKRD |
| pembayaran_id | bigint | NULL FK pembayaran | Pemilik STBP/STS |
| nomor_dokumen | varchar(150) | NULL | Wajib saat diterbitkan |
| tahun_nomor | integer | NULL | Tahun seri penomoran |
| status | varchar(20) | NOT NULL | draft, terbit, dibatalkan |

CHECK tepat satu FK pemilik terisi dan sesuai jenis. UNIQUE(pembayaran_id, jenis) untuk STBP/STS; UNIQUE(jenis, tahun_nomor, nomor_dokumen). Penomoran dilakukan atomik, tidak memakai MAX+1 tanpa lock. STS disimpan sebagai dokumen internal; tidak mengklaim transaksi setoran terpisah sudah dicatat.

## versi_dokumen

Snapshot lengkap setiap revisi dokumen; sumber sistem atau unggahan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| versi_dokumen_id | bigint | PK | Identitas versi |
| dokumen_terbit_id | bigint | FK dokumen_terbit | Dokumen logis |
| template_surat_id | bigint | NULL FK template_surat | Versi template jika sumber sistem |
| versi | integer | NOT NULL >0 | Urutan versi |
| tanggal_dokumen | date | NOT NULL | Tanggal tercetak |
| sumber | varchar(20) | NOT NULL | sistem atau unggah |
| data_snapshot | json | NOT NULL | Data cetak sesuai kontrak di dokumen rancangan |
| disk | varchar(50) | NULL | Penyimpanan privat |
| file_path | varchar(500) | NULL | File final/unggahan |
| nama_asli | varchar(255) | NULL | Nama unggahan |
| mime_type | varchar(100) | NULL | Tipe file |
| ukuran_byte | bigint | NULL | Ukuran >=0 |
| sha256 | char(64) | NULL | Hash file; wajib saat final |
| status | varchar(20) | NOT NULL | draft, final |
| dibuat_oleh | bigint | FK pengguna | Pembuat versi |
| alasan_revisi | text | NULL | Wajib untuk versi >1 |
| final_pada | datetime | NULL | Penguncian versi |

UNIQUE(dokumen_terbit_id, versi). Konten versi yang dipakai review/tanda tangan/serah terima immutable. Sumber unggah wajib metadata file; sumber sistem wajib template. Versi final wajib file dan hash. Nominal snapshot dihasilkan dari transaksi, tidak diedit terpisah.

## tanda_tangan_dokumen

Setiap blok penandatangan per versi, termasuk dua pejabat STS.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| tanda_tangan_dokumen_id | bigint | PK | Identitas tanda tangan |
| versi_dokumen_id | bigint | FK versi_dokumen | Versi yang ditandatangani |
| urutan | integer | NOT NULL >0 | Blok tanda tangan |
| pengguna_id | bigint | NULL FK pengguna | Pejabat internal bila diketahui |
| nama_snapshot | varchar(150) | NOT NULL | Nama saat tanda tangan |
| nip_snapshot | varchar(20) | NULL | NIP historis |
| jabatan_snapshot | varchar(200) | NOT NULL | Jabatan/atas nama |
| pangkat_snapshot | varchar(100) | NULL | Pangkat saat penerbitan |
| metode | varchar(25) | NOT NULL | simulasi, unggahan_manual, elektronik |
| status | varchar(20) | NOT NULL | menunggu, berhasil, gagal |
| ditandatangani_pada | datetime | NULL | Waktu keberhasilan |
| referensi_penyedia | varchar(200) | NULL | ID transaksi provider TTE, bukan secret |
| hash_dokumen_ditandatangani | char(64) | NULL | Hash hasil dokumen |

UNIQUE(versi_dokumen_id, urutan). Surat persetujuan elektronik mensyaratkan pengguna Kadis; metode simulasi tidak dianggap TTE sah. Unggahan manual menyimpan provenance, tidak otomatis terverifikasi. Beberapa tanda tangan dapat memiliki hash hasil antara; hash versi final menunjuk artefak setelah semua tanda tangan.

## riwayat_disposisi

Event proses berurutan, termasuk revisi berulang.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| riwayat_disposisi_id | bigint | PK | Identitas kejadian |
| permohonan_id | bigint | FK permohonan | Pemilik alur |
| aktor_id | bigint | FK pengguna | Pelaku nyata |
| role_snapshot | varchar(100) | NOT NULL | Peran saat tindakan |
| status_sebelum | varchar(40) | NULL | NULL pada penerimaan pertama |
| status_sesudah | varchar(40) | NOT NULL | Tahap setelah tindakan |
| aksi | varchar(50) | NOT NULL | Terima, kirim, setujui, revisi, rilis, bayar, tanda_tangan, serah, hapus, pulihkan |
| draft_surat_id | bigint | NULL FK draft_surat | Versi yang ditinjau |
| versi_dokumen_id | bigint | NULL FK versi_dokumen | Dokumen terkait jika ada |
| catatan | text | NULL | Alasan keputusan |
| terjadi_pada | datetime | NOT NULL | Waktu event |

Append-only. FK versi harus milik permohonan yang sama: validasi dalam transaksi. Indeks(permohonan_id, terjadi_pada, riwayat_disposisi_id). Waktu tahap dihitung antarevent transisi, bukan event simpan biasa. Revisi/penolakan wajib catatan.

## serah_terima

Satu penyelesaian penyerahan fisik per permohonan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| serah_terima_id | bigint | PK | Identitas penyerahan |
| permohonan_id | bigint | FK permohonan UNIQUE | Permohonan selesai |
| nama_penerima | varchar(150) | NOT NULL | Pemohon atau perwakilan, bukan akun internal |
| hubungan_penerima | varchar(100) | NULL | Keterangan perwakilan |
| diserahkan_oleh | bigint | FK pengguna | Petugas loket |
| diserahkan_pada | datetime | NOT NULL | Waktu selesai layanan |
| kelengkapan_diperiksa | boolean | NOT NULL TRUE | Konfirmasi pemeriksaan fisik |
| bukti_disk | varchar(50) | NULL | Penyimpanan tanda terima |
| bukti_path | varchar(500) | NULL | Scan tanda terima/SKRD ditandatangani penerima |
| bukti_sha256 | char(64) | NULL | Hash bukti |
| catatan | text | NULL | Keterangan |

Tanggal selesai layanan berasal dari diserahkan_pada; tidak diduplikasi sebagai tanggal yang dapat diedit sendiri. Bukti dapat diunggah setelah penyerahan dengan audit; konfirmasi penerimaan tetap wajib.

## serah_terima_dokumen

Versi tepat yang diserahkan, agar arsip tidak bergeser setelah revisi.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| serah_terima_id | bigint | PK FK serah_terima | Penyerahan |
| versi_dokumen_id | bigint | PK FK versi_dokumen | Dokumen final yang diserahkan |

PK gabungan. Seluruh versi harus milik permohonan yang sama, final dan jenis bukan STS. Satu versi per jenis dalam satu penyerahan (validasi transaksi lintas tabel).

## audit_aktivitas

Audit perubahan akun, tarif, berkas, keputusan dan data penting.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| audit_aktivitas_id | bigint | PK | Identitas event |
| aktor_id | bigint | FK pengguna | Pelaku |
| aksi | varchar(50) | NOT NULL | Tindakan |
| jenis_objek | varchar(100) | NOT NULL | Nama objek dari whitelist |
| objek_id | bigint | NOT NULL REF | Identitas historis, bukan FK polimorfik SQL |
| sebelum | json | NULL | Nilai relevan sebelum perubahan |
| sesudah | json | NULL | Nilai relevan sesudah perubahan |
| alasan | text | NULL | Alasan |
| terjadi_pada | datetime | NOT NULL | Waktu audit |

Append-only, indeks(jenis_objek, objek_id, terjadi_pada). Whitelist atribut: password, token, isi berkas KTP tidak boleh direkam. Tidak menggantikan FK tabel bisnis; objek historis tetap terbaca melalui snapshot.

## roles

Master role; pola akses Spatie dari rancangan sebelumnya.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| role_id | bigint | PK | Identitas role |
| name | varchar(100) | NOT NULL | Nama peran |
| guard_name | varchar(50) | NOT NULL | web |

UNIQUE(name, guard_name). Role operasional aktif pengguna dipilih dari penugasan yang sah; tidak bebas dipilih seperti demo.

## permissions

Master kemampuan.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| permission_id | bigint | PK | Identitas permission |
| name | varchar(150) | NOT NULL | Kode kemampuan |
| guard_name | varchar(50) | NOT NULL | web |

UNIQUE(name, guard_name).

## model_has_roles

Penugasan role kepada model pengguna.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| role_id | bigint | PK FK roles | Role |
| model_id | bigint | PK REF pengguna | ID akun |
| model_type | varchar(150) | PK | Alias model pengguna |

PK gabungan tiga kolom; model_id adalah REF polimorfik, bukan FK SQL. Lingkup ini hanya mengizinkan model pengguna. Tanpa teams. Indeks(model_id, model_type).

## role_has_permissions

Kemampuan yang dimiliki role.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| role_id | bigint | PK FK roles | Role |
| permission_id | bigint | PK FK permissions | Permission |

PK gabungan. Kemampuan pemutus keringanan belum diberikan otomatis kepada role apa pun.

## model_has_permissions

Dukungan permission langsung pola package; tidak dipakai secara default.

| Atribut | Tipe | Aturan | Makna |
|---|---|---|---|
| permission_id | bigint | PK FK permissions | Permission |
| model_id | bigint | PK REF pengguna | ID akun |
| model_type | varchar(150) | PK | Alias model pengguna |

PK gabungan tiga kolom; tanpa teams. Indeks(model_id, model_type). Kosongkan pada seed awal jika seluruh permission melalui role.
