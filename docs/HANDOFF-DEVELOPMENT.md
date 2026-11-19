# Konteks development SIAP-Wisata untuk agent berikutnya

Snapshot: **7 November 2026**, kode acuan **`1be5441`**, branch lokal **`hanlie`**. Dokumen ini merangkum keputusan pengguna, perjalanan pengembangan, dan status implementasi; bukan pernyataan aplikasi siap produksi.

> **Arsip konteks 7 November. Untuk status proyek terbaru dan daftar lampiran, mulai dari [KONTEKS-PROYEK.md](KONTEKS-PROYEK.md).**

## 1. Baca ini terlebih dahulu

- Aplikasi adalah SIAP-Wisata / SIPTR Dispar untuk pelayanan persetujuan kegiatan Dinas Pariwisata Kota Pangkalpinang.
- Stack yang diwajibkan pengguna: **Laravel Blade + Bootstrap**. PHP minimal 8.2; lingkungan pengembangan memakai PHP portabel 8.3 dan MariaDB 10.4.32 XAMPP.
- **Migration dan integrasi penyimpanan bisnis sudah dibuat.** Jangan kembali menyebut aplikasi hanya frontend/session tanpa memeriksa mode konfigurasi.
- Dua mode database terpisah: `SIAP_DATABASE_BUSINESS` untuk data layanan dan `SIAP_DATABASE_ACCOUNTS` untuk autentikasi/akun. CRUD layanan tidak harus menunggu login akun database ketika demonstrasi lokal menggunakan pemilih role.
- Konfigurasi lokal terakhir: business database aktif, accounts database belum aktif. Ini konfigurasi mesin pengembang, **tidak ikut Git** dan harus diperiksa pada mesin penerima.
- Branch tema biru tidak membawa migration dan CRUD tersebut. Memiliki dokumen ini saja tidak berarti memiliki kode implementasinya.
- Setiap revisi bermakna dicatat dalam commit lokal; push/merge memerlukan otorisasi tersendiri.

## 2. Status branch dan publikasi

Hasil pemeriksaan lokal saat dokumen dibuat, tanpa fetch jaringan:

| Branch | Commit | Makna |
|---|---|---|
| `hanlie` | `1be5441` | Tema, rancangan, migration, akun opsional, dan CRUD bisnis; ahead 9 terhadap tracking `origin/hanlie` |
| `tema-biru` | `83e8180` | Tema biru beserta perbaikan logo dan dokumentasi ERD sebelumnya; tracking `origin/tema-biru` pada commit yang sama |
| `main` lokal | `afb795d` | Behind 1 terhadap tracking `origin/main`; bukan tempat implementasi terbaru di mesin ini |

Tracking remote adalah informasi lokal terakhir, bukan pemeriksaan langsung kondisi GitHub saat ini. Repository: `Parla-hue/siap-wisata` (private). Pengguna sebelumnya hanya mengizinkan publikasi branch tema biru. Jangan menganggap perubahan database sudah dipush atau boleh digabung ke main.

Commit warna `83e8180` sendiri mengubah dua file CSS. Isi branch `tema-biru` juga mencakup commit logo dan dokumentasi sebelumnya; tidak mencakup migration, autentikasi database, atau CRUD baru.

## 3. Aturan kerja dan preferensi pengguna

1. Periksa status Git sebelum edit; jangan menimpa pekerjaan rekan. Pisahkan perubahan per tujuan, periksa diff, lalu commit lokal dengan alasan dan hasil pemeriksaan.
2. Commit merupakan checkpoint, **bukan konfirmasi bahwa pengguna menerima hasil akhir**. Revisi dapat tetap berlangsung.
3. Jangan push, merge, force-push, atau mengubah branch pengguna tanpa dasar otorisasi. Jangan memasukkan `.env`, credential, database, session, unggahan, cache, atau vendor ke Git.
4. Utamakan bug, kemudian perilaku fitur, baru penyempurnaan tampilan. Hindari refactor besar tanpa kebutuhan.
5. Pengguna sensitif terhadap penggunaan kuota: lakukan penelusuran terarah, laporkan batas pengujian, jangan mengklaim persentase kuota yang tidak terukur.
6. Pertahankan nama primary key `nama_entitas_id`; jangan menggantinya menjadi `id` generik tanpa pembahasan.
7. Pertahankan kualitas UI, ikon, istilah, dan struktur referensi. Ringkasan terpisah dari Antrean telah disetujui; jangan mengubah seluruh layout ketika diminta melengkapi fitur.
8. Prototype harus bisa didemonstrasikan secara fungsional, bukan sekadar tombol atau dokumen kosong. Tanda tangan dan dokumen demo harus jelas sebagai simulasi.

