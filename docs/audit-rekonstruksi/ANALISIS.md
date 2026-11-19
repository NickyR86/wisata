# Analisis perubahan kode SIAP-Wisata

Tanggal analisis: 25 Oktober 2026. Status: dokumentasi hasil penelusuran, bukan persetujuan akhir aplikasi.

## 1. Bukti yang tersedia

Perbandingan menggunakan `siap-wisata.zip` sebagai kondisi sebelum dan snapshot folder aplikasi pada **24 Oktober 2026 pukul 21:51:43 +07:00** sebagai kondisi sesudah. Arsip tersebut bukan bukti versi pertama proyek tanggal 16 September. Perbandingan tidak dapat menunjukkan setiap revisi di antara kedua kondisi, perubahan yang pernah dibuat lalu dibatalkan, atau waktu/pelaku setiap perubahan.

SHA-256 ZIP dan seluruh pasangan file dalam manifest telah diperiksa ulang terhadap arsip serta kode saat analisis. Semuanya cocok. Dengan demikian, kode aplikasi yang dianalisis masih sama dengan snapshot pembanding; penambahan dokumentasi Git tidak mengubah hasil diff ini.

- [Lampiran kode lengkap sebelum–sesudah](LAMPIRAN-KODE.md): seluruh 20 bagian diff, bukan hanya ringkasan fitur.
- [Manifest sumber](manifest-sumber.json): hash asli, waktu snapshot, dan batas cakupan.
- [Hasil verifikasi hash](verifikasi-bukti.json).

Terdapat **14 file berubah, 6 file baru, 0 file dihapus**, dengan 419 baris ditambahkan dan 50 dihapus. Banyak Blade/CSS ditulis dalam baris panjang, sehingga angka baris bukan ukuran kompleksitas pekerjaan. Cakupan hanya PHP/CSS/JS aplikasi dalam direktori yang tercantum di manifest; konfigurasi, dependency, unggahan, session, dan seluruh riwayat sejak proyek dimulai tidak termasuk.

Istilah “baru” di laporan ini berarti tidak ada pada ZIP pembanding. Alasan perubahan di bawah menjelaskan masalah yang diselesaikan oleh kode dan kecocokannya dengan permintaan percakapan; bukan klaim bahwa tanggal atau urutan pengerjaannya telah dipulihkan.

## 2. Controller dan data demo

### A. Data contoh diperbanyak tanpa mereset seluruh session

**File/fungsi:** `app/Http/Controllers/DemoController.php::init()` dan `app/Support/DemoData.php::requests()`.

Sebelumnya `init()` hanya membuat data saat `demo.requests` belum tersedia. Sesudahnya terdapat penanda `demo.enriched_v2`: record seed yang ID-nya belum ada ditambahkan; record yang sudah ada dipertahankan, sementara `templates` dan `signature` yang kosong diisi dari seed.

```php
// Tambahan utama; lihat lampiran untuk seluruh blok.
if(!$existing->has($sample['id'])) $existing->put($sample['id'],$sample);
```

`requests()` menambahkan 12 record ID 330–341 dari 12 skenario awal, sehingga sesi baru mempunyai 24 record. Nama lembaga dibedakan, tanggal kegiatan ditempatkan pada bulan berjalan, template keuangan diberi data fiktif, dan riwayat contoh dibentuk sampai tahap masing-masing record. Masalah yang ditangani: antrean peran, dokumen, dan kalender terlalu kosong untuk demonstrasi.

**Batas:** riwayat seed adalah skenario buatan, bukan audit aksi pengguna nyata. Pengayaan sesi lama tidak mengganti tanggal, draft, atau riwayat record lama; tampilan sesi lama bisa berbeda dari sesi baru. Penambahan berdasarkan ID juga bukan migrasi database relasional.

### B. Draft satu teks menjadi surat terstruktur

**File/fungsi:** `DemoController::act()`, `DemoData::letter()` (baru), `DemoData::draft()`.

Sebelum:

```php
$q->validate(['draft'=>'required|string|min:30|max:15000']);
$r['draft']=$q->draft;
```

