# Audit kelengkapan ERD SIAP-Wisata

Tanggal: 5 November 2026. Audit rancangan dan sumber, bukan implementasi database atau pengesahan aturan resmi. Tidak mengubah workflow atau kode aplikasi.

## Sumber yang diperiksa

- `../outputs/ERD_SIAP_Wisata_PK_Entitas.puml`: struktur entitas, atribut, kardinalitas, referensi pengguna dan role.
- `../outputs/Catatan_Revisi_ERD_Pengguna_Role.md`: keputusan pelaku historis, role, serta asumsi satu tagihan dan satu pembayaran.
- `../outputs/ERD_SIAP_Wisata_Manual.html`: versi visual lama; masih memakai `id`, bukan sumber penamaan PK terbaru.
- Downloads/SOP PERIZINAN RETRIBUSI REKREASI PARIWISATA  2026(1) (1).xlsx: teks sel dan teks drawing; urutan kegiatan diperiksa pada LAMPIRAN IZIN B5:B15. Tidak menyimpulkan arah panah dari urutan sharedStrings.
- Downloads/STANDAR PERSETUJUAN PELAYANAN KEGIATAN ATAU ACARA 2.docx: persyaratan, prosedur, tarif per hari, keringanan, target pelayanan, dan pengambilan dengan tanda tangan SKRD.
- Downloads/Spesifikasi_Sistem_Perizinan_Acara_Dispar_Pangkalpinang.docx: spesifikasi pendukung, bukan otomatis keputusan final; termasuk versi draft, audit, akun, template dan notifikasi.
- `app/Http/Controllers/DemoController.php`, `app/Support/FinanceDocument.php`: data aktual yang disimpan demo, editor, penerbitan, pembayaran, tanda tangan, serah terima dan penghapusan.
- Foto format SKRD, STS dan STBP dalam percakapan sebagai acuan isi dokumen, bukan verifikasi hukum atau tarif terkini.

## Kesimpulan

Dasar ERD dapat dipertahankan, tetapi belum lengkap untuk seluruh fitur yang sudah dibahas. Folder database saat audit hanya berisi .gitignore; controller bisnis masih menyimpan data pada sesi. Diagram bukan bukti implementasi tabel. Gunakan PK nama_entitas_id pada rancangan berikutnya.

## Kekurangan konkret dan perbaikan

