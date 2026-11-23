# Tahap 10: Konsistensi desain dan kenyamanan (UI/UX)

Batas yang harus dibaca lebih dulu: aplikasinya belum bisa dijalankan di lingkungan penyusun (tidak ada PHP), jadi **halaman Blade belum pernah dirender**. Pengukuran di bawah dilakukan di Chromium sungguhan terhadap **CSS asli aplikasi** dan komponen asli (kelas yang sama dengan yang dipakai view), bukan terhadap halaman jadi. Skor kenyamanan di bagian 5 adalah dugaan dari kode dan pengukuran, bukan hasil uji pengguna.

## 1. Koreksi audit awal
Audit pertama menghitung semua berkas CSS. Ternyata layout utama hanya memuat `siptr-reference.css` dan `siptr-blade.css`; `app.css` hanya dimuat halaman error 403/404, dan `login.css` hanya halaman login. Angka yang benar untuk layout utama:

| Ukuran | Hitung awal (salah, semua CSS) | Benar (CSS yang dimuat) |
|---|---|---|
| Ukuran font berbeda | 92 | 28 |
| Nilai sudut membulat berbeda | 57 | 49 |
| Warna hex berbeda | 264 | 73 |
| Pasangan teks/latar gagal kontras (cara statis) | 22 | 1 |

Dominan di CSS yang dimuat: font 12px (32 pemakaian), 13px (28), 11px (22); sudut 8px (37), 10px (16), 12px (11), pil 999px (11). Jadi sistem desainnya sebenarnya cukup terpusat, tidak sekacau hitungan awal.

## 2. Temuan nyata dan perbaikannya (diukur di Chromium)

| Temuan | Sebelum | Sesudah | Cara verifikasi |
|---|---|---|---|
| Kontras lencana status Tagihan, Bayar, TTD dan peringatan kuning | 4,30:1 (di bawah AA 4,5:1) | 4,68:1 | Ukur warna terhitung pada komponen asli |
| Penanda fokus keyboard pada tombol dan isian | **Tidak ada** (outline 0, bayangan transparan) | Garis 3px biru, jarak 2px | Tab melalui komponen, baca gaya terhitung |
| Isian pada ponsel (390px) | font 13px (iOS memperbesar layar saat fokus), tinggi 39px | font 16px, tinggi 44px | Ukur pada lebar 390px |
| Tombol pada ponsel | 40px dan 32px (kecil) | 44px dan 40px (kecil) | Ukur pada lebar 390px |
| Gambar latar login | 8,7 MB (PNG 2560x1632) | 200 KB (JPG 1600x1020), `loading=lazy`, `alt=""` | Ukuran berkas |
| Klik ganda pada tombol kirim | Mengirim dua kali | Hanya satu; nilai tombol (mis. `action=approve`) tetap terkirim | 9 uji di Chromium dengan server tiruan |
| Lompat ke konten utama untuk pengguna keyboard | Tidak ada (harus Tab melewati seluruh menu) | Tautan "Lewati ke konten utama" muncul pada Tab pertama | Uji di Chromium |
| Pengguna yang mengurangi gerakan | `scroll-behavior: smooth` dipaksa | Dihormati lewat `prefers-reduced-motion` | Aturan CSS |
| Teks kecil di antarmuka (9 dan 10px) | `.attention-days small`, `.summary-footnote`, `.stepper .c` | 11px | Aturan CSS |

Tombol dan isian desktop tingginya 32 sampai 40px: lulus target minimum WCAG 2.2 AA (24px). Penambahan ke 44px hanya di ponsel.

## 3. Perubahan
| Berkas | Perubahan |
|---|---|
| `public/css/ui-comfort.css` (baru) | Semua perbaikan CSS di atas dalam satu berkas, dimuat paling akhir; dapat dilepas dengan menghapus tautannya |
| `public/js/ux.js` (baru) | Pencegah pengiriman ganda; dimuat terpisah dari `app.js` |
| `resources/views/layouts/app.blade.php` | Tautan CSS dan JS baru, skip link |
| `resources/views/auth/login.blade.php` | CSS dan JS baru, gambar latar baru (`latarlogin.jpg`), `alt` kosong untuk gambar dekoratif |
| `public/images/login/` | `latarlogin.png` (8,7 MB) dihapus; `latarlogin.jpg` diganti versi 200 KB |
| `tests/ui_audit.cjs` (baru) | Audit antarmuka otomatis untuk aplikasi yang berjalan |