Sesudah: bila request memuat `letter`, controller memvalidasi nomor, perihal, lampiran, tanggal, pembukaan, isi, ketentuan, dan penutup. Hasilnya disimpan dalam `$r['letter']`; empat bagian narasi juga digabungkan ke `$r['draft']` untuk kompatibilitas. Jalur `draft` lama tetap diterima jika tidak ada `letter`.

```php
$r['letter']=array_intersect_key($valid['letter'],D::letter($r));
$r['draft']=implode("\n\n",array_intersect_key(
    $r['letter'],array_flip(['opening','body','conditions','closing'])
));
```

`DemoData::letter()` menyediakan nilai awal dan menggabungkan nilai tersimpan. Draft lama yang bukan teks contoh dipakai sebagai isi awal agar tidak langsung hilang. `draft()` sendiri diperpanjang dengan ketentuan pelaksanaan.

**Dampak:** pengguna dapat menyusun lebih dari separuh narasi surat; pratinjau, review, dan dokumen memakai struktur yang sama. Identitas kegiatan tetap berasal dari permohonan, bukan diketik ulang melalui editor narasi.

### C. Simpan draft tetap berada di editor

Sebelumnya semua aksi berakhir pada redirect ke detail permohonan. Sekarang `save` memiliki redirect khusus:

```php
if($action==='save') return redirect()->route('listing',[
    'kind'=>'draft','selected'=>$id
])->with('success','Draft '.$r['tracking'].' berhasil disimpan. Anda tetap berada di editor; draft belum dikirim ke Kabid.');
```

`send` tetap mengubah status menjadi `review_draft`. Simpan dan kirim memang berbeda. Pemeriksaan peran/tahap di `act()` sudah ada dalam ZIP; jangan menyebut seluruh pembatasan akses sebagai fitur baru pada diff ini. Kabid tetap meninjau/mengembalikan, bukan menyunting draft petugas.

### D. Tarif penetapan dapat disesuaikan dengan alasan

**File/fungsi:** `DemoController::act()`, cabang `invoice`.

Sebelumnya tarif selalu `$loc['rate']`. Sekarang request dapat membawa tarif dan alasan:

```php
$q->validate(['rate'=>'nullable|numeric|min:0|max:100000000',
    'rate_reason'=>'required_with:rate|string|max:500']);
$rate=$q->filled('rate')?(float)$q->rate:$loc['rate'];
```

Tagihan menyimpan tarif, subtotal, persentase, total, serta alasan. Aturan keringanan 25% dan pengajuan yang belum diputuskan sudah ada pada sisi sebelum. Perubahan ini bukan implementasi baru seluruh aturan keringanan.

**Batas:** bukan editor bebas nominal dokumen setelah terbit. Tarif boleh numerik pecahan, sementara rincian template diwajibkan integer rupiah; konsistensi pecahan perlu ditetapkan dan diuji lebih lanjut.

### E. Data simulasi tanda tangan

**File/fungsi:** `DemoController::act()`, cabang `sign`.

Sebelumnya aksi hanya menandai `signed=true` dan berpindah ke `serah`. Sekarang juga menyimpan nama fiktif, waktu, dan referensi `SIM-<tracking>` pada `signature`. Ini membuat pratinjau menampilkan informasi persetujuan demo yang dapat ditelusuri. Bukan tanda tangan elektronik tersertifikasi atau gambar tanda tangan pejabat asli.

### F. Editor keuangan menyimpan lebih banyak field dan rincian

**File/fungsi:** `DemoController::template()`.

Sebelumnya hanya uraian, alamat, bank, rekening, dan jatuh tempo yang divalidasi. Sekarang termasuk masa, tanggal, NPWR, nomor, penyetor, metode penerimaan, identitas pejabat, catatan, dan array rincian 1–20 baris. Setiap rincian berisi kode rekening, uraian rekening, dan nominal integer.

```php
$expected=$type==='skrd'?$finance['base']:$finance['total'];
if(abs(array_sum(array_column($data['rows'],'amount'))-$expected)>0.01)
    throw \Illuminate\Validation\ValidationException::withMessages(/* pesan */);
```

Potongan di atas dipersingkat pada pesan error; kode lengkap tersedia di lampiran. Total rincian SKRD wajib sama dengan pokok sebelum keringanan, sedangkan STS/STBP sama dengan total tagihan. Penyimpanan berubah dari mengganti seluruh konfigurasi menjadi `array_replace()` pada konfigurasi sebelumnya. Setelah simpan, pengguna tetap berada di editor dokumen tersebut.

