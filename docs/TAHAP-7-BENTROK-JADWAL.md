# Tahap 7: Cek bentrok jadwal

Dasar: kalender sebelumnya hanya menampilkan kegiatan, belum mencegah dua kegiatan di lokasi dan tanggal yang sama. Konsep diambil dari cabang biru dan ditulis ulang untuk kode hanlie.

## Aturan
- Bentrok = dua permohonan di **lokasi yang sama** dengan rentang tanggal yang beririsan (hari pertama sampai hari terakhir, termasuk hari yang bersinggungan).
- Yang dihitung menempati lokasi: semua status kecuali `ditolak` dan permohonan yang dihapus sementara. Permohonan baru (`masuk`) dan yang sudah selesai tetap dihitung.
- Lokasi berbeda pada tanggal yang sama tidak dianggap bentrok.

## Perilaku
1. **Saat mengisi form (peringatan langsung):** begitu lokasi, tanggal, dan jumlah hari diisi, bila ada kegiatan lain yang beririsan muncul kotak kuning berisi nomor tiket dan nama pemohon (maksimal tiga).
2. **Saat menyimpan (pemeriksaan server):** pendaftaran bentrok ditolak dengan pesan berisi tiket, tanggal, jumlah hari, dan status kegiatan yang bentrok. Muncul kotak centang "Tetap daftarkan meskipun jadwal bentrok (sudah disadari)".
3. **Bila tetap didaftarkan:** riwayat permohonan mencatat "Jadwal bentrok disadari: <tiket>".
4. **Kalender:** tanggal yang memuat dua kegiatan atau lebih di lokasi yang sama diberi bingkai merah dan tanda "!"; di daftar agenda kegiatan terkait diberi lencana "Bentrok jadwal".

Pemeriksaan server berjalan sebelum berkas diunggah, sehingga penolakan karena bentrok tidak meninggalkan berkas yatim.

## Perubahan
| Berkas | Perubahan |
|---|---|
| `app/Support/DemoData.php` | `endDate()` dan `conflicts()` |
| `app/Http/Controllers/DemoController.php` | Pemeriksaan bentrok di `store()`; catatan di riwayat bila disadari; daftar permohonan dimuat satu kali |
| `resources/views/permohonan/create.blade.php` | Data jadwal terisi (JSON, aman dari injeksi), kotak peringatan, kotak centang persetujuan |
| `public/js/app.js` | Peringatan langsung; perhitungan tanggal memakai UTC agar tidak bergeser sehari pada zona waktu WIB |
| `resources/views/kalender/index.blade.php`, `public/css/siptr-blade.css` | Penanda bentrok pada kalender |
| `resources/views/admin/lokasi/index.blade.php` | Perbaikan kecil: pesan error tidak lagi tampil dua kali (layout sudah menampilkannya) |
| `tests/` | Tes bentrok di `demo_smoke.py`; tanggal pendaftaran pada 5 tes lama digeser ke luar jangkauan data contoh |

## Temuan saat menguji ulang tes lama
Simulasi tanggal menemukan dua tes lama yang akan bentrok dengan data contoh begitu aturan ini aktif (`demo_smoke.py` pada lokasi 1 di hari ke-35, `dlh_requirements_smoke.py` pada lokasi 3 di hari ke-20), ditambah satu tes yang mendaftar dua kali di lokasi dan tanggal yang sama. Semua tanggal pendaftaran tes dipindah ke hari ke-70 sampai ke-100, di luar rentang data contoh (maksimal sekitar hari ke-45) pada hari berapa pun dalam bulan.

## Batasan
- Pemeriksaan hanya saat pendaftaran. Dua pendaftaran yang disimpan tepat bersamaan dapat lolos keduanya (tidak ada kunci di database); bentrok tetap tertandai di kalender.
- Pemeriksaan membaca seluruh daftar permohonan (mode database memuat semua; lihat T-5).
- Siapa yang boleh "tetap mendaftarkan" belum dibatasi: semua pengguna dengan akses form (Sekretariat).
- Jam mulai dan selesai kegiatan belum dipakai; satuannya hari.

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. Jalankan `tests/demo_smoke.py` dan tes lain yang berubah (`dlh_requirements_smoke.py`, `document_smoke.py`, `browser_access_smoke.py`, `business_http_smoke.py`).
2. Manual: daftarkan kegiatan 3 hari di sebuah lokasi, lalu isi form kedua dengan lokasi sama dan tanggal hari ke-2: kotak kuning muncul sebelum menyimpan; menyimpan ditolak; centang persetujuan lalu simpan: berhasil dan riwayat memuat "Jadwal bentrok disadari".
3. Buka kalender bulan itu: tanda "!" pada tanggal bentrok dan lencana "Bentrok jadwal" di agenda.
4. Pilih lokasi berbeda atau tanggal sehari setelah kegiatan pertama berakhir: tidak ada peringatan.

## Membatalkan
`git revert <hash Tahap 7>`.
