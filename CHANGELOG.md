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

## Tahap 6: Persyaratan sesuai lembar periksa dan cetak lembar periksa (`docs/TAHAP-6-LEMBAR-PERIKSA.md`)
**Ditambah**: persyaratan opsional "fotokopi jasa impresariat/promotor dari PTSP"; halaman cetak Lembar Periksa Kelengkapan Persyaratan (☑/☐/−, tanda tangan Petugas dan Kabid DIP); migrasi katalog REQ-1..REQ-6.
**Diubah**: nama persyaratan mengikuti formulir fisik dinas.
**Dampak**: wajib `php artisan migrate`.

## Perubahan kecil: login ke Dashboard
**Diubah**: setelah login semua peran langsung diarahkan ke halaman **Dashboard** (sebelumnya tiap peran ke antrean kerjanya masing-masing). Halaman "Ringkasan" diganti nama menjadi **Dashboard** (menu, judul, alamat `/dashboard`). Alamat lama `/ringkasan` tetap berfungsi dan diarahkan ke `/dashboard`. Antrean kerja peran dibuka dari tombol "Buka tugas saya" di Dashboard.
**Teknis**: rute `/` (nama `home`) hanya mengalihkan ke `/dashboard`; nama rute `dashboard` kini menunjuk halaman Dashboard; `FormRecovery` menerima `return_to` ke `/dashboard`; tes `demo_smoke.py`, `security_baseline_smoke.py`, `master_data_smoke.py` diperbarui.
**Dampak**: tidak ada migrasi. Tombol/tautan yang memakai nama rute `summary` tetap bekerja (rute alihan).

## Tahap 7: Cek bentrok jadwal (`docs/TAHAP-7-BENTROK-JADWAL.md`)
**Ditambah**: peringatan langsung di form, penolakan server untuk lokasi dan tanggal yang beririsan (dengan opsi "tetap daftarkan" yang tercatat di riwayat), dan penanda bentrok di kalender.
**Diperbaiki**: perhitungan tanggal peringatan memakai UTC; pesan error Data Master tidak lagi tampil dobel.
**Diubah (tes)**: tanggal pendaftaran di lima tes digeser agar tidak bentrok dengan data contoh.
**Dampak**: tidak ada migrasi.

## Tahap 8: Lokasi di luar Dispar, input manual (`docs/TAHAP-8-LOKASI-DI-LUAR-DISPAR.md`)
**Ditambah**: pilihan "Lainnya (di luar lokasi Dispar)" dengan nama dan alamat manual; jalur tanpa retribusi (tanpa SKRD, STS, STBP, bukti transfer); nama lokasi manual tampil di antrean, kalender, arsip, pencarian, lembar periksa, dan Surat Persetujuan.
**Asumsi menunggu konfirmasi**: lokasi di luar Dispar tidak dikenai retribusi.
**Dampak**: wajib `php artisan migrate`; sesi demo lama perlu direset.

## Tahap 10: Konsistensi desain dan kenyamanan (`docs/TAHAP-10-UIUX.md`)
**Diukur** di Chromium terhadap CSS asli: kontras, fokus keyboard, ukuran sentuh di ponsel. Audit awal dikoreksi (CSS yang dimuat sebenarnya 28 ukuran font, bukan 92).
**Diperbaiki**: kontras lencana Tagihan/Bayar/TTD dan peringatan; penanda fokus keyboard yang sebelumnya tidak ada; isian dan tombol ponsel; skip link; gerakan berkurang; pencegah klik ganda; gambar login 8,7 MB menjadi 200 KB.
**Ditambah**: `ui-comfort.css`, `ux.js`, `tests/ui_audit.cjs` (audit otomatis), rubrik skala kenyamanan, kuesioner SUS, dan skrip uji tugas.
**Belum**: penyatuan ukuran font, sudut, dan warna (Tahap 10b); audit istilah; pengukuran pada halaman sungguhan.
**Dampak**: tidak ada migrasi.

## Belum dikerjakan

### Tahap berikutnya (usulan)
| Tahap | Isi | Rujukan |
|---|---|---|
| Uji | Menjalankan semua tahap di komputer ber-PHP dan MySQL (`migrate`, seluruh `tests/`, dicoba di browser) dan memperbaiki hasilnya | Belum ada tahap yang pernah dijalankan |
| 9 | Hardening produksi: hapus watermark "DEMO" dan tanda tangan simulasi, batasi ekspor/cadangan per peran, rapikan migrasi untuk MySQL, CSP mode enforce, pembatasan laju endpoint lain, performa mode database | T-2, T-4, T-5, T-11 |
| 10b | Menjalankan `ui_audit.cjs` pada aplikasi sungguhan, menyatukan ukuran font, sudut, warna ke token, dan audit istilah | Tahap 10 |
| 11 | Sisa fitur: layar verifikasi per berkas oleh petugas, nama pelaku di riwayat, kalender berwarna per tahap, kolom lokasi di ekspor CSV, estimasi form mengikuti jenis kegiatan dan keringanan | - |
| Akhir | Daftar pengecekan detail untuk website yang sudah jadi | Diminta pemilik |

### Menunggu keputusan pemilik
| Pertanyaan | Dampak |
|---|---|
| Kabid pemutus bebas biaya dan keringanan? (asumsi: ya) | Tahap 5 |
| STBP tidak terbit pada bebas biaya? (asumsi: ya) | Tahap 5 |
| Lokasi di luar Dispar tanpa retribusi? (asumsi: ya) | Tahap 8 |
| Dokumen khusus untuk lokasi di luar Dispar? | Persyaratan form |
| Tarif per lokasi saja atau per jenis kegiatan? | Data Master dan estimasi form |
| Mesin database produksi (MySQL atau MariaDB, versi) | T-2 |
| Kadis ikut meninjau draft surat? | Alur |
| Prosedur bila surat salah setelah ditandatangani | Alur |
| Alamat pemohon dijadikan wajib? Tarif Rp 0 untuk lokasi Dispar diberi peringatan? | Form dan Data Master |
| Alur tanda tangan produksi (tanda tangan basah atau elektronik) | T-11 |
| Siapa yang boleh "tetap daftarkan" saat jadwal bentrok | Tahap 7 |

### Menunggu data resmi dinas
Format penomoran dokumen, bank dan rekening, nama/pangkat/NIP penandatangan, NPWRD, tarif dan lokasi resmi, template surat resmi.
