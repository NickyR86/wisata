# Konteks proyek SIPTR / SIAP-Wisata

Diperbarui: 15 November 2026. Titik acuan implementasi terbaru: `e392cfc`, branch lokal `hanlie`; dokumentasi handoff SKRD: `67dec57`. Dokumen ini menjadi pintu masuk konteks proyek secara keseluruhan. Ini rangkuman keputusan dan bukti pengembangan, bukan pernyataan siap produksi atau audit ulang seluruh kode.

## 1. Tujuan dan prioritas sumber

Sistem pelayanan persetujuan kegiatan/acara pada lokasi wisata Kota Pangkalpinang: pengajuan, pemeriksaan, persuratan, retribusi, pembayaran, tanda tangan, serah terima, dan arsip. Demo harus dapat dicoba pembimbing lintas role, termasuk simpan/buka ulang dan dokumen terisi; pengembangan diarahkan ke penyimpanan database.

Stack yang disepakati Laravel Blade + Bootstrap. Pertahankan tema biru wisata, kualitas layout referensi, serta Ringkasan terpisah dari Antrean. Perubahan fitur tidak berarti redesain menyeluruh. Gunakan primary key bernama entitas, misalnya permohonan_id.

Urutan sumber: kode pada commit yang benar untuk perilaku aktual; keputusan terbaru untuk tujuan; SOP/standar/form resmi untuk proses administrasi yang perlu dikonfirmasi; foto/Figma/HTML rekan untuk referensi tampilan. Contoh dokumen tidak dengan sendirinya membuktikan aturan hukum atau tarif masih berlaku. Bila berbeda, laporkan perbedaannya, jangan diam-diam memilih salah satunya.

## 2. Versi dan kerja bersama

Repository private: Parla-hue/siap-wisata. Perubahan terbaru dikerjakan pada hanlie. Branch tema-biru adalah versi yang lebih lama dan tidak otomatis memuat integrasi database atau fitur terbaru. Kondisi GitHub tidak diperiksa ulang pada penulisan dokumen ini; e392cfc dan handoff SKRD terakhir belum dipush saat dibuat.

Agent penerima perlu menjalankan git status, git branch --show-current, dan git log; pastikan e392cfc berada dalam ancestry checkout. Markdown tidak memindahkan kode. Jangan menimpa pekerjaan rekan atau menganggap ZIP siap-wisata-produksi.zip telah digabung. Catat perubahan bermakna melalui commit; push/merge adalah tindakan terpisah sesuai instruksi pemilik proyek.

Riwayat sebelum baseline Git 4c33f0e tidak lengkap. docs/audit-rekonstruksi memuat rekonstruksi, bukan jaminan exact diff setiap revisi lama.

## 3. Workflow dan role

Alur utama: masuk → draft → review_draft → verifikasi → rilis → tagihan → bayar → ttd → serah → selesai.

| Role | Tugas |
|---|---|
| Sekretariat | Membuat permohonan, mengunggah persyaratan, melengkapi sumber surat sebelum review, serah terima |
| Kabid | Review awal, review draft, rilis; tidak menyunting draft |
| Petugas layanan bidang | Menyusun/revisi draft, cetak draft, mencatat PDF surat bertanda tangan basah pada jalur manual |
| Sekretaris | Verifikasi internal atau mengembalikan draft |
| Bendahara | Penetapan, SKRD, pencatatan/verifikasi pembayaran dan dokumen keuangan |
| Kadis | Tanda tangan pada tahap yang memenuhi syarat; TTE saat ini simulasi |
| Admin | Akun dan master sesuai implementasi menu/guard |

Penolakan tersedia pada review awal; revisi mengembalikan draft ke petugas. Menu bersama meliputi antrean/detail, arsip, kalender, log, dan ringkasan sesuai hak akses. Menu laporan/statistik dan tindakan spesifik tetap perlu mengikuti guard kode, bukan diasumsikan terbuka untuk semua role.

