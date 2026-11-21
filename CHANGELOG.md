# Catatan Perubahan (perbaikan profesionalisasi SIAP-Wisata)

Format: tiap tahap = satu atau beberapa commit Git. Dasar temuan ada di `LAPORAN-PENGUJIAN-WHITEBOX-BLACKBOX.md`
(T-x = temuan whitebox). Semua perubahan **belum dijalankan** oleh penyusun karena lingkungannya tidak memiliki PHP;
yang sudah dilakukan hanya pemeriksaan statis (keseimbangan kurung, token PHP, direktif Blade, sintaks JS/Python, uji
logika regex). Jalankan tes di komputer yang memiliki PHP sebelum digabungkan.

## Tahap 0: Baseline
Isi persis `siap-wisata-hanlie.zip` sebelum perubahan.

## Tahap 1: Fondasi keamanan (`docs/TAHAP-1-KEAMANAN.md`)
**Diubah**
- Login demo tanpa sandi ditolak di luar `APP_ENV=local/testing`; sesi demo di luar lokal diarahkan ke login; isian otomatis `demo1234` hanya di lokal (T-1).
- Rate limit login ditambah per IP; waktu respons seragam untuk akun yang tidak ada (T-9).
- Penetralan CSV mencakup awalan tab dan CR (T-10).
- Pendaftaran: validasi keringanan dipindah sebelum penulisan berkas; berkas dihapus bila pendaftaran gagal (T-7).
- Dua `onchange` inline diganti `data-autosubmit`.

**Ditambah**
- `SecurityHeaders` (header keamanan + CSP report-only) dan `TrustProxiesFromConfig` (T-6).
- `.env.production.example`, `docs/DEPLOY-PRODUKSI.md`, `tests/security_baseline_smoke.py`.
- Konfigurasi `siap.trusted_proxies` dan `siap.csp`.

**Dampak**: mode demo hanya jalan bila `.env` berisi `APP_ENV=local`. CSP belum menegakkan apa pun sampai `SIAP_CSP=enforce`.

## Tahap 1b: Koreksi panduan deploy
Panduan sebelumnya menyuruh menjalankan `siap:siapkan-demo-db` di produksi; ternyata perintah itu menolak di luar lokal.
Dicatat sebagai temuan baru **T-12**: data master awal (lokasi, tarif, jenis kegiatan, persyaratan) belum punya jalur
resmi di produksi (lihat bagian "Belum dikerjakan").

## Tahap 2: Opsi berkas contoh hanya lokal (`docs/TAHAP-2-BERKAS-CONTOH.md`)
**Diubah**: `store()` hanya menghormati `files=1` di `local/testing`; kotak centang dan kalimatnya hanya tampil di lokal (T-11).
**Dampak**: di produksi unggahan persyaratan selalu wajib.

## Tahap 3: Data Master (`docs/TAHAP-3-DATA-MASTER.md`)
**Ditambah**: menu admin untuk menambah/ubah/nonaktifkan/hapus lokasi dan jenis kegiatan beserta tarif awal (mode database), jejak audit, migrasi penanda DLH dan katalog persyaratan, tes `tests/master_data_smoke.py`.
**Diubah**: dropdown jenis kegiatan pada form pendaftaran membaca master; kewajiban rekomendasi DLH ditentukan kolom lokasi (menyelesaikan T-12).
**Dampak**: wajib `php artisan migrate`. Lokasi dan kegiatan baru langsung muncul di formulir Permohonan Baru.

## Tahap 4: Sinkron data dokumen keuangan (`docs/TAHAP-4-SINKRON-DOKUMEN.md`)
**Diperiksa**: alur lokasi master -> dropdown -> kalender, dan tarif master -> SKRD/STS/STBP.
**Diperbaiki**: alamat pemohon kini tampil di SKRD/STS/STBP; tarif master kosong tidak lagi diam-diam menjadi Rp 0 (penetapan dikunci dengan peringatan); identitas dokumen (awalan nomor, bank, rekening, penandatangan) dibaca dari konfigurasi `SIAP_DOC_*`.
**Dampak**: di produksi, dokumen mencetak "(belum diatur)" sampai `SIAP_DOC_*` diisi dari data resmi dinas.

## Tahap 5: Bebas biaya dan keringanan 25% (`docs/TAHAP-5-GRATIS-DAN-KERINGANAN.md`)
**Ditambah**: pilihan Tidak ada / Keringanan 25% / Bebas biaya saat pendaftaran dengan dokumen pendukung wajib; keputusan Kabid saat peninjauan awal; jalur bebas biaya tanpa SKRD, STS, STBP, dan bukti transfer; katalog persyaratan REQ-5.
**Diperbaiki**: keringanan 25% tidak lagi buntu (T-3); tidak ada lagi jalur menihilkan retribusi dengan tarif 0.
**Asumsi menunggu konfirmasi**: pemutus = Kabid; STBP juga tidak terbit pada bebas biaya.
**Dampak**: wajib `php artisan migrate`; tes demo diperbarui.

## Belum dikerjakan (menunggu keputusan atau tahap berikutnya)
| Kode | Isi | Menunggu |
|---|---|---|
| T-2 | Migrasi hanya MariaDB/MySQL; contoh `.env` default SQLite | Keputusan mesin database produksi |
| T-4 | Ekspor/cadangan terbuka untuk semua peran | Kebijakan peran yang boleh mengunduh |
| T-5 | Pemuatan semua permohonan berulang (mode database) | Pengukuran di mesin ber-PHP |
| T-11 | Tanda tangan simulasi, kirim ulang tagihan simulasi | Keputusan alur tanda tangan produksi |
| T-12 | (sisa) Data resmi tarif dan lokasi perlu diinput admin lewat menu | Data resmi dari dinas |
| - | Tahap 6: persyaratan sesuai lembar periksa fisik (jasa impresariat/promotor PTSP bersyarat, KTP, rekomendasi DLH bersyarat) dan cetak lembar periksa | Konfirmasi daftar dari dinas |
| - | Tahap 7: cek bentrok jadwal dari cabang biru | - |
| - | Tahap 8: lokasi di luar master (input manual) | Keputusan kebijakan retribusi untuk lokasi non-Dispar |
| - | Watermark "DEMO - BUKAN DOKUMEN RESMI" pada dokumen produksi, tanda tangan simulasi | Alur tanda tangan produksi dan template resmi |
| - | Kalender berwarna dan nama pelaku dari cabang biru | Tahap tersendiri |
