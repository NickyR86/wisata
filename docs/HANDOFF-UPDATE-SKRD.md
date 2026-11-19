# Handoff pembaruan penetapan dan SKRD

Untuk konteks keseluruhan dan daftar sumber proyek, baca [KONTEKS-PROYEK.md](KONTEKS-PROYEK.md).

Tanggal: 15 November 2026
Commit implementasi: `e392cfc` pada branch `hanlie`.
Saat dokumen ini dibuat, commit implementasi masih lokal dan belum dipush. Dokumen ini adalah tambahan untuk HANDOFF-DEVELOPMENT.md, bukan audit ulang seluruh aplikasi. Verifikasi kode checkout sebelum menganggap pembaruan tersedia.

## Masalah dan keputusan

Sebelumnya nominal bisa diubah pada editor surat SKRD, tetapi panel penetapan menghitung sendiri dari tarif. Akibatnya draft dapat menunjukkan Rp3.000.000 sementara penetapan Rp1.500.000.

Keputusan: Penetapan Retribusi menjadi satu tempat pengaturan nominal. Editor surat mengatur informasi dokumen, kode rekening, dan uraian; nominal mengikuti penetapan. Subtotal dan rincian pengurangan merupakan informasi perhitungan internal, bukan permintaan untuk menambah bagian pada format cetak. Tidak menambah lembar cetak rincian keringanan.

## Implementasi e392cfc

- FinanceDocument::assessment() menyediakan perhitungan bersama. Tagihan terbit menjadi acuan bila sudah tersedia; sebelum terbit memakai tarif draft yang tersimpan, atau tarif master berdasarkan lokasi, kegiatan, dan tanggal pada mode database.
- Rumus saat ini: tarif × jumlah hari, dikurangi 25% bila keringanan berstatus disetujui dan lokasi memenuhi syarat. Ini mempertahankan perilaku aplikasi, bukan verifikasi ketentuan hukum.
- Aksi save_assessment tersedia untuk Bendahara pada status tagihan. Menyimpan tarif dan alasan tanpa membuat tagihan terbit. Ringkasan pada form berubah saat tarif diketik; penyimpanan diperlukan agar editor surat membaca hasil terbaru.
- Draft penetapan disimpan dalam pengaturan dokumen SKRD, key assessment (rate, note), pada pengaturan_dokumen_permohonan. Tidak ada migrasi baru pada commit ini.
- Nominal rincian SKRD readonly. Server menolak POST rincian yang totalnya berbeda dari penetapan; readonly bukan satu-satunya perlindungan.
- Rincian template lama yang tidak cocok dinormalisasi menjadi satu rincian dengan kode/uraian pertama dipertahankan dan nominal dari penetapan. Jangan menganggap total lama pada template masih berlaku.
- Penerbitan menyimpan tagihan dan snapshot. Perubahan nominal terbit melalui editor surat tidak tersedia. Revisi informasi surat tetap memerlukan alasan dan menyimpan versi sebelumnya.
- Tidak mengubah format cetak, alur STS/STBP, atau otomatis menulis ulang snapshot dokumen lama.

## File utama

- app/Support/FinanceDocument.php
- app/Http/Controllers/DemoController.php: act() dan template()
- resources/views/permohonan/action.blade.php
- resources/views/dokumen/editor.blade.php
- public/js/app.js
- docs/PENETAPAN-SUMBER-NOMINAL-SKRD.md

BusinessStore.php adalah jalur persistence yang digunakan; tidak diubah pada commit ini.

## Bukti pengujian

Lulus saat implementasi:
- tests/assessment_sync_smoke.py: 12 permintaan HTTP kernel untuk simpan/buka ulang, guard nominal, terbit, role, dan kunci setelah terbit.
- tests/assessment_sync_database.php: SQLite in-memory, penyimpanan draft, keringanan, rincian lama, penerbitan, dan nominal beku.
- tests/skrd_behavior_regression.php: 56 pemeriksaan termasuk regresi lokasi.
- tests/skrd_behavior_smoke.py: 33 permintaan HTTP kernel termasuk revisi informasi dan versi sebelumnya.
- Pemeriksaan sintaks PHP controller, sintaks JavaScript, dan git diff --check.

Batas bukti: pembaruan ini belum diuji ulang melalui browser atau integrasi MySQL/MariaDB. Jangan menyatakan semua tes proyek atau pengujian produksi telah lulus. finance_format_smoke.py diperbarui untuk pesan validasi baru, tetapi tidak dijalankan ulang pada tahap ini.

Tes HTTP harus dijalankan dalam mode session terisolasi: SIAP_DATABASE_BUSINESS=false, SIAP_DATABASE_ACCOUNTS=false, DEMO_TEST_PHP menunjuk executable PHP yang tersedia. Tes database menggunakan SQLite in-memory. Jangan menjalankan reset/migrate:fresh pada database development bersama.

## Belum diselesaikan / tidak boleh diasumsikan

- Aturan dan perhitungan denda/kenaikan belum diterapkan; cetakan masih memakai nilai demo nol.
- Alur keputusan keringanan baru dan pembebasan penuh untuk charity belum ditambahkan. Kegiatan charity tidak otomatis gratis; dokumen pengajuan dan keputusan persetujuan perlu dibedakan.
- Cara resmi mencantumkan keringanan atau menerbitkan SKRD nihil perlu dikonfirmasi kepada dinas. Jangan mengubah format referensi tanpa keputusan tersebut.
- Pemisahan pembayaran diterima (STBP) dan penyetoran (STS) belum dikerjakan; jangan menganggap keduanya sudah disinkronkan melalui alur baru.
- Koreksi nominal setelah penerbitan memerlukan rancangan revisi khusus, bukan mengaktifkan kembali input nominal pada editor surat.

## Paket yang dibagikan ke pengembang / agent lain

1. Source code branch/commit yang sesuai (setelah dipush atau melalui Git bundle). Markdown saja tidak membawa perubahan kode.
2. Dokumen ini dan PENETAPAN-SUMBER-NOMINAL-SKRD.md.
3. HANDOFF-DEVELOPMENT.md sebagai konteks lama; untuk perilaku nominal SKRD gunakan pembaruan ini bila ada perbedaan.
4. PDF referensi SKRD, STS, STBP bila pekerjaan menyangkut format surat. Dokumen eksternal tersebut tidak diasumsikan tersedia dalam repository.

Jangan menyertakan .env, kredensial, vendor, atau database berisi data pribadi. Bila perlu data reproduksi, gunakan seeder/fixture dummy dan jelaskan skenarionya.

## Prompt awal untuk agent penerima

Baca docs/HANDOFF-UPDATE-SKRD.md dan docs/PENETAPAN-SUMBER-NOMINAL-SKRD.md. Periksa branch, git status, dan apakah commit e392cfc sudah terdapat dalam checkout. Bandingkan dengan implementasi aktual sebelum mengedit. Pertahankan Penetapan Retribusi sebagai sumber nominal; jangan membuka kembali nominal independen di editor SKRD. Laporkan perbedaan versi dan pekerjaan yang belum selesai. Jangan menganggap dokumen ini sebagai izin melakukan push atau menghapus data.
