# Perilaku field SKRD — 25 Oktober 2026

## Aturan yang diterapkan

| Kelompok | Perilaku |
|---|---|
| Pemohon / penanggung jawab | Dibaca dari permohonan; field penyetor SKRD readonly dan nilai POST tidak menjadi override. |
| Lokasi, kegiatan, tanggal, durasi | Mengikuti sumber terbaru pada draft/editor revisi; cetakan terbit memakai snapshot. |
| Tarif master dan keputusan keringanan | Perubahannya masuk perbandingan sumber. Nominal tagihan terbit tetap mengikuti penetapan. |
| Nomor dokumen, masa retribusi, tanggal dokumen, alamat, NPWR, jatuh tempo, pejabat | Isian administratif petugas; tidak ditimpa sinkronisasi. Alamat/NPWR belum tersedia sebagai atribut permohonan, sehingga tidak dianggap otomatis. |
| Uraian kegiatan | Nilai awal dari sumber; uraian tersimpan dipertahankan. Checkbox penggantian otomatis adalah pilihan eksplisit untuk membangun ulang dari kegiatan/lokasi saat menyimpan. |
| Catatan perhatian | Boleh sengaja dikosongkan; tidak kembali ke default setelah simpan. |
| Rincian dan jumlah | Tetap memakai validasi total yang sudah ada. Denda/kenaikan belum diimplementasikan. |

Sesudah penyimpanan, `skrd_source` menyimpan kondisi sumber yang telah ditinjau. Perubahan berikutnya ditampilkan sebagai tabel sebelum/sekarang. Pengguna dapat meninggalkan editor tanpa mengubah dokumen, atau meninjau dan mencentang konfirmasi untuk menggunakan sumber terbaru. Perubahan sumber sejak halaman dibuka menyebabkan penyimpanan ditolak dan meminta muat ulang (token hash sumber).

## Penerbitan dan revisi

- Aksi penerbitan menolak sumber draft yang belum ditinjau dan rincian tersimpan yang berbeda dari pokok penetapan.
- Penerbitan menyimpan data lengkap hasil helper ke `skrd_snapshot` dan versi pertama pada `skrd_versions`.
- Cetakan menggunakan snapshot termasuk lokasi/tanggal/durasi, sehingga tidak mengambil identitas sumber baru secara diam-diam.
- Editor SKRD yang telah terbit merupakan editor revisi: wajib alasan minimal lima karakter; simpan menghasilkan versi baru, mempertahankan versi lama, dan menulis riwayat. Nominal tagihan tidak diubah.
- Versi terdahulu dapat dibuka melalui tautan Riwayat versi di editor. Akses tetap mengikuti peran dan aturan dokumen yang sudah ada. Revisi hanya pada tahap yang sebelumnya mengizinkan editor (`tagihan`, `bayar`, `ttd`); dokumen selesai tidak dibuka untuk revisi pada tugas ini.
- Penggantian unggahan SKRD setelah terbit diblokir untuk mencegah melewati aturan revisi. Revisi PDF unggahan dan riwayat binernya belum tersedia; jangan menganggap versi template sebagai salinan PDF unggahan.

## Data lama

Record demo yang sudah memiliki SKRD tetapi belum mempunyai snapshot mendapat snapshot dari kondisi saat migrasi session pertama. Alasan versi tertulis **“Snapshot awal data demo lama; bukan rekonstruksi saat penerbitan.”** Ini tidak memulihkan kondisi historis yang tidak tersedia. Uraian lama tidak ditimpa; petugas dapat meninjau atau memilih membangun ulang melalui revisi.

Riwayat dan snapshot masih berada di session demo, bukan audit database permanen. Tidak ada reset data browser pengguna pada pengujian.

## Pemeriksaan dan cara meninjau

- `tests/skrd_behavior_regression.php`: 52 pemeriksaan helper, termasuk 42 regresi lokasi; perubahan sumber, identitas otomatis, uraian tetap, catatan kosong, snapshot stabil, versi lama utuh, nominal tetap.
- `tests/finance_format_smoke.py`: 20 request integrasi ketiga dokumen; diperbarui untuk menyertakan token sumber dan alasan revisi SKRD.
- `tests/skrd_behavior_smoke.py`: 25 request integrasi lulus: draft → perubahan tarif master → penolakan token lama/konfirmasi belum diberikan → simpan → terbit → penolakan revisi tanpa alasan → revisi → buka versi lama → penggantian uraian otomatis.
- PHP lint controller/helper dan pemeriksaan whitespace Git dijalankan.

Untuk meninjau: masuk Bendahara, buka SKRD yang belum terbit untuk memeriksa data sumber dan simpan; pada SKRD terbit isi alasan revisi lalu simpan dan buka Riwayat versi. Form permohonan saat ini belum menyediakan semua jenis koreksi sumber; perubahan nama/tanggal/durasi diverifikasi pada pengujian helper, sedangkan perubahan tarif master diuji melalui HTTP kernel.

Pemeriksaan ini belum mencakup inspeksi visual semua ukuran layar, printer fisik, ataupun konkurensi pengguna produksi. Tahap berikutnya memerlukan tinjauan pengguna sebelum kalkulasi sanksi.