**Batas:** dokumen tetap boleh diedit pada status `tagihan`, `bayar`, dan `ttd`; belum ada snapshot final tak berubah. STS dan STBP juga belum menjalankan siklus penerimaan dan penyetoran yang terpisah; aksi pembayaran yang membuat keduanya sudah ada sebelum diff ini.

### G. Arsip dan log mempunyai jalur data sendiri

**File/fungsi:** `DemoController::listing()`.

Arsip kini memakai tanggal `completed` untuk periode, diurutkan berdasarkan tanggal selesai, dan diberikan ke `arsip.index` bersama record `selected`. Sebelumnya jalur umum mengurutkan ID dan memakai tanggal pengajuan.

Riwayat kini meratakan `history` semua record menjadi daftar kejadian, memfilter pencarian/peran/periode, mengurutkan `at`, lalu melakukan pagination 20 kejadian. Ini memperbaiki masalah pengelompokan per permohonan yang tidak menjamin urutan kronologis global. Data yang dihapus dibatasi berdasarkan peran.

## 3. Surat dan dokumen keuangan

### Sumber nilai dokumen: `FinanceDocument.php` (baru)

`data()` menyatukan nilai awal dan template tersimpan untuk SKRD, STS, STBP. Nominal utama berasal dari tagihan jika ada, terbilang dihitung melalui `words()`. Nilai `base`, `percent`, `total`, dan `words` ditempatkan terakhir dalam `array_replace()`, sehingga konfigurasi template tidak menimpa nominal penetapan.

**Sinkronisasi yang sebenarnya:** nilai awal penyetor/uraian dapat berasal dari permohonan, tetapi setelah disimpan menjadi override khusus dokumen. Tempat, tanggal kegiatan, dan durasi pada layout SKRD dibaca dari data permohonan. Ini belum berarti semua field selalu sinkron ataupun dokumen final telah dibekukan.

### Perubahan tampilan dan interaksi

| File | Perubahan kode dan dampak |
|---|---|
| `resources/views/persuratan/draft/editor.blade.php` (baru) | Form metadata + empat bagian narasi, identitas sumber readonly, tombol simpan/kirim, pratinjau surat lengkap. |
| `resources/views/dokumen/letter.blade.php` (baru) | Kop, identitas kegiatan, narasi terstruktur, nomor, perihal, lampiran, blok Kadis dan status simulasi tanda tangan. Dipakai ulang untuk review dan cetak. |
| `resources/views/dokumen/editor.blade.php` | Form keuangan menggunakan helper yang sama dengan cetakan; field dibedakan menurut jenis, rincian dapat ditambah/dihapus, penandatangan dan catatan dapat diedit. |
| `resources/views/dokumen/financial.blade.php` | Menjadi pemilih layout: SKRD memakai partial khusus; STBP memakai daftar a–g dan satu blok bendahara; STS memakai daftar bernomor dan dua blok pejabat. Rincian memakai array yang tersimpan. |
| `resources/views/dokumen/skrd.blade.php` (baru) | Format formulir menyambung: kop, nomor urut kanan, identitas, kode rekening dalam sel, lima baris per rincian, perhitungan, perhatian, tanda tangan, potong di sini, tanda terima. |
| `resources/views/dokumen/preview.blade.php` | Tombol kembali membaca `from` melalui daftar tujuan yang diizinkan; detail permohonan tetap tersedia. Surat memakai partial; dokumen manual membuka unggahan. |
| `public/js/app.js` | Pratinjau surat dan field keuangan diperbarui melalui `textContent`; tanggal diformat Indonesia; editor rincian mengatur ulang nama array/input serta menghitung total; SKRD dibangun lima baris per rincian. Masa/tahun dipisahkan. |
| `public/css/siptr-blade.css` | Aturan arsip dua panel, surat, editor, layout ketiga dokumen, responsif, dan print A4; `.print-area` serta turunannya dibuat terlihat untuk menangani cetakan kosong. |