Paket pemohon: Surat Persetujuan, SKRD, STBP. STS adalah dokumen internal dinas. Arsip menyajikan transaksi selesai beserta dokumen/riwayat, tidak menggandakan seluruh transaksi ke database arsip terpisah. Kalender dan laporan membaca data layanan yang sama.

## 4. Arsitektur dan database

Monolith Laravel dengan pemisahan view/support/service; modularisasi domain belum lengkap dan DemoController masih besar. Jangan menyebut seluruh sistem telah menjadi modular monolith yang terpisah sempurna.

| Lokasi kode | Fungsi |
|---|---|
| routes/web.php | Route dan entry point |
| app/Http/Controllers/DemoController.php | Request, validasi dan alur tindakan |
| app/Support/Workspace.php | Pemilihan mode session/database |
| app/Services/BusinessStore.php | Mapping/persistence bisnis lintas tabel, transaksi dan versi |
| app/Support/FinanceDocument.php | Perhitungan/sumber/snapshot keuangan |
| app/Support/ApprovalLetter.php | Sumber dan struktur surat persetujuan |
| app/Support/ManualSignature.php | Kelayakan jalur tanda tangan manual dan versi |
| app/Services/AccountService.php | Akun dan autentikasi database |
| resources/views | DOM halaman melalui Blade, layout, komponen dan surat |
| public/css, public/js, public/images | Aset frontend; cek nama folder aset aktual bila mencari logo |
| database/migrations | Schema yang dipasang di mesin penerima |
| tests | Pengujian terarah; baca mode dan database target sebelum menjalankan |

Dua sakelar independen: SIAP_DATABASE_BUSINESS untuk data layanan, SIAP_DATABASE_ACCOUNTS untuk login akun. Mode lokal terakhir yang dibahas memakai business=true, accounts=false, sehingga data bisnis tersimpan di DB walaupun identitas dipilih melalui role demo. Mode harus diperiksa di mesin masing-masing tanpa membagikan .env.

Database development berisi dummy yang terakhir digunakan: siap_wisata_test_20261106_crud. Walaupun namanya test, pengguna memakainya untuk development. Jangan meresetnya sebagai database tes otomatis. siap_wisata yang kosong disiapkan sebagai baseline/QC dan telah diekspor pengguna; jangan menganggap file ekspor tersedia di repo. Pengujian otomatis baru memakai session terisolasi atau SQLite in-memory.

Migration yang ada:
1. 2026_11_05_000001_create_siap_wisata_revision_one.php — schema awal.
2. 2026_11_06_000001_support_business_persistence.php — lock_version dan pengaturan dokumen.
3. 2026_11_15_000001_add_structured_approval_letter.php — JSON data_surat dan struktur_surat nullable.

Kelompok entitas: akun/role/permission; lokasi/jenis kegiatan/tarif; permohonan/persyaratan/berkas; template/draft/surat persetujuan; tagihan/rincian/pembayaran; dokumen terbit/versi/tanda tangan; disposisi/audit/serah terima. Kamus dan diagram ada di docs/database, tetapi diagram revisi 1 tidak otomatis memuat seluruh perubahan migration lanjutan.

Database menyimpan data terstruktur dan metadata; berkas privat berada di storage. Git tidak membawa isi database, unggahan, atau sesi. Akun administrator diperlukan untuk bootstrap pengelolaan akun ketika login database dipakai, bukan syarat CRUD demo dengan role lokal.

## 5. Pembaruan penting setelah handoff 7 November

### Pemulihan sesi

Sesi Laravel tetap terbatas, tetapi akses browser dapat dipulihkan setelah idle/sleep. Pemeriksaan berjalan saat halaman aktif/online dan sebelum submit, tanpa heartbeat berkala atau replay POST bisnis otomatis. Default kredensial pemulihan 30 hari sejak akses terakhir; bukan jaminan login tak terbatas atau logout ketika tab ditutup. Draft teks sementara menggunakan sessionStorage dan pemulihan atas pilihan pengguna; file perlu dipilih ulang setelah reload. Detail dan batas tes: PEMULIHAN-SESI.md. Halaman 419 dan navigasi kembali telah diperbaiki.