Tidak ada migrasi dan tidak ada perubahan perilaku server.

## 4. Audit otomatis untuk aplikasi yang berjalan
```sh
php artisan serve --port=8017
node tests/ui_audit.cjs http://127.0.0.1:8017          # tujuh peran, sembilan halaman, tiga lebar layar
```
Keluaran: `ui-audit/REPORT.md` dan tangkapan layar tiap halaman. Memeriksa kode HTTP, galat konsol, geser horizontal, kontrol tanpa nama, gambar tanpa alt, kontras teks (WCAG AA), teks di bawah 11px, target sentuh kecil, penanda fokus, dan jumlah `h1`.

Skrip ini sudah diuji pada halaman uji: semua cacat yang sengaja disisipkan (kontras rendah, teks 9px, tombol tanpa nama, gambar tanpa alt, geser horizontal, galat JavaScript, fokus hilang) terdeteksi, dan halaman yang baik menghasilkan nol temuan. Skrip **belum pernah dijalankan terhadap aplikasi sungguhan**.

## 5. Skala kenyamanan

### 5.1 Rubrik (skor 1 sampai 5 per dimensi)
Skor awal di bawah adalah **dugaan dari kode dan pengukuran, belum diuji pengguna**. Gunakan untuk menentukan prioritas, bukan sebagai hasil.

| Dimensi | Bukti yang ada | Skor awal | Cara mengukur sebenarnya |
|---|---|---|---|
| Kejelasan alur dan navigasi | Menu per peran, Dashboard dengan tombol "Buka tugas saya", penanda tahap, lembar periksa | 4 | Uji tugas (5.3): waktu dan jumlah salah klik |
| Beban isian formulir | Penanda wajib, estimasi retribusi langsung, peringatan bentrok jadwal, isian bersyarat muncul sesuai pilihan | 4 | Waktu mengisi permohonan; jumlah isian yang dikoreksi |
| Umpan balik dan pesan kesalahan | Pesan sukses dan galat seragam di bagian atas halaman; pesan menyebut penyebab dan tindakan | 3 | Apakah pengguna paham harus berbuat apa (kuesioner butir 5 dan 9) |
| Pencegahan dan pemulihan kesalahan | Konfirmasi sebelum hapus, cegah klik ganda, persetujuan bentrok tercatat, pemulihan form saat sesi habis | 4 | Jumlah kesalahan per tugas dan kemampuan memulihkannya |
| Keterbacaan | Kontras kini memenuhi AA pada yang terukur; teks 11 sampai 14px; masih banyak teks 11 dan 12px | 3 | Uji baca oleh pengguna; ukuran teks ponsel |
| Aksesibilitas keyboard dan pembaca layar | Penanda fokus, skip link, label bahasa Indonesia; belum diaudit per halaman | 3 | `ui_audit.cjs` dan uji dengan keyboard saja |
| Tampilan ponsel | Navigasi bawah, isian 16px, target 44px; tabel lebar belum diperiksa | 3 | `ui_audit.cjs` pada 390px, uji di ponsel nyata |
| Kecepatan muat | Gambar login 8,7 MB menjadi 200 KB; CSS 286 KB tanpa minify; font Google dari luar | 3 | Waktu muat di jaringan dinas yang sebenarnya |
| **Total** | | **27 dari 40** | |

Tafsir skor awal: 8 sampai 16 buruk, 17 sampai 24 perlu perbaikan, 25 sampai 32 cukup baik, 33 sampai 40 baik. Skor 27 berarti cukup baik tetapi belum terbukti.

### 5.2 Kuesioner SUS (System Usability Scale), versi Indonesia
Berikan kepada minimal 5 pegawai yang benar-benar memakai aplikasi setelah mencoba tugas di 5.3. Jawaban 1 (sangat tidak setuju) sampai 5 (sangat setuju).

