# Keputusan rancangan database SIAP-Wisata revisi 1

Status: rancangan inti hasil audit sudah disusun untuk ditinjau sebelum migration. Belum membuat database, belum mengganti penyimpanan sesi demo, dan bukan persetujuan final pengguna. Sumber kebutuhan tercatat di ../AUDIT-KELENGKAPAN-ERD.md.

## Cara membaca paket

- KAMUS-DATA.md: tabel, atribut, tipe, nullable, keunikan, dan relasi.
- schema.json: representasi definisi yang sama untuk pemeriksaan konsistensi; bukan konfigurasi aplikasi.
- 01-intake.svg sampai 04-akses-audit.svg: ERD per kelompok, dirender dari sumber DOT. Tabel yang muncul kembali adalah tabel yang sama. Sumber PlantUML alternatif turut disertakan, tetapi belum diuji render.
- Rancangan lama tetap disimpan sebagai riwayat, tetapi implementasi berikutnya mengacu pada revisi 1 ini beserta keputusan pengguna setelah peninjauan.

## Perubahan terhadap ERD sebelumnya

Sepuluh entitas bisnis lama tetap ada: pengguna, lokasi, tarif_retribusi, permohonan, dokumen_persyaratan, draft_surat, surat_persetujuan, tagihan_retribusi, pembayaran, riwayat_disposisi. Atributnya diperjelas dan ditambah. Hubungan dokumen_persyaratan ke permohonan sekarang melalui persyaratan_permohonan agar persyaratan yang belum diunggah tetap tercatat.

Entitas baru: jenis_kegiatan, jenis_persyaratan, persyaratan_permohonan, template_surat, rincian_tagihan_retribusi, dokumen_terbit, versi_dokumen, tanda_tangan_dokumen, serah_terima, serah_terima_dokumen, audit_aktivitas. Lima tabel role/permission dari rancangan akses sebelumnya dipertahankan. Total 26 tabel dalam paket, termasuk pivot. Bukan 26 menu aplikasi.

Nomor dan berkas final yang sebelumnya tersebar di surat/tagihan/pembayaran dipindahkan ke dokumen_terbit dan versi_dokumen. Ini menghindari dua tempat yang sama-sama dapat mengubah nomor atau isi final. Rincian transaksi tetap pada tagihan dan pembayaran, bukan hanya pada JSON dokumen.

## Kardinalitas utama

| Induk | Anak | Hubungan |
|---|---|---|
| lokasi / jenis_kegiatan | permohonan | 1 : 0..N; setiap permohonan wajib memilih satu masing-masing |
| lokasi + jenis_kegiatan | tarif_retribusi | Banyak versi tarif berdasarkan masa berlaku |
| permohonan | persyaratan_permohonan | 1 : 0..N; intake membuat daftar wajib dan bersyarat |
| persyaratan_permohonan | dokumen_persyaratan | 1 : 0..N versi unggahan |
| permohonan | draft_surat | 1 : 0..N versi |
| permohonan | surat_persetujuan | 1 : 0..1 |
| draft_surat | surat_persetujuan | Setiap surat menunjuk satu versi draft; periksa kepemilikan dan persetujuannya |
| permohonan | tagihan_retribusi | 1 : 0..1, mempertahankan lingkup pelunasan sekali |
| tagihan_retribusi | rincian_tagihan_retribusi | 1 : 0..N saat draft, minimal satu sebelum diterbitkan |
| tagihan_retribusi | pembayaran | 1 : 0..1; pembebasan tidak menghasilkan pembayaran |
| surat_persetujuan / tagihan_retribusi | dokumen_terbit | Masing-masing 1 : 0..1 untuk jenis yang sesuai |
| pembayaran | dokumen_terbit | 1 : 0..2, masing-masing satu STBP dan STS |
| dokumen_terbit | versi_dokumen | 1 : 0..N, minimal satu sebelum terbit |
| versi_dokumen | tanda_tangan_dokumen | 1 : 0..N blok penandatangan |
| permohonan | serah_terima | 1 : 0..1 |
| serah_terima | versi_dokumen | M:N melalui serah_terima_dokumen; versi yang sama tidak harus disalin |
| permohonan | riwayat_disposisi | 1 : 0..N event; setiap event memiliki pelaku pengguna |

Cardinality minimum pada tahap tertentu ditegakkan layanan transaksi; FK saja tidak mewajibkan induk mempunyai anak. Diagram menampilkan cardinality fisik, sedangkan tabel ini menjelaskan pembatasan menurut jenis dan tahap.

## Transisi proses dan data yang dicatat