### Lokasi dan rekomendasi DLH — keputusan belum final

Pengguna menyebut empat lokasi dikelola Dispar: Alun-alun, Taman Wilhelmina/Taman Sari, Pasir Padi, Jerambah Gantung. Jerambah Gantung/Kawasan Jembatan Gantung dibahas sebagai lokasi yang sama, bukan dua lokasi baru.

Katalog tambahan: Kolong Reterasi Kacang Pedang (varian nama Retensi), Taman Mandara, Sungai Rangkui, Kawasan Sungai Pangkalbalam, Kawasan Sungai Selindung, Kawasan Tampuk Pinang Pura, Hutan Raya Tua Tunu/Rimba Daerah. Perintah siap:tambah-lokasi-wisata idempotent; tidak menciptakan tarif baru.

PENTING: implementasi DLH sebelumnya membatasi kebutuhan rekomendasi pada Pasir Padi dan Jeramba Gantung. Pengguna kemudian memperoleh informasi berbeda; standar yang ditinjau memuat persyaratan DLH secara umum, belum ada kepastian pengecualian per lokasi. Penambahan katalog tidak menyelesaikan konflik aturan tersebut. Jangan menganggap rule DLH saat ini sudah disahkan. Status dikelola_dispar dan butuh_rekomendasi_DLH adalah konsep berbeda.

### Surat persetujuan

Mengikuti referensi surat Reuni IKAK Babel: kop, Tentang, Dasar, Kepada, Untuk, ketentuan, penandatangan, tembusan. Sumber permohonan mencakup nama acara, alamat, jabatan PIC, nomor/tanggal surat pemohon, jam kegiatan. Petugas dapat menyunting redaksi serta menambah/mengurutkan dasar hukum, ketentuan dan tembusan. Sumber disalin per versi; pembaruan sumber dilakukan eksplisit agar redaksi tersimpan tidak tertimpa. Kabid tetap reviewer.

Pratinjau dan cetak surat persetujuan memakai A4 210×297 mm dengan margin 12 mm dan halaman tambahan otomatis; bukan memanjangkan satu lembar. Tidak memaksa pemisahan persis pada kalimat yang sama dengan PDF contoh. Finance preview tidak diubah oleh pekerjaan A4 ini. Dasar hukum contoh belum menjadi katalog regulasi yang tervalidasi. Field tambahan masih opsional untuk data lama. Detail: SURAT-PERSETUJUAN-TERSTRUKTUR.md.

### Tanda tangan manual

Petugas mencetak draft tersimpan. Pada tahap ttd yang memenuhi syarat, Petugas/Kadis dapat mencetak untuk tanda tangan basah. Petugas mengunggah PDF final, memilih Kadis, tanggal dan alasan. Server memeriksa peran/status/versi; identitas penandatangan berbeda dari pengunggah. Setelah tercatat, permohonan masuk serah, dan arsip/pratinjau memakai PDF unggahan tersebut. Bukan verifikasi otomatis keaslian tanda tangan. Detail: TANDA-TANGAN-MANUAL.md.

### Penetapan dan SKRD

Penetapan menjadi sumber nominal; Simpan penetapan menyimpan tarif/alasan sebelum penerbitan. Editor surat mengambil nominal tersebut secara readonly dan server menolak nominal berbeda. Informasi surat tetap editable. Setelah terbit, snapshot menjaga nominal historis; koreksi nominal terbit belum tersedia melalui alur baru. Detail kode/tes/limit: HANDOFF-UPDATE-SKRD.md dan PENETAPAN-SUMBER-NOMINAL-SKRD.md.

Formula yang dipertahankan: tarif × durasi dikurangi 25% bila persetujuan dan lokasi memenuhi syarat. Tidak menambah dokumen cetak rincian potongan. Dokumen pengajuan harus dibedakan dari bukti persetujuan. Pembebasan charity, denda/kenaikan, otoritas keputusan, dan cara resmi SKRD nihil belum selesai. Jangan menganggap charity otomatis gratis atau seluruh nominal SKRD/STBP/STS selalu sama; penerimaan dan setoran berbeda tahap. Implementasi pembayaran saat ini masih membuat contoh STBP/STS bersama; pemisahan proses tersebut belum dikerjakan.