1. Saya merasa akan sering menggunakan sistem ini.
2. Saya merasa sistem ini terlalu rumit.
3. Saya merasa sistem ini mudah digunakan.
4. Saya merasa membutuhkan bantuan orang teknis untuk dapat menggunakan sistem ini.
5. Saya merasa fitur-fitur pada sistem ini berjalan dengan semestinya.
6. Saya merasa ada banyak hal yang tidak konsisten pada sistem ini.
7. Saya merasa kebanyakan orang akan mudah mempelajari sistem ini dengan cepat.
8. Saya merasa sistem ini sangat merepotkan untuk digunakan.
9. Saya merasa sangat percaya diri menggunakan sistem ini.
10. Saya perlu belajar banyak hal sebelum dapat menggunakan sistem ini.

Cara menghitung: untuk butir ganjil, skor = jawaban − 1; untuk butir genap, skor = 5 − jawaban; jumlahkan sepuluh skor lalu kalikan 2,5 (hasil 0 sampai 100). Rata-rata semua responden. Ukuran umum: di bawah 68 berarti di bawah rata-rata, 68 sampai 79 cukup baik, 80 ke atas baik.

### 5.3 Uji tugas (disarankan 5 sampai 7 pengguna)
Catat: berhasil atau tidak, waktu, jumlah salah klik atau salah isi, dan bantuan yang diminta.

| Tugas | Peran | Target |
|---|---|---|
| Mendaftarkan permohonan di lokasi Dispar lengkap dengan berkas | Sekretariat | Selesai dalam 4 menit tanpa bantuan |
| Mendaftarkan kegiatan di lokasi di luar Dispar | Sekretariat | Selesai dalam 4 menit |
| Menyetujui berkas awal dan memutuskan bebas biaya | Kabid | Selesai dalam 2 menit |
| Menyusun dan mengirim draft surat | Petugas Layanan | Selesai dalam 5 menit |
| Menetapkan retribusi dan menerbitkan SKRD | Bendahara | Selesai dalam 3 menit |
| Mencetak lembar periksa dan menyerahterimakan berkas | Sekretariat | Selesai dalam 3 menit |

Target awal: keberhasilan di atas 90 persen, rata-rata kurang dari 1 kesalahan per tugas, SUS minimal 68 (idealnya 80).

## 6. Konsistensi desain: rencana, belum diterapkan
Tidak diterapkan di tahap ini karena perlu pemeriksaan visual di halaman jadi. Usulan standar dari nilai yang paling banyak dipakai:

| Aspek | Standar usulan | Sekarang |
|---|---|---|
| Ukuran teks | 12, 13, 14, 16px untuk isi; judul mengikuti skala Bootstrap | 28 ukuran berbeda |
| Sudut membulat | 8px (komponen), 12px (panel besar), 999px (pil), 50% (bulat) | 49 nilai berbeda |
| Warna | Satu set token (primer, status, netral) di `:root` | 73 warna hex terpisah |
| Istilah | Daftar istilah baku (Permohonan, Berkas, Dokumen, Antrean, Dashboard) | Belum diaudit |

Langkah berikutnya (Tahap 10b), setelah `ui_audit.cjs` dijalankan pada aplikasi sungguhan: gabungkan ukuran font dan sudut ke skala di atas, ubah warna keras menjadi token, dan audit istilah di semua tampilan.

## 7. Verifikasi di aplikasi sungguhan (belum dijalankan)
1. Buka halaman login: gambar latar tampil, ukuran transfer sekitar 200 KB.
2. Tab melalui halaman mana pun: setiap tombol dan isian punya garis fokus; tekan Tab sekali dari halaman baru: muncul "Lewati ke konten utama".
3. Klik dua kali cepat pada tombol Simpan di formulir: hanya satu permohonan tercipta.
4. Buka di ponsel atau jendela 390px: isian tidak memperbesar layar saat disentuh, tombol mudah ditekan.
5. Lencana status Tagihan, Bayar, TTD terbaca jelas.
6. Jalankan `node tests/ui_audit.cjs`, lampirkan `ui-audit/REPORT.md`.

## 8. Membatalkan
Lepas tautan `ui-comfort.css` dan `ux.js` dari dua layout, atau `git revert <hash Tahap 10>`. Gambar login lama tidak disimpan di repo; ambil dari riwayat Git baseline bila perlu.