| Area | Bukti kekurangan | Perbaikan minimum yang direkomendasikan |
|---|---|---|
| Pengguna | ERD memiliki nama, email, password, status; demo memakai NIP. | Tambah nip unik nullable bila ada akun nonpegawai; username unik bila login alternatif dipertahankan. Unit kerja disebut spesifikasi, dapat berupa atribut dahulu. Role/pivot sudah ada, jangan diduplikasi menjadi teks role lain. Pengelola adalah tim teknologi internal sesuai penjelasan pengguna. |
| Permohonan | ERD tidak memiliki pic, email, days, recipient/completed yang ada di demo. | Tambah penanggung_jawab, email_pemohon nullable, alamat_pemohon untuk dokumen, tanggal_diterima, jumlah_hari positif. Dengan tanggal mulai + jumlah_hari, tanggal selesai dihitung; jangan menjadikan tiga nilai independen. Waktu serah terima dipisahkan di bawah. NPWR dapat menjadi data administratif penetapan nullable, bukan wajib semua pemohon. |
| Berkas belum lengkap | SOP B7 mengembalikan berkas kepada pemohon; controller tahap masuk hanya approve/reject. | Tambah status dikembalikan_untuk_dilengkapi dan kejadian penerimaan ulang, catatan serta petugas. Jangan menyamakan berkas kurang dengan ditolak. Ini koreksi status/transisi, tidak wajib tabel baru. |
| Persyaratan | Hanya jenis_dokumen teks, file_path dan boolean kelengkapan. Tidak bisa membedakan belum diunggah, diterima, diverifikasi, ditolak. | Master jenis_persyaratan/aturan persyaratan; tentukan wajib/bersyarat di aturan, bukan hanya file yang sudah ada. Dokumen menyimpan nama_asli, mime_type, ukuran_byte, lokasi penyimpanan, diunggah_oleh/pada, status_verifikasi, diverifikasi_oleh/pada, catatan. Dokumen pendukung keringanan harus dapat ditautkan ke permohonannya. |
| Riwayat proses | Ada tahap, aktor, aksi, catatan, created_at; belum status asal/tujuan dan referensi versi yang ditinjau. | Tambah status_sebelum/sesudah, draft_surat_id nullable dan dokumen_versi_id jika terkait dokumen final. Waktu masuk/keluar tahap dapat diturunkan dari event lengkap; jika disimpan eksplisit, jangan memakai satu waktu untuk beberapa siklus revisi. |
| Draft surat | Versi dan isi_surat sudah ada; editor menyimpan number/date/subject/attachment/opening/body/conditions/closing. | Simpan metadata tersebut dan isi terstruktur (kolom atau JSON yang tervalidasi), UNIQUE(permohonan_id, versi), alasan revisi dan referensi template versi. Tiap versi harus dapat dibaca kembali; bukan hanya menaikkan angka lalu menimpa isi. |
| Surat persetujuan | Belum menunjuk draft yang disahkan, waktu/pelaku rilis Kabid, serta bukti tanda tangan; approved_by wajib walau waktu approval nullable. | Tambah draft_surat_id FK, dirilis_oleh/pada, tanggal_terbit, ditandatangani_oleh/pada nullable sampai tanda tangan. Identitas penandatangan historis dan referensi/hash dokumen final perlu tersimpan. Rilis bukan tanda tangan. Kunci versi final; integrasi sertifikat tetap tahap implementasi tersendiri. |
| Tarif | Lokasi dan jenis_kegiatan sudah ada pada master, tetapi tidak terhubung ke rincian penetapan; demo tarif hanya per lokasi. | Gunakan pasangan lokasi + jenis kegiatan konsisten, satuan hari dan masa berlaku tarif. Rincian tagihan menunjuk tarif asal sekaligus menyimpan nilai tarif saat penetapan. Master jenis_kegiatan direkomendasikan untuk mencegah ejaan berbeda; bukan alasan membuat ulang lokasi. |
| Rincian SKRD | Tagihan hanya subtotal/potongan/total, tidak ada baris kode rekening, uraian, jumlah dan dasar perhitungan. | Tambah rincian_tagihan_retribusi (PK, tagihan_id, urutan, tarif_id nullable, kode_rekening, uraian, kuantitas, satuan, tarif_satuan, jumlah). UNIQUE(tagihan_id, urutan). Total harus direkonsiliasi dengan rincian. Rincian tampilan dokumen tidak boleh menjadi sumber nominal berbeda. |
| Header penetapan | Tidak ada periode, NPWR, tanggal terbit/jatuh tempo, data alamat dan penandatangan yang tersedia pada editor. | Simpan periode_retribusi, tanggal_terbit, tanggal_jatuh_tempo dan keterangan_jatuh_tempo terpisah; NPWR serta snapshot identitas/alamat/jabatan pada versi dokumen. Nomor varchar(30) perlu diperlebar sesuai nomor contoh STS/STBP yang lebih panjang. |
| Sanksi/perhitungan | Format SKRD memiliki denda dan kenaikan; tidak ada atribut perhitungan, demo masih nol. | Sediakan dasar nominal, persentase/nominal denda dan kenaikan bila fitur perhitungan diterapkan. Jangan menetapkan rumus atau denda otomatis dari foto. Total = pokok setelah potongan + komponen sanksi yang memang disetujui; perhitungan server, decimal untuk uang. |
| Keringanan/pembebasan | Atribut kategori/status/alasan/pemutus sudah ada, tetapi demo hanya jalur 25%; sumber juga menyebut pembebasan. | Tidak wajib entitas baru untuk satu keputusan per permohonan. Perjelas jenis normal/pengurangan/pembebasan, persentase yang disetujui, dokumen pendukung, pelaku/waktu dan jejak revisi keputusan. Jalur bebas biaya harus tidak dipaksa membuat pembayaran fiktif. Kewenangan pemutus mengikuti keputusan yang ada, tidak diisi otomatis oleh auditor. |
| Pembayaran | ERD tidak punya jumlah_bayar, metode, bukti, bank/rekening, verifikator/waktu; hanya tanggal dan nomor STS/STBP. | Tambah nominal, metode, referensi transaksi, bukti dokumen nullable, bank/rekening terkait, diverifikasi_oleh/pada. Pertahankan maksimum satu pembayaran hanya untuk skenario pelunasan sekali yang disepakati; cicilan tidak diasumsikan. |
| STS dan STBP | Satu baris pembayaran memuat dua nomor; format dan pelaku berbeda. | Simpan dua dokumen terpisah beserta versi, sumber sistem/unggah, tanggal, nomor dan penandatangan. Entitas penyetoran terpisah bersifat bersyarat: dibutuhkan bila pencatatan setoran mandiri/gabungan masuk lingkup. Jangan mengklaim SOP yang dibaca sudah menetapkan setoran gabungan. |
| Versi dokumen terbit | ERD tidak menampung templates, document_sources, skrd_versions dan skrd_snapshot demo. | Tambah dokumen_terbit dan versi_dokumen: jenis, pemilik transaksi dengan FK jelas, nomor, versi, data_snapshot, file_path nullable, sumber, pembuat/waktu, alasan revisi, status final. Dokumen tagihan/pembayaran menggunakan nominal transaksi sebagai sumber tunggal. Hindari pasangan id_jenis/id_objek tanpa integritas FK jika tidak diperlukan. |
| Serah terima | Tidak ada tabel/atribut, padahal demo menyimpan recipient dan completed; standar menyebut tanda tangan SKRD saat pengambilan. | Tambah serah_terima (permohonan_id unik untuk satu penyerahan, nama_penerima, diserahkan_oleh FK pengguna, diserahkan_pada, catatan, bukti_penerimaan nullable). Tautkan versi dokumen yang diserahkan bila perlu membuktikan isi paket. Penerima eksternal bukan FK pengguna internal. |
| Hapus/pulihkan | Tidak tercatat di ERD, demo sudah mempunyai deleted_at/by/reason dan event pemulihan. | Soft delete permohonan + identitas pelaku/alasan dan event pemulihan pada audit. Tidak perlu tabel log hard delete sebagai default. Riwayat jangan ikut cascade hilang ketika akun dinonaktifkan. |
| Audit admin | Riwayat_disposisi wajib terkait permohonan, tidak dapat mencatat perubahan tarif/role/akun. | Audit aktivitas terpisah bila pengelolaan akun/master masuk backend; pelaku, aksi, objek, waktu dan perubahan relevan. Jangan merekam password/token ke audit. |