## 4. Perjalanan development

Bagian awal di bawah merupakan rekonstruksi konteks percakapan, bukan diff historis yang lengkap:

- Dimulai dari dummy normalisasi UNF–3NF, hubungan permohonan dengan entitas terkait, ERD, dan penjelasan masalah yang diselesaikan tiap pemisahan tabel.
- Dibahas identitas pelaku seperti `diinput_oleh`, `diputuskan_oleh`, role pengguna, riwayat, dan relasi pengguna. Role tidak menggantikan identitas orang yang melakukan tindakan.
- Diagram diperbaiki agar atribut tetap di dalam entitas, garis terbaca, dan PK memakai nama entitas. Ada diagram HTML serta PlantUML; rancangan terbaru dipecah menjadi empat diagram SVG.
- Referensi UI berasal dari Figma Make dan HTML/CSS/JS rekan. Implementasi dipilih Laravel Blade + Bootstrap agar dapat langsung diintegrasikan ke backend.
- Demo diperluas: login NIP/username, menu per role, Ringkasan, Antrean, detail dan progres permohonan, penyusunan/review surat, retribusi, tanda tangan Kadis, serah terima, arsip, kalender, log, dan laporan.
- Dokumen SKRD, STS, dan STBP disesuaikan dengan contoh nyata: editor field, rincian, pratinjau, cetak, unggah manual, dan sinkronisasi sumber. Tombol kembali perlu mempertahankan konteks halaman asal.
- Git diperkenalkan setelah sebagian revisi berlangsung. Baseline pertama `4c33f0e`; **riwayat sebelum baseline tidak dapat dianggap lengkap atau dipulihkan hanya dari kode terbaru**. Rekonstruksi bukan bukti exact diff jika versi pembanding tidak tersedia.
- Perubahan rekan pada branch `hanlie` diambil; logo diperbaiki; dilakukan audit ERD, migration lokal, integrasi akun opsional, lalu penyimpanan alur bisnis ke MariaDB.
- ZIP `siap-wisata-produksi.zip` dari rekan pernah dibahas. Klaim agent rekan tentang tes/produksi bukan hasil verifikasi branch ini; jangan menganggap ZIP tersebut telah digabung.

### Checkpoint Git terbaru yang terverifikasi

| Commit | Perubahan |
|---|---|
| `2558f97` | Mengarahkan logo login ke aset publik yang tersedia |
| `401644c` | Menata logo login agar tidak menutupi teks SIPTR |
| `3c7b0b8` | Audit kelengkapan ERD terhadap SOP dan demo |
| `da1feb1` | Rancangan database revisi 1, kamus data, dan diagram |
| `83e8180` | Tema utama biru laut |
| `511b0c0` | Migration ERD revisi 1 dan pemeriksaan schema |
| `fe753a5` | Akun database dan autentikasi opsional |
| `afb5c2a` | Perbaikan directive Blade penutup menu role penyebab error |
| `1be5441` | Persistensi alur permohonan hingga serah terima di MariaDB |

Gunakan `git show <hash>` untuk perubahan kode sebenarnya, bukan menebak dari ringkasan ini.

## 5. Workflow dan pembagian peran

Alur status implementasi:

`masuk → draft → review_draft → verifikasi → rilis → tagihan → bayar → ttd → serah → selesai`

| Role | Tanggung jawab utama |
|---|---|
| Sekretariat | Input permohonan/persyaratan, proses serah terima, akses arsip dan riwayat |
| Kabid | Review awal, review draft, dan rilis surat; **bukan penyusun/editor draft** |
| Petugas layanan bidang | Menyusun dan merevisi draft surat |
| Sekretaris | Verifikasi internal draft |
| Bendahara | Penetapan retribusi, dokumen keuangan, dan verifikasi pembayaran |
| Kadis | Tahap tanda tangan dokumen; implementasi saat ini simulasi |
| Admin | Pengelolaan akun dan master sesuai menu/otorisasi implementasi |

Review awal dapat menolak permohonan. Review draft/verifikasi dapat mengembalikan ke penyusunan. Revisi harus tetap dapat ditelusuri melalui versi/riwayat.

Aturan penting:

- Data identitas pemohon, kegiatan, lokasi, tanggal, dan durasi menjadi sumber dokumen; tidak seharusnya diketik ulang tanpa alasan.
- Nominal yang belum ditetapkan tidak boleh disajikan seolah tagihan final. Antrean boleh menampilkan informasi retribusi dengan status yang tepat.
- Perilaku keringanan 25% yang telah diterapkan dipertahankan; keputusan keringanan yang belum selesai menghalangi penetapan tagihan. Jangan mengarang kewenangan keputusan atau kebijakan sanksi.
- Setelah penerbitan, nilai/sumber dokumen memakai snapshot agar perubahan master tidak diam-diam mengubah dokumen lama.
- Paket serah terima pemohon: Surat Persetujuan, SKRD, STBP. **STS merupakan dokumen internal dinas**.
- Arsip, kalender, ringkasan, dan laporan menggunakan transaksi yang sama; tidak membutuhkan duplikasi seluruh data ke tabel terpisah.
- Dokumen demo bukan surat resmi; tanda tangan simulasi bukan TTE tersertifikasi.

## 6. Peta kode dan arsitektur aktual

Arsitektur diarahkan ke monolith yang memisahkan tanggung jawab. Saat ini masih terdapat controller besar; jangan menyebut pemisahan modular monolith sudah lengkap.

| Lokasi | Fungsi |
|---|---|
| `routes/web.php` | Route halaman dan tindakan |
| `app/Http/Controllers/DemoController.php` | Penanganan request, validasi/alur role, penghubung view dan penyimpanan |
| `app/Support/Workspace.php` | Memilih sumber session/demo atau database |
| `app/Services/BusinessStore.php` | Penyimpanan dan pembacaan transaksi bisnis lintas tabel |
| `app/Support/FinanceDocument.php` | Sumber, pengaturan, perhitungan/snapshot dokumen keuangan |
| `app/Support/DemoData.php` | Definisi role/status dan fixture demo lama |
| `app/Services/AccountService.php` | Akun, role, hashing password, dan autentikasi database |
| `app/Models/Pengguna.php`, `Role.php` | Model akun dan role |
| `app/Http/Middleware/DemoSession.php` | Validasi sesi/mode akses |
| `app/Console/Commands/` | Pembuatan admin dan persiapan master demo database |
| `resources/views/` | HTML halaman berbentuk template Blade, layout, komponen dan dokumen |
| `public/` | Aset yang dilayani web: CSS, JS, gambar/logo |
| `database/migrations/` | Struktur database yang dapat dijalankan di mesin lain |
| `tests/` | Skrip pemeriksaan schema, akun, alur bisnis, HTTP, dan regresi demo |
| `docs/database/` | Kamus data, keputusan alur, schema dan diagram |

View penting: `auth/login`, `dashboard/index`, `permohonan/*`, `persuratan/draft/editor`, `dokumen/{editor,preview,letter,financial,skrd}`, `arsip/index`, `riwayat/index`, `kalender/index`, `laporan/index`, dan `layouts/app` (semua `.blade.php`).

## 7. Database dan persistensi

### Migration

1. `2026_11_05_000001_create_siap_wisata_revision_one.php`: 26 tabel rancangan awal.
2. `2026_11_06_000001_support_business_persistence.php`: menambah `permohonan.lock_version` dan tabel `pengaturan_dokumen_permohonan` untuk pengaturan dokumen sebelum penerbitan.

Total setelah kedua migration: **27 tabel domain/akses + tabel `migrations` Laravel**. Diagram revisi 1 belum menggambarkan tambahan migration kedua secara lengkap.

Kelompok tabel:

- Akun/akses: `pengguna`, `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `model_has_permissions`.
- Master/intake: `lokasi`, `jenis_kegiatan`, `tarif_retribusi`, `permohonan`, `jenis_persyaratan`, `persyaratan_permohonan`, `dokumen_persyaratan`.
- Surat: `template_surat`, `draft_surat`, `surat_persetujuan`.
- Keuangan: `tagihan_retribusi`, `rincian_tagihan_retribusi`, `pembayaran`, `pengaturan_dokumen_permohonan`.
- Dokumen/audit: `dokumen_terbit`, `versi_dokumen`, `tanda_tangan_dokumen`, `riwayat_disposisi`, `serah_terima`, `serah_terima_dokumen`, `audit_aktivitas`.

`BusinessStore` menyimpan permohonan, persyaratan/metadata berkas, versi draft, surat, tagihan/rincian, pembayaran, versi dokumen, simulasi tanda tangan, serah terima, serta audit. Data utama dinormalisasi; JSON dipakai untuk pengaturan/snapshot tertentu, bukan satu blob seluruh database.

Penyimpanan memakai transaksi, penguncian baris, dan versi untuk mendeteksi perubahan basi. Penghapusan permohonan menggunakan soft delete dan pemulihan. Reset demo diblokir pada mode database. File unggahan/artifact berada pada penyimpanan lokal privat, bukan otomatis ikut repository.

### Demo role vs akun database

`siap:siapkan-demo-db` menyiapkan master contoh dan tujuh identitas pelaku demo **nonaktif**, dengan password acak. Mereka mendukung relasi pelaku/audit saat pemilih role demo digunakan di lingkungan lokal/testing; bukan akun login produksi.

Perintah ini tidak mengimpor seluruh fixture permohonan session lama dan tidak mereset permohonan yang ada. Karena itu database baru bisa memiliki master tetapi antrean masih kosong hingga ada input permohonan.

Mode akun database menyediakan login username/NIP, password hash, status aktif, pembatasan percobaan login, pengelolaan role, perlindungan admin aktif terakhir, dan audit tanpa password. Tabel permission tersedia, tetapi otorisasi granular permission belum sepenuhnya menggantikan pemeriksaan role di aplikasi.

## 8. Menjalankan di mesin rekan

Pilih branch yang sesuai terlebih dahulu. Instruksi database berikut untuk kode `hanlie` pada checkpoint ini, **bukan branch tema-biru saja**.

1. Sediakan PHP 8.2+ dan Composer. Pastikan CLI memakai PHP yang benar (`php -v`, `php --ini`), dengan `pdo_mysql` dan `fileinfo` serta ekstensi yang diminta Composer. PHP bawaan XAMPP lama 8.0 tidak cukup.
2. Jalankan `composer install` dari root proyek. Pertahankan `composer.lock`; error ekstensi hilang bukan alasan langsung menjalankan `composer update` atau mengabaikan platform requirement.
3. Buat `.env` lokal berdasarkan contoh proyek, atur koneksi database sendiri dan `APP_ENV=local`. Jangan menyalin credential pengembang ke dokumen atau Git.
4. Jalankan service MySQL/MariaDB XAMPP dan buat database kosong yang dituju konfigurasi. Apache tidak wajib bila memakai Artisan server.
5. Pada instalasi baru yang belum punya application key, jalankan `php artisan key:generate`. Jangan mengganti key instalasi yang sudah digunakan tanpa memahami dampaknya.
6. Jalankan `php artisan migrate`, lalu `php artisan siap:siapkan-demo-db` untuk demo lokal.
7. Untuk demo layanan dengan pemilih role, atur `SIAP_DATABASE_BUSINESS=true` dan `SIAP_DATABASE_ACCOUNTS=false`. Jalankan `php artisan config:clear` setelah perubahan konfigurasi.
8. Jalankan `php artisan serve --host=127.0.0.1 --port=8017`, lalu buka `/login`. Launcher `JALANKAN-DEMO.cmd` tersedia; lokasi PHP portabel pengembang mungkin tidak ada di laptop rekan.

Jika hendak menguji akun nyata, gunakan `php artisan siap:buat-admin` sesuai pembatasan perintah, lalu aktifkan `SIAP_DATABASE_ACCOUNTS=true`. Administrator diperlukan untuk bootstrap pengelolaan akun, bukan syarat semua CRUD bisnis pada demo role lokal.

Git tidak menyalin isi database atau unggahan. Migration membangun struktur; seed/input/import yang disengaja menyediakan isi. Hindari `migrate:fresh` pada database berisi data yang ingin dipertahankan.

## 9. Dokumen dan perilaku yang harus dipertahankan

- SKRD mengikuti contoh pengguna: kop dan judul, masa/tahun, kotak nomor urut, identitas, grid kode rekening, uraian, perhitungan pokok/sanksi/jumlah, terbilang, perhatian, area tanda tangan, garis potong, dan tanda terima.
- STBP memiliki daftar penerimaan a–g, rincian kode rekening/uraian/jumlah, total, serta blok Bendahara.
- STS memiliki daftar bernomor, bank/rekening, nominal/terbilang, tabel rincian, dan blok Pengguna Anggaran serta Bendahara.
- Jangan menyederhanakan cetak menjadi halaman kosong dengan label. Pratinjau dan hasil cetak harus menampilkan dokumen yang diisi.
- Editor harus menyimpan field yang ditampilkan. Sumber otomatis, override manual, dan snapshot terbit harus jelas; jangan mengklaim semuanya tersinkron tanpa menguji field terkait.
- Tarif master bergantung lokasi/kegiatan/tanggal. Perubahan master tidak boleh menimpa tarif historis yang sudah dipakai.
- SKRD/STBP/STS tidak mempunyai fungsi yang identik; jangan menyamakan penerbitan tagihan, penerimaan pembayaran, dan setoran internal.

## 10. Bukti pengujian dan batasnya

Berikut hasil pekerjaan sebelumnya yang tercatat pada konteks pengembangan. **Pembuatan handoff ini tidak menjalankan ulang tes aplikasi.**

| Pemeriksaan | Hasil tercatat |
|---|---|
| `tests/database_schema_smoke.php` | Lulus di MariaDB; struktur dan delapan kasus penolakan constraint |
| `tests/account_database_smoke.php` | Lulus; akun, hash, login, role, status, konflik identitas dan audit melalui transaksi |
| `tests/business_database_smoke.php` | Lulus; alur bisnis, versi, dokumen, penghapusan/pemulihan dan konflik penyimpanan |
| `tests/business_http_smoke.py` | Lulus 63 request melalui HTTP kernel dengan sesi lintas role dan database uji terpisah |
| Kompilasi Blade | Berhasil pada revisi terkait; error directive menu telah diperbaiki |

Pengujian HTTP memakai `tests/kernel_request.php`, bukan inspeksi visual browser. Skrip HTTP bisnis mengharuskan nama database berawalan `siap_wisata_test_`; baca konfigurasinya sebelum menjalankan. Database uji lokal `siap_wisata_test_20261106_crud` pernah dibuat dan tidak dinyatakan telah dibersihkan.

Batas yang harus dilaporkan jujur:

- Belum diverifikasi di server produksi atau MySQL asli; hasil MariaDB bukan bukti kompatibilitas semua server.
- Belum ada pengujian visual browser menyeluruh setelah integrasi CRUD terbaru atau stress test concurrency formal.
- Jangan mengatakan seluruh tes legacy lulus: pernah ada kegagalan assertion watermark pada `demo_smoke.py`; rangkaian legacy lengkap belum dinyatakan hijau pada HEAD ini.
- Belum ada TTE tersertifikasi, integrasi bank, atau pengiriman notifikasi eksternal yang diverifikasi.
- Kewenangan keringanan, kebijakan sanksi, dan paket dokumen pembebasan penuh masih perlu kepastian kebijakan; jangan menebak untuk produksi.
- Pembacaan daftar masih banyak menghidrasi data/relasi di aplikasi; pagination SQL dan efisiensi query perlu dievaluasi jika volume bertambah.
- Transaksi database tidak otomatis membatalkan penulisan file; cleanup artifact yatim masih perlu diperhatikan.
- Validasi workflow masih banyak berada di controller; pemisahan layanan/policy belum sepenuhnya selesai.

## 11. Referensi dan urutan penelusuran

1. [Konteks proyek terbaru](KONTEKS-PROYEK.md).
2. [Audit kelengkapan ERD](AUDIT-KELENGKAPAN-ERD.md).
3. [Keputusan alur](database/KEPUTUSAN-DAN-ALUR.md), [kamus data](database/KAMUS-DATA.md), dan [schema revisi 1](database/schema.json).
4. [Diagram dan indeks database](database/README.md), [catatan migration awal](database/IMPLEMENTASI-MIGRATION.md), [integrasi akun awal](database/INTEGRASI-AKUN.md).
5. Kedua migration, `Workspace`, `BusinessStore`, `FinanceDocument`, controller dan tes terkait untuk perilaku runtime terbaru.

**Peringatan versi dokumentasi:** beberapa dokumen database di atas masih menyebut CRUD belum terintegrasi atau belum ada autentikasi. Itu status tahap penulisan sebelumnya dan sudah disusul commit `fe753a5` serta `1be5441`. Gunakan dokumen tersebut untuk alasan rancangan, bukan sebagai satu-satunya status implementasi terbaru.

Referensi eksternal yang pernah diberikan: Figma Make `cyZnvuli9hShrpOzXVzH7M`, HTML demo SIPTR Dispar rekan, ZIP template Retribusi/SKRD/STS/STBP, foto format surat, SOP Excel perizinan retribusi 2026, serta dokumen standar pelayanan dan spesifikasi sistem. File eksternal tersebut tidak otomatis tersedia pada clone rekan; jangan mengaku sudah membacanya jika hanya memiliki ringkasan ini.

## 12. Langkah aman untuk agent penerima

Mulai dengan `git status`, `git log`, dan identifikasi branch/commit. Cocokkan konfigurasi mode tanpa membocorkan `.env`, cek `php artisan migrate:status`, lalu pilih tes terarah sesuai pekerjaan yang diminta. Konfirmasikan status aktual dengan bukti, terutama bila repo rekan berbeda dari checkpoint ini.

Tidak ada otorisasi baru dalam dokumen ini untuk menggabungkan branch, menerbitkan kode database, mereset database, atau mengaktifkan akun produksi. Kerjakan permintaan pengguna berikutnya dengan perubahan terukur dan checkpoint Git yang dapat ditinjau.