Pratinjau JavaScript bukan bukti data sudah disimpan: yang menyimpan adalah request POST yang lolos validasi controller. Lampiran memperlihatkan kedua sisi ini secara terpisah.

## 4. Halaman lain

| File | Perubahan dan batasnya |
|---|---|
| `resources/views/arsip/index.blade.php` (baru) | Daftar arsip + rincian terpilih, tanggal/penerima, nilai retribusi, status tanda tangan, nomor/sumber dokumen, persyaratan, tautan riwayat. Unduhan diberi nama metadata/CSV; bukan klaim paket PDF seluruh berkas. |
| `resources/views/riwayat/index.blade.php` (baru) | Nomor urut kontinu, permohonan, aktivitas, peran, waktu, catatan; filter dan pagination kejadian. Unduhan masih rekap permohonan CSV, bukan ekspor seluruh kejadian. |
| `resources/views/permohonan/action.blade.php` | Mengganti editor teks tunggal dengan partial surat, menambahkan input penyesuaian tarif/alasan, menggunakan tampilan surat saat review, memperjelas simulasi tanda tangan. |
| `resources/views/permohonan/work.blade.php` | Spacing lebih rapat, jumlah baru diserahkan, cetak dokumen sebelum formulir tindakan, parameter asal pratinjau, riwayat pemeriksaan. |
| `resources/views/permohonan/filters.blade.php` | “Perlu tindakan saya” hanya ditampilkan untuk antrean. |
| `resources/views/permohonan/index.blade.php` | Ada tambahan cabang tabel arsip/riwayat, tetapi controller saat ini lebih dulu mengembalikan view khusus. Cabang lama ini bukan tampilan aktif jalur normal. |
| `resources/views/kalender/index.blade.php` | Agenda membedakan tentatif dan surat ditandatangani, serta memberi pesan untuk tanggal terpilih tanpa kegiatan. Pengisian bulan demo juga ditopang perubahan seed. |
| `resources/views/laporan/index.blade.php` | Tambahan rekap per lokasi: jumlah permohonan, selesai, tagihan, diterima, belum dibayar, serta pencapaian target layanan. |
| `resources/views/layouts/app.blade.php` | Perubahan judul untuk halaman pratinjau; bukan pembangunan ulang seluruh navigasi. Detail satu baris ada pada lampiran. |

## 5. Temuan kekurangan yang masih ada

Temuan ini dipisahkan dari daftar perubahan; belum diperbaiki dalam tugas analisis ini.

### Prioritas tinggi — lookup lokasi helper keuangan salah indeks

Pada `FinanceDocument::data()` terdapat:

```php
$loc=DemoData::locations()[$r['location_id']];
```

`locations()` mengembalikan array indeks 0–3 dengan ID 1–4. Uji langsung helper, satu hari dan tanpa tagihan, menghasilkan:

| ID | Seharusnya | Hasil helper |
|---|---|---|
| 1 | Pantai Pasir Padi, Rp750.000 | Jeramba Gantung, Rp500.000 |
| 2 | Jeramba Gantung, Rp500.000 | Taman Sari, Rp450.000 |
| 3 | Taman Sari, Rp450.000 | Alun-alun Taman Merdeka, Rp900.000 |
| 4 | Alun-alun Taman Merdeka | `Undefined array key 4` |

Nominal tagihan tersimpan dapat menutupi salah tarif pada ID 1–3, tetapi lookup dan nilai awal uraian tetap bermasalah. View menggunakan peta lokasi berbasis ID dari session, sehingga uraian default dan label tempat bisa tidak konsisten. Helper juga mengambil master statis, berbeda dari penetapan yang menggunakan `demo.locations` pada session.

**Usulan perbaikan:** gunakan lookup berdasarkan ID dan sumber lokasi konsisten; uji empat lokasi, sebelum/sesudah tagihan, serta perubahan master pada session. Ini bug nyata yang perlu didahulukan sebelum demonstrasi.

### Prioritas menengah — field kosong tidak benar-benar mengosongkan cetakan

`array_filter(..., fn($v)=>$v!==null&&$v!=='')` menghapus nilai kosong sebelum digabung dengan default. Contohnya, catatan perhatian boleh kosong pada form/controller, tetapi cetakan menampilkan default lagi. Perlu membedakan field belum pernah diisi dengan field sengaja dikosongkan.