## 6. Referensi dan paket yang perlu dibagikan

Daftar ini mengidentifikasi sumber dari percakapan, bukan klaim seluruh berkas telah disalin/diverifikasi berada dalam repo. Dokumen asli eksternal perlu disertakan terpisah dengan nama yang mudah dikenali. Bila tidak tersedia, agent harus menyatakan batas referensinya.

| Sumber | Kebutuhan | Ketersediaan |
|---|---|---|
| Source checkout yang mencakup e392cfc, composer.json/lock, migration dan tests | Wajib untuk melanjutkan implementasi yang sama | Repository, tetapi commit terbaru perlu dikirim/push terlebih dahulu |
| Dokumen ini + docs/HANDOFF-DEVELOPMENT.md | Konteks keseluruhan terkini dan sejarah | Repo; handoff lama dibaca sebagai snapshot historis |
| docs/database/README.md, KAMUS-DATA.md, KEPUTUSAN-DAN-ALUR.md, schema.json, 4 diagram SVG/PUML | ERD, entitas, atribut, keputusan relasi | Repo; cocokkan dengan 3 migration |
| PRD_Aplikasi_Perizinan_Acara_Dispar_Pangkalpinang.docx | Ruang lingkup/kebutuhan produk | Lampiran eksternal yang pernah dibahas |
| Spesifikasi_Sistem_Perizinan_Acara_Dispar_Pangkalpinang.docx | Spesifikasi sistem | Lampiran eksternal yang pernah dibahas |
| SOP PERIZINAN RETRIBUSI REKREASI PARIWISATA  2026(1) (1).xlsx | Flowchart, urutan peran dan disposisi; sheet LAMPIRAN IZIN | Eksternal, perlu diberikan |
| STANDAR PERSETUJUAN PELAYANAN KEGIATAN ATAU ACARA 2.docx | Persyaratan, tarif contoh sumber, waktu dan keringanan | Eksternal, perlu diberikan; konfirmasi keberlakuan aturan |
| suraT REUNI IKAK BABEL.pdf | Format dan isi surat persetujuan | Eksternal, perlu diberikan untuk pekerjaan persuratan |
| SKRD.pdf | Referensi format SKRD terbaru | Eksternal, terakhir pada folder transfer WhatsApp pengguna |
| Template Retribusi, SKRD, STS, dan STBP.zip | Template keuangan yang pernah diberikan | Eksternal; periksa isi dan bandingkan dengan referensi terbaru |
| Foto STBP dan STS: codex-clipboard-b6bc5b0c-8c4a-454f-af6e-799df925af9c.png; codex-clipboard-9d28d84a-dcf9-41c6-a433-2faef29fe067.png | Daftar a–g/bernomor, rincian rekening, blok tanda tangan | Screenshot eksternal; tidak mengasumsikan ada PDF STS/STBP terpisah |
| Foto detail SKRD: codex-clipboard-75c11410-8dc3-41af-b6be-1b2057b1232d.png; codex-clipboard-f4dc9dee-3652-46a6-80b2-71e81a70b680.png | Detail grid, lima baris uraian dan sanksi | Pelengkap bila PDF belum cukup terbaca |
| SIPTR Dispar — Dinas Pariwisata Kota Pangkalpinang.html | Referensi interaksi/UI rekan | Eksternal, pernah berada di Downloads; bukan source Laravel saat ini |
| Figma Make Dispar (Copy), file cyZnvuli9hShrpOzXVzH7M | Referensi desain dan menu | Tautan di bawah; memerlukan akses akun penerima |
| Google Sheets Normalisasi Data, file 1epqlWZiTkCmAWJRy_h2K35PjO9f60vVR6uDbKSRjK9k | Referensi pedagogis normalisasi awal, bukan schema runtime terbaru | Opsional untuk riwayat rancangan |
| laravel-session-expired.md dan feed-media-sosial-refresh.md | Masukan agent lain tentang sesi | Opsional; implementasi acuan PEMULIHAN-SESI.md |
| siap-wisata-produksi.zip | Versi usulan rekan | Hanya diperlukan untuk membandingkan versi; tidak dianggap sudah digabung |
| Ekspor database dummy/berkas dummy | Reproduksi data yang sama bila dibutuhkan | Terpisah dari Git; gunakan data dummy, jangan kirim credential/data pribadi |