| Status awal | Tindakan/peran | Status hasil | Data wajib |
|---|---|---|---|
| belum ada | Sekretariat menerima | masuk | Identitas, lokasi/kegiatan, tanggal/durasi, checklist, diinput_oleh, diterima_pada, event awal |
| masuk | Kabid meminta kelengkapan | dikembalikan_untuk_dilengkapi | Catatan kekurangan dan pelaku |
| dikembalikan_untuk_dilengkapi | Sekretariat menerima tambahan | masuk | Versi berkas baru, verifikasi diperbarui, event penerimaan ulang; tanggal diterima awal tetap |
| masuk | Kabid meneruskan | draft | Seluruh persyaratan wajib diterima pemeriksa |
| masuk | Kabid menolak substantif | ditolak | Alasan; tidak menyamakan dengan berkas kurang |
| draft | Petugas menyimpan | draft | Versi konten baru bila berubah; simpan bukan pengiriman |
| draft | Petugas mengirim | review_draft | ID draft tertentu, dikirim_pada |
| review_draft | Kabid menyetujui | verifikasi | Event persetujuan dengan draft_surat_id |
| review_draft / verifikasi | Kabid/Sekretaris meminta revisi | draft | Catatan wajib, versi lama tetap; pengiriman baru melewati review Kabid kembali |
| verifikasi | Sekretaris memverifikasi | rilis | Event verifikasi untuk ID draft yang sama |
| rilis | Kabid merilis | tagihan | Surat_persetujuan menunjuk draft terverifikasi, dirilis_oleh/pada |
| tagihan | Bendahara menetapkan positif | bayar | Rincian, keputusan keringanan selesai, total, jatuh tempo, versi SKRD |
| tagihan | Bendahara mencatat pembebasan disetujui | ttd | Penetapan jumlah nol berstatus dibebaskan, dasar keputusan; tanpa pembayaran/STBP fiktif |
| bayar | Bendahara memverifikasi pelunasan | ttd | Pembayaran, nominal cocok, bukti/referensi, waktu/pelaku; STBP dibuat |
| ttd | Kadis menandatangani | serah | Versi surat final beserta tanda tangan dan hash hasil |
| serah | Sekretariat menyerahkan | selesai | Penerima, petugas, waktu, checklist, versi dokumen yang diserahkan |

Urutan normal berasal dari SOP LAMPIRAN IZIN B5:B15. Jalur pembebasan di atas adalah pemetaan teknis kebutuhan pembebasan pada sumber, bukan pemberian kewenangan keputusan kepada Bendahara. Bendahara hanya menerapkan keputusan yang sah. Surat persetujuan tidak diberi status sudah ditandatangani saat rilis.

## Keringanan dan batas kebijakan

- Kolom pemutus dan waktu keputusan sudah ditentukan. Permission `keringanan.putuskan` disediakan dalam daftar kemampuan tetapi tidak otomatis diberikan ke role mana pun sampai kewenangannya ditetapkan. Pengajuan berstatus diajukan menahan penetapan.
- Data pembebasan dan pengurangan dipisahkan. Ketentuan 25% yang telah dibahas tetap dipertahankan; jangan menyimpulkan semua lokasi atau semua kegiatan mendapat potongan. Simpan persentase hasil keputusan pada penetapan.
- Jalur bebas biaya tidak mencatat pembayaran nol sebagai lunas. Penetapan nol menyimpan bukti keputusan; penerbitan SKRD nol sebagai dokumen resmi dan kelengkapan paket bebas biaya perlu mengikuti prosedur instansi, tidak dipaksakan dari asumsi demo. Sistem dapat menyiapkan draft nol, tetapi penerbitan resmi jalur ini dinonaktifkan sampai ketentuan dokumennya disetujui.
- Sanksi nonnol belum diaktifkan. Kolom disiapkan, tetapi rumus, basis denda, kewenangan dan pembulatan khusus tidak dikarang. Selama belum ditetapkan, server mewajibkan komponen sanksi nol.
- Asumsi desain pemilihan tarif: masa berlaku berdasarkan tanggal kegiatan. Ini pilihan teknis eksplisit, bukan kutipan SOP. Perlu diterima sebelum pengisian tarif operasional; perubahan kebijakan cukup mengganti pemilih tarif dan snapshotnya, bukan struktur transaksi.
- Cicilan, pengembalian uang, penetapan pengganti nominal setelah lunas, setoran gabungan dan pergantian lokasi banyak dalam satu permohonan tidak dimasukkan ke lingkup awal. Menambahnya kelak membutuhkan rancangan transaksi tambahan.

## Kontrak snapshot dokumen

`data_snapshot` menggunakan struktur JSON dengan `schema_version` integer dan key yang tervalidasi, bukan JSON bebas dari browser:

- `identitas`: nama_pemohon, penanggung_jawab, alamat, kontak, npwr nullable.
- `kegiatan`: nama lokasi, jenis kegiatan, tanggal mulai, jumlah hari; tanggal selesai dihitung dan dapat disalin sebagai nilai historis cetak.
- `administrasi`: nomor, tanggal dokumen, masa/tahun, jatuh tempo tanggal dan teks, instansi/alamat kop, perihal/lampiran untuk persetujuan.
- `narasi`: pembuka, isi, ketentuan, penutup, uraian pembayaran/sewa dan perhatian sesuai jenis dokumen.
- `keuangan`: daftar rincian urut, kode rekening, uraian, kuantitas, satuan, tarif, subtotal, potongan, sanksi, total; dihasilkan dari transaksi. STBP/STS menampilkan alokasi penerimaan yang totalnya sama dengan pembayaran. Jika baris lebih dari satu dan ada potongan, alokasi proporsional dibulatkan dua desimal, selisih pembulatan diberikan ke baris terakhir dan disimpan dalam snapshot.
- `pembayaran`: tanggal, metode, bank, rekening dan referensi sesuai pembayaran, hanya untuk jenis terkait.
- `sumber`: ID permohonan, ID transaksi/draft, versi template, waktu pengambilan sumber, dan hash data sumber untuk mendeteksi perubahan editor.

Nilai uang dalam JSON berupa string desimal dua digit, bukan floating point. Data penandatangan historis dikelola tanda_tangan_dokumen; renderer menggunakan baris milik versi yang sama. Nomor di snapshot harus sama dengan nomor dokumen_terbit. Terbilang dibentuk dari total dan boleh disimpan sebagai hasil render; tidak menjadi input nominal kedua.

Draft preview boleh mengikuti sumber terbaru, dengan peringatan bila sumber berubah sejak editor dibuka. Simpan memerlukan validasi token sumber. Dokumen final tidak berubah ketika nama akun, lokasi atau tarif master berubah. Revisi administratif membuat versi baru beralasan. Dokumen final yang sudah diserahkan tetap dirujuk melalui serah_terima_dokumen, bukan otomatis versi terbesar terbaru.

Sumber sistem menyimpan snapshot dan file hasil finalisasi. Sumber unggah wajib file privat, MIME/ukuran/hash dan pencatat; tetap harus memiliki keterkaitan transaksi serta pemeriksaan manual. Mengunggah file tidak otomatis mengubah status pembayaran atau mengesahkan tanda tangan.

## Constraint lintas tabel dan transaksi

1. Semua FK bisnis mengacu PK bernama entitas. Gunakan RESTRICT; tidak ada cascade yang menghapus permohonan, pembayaran, dokumen, atau audit karena pengguna dihapus. Pengguna/master dinonaktifkan. Pivot akses boleh dihapus melalui layanan admin dengan audit.
2. Draft, surat, tagihan, versi dokumen dan penyerahan harus merujuk pengajuan yang sama. Implementasi awal melakukan cek ownership dalam transaksi, bukan mengandalkan tampilan pilihan. Bila migration memilih composite FK, tambahkan key pendukung yang diperlukan dan dokumentasikan; jangan menyatakan FK tunggal menjamin kesamaan pemilik.
3. Transisi status, perubahan transaksi bisnis, dan riwayat dicatat dalam satu transaksi database. Lock permohonan/tagihan untuk mencegah klik ganda membuat dua penerbitan atau pelunasan. UNIQUE tetap menjadi pengaman terakhir.
4. Perhitungan memakai decimal/integer unit uang; server menghitung ulang subtotal/potongan/total. Sesudah tagihan terbit, edit uraian dokumen tidak mengubah nominal. Penetapan draft bisa dihitung ulang sebelum dibekukan.
5. Penerbitan nomor harus atomik. Awal implementasi dapat memakai ID dokumen yang dialokasikan database untuk nomor demo; format/seri resmi baru diterapkan ketika ditetapkan, dengan allocator terkunci. Nomor demo tidak diklaim nomor resmi.
6. I/O berkas dan panggilan TTE tidak berada dalam transaksi database panjang. Simpan draft/status menunggu, lakukan operasi idempoten, kemudian finalisasi metadata dan event dalam transaksi singkat. Jangan menandai final sebelum artefak tersedia dan hash diverifikasi.
7. Berkas privat dibuka melalui route berotorisasi. Path adalah key storage server. Backup mencakup database dan file, bukan Git saja.
8. Soft delete menahan tindakan lanjutan dan menyembunyikan antrean biasa; pemulihan mempertahankan status lama dan mencatat event. Penghapusan berulang tidak menimpa riwayat karena audit append-only.
9. Indeks minimum: permohonan(status, diterima_pada), (lokasi_id, tanggal_kegiatan); tracking unique; tagihan(status, tanggal_jatuh_tempo); pembayaran(tanggal_bayar, status); riwayat(permohonan_id, terjadi_pada, riwayat_disposisi_id); serah_terima(diserahkan_pada); FK yang dipakai join.
10. Login username/NIP, hash password dan status akun diperiksa server. Assignment satu Kadis aktif ditangani layanan admin dengan lock penugasan; bukan UNIQUE(role_id) yang akan melarang banyak pengguna pada role lain. Jangan menafsirkan akun Admin sebagai izin mengubah keputusan bisnis semua pejabat.