### Prioritas menengah — batas edit dan sinkronisasi belum merupakan arsip final

Template masih dapat diubah setelah penetapan pada tahap yang diizinkan; sebagian identitas dibaca langsung dari permohonan, sebagian override tersimpan. Tentukan kapan dokumen dibekukan, bagaimana revisi dibuat, dan bagaimana nilai sumber yang berubah ditangani. Saat ini jangan menjelaskan dokumen sebagai snapshot final immutable.

### Prioritas menengah — sanksi belum bisa diedit/dihitung

SKRD mencetak denda `0%` dan kenaikan `Rp 0` secara literal. Form juga mengakui perhitungan sanksi belum diterapkan. Tambahan array rincian tidak mengimplementasikan sanksi. Kebutuhan dan rumusnya harus disepakati sebelum menambah input.

### Pemeliharaan dan batas pengujian

- `financial.blade.php` masih menyimpan cabang SKRD di dalam `else` yang hanya dicapai saat bukan SKRD. `permohonan/index` juga menyimpan cabang arsip/log yang dilewati jalur controller saat ini. Pembersihan terpisah akan mengurangi kebingungan saat revisi.
- Generator SKRD JavaScript mengambil tempat/tanggal dengan membaca teks DOM (`split('Tempat:')`), bukan atribut data terstruktur. Perubahan redaksi dapat merusak sinkronisasi.
- Validasi maksimum 20 rincian tidak membuktikan semua kombinasi teks panjang akan tercetak rapi dalam satu halaman.
- Seed tidak membuktikan alur operasional dokumen sah; label STS “Penerimaan Tanggal” masih berisi nominal mengikuti foto referensi, sehingga maknanya perlu dikonfirmasi.

## 6. Pemeriksaan yang dilakukan

- Hash ZIP dan seluruh 20 file sebelum/sesudah: cocok.
- Lampiran dibuat langsung dari patch, mempertahankan kode lengkap setiap bagian.
- Pemeriksaan integrasi surat terstruktur: **13 pemeriksaan lulus**, meliputi simpan, baca kembali, kirim, larangan edit oleh Kabid, revisi, dan escaping teks.
- Uji helper lokasi terisolasi: bug ID/index berhasil direproduksi pada empat lokasi seperti tabel di atas. Tidak mengubah data aplikasi.
- Pemeriksaan integrasi format keuangan: **20 request integrasi lulus** setelah dijalankan dari direktori aplikasi. Mencakup simpan/baca kembali template SKRD, STS, STBP pada record 338, penolakan total rincian tidak cocok, escaping teks, serta penolakan akses Kabid. Tidak mencakup seluruh lokasi; karena itu hasil lulus ini tidak meniadakan bug lookup lokasi.
- Analisis ini tidak menjalankan inspeksi visual seluruh halaman, tidak menguji printer fisik, dan tidak mengklaim seluruh field/skenario telah lolos.

Pengujian memakai HTTP kernel Laravel dengan session pengujian sendiri, bukan session browser pengguna. Percobaan pertama tes keuangan terhenti karena working directory membuat path keluaran HTML tidak ditemukan; tes diulang dari direktori aplikasi. Itu kegagalan cara menjalankan tes, bukan bukti kegagalan penyimpanan dokumen.

## 7. Cara menggunakan laporan untuk evaluasi

1. Baca bagian controller untuk menjelaskan perubahan penyimpanan dan alur.
2. Buka bagian file yang sama di lampiran untuk menunjukkan baris `-` dan `+` yang sebenarnya.
3. Bedakan “perubahan yang terbukti” dari “temuan yang masih harus diperbaiki”.
4. Untuk revisi berikutnya, gunakan commit Git sebagai pembanding; jangan mengubah laporan ini menjadi riwayat kronologis yang tidak didukung bukti.

Kalimat yang akurat untuk rekan: “Ini rekonstruksi perubahan bersih antara arsip yang masih tersedia dan snapshot aplikasi. Kode sebelum–sesudah terlampir serta hash sumbernya diverifikasi. Riwayat setiap langkah sebelum Git belum lengkap; mulai baseline Git, revisi berikutnya dicatat melalui commit.”