## Yang tidak perlu ditambahkan otomatis

- Tabel arsip: tampilan permohonan selesai ditambah dokumen final, versi, dan serah terima sudah memenuhi kebutuhan arsip yang dibahas.
- Tabel kalender, dashboard, statistik: dapat dihasilkan dari permohonan, tahapan dan transaksi.
- Tabel pemohon/organisasi: opsional jika ada kebutuhan identitas berulang yang dikelola sebagai master. Data pemohon per pengajuan tetap sah dan menghindari perubahan identitas historis.
- Tabel keputusan keringanan: opsional jika banyak pengajuan/keputusan mandiri per permohonan. Untuk satu keputusan, atribut yang ada ditambah audit cukup.
- Master template versi dan notifikasi internal disebut spesifikasi pendukung; masukkan sebagai modul tambahan jika lingkup tersebut tetap dipakai. Tidak semua field editor membutuhkan kolom transaksi sendiri.

## Aturan yang sudah jelas dan perbedaan yang harus dinyatakan

Urutan SOP Excel LAMPIRAN IZIN B8:B15: petugas menyusun, Kabid meninjau, Sekretaris memverifikasi, Kabid merilis, pembayaran, Bendahara menerima, Kadis menandatangani, pemohon mengambil. Standar pelayanan mendukung urutan ini. Tidak perlu meminta ulang urutan tersebut.

Standar menyebut pengajuan 10 hari sebelum kegiatan dan target 3 hari kerja. Demo menerima tanggal hari ini/ke depan dan SLA tanpa daftar libur. Ini perbedaan implementasi, bukan alasan membuat tabel besar baru; gunakan tanggal diterima dan kebijakan kalender kerja yang jelas.

Standar menyebut pembebasan untuk keagamaan/sosial/amal/pendidikan serta pengurangan 25% untuk masyarakat sekitar/organisasi kemasyarakatan. Ketentuan demo yang hanya mencakup 25% belum mewakili seluruh sumber. Audit tidak mengubah keputusan cakupan lokasi sebelumnya atau memverifikasi aturan hukum terkini.

Foto SKRD memiliki penandatangan atas nama Kepala Dinas/Kabid, STBP Bendahara, STS dua blok pejabat. Jangan menyamakan seluruh tanda tangan dengan Kadis. Surat persetujuan mengikuti tahap tanda tangan Kadis yang telah disepakati.

## Urutan penyelesaian rancangan

1. Lengkapi atribut pengguna, permohonan, persyaratan, status kembali melengkapi, tanggal dan FK pelaku.
2. Tetapkan rincian penetapan dan sumber nominal; lengkapi pembayaran dan jalur pembebasan.
3. Hubungkan versi draft yang disetujui, versi dokumen terbit, rilis, tanda tangan dan serah terima.
4. Definisikan constraint: PK, FK, unique versi/nomor sesuai lingkup penomoran, null sebelum tindakan, indeks pencarian serta larangan menghapus riwayat bisnis berantai.
5. Baru buat migration dan uji alur normal, berkas kurang, revisi berulang, potongan, bebas biaya, pembayaran, tanda tangan, penyerahan, hapus/pulihkan.

Satu tagihan per permohonan dan satu pembayaran per tagihan adalah asumsi rancangan lama, bukan bukti semua kebutuhan masa depan. Tidak diubah menjadi banyak tanpa kebutuhan yang terkonfirmasi. Pengguna tidak diminta mengulang seluruh penjelasan; keputusan yang benar-benar belum ada cukup dicatat sebagai batas implementasi.