Figma: https://www.figma.com/make/cyZnvuli9hShrpOzXVzH7M/Dispar--Copy-
Referensi normalisasi: https://docs.google.com/spreadsheets/d/1epqlWZiTkCmAWJRy_h2K35PjO9f60vVR6uDbKSRjK9k/edit?gid=1077699423

Tidak perlu mengirim semua screenshot error lama untuk pekerjaan baru. Pilih screenshot yang menjelaskan bug aktif atau layout yang sedang dikerjakan. Jangan menyalin tanda tangan asli/QR sertifikasi ke dokumen demo.

## 7. Urutan baca agar hemat context window

1. Baca dokumen ini dan identifikasi branch/commit aktual.
2. Baca docs/database/README.md, KEPUTUSAN-DAN-ALUR.md dan migration untuk pekerjaan database; kamus tabel yang relevan saja.
3. Baca dokumen fitur terkait: PEMULIHAN-SESI.md, SURAT-PERSETUJUAN-TERSTRUKTUR.md, TANDA-TANGAN-MANUAL.md, HANDOFF-UPDATE-SKRD.md.
4. Buka SOP/standar asli bila keputusan menyangkut aturan, serta PDF/HTML/Figma bila menyangkut bentuk tampilan.
5. Baca controller/service/view dan tes yang berkaitan dengan tugas. Tidak perlu memasukkan seluruh source atau semua lampiran ke satu prompt.

Handoff lama dan README memuat deskripsi tahap frontend/session yang historis. Pernyataan tersebut tidak meniadakan mode database yang sudah diimplementasikan. Bila rujukan bertentangan dengan dokumen terbaru, periksa kode dan tanggal sebelum menyimpulkan.

## 8. Setup dan batas verifikasi

PHP 8.2+ dan Composer diperlukan; pengembang memakai PHP portabel 8.3, database lokal MariaDB XAMPP. composer install menggunakan lock yang ada; error ext-fileinfo diselesaikan dengan mengaktifkan ekstensi, bukan langsung composer update. Buat .env lokal sendiri, pilih database/mode, jalankan migrate tanpa reset dan siapkan master demo bila perlu (siap:siapkan-demo-db). php artisan serve --host=127.0.0.1 --port=8017 dapat dipakai tanpa Apache. Jangan mengganti APP_KEY instalasi yang sudah dipakai sembarangan.

Bukti tes spesifik tercatat pada dokumen masing-masing. SKRD terbaru lulus pengujian HTTP kernel dan SQLite in-memory, belum diuji ulang di MariaDB/browser pada tahap itu. Surat A4/manual pernah diperiksa headless; ini bukan pengujian seluruh aplikasi saat ini. Tidak ada klaim siap produksi, TTE tersertifikasi, integrasi bank/notifikasi, atau lulus semua tes legacy. Dokumen ini dibuat tanpa menjalankan ulang tes aplikasi.

## 9. Prompt untuk agent penerima

Baca docs/KONTEKS-PROYEK.md sebagai konteks keseluruhan. Periksa git status dan versi checkout terlebih dahulu, lalu baca dokumentasi fitur yang terkait dengan tugas saya. Bedakan implementasi aktif, keputusan yang belum final, dan sumber eksternal yang belum tersedia. Sebutkan bila kode Anda berbeda dari e392cfc. Pertahankan workflow role, sumber nominal pada penetapan, snapshot dokumen terbit, dan data development. Jangan menganggap seluruh dokumen eksternal sudah ada di repository atau bahwa semua aturan administrasi sudah tervalidasi. Kerjakan hanya tugas yang saya minta, catat diff dan hasil tes yang benar-benar dijalankan.
