# Tahap 4: Sinkron data ke dokumen keuangan (SKRD, STS, STBP)

Dasar: permintaan memeriksa sinkron data dari input lokasi dan tarif sampai kalender dan dokumen retribusi.

## Hasil pemeriksaan alur (dari pembacaan kode, belum dijalankan)

| Alur | Hasil |
|---|---|
| Lokasi baru di Data Master muncul di dropdown pendaftaran | Ya (Tahap 3): daftar diambil dari master, hanya lokasi aktif. |
| Lokasi baru muncul di filter kalender | Ya: filter kalender membaca daftar lokasi master (termasuk lokasi nonaktif agar data lama tetap terfilter). |
| Permohonan baru langsung tampil di kalender | Ya: kalender membaca semua permohonan berstatus bukan `ditolak` berdasarkan `location_id`, tanggal, dan jumlah hari, sejak status `masuk`. |
| Tarif master menjadi nominal di SKRD | Ya (mode database): tarif diambil dari master menurut lokasi, jenis kegiatan, dan tanggal kegiatan; nominal SKRD = tarif x jumlah hari. |
| Potongan 25% | Hanya bila keringanan berstatus `disetujui` dan lokasi dikelola Dispar (alur keputusan menunggu Tahap 5). |
| STS dan STBP memakai nominal setelah potongan | Ya: SKRD menampilkan nilai sebelum potongan; STS dan STBP menampilkan total. Terbilang otomatis. |
| Dapat diubah (custom) setelah terisi otomatis | Ya, lewat editor template Bendahara; SKRD yang sudah terbit hanya berubah lewat revisi beralasan dan menjadi versi baru. |
| Perubahan tarif master setelah SKRD terbit | SKRD yang sudah terbit tidak berubah (snapshot). Sebelum terbit, perubahan tarif terdeteksi dan penerbitan dikunci sampai template ditinjau. |

## Tiga celah yang ditemukan dan diperbaiki

1. **Alamat pemohon tidak masuk ke dokumen keuangan.** Surat persetujuan memakai alamat yang diinput, tetapi SKRD/STS/STBP selalu mencetak "Alamat contoh, Pangkalpinang". Kini dokumen keuangan memakai alamat pemohon; bila kosong: di lokal tetap contoh, di lingkungan lain tertulis "(alamat belum diisi)".
2. **Tarif tidak ditemukan diam-diam menjadi Rp 0.** Bila master tarif tidak punya baris berlaku untuk lokasi, jenis kegiatan, dan tanggal kegiatan, nominal jatuh ke Rp 0 tanpa peringatan. Kini panel penetapan menampilkan peringatan, dan aksi penetapan/penerbitan SKRD ditolak sampai admin menetapkan tarif.
3. **Identitas dokumen berisi data contoh.** Nomor `DEMO/...`, bank contoh, rekening `0000000000`, pejabat penandatangan fiktif. Kini diisi dari konfigurasi (`SIAP_DOC_*` di `.env`). Di lokal tanpa konfigurasi tetap memakai contoh; di lingkungan lain tanpa konfigurasi tertulis "(belum diatur)" agar tidak lolos tanpa disadari.

## Perubahan
| Berkas | Perubahan |
|---|---|
| `app/Support/FinanceDocument.php` | Alamat pemohon; identitas dari config; fungsi `tariffMissing()` |
| `app/Http/Controllers/DemoController.php` | Penetapan/penerbitan SKRD ditolak bila tarif kosong |
| `resources/views/permohonan/action.blade.php` | Peringatan tarif kosong di panel penetapan |
| `config/siap.php`, `.env.production.example` | Kunci `SIAP_DOC_*` |

## Yang belum diselesaikan (butuh data dari dinas)
- Format penomoran resmi (saat ini awalan `SIAP_DOC_NUMBER_PREFIX` + nomor tiket + jenis dokumen).
- Nama, pangkat, dan NIP pejabat penandatangan, bank, dan nomor rekening resmi.
- Kolom NPWRD (`npwr`) masih bertanda "—".

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. Mode database: Data Master punya lokasi dan kegiatan dengan tarif; daftarkan permohonan lokasi itu dengan alamat pemohon terisi; proses sampai penetapan; pratinjau SKRD menampilkan alamat dan nominal sesuai tarif x hari.
2. Hapus/nonaktifkan baris tarif (atau pilih tanggal di luar masa berlaku) pada database uji: panel penetapan menampilkan peringatan dan tombol penerbitan ditolak.
3. Di server dengan `APP_ENV=production` tanpa `SIAP_DOC_*`: dokumen mencetak "(belum diatur)". Isi `.env` lalu `php artisan config:cache`: identitas resmi tampil.
4. Jalankan `tests/finance_format_smoke.py` dan `tests/document_smoke.py` untuk memastikan tidak ada regresi pada mode demo.

## Membatalkan
`git revert <hash Tahap 4>`.