## Pengelolaan akses

Pertahankan lima tabel akses pada ERD lama agar keputusan sebelumnya tidak berubah diam-diam. Model polymorphic dibatasi ke pengguna pada lingkup sekarang. `model_id` bukan FK SQL; pengelolaan akun harus menjaga integritasnya. Karena PK role/permission memakai role_id/permission_id, integrasi package perlu penyesuaian model/config/migration sesuai versi yang dipilih. Package belum diimplementasikan; jangan langsung menjalankan migration vendor dengan asumsi PK id.

Kemampuan awal: Sekretariat menerima/melengkapi/menyerahkan; Petugas menyusun/mengirim draft; Kabid review/revisi/rilis; Sekretaris verifikasi/revisi; Bendahara menetapkan/mencatat pembayaran/mengelola dokumen keuangan; Kadis menandatangani; Admin mengelola akun, master dan template. Detail visibilitas dokumen dan laporan mengikuti batas peran demo serta peninjauan akses saat implementasi. Penandatangan SKRD/STBP/STS mengikuti jenis dokumen; bukan selalu Kadis.

Tabel teknis autentikasi, sesi, reset password, queue, dan token provider bukan entitas bisnis pada gambar ini. Tentukan sesuai driver dan fitur Laravel saat migration. Reset password via email belum diasumsikan karena akun dapat tidak memiliki email; pengelola akun internal harus mempunyai prosedur reset yang diaudit.

## Yang tidak menjadi tabel baru

Arsip adalah permohonan selesai beserta serah terima dan versi final. Kalender adalah proyeksi tanggal/durasi/status permohonan. Statistik mengambil transaksi dan event. Durasi SLA dihitung dari diterima_pada sampai diserahkan_pada, serta event transisi untuk durasi tahap; hari libur operasional membutuhkan konfigurasi kalender kerja pada implementasi, tidak diasumsikan hanya Sabtu/Minggu. Pengajuan H-10 adalah validasi tanggal, bukan tabel tersendiri.

Notifikasi internal dapat memakai tabel infrastruktur notifikasi Laravel ketika modul itu dibangun; WhatsApp/email eksternal belum dijadikan kewajiban implementasi inti. STS tetap dokumen internal yang terkait pembayaran dalam lingkup ini; tidak ada klaim rekonsiliasi atau penyetoran nyata terpisah.

## Urutan migration dan pemeriksaan berikutnya

Urutan tabel: pengguna dan akses; lokasi/jenis_kegiatan/tarif; permohonan; jenis_persyaratan/checklist/unggahan; template/draft/surat; tagihan/rincian/pembayaran; dokumen/versi/tanda tangan; riwayat; serah terima/pivot; audit. Jalankan semua transaksi bisnis setelah tabel dan FK tersedia. Belum ada migration yang dibuat pada tahap ini.

Skenario penerimaan rancangan: (1) pengajuan lengkap normal; (2) dikembalikan lalu dilengkapi; (3) revisi draft dua kali dan review mengacu versi benar; (4) tarif master berubah tanpa mengubah tagihan lama; (5) potongan 25% disetujui; (6) pengajuan keringanan belum diputuskan tertahan; (7) pembebasan tanpa pembayaran fiktif, penerbitan resmi menunggu kebijakan; (8) pembayaran ganda ditolak; (9) revisi administratif mempertahankan dokumen yang sudah diserahkan; (10) akun dinonaktifkan tetap terbaca pada riwayat; (11) hapus/pulihkan; (12) dua petugas mengubah sumber bersamaan ditolak/diulang secara terkontrol.

Status tahap setelah paket ini: audit selesai; rancangan revisi 1 tersusun; berikutnya tinjau keputusan batas lingkup dan lanjut migration setelah pengguna meminta. Tidak menyatakan aplikasi sudah memakai database atau seluruh aturan operasional telah disahkan.
