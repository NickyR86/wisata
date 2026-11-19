# SIAP-Wisata — frontend Laravel Blade + Bootstrap

Demo interaktif layanan permohonan kegiatan Dinas Pariwisata Kota Pangkalpinang.
Seluruh identitas, tarif, nomor dokumen, dan transaksi adalah data contoh.

## Menjalankan pada komputer ini

Klik dua kali `JALANKAN-DEMO.cmd`, atau buka PowerShell pada folder proyek lalu jalankan:

```powershell
.\JALANKAN-DEMO.cmd
```

Script memilih PHP portabel di `../work/php83` jika tersedia; tidak mengubah PHP XAMPP/Laragon.
Buka http://127.0.0.1:8017/login. Halaman login mengikuti referensi SIPTR: NIP/Username, Kata Sandi, dan pilihan peran demo. Nilai contoh sudah terisi; klik Masuk. Nilai tersebut bukan kredensial akun nyata.
Hentikan server dengan Ctrl+C. Tidak perlu MySQL, Node, atau Vite saat demo berjalan setelah dependensi terpasang. Font dan foto eksternal membutuhkan internet; tampilan fallback tetap tersedia.

## Menjalankan pada komputer lain

Persyaratan: PHP 8.2+ dengan extension standar Laravel, mbstring, fileinfo, openssl; Composer 2.

```sh
composer install
```

Salin `.env.example` menjadi `.env`, lalu:

```sh
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8017
```

Jika hasil salinan tidak menyertakan folder kosong, buat `bootstrap/cache`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, dan `storage/logs` sebelum menjalankan.

## Cakupan penyerahan frontend

- Layout sidebar/header dan navigasi responsif; komponen icon, badge status, tabel, dan pesan validasi.
- Login demo dan tujuh role, dengan navigasi serta guard tindakan berdasarkan role/tahap.
- Ringkasan, antrean, pencarian/filter/pagination, pendaftaran, detail, persyaratan contoh, dan riwayat.
- Penyusunan draft, review/revisi Kabid, verifikasi/revisi Sekretaris, rilis.
- Penetapan SKRD; konfirmasi pembayaran contoh; STBP dan STS contoh.
- Simulasi tanda tangan; serah terima dengan nama penerima; arsip.
- Pratinjau dokumen yang dapat dicetak atau disimpan sebagai PDF lewat browser.
- Kalender agenda, laporan dari data sesi, ekspor rekap CSV, profil pengguna demo, edit tarif contoh.
- Data menggunakan sesi file Laravel, bukan database. Reload halaman mempertahankan data selama sesi masih berlaku. Browser/sesi lain memiliki data terpisah.

## Skenario presentasi dari awal sampai akhir

1. Masuk sebagai **Sekretariat**. Buka Permohonan baru, isi data contoh, pilih tanggal di masa depan, centang penggunaan contoh berkas, lalu daftarkan. Untuk alur penuh tanpa tertunda, jangan centang pengajuan keringanan.
2. Klik pilihan Peran kanan atas → **Kepala Bidang**. Buka permohonan tadi → Setujui & teruskan.
3. Ganti ke **Petugas Layanan** → Editor draft → buka permohonan → edit/simpan → Kirim ke Kabid.
4. Ganti ke **Kepala Bidang** → antrean → buka draft → Setujui draft. Revisi membutuhkan catatan minimal 5 karakter dan mengembalikan proses ke Petugas.
5. Ganti ke **Sekretaris** → Verifikasi draft → verifikasi. Tersedia jalur kembali untuk revisi dengan catatan.
6. Ganti ke **Kepala Bidang** → Rilis surat → Rilis ke Bendahara.
7. Ganti ke **Bendahara** → Retribusi & pembayaran → Terbitkan SKRD demo. Pilih tanggal pembayaran, centang pemeriksaan contoh pembayaran/setoran → Konfirmasi lunas.
8. Ganti ke **Kepala Dinas** → Tanda tangan → centang pemahaman simulasi → Simulasikan tanda tangan.
9. Ganti ke **Sekretariat** → Serah terima → isi penerima dan checklist → Serahkan & selesaikan.
10. Buka Arsip. Periksa dokumen dan riwayat lengkap. Gunakan Cetak / simpan PDF jika diperlukan.

Untuk demo potongan 25%, gunakan permohonan contoh **Komunitas Fotografi Babel** yang sudah berada di tahap penetapan dengan keringanan disetujui.

## Aturan dan batas demonstrasi

- Login adalah pilihan role yang sengaja terbuka untuk demo, **bukan autentikasi produksi**. Jangan dipublikasikan sebagai layanan operasional.
- Aksi demonstrasi divalidasi di server, memakai CSRF, serta dibatasi role dan tahap. Ini belum merupakan sistem akun, policy, dan otorisasi produksi yang lengkap.
- SLA 3 hari kerja dihitung sejak pengajuan dan tidak direset per tahap. Sabtu/Minggu dikecualikan; daftar hari libur belum tersedia.
- Tidak memberlakukan ketentuan H-10 sebagai aturan resmi baru. Demo hanya meminta tanggal kegiatan hari ini atau setelahnya. Pemeriksaan bentrok lokasi belum diimplementasikan.
- Permohonan ditolak tidak tampil sebagai agenda. Kalender demo hanya menampilkan status menunggu tanda tangan, siap diserahkan, dan selesai; status menunggu tanda tangan diberi warna berbeda. Ini kebijakan tampilan demo, bukan keputusan tentang kapan reservasi resmi berlaku.
- Keringanan 25% tidak otomatis. Lokasi harus dikelola Dispar dan keputusan harus disetujui. Pengajuan baru berstatus diajukan menahan penetapan; role pengambil keputusan belum diasumsikan. Contoh approved digunakan untuk presentasi.
- Kelayakan selain Pasir Padi/Jeramba Gantung dan seluruh tarif adalah fixture demonstrasi yang harus dicocokkan dengan master resmi.
- Tarif demo per lokasi/per hari; tarif per jenis kegiatan, kategori pembebasan, penomoran resmi, dan validasi setoran terpisah belum diimplementasikan.
- SKRD bukan bukti pembayaran. STBP contoh dicatat setelah konfirmasi lunas. STS contoh dibuat bersamaan setelah checkbox simulasi pemeriksaan pembayaran/setoran; proses setoran nyata harus dibangun terpisah sesuai SOP.
- STS internal dinas; Surat Persetujuan, SKRD, dan STBP tercantum untuk pemohon. Semua pratinjau berlabel DEMO, tidak menggunakan logo/tanda tangan asli.
- Persyaratan dapat memakai metadata contoh atau unggahan lokal PDF/JPG/PNG (maksimal 2 MB per berkas). Gunakan berkas demonstrasi. Tidak ada email/WhatsApp nyata, integrasi bank, atau tanda tangan elektronik tersertifikasi.
- Ekspor adalah CSV rekap, bukan paket backup berkas. Riwayat disposisi tetap ditampilkan dalam detail permohonan selesai.
- Admin dapat menambah/mengedit profil contoh (nama, NIP, peran, status aktif) dan mengubah tarif. Ini belum akun autentikasi produksi atau CRUD lokasi lengkap.

## Struktur yang perlu dipelajari

```text
routes/web.php                       URL menuju controller demo
app/Http/Controllers/DemoController.php   Penghubung halaman, validasi, dan sesi demo
app/Http/Middleware/DemoSession.php       Pemeriksaan sesi demo
app/Support/DemoData.php                  Fixture, status, role, format data
resources/views/layouts/app.blade.php     Shell aplikasi bersama
resources/views/components/              Komponen Blade yang dipakai ulang
resources/views/auth/                    Login demo
resources/views/dashboard/               Ringkasan
resources/views/permohonan/              Daftar, form, detail, aksi kontekstual
resources/views/dokumen/                 Template pratinjau dokumen
resources/views/kalender/                Agenda
resources/views/laporan/                 Rekap
resources/views/admin/                   Profil pengguna dan tarif
public/css/siptr-reference.css           CSS dari referensi SIPTR, termasuk Bootstrap
public/css/siptr-blade.css               Adaptasi Blade dan layout Ringkasan
resources/icons.json                     Ikon SVG referensi
public/js/app.js                         Interaksi form, estimasi, cetak
public/vendor/bootstrap/                 Bootstrap 5.3.8 lokal beserta lisensi
```

Folder Actions, Models, Policies, Requests, dan migrasi yang sudah dibuat sebelumnya dipertahankan untuk integrasi backend berikutnya. Controller demo sengaja terpusat supaya sumber simulasi mudah ditemukan; jangan menjadikannya rancangan akhir backend. Saat integrasi, pindahkan aturan bisnis ke Actions, input ke Form Requests, dan otorisasi ke Policies, lalu ganti sesi dengan model/database secara bertahap.

## Verifikasi dasar

```sh
php artisan route:list
php artisan view:cache
```

Lihat `PANDUAN-PENYERAHAN.md` untuk ringkasan yang dapat diberikan ke rekan/dosen.


Login memakai font Google Fonts dan foto pantai dari URL pada referensi; keduanya membutuhkan internet. Jika tidak tersedia, font fallback dan latar teal tetap digunakan.

## Ringkasan dan Antrean

Identitas visual mengikuti referensi SIPTR: warna, tipografi, ikon, sidebar, dan kontrol. Ringkasan dipisahkan untuk pemantauan SLA, sebaran tahap, agenda, dan pembayaran. Antrean berfokus pada pencarian, filter, dan penanganan permohonan tanpa banner besar. Kartu Ringkasan membuka daftar terkait.

Halaman awal setelah masuk atau berganti peran:

| Peran | Halaman awal |
|---|---|
| Sekretariat | Antrean Permohonan |
| Kepala Bidang | Antrean dengan filter perlu tindakan saya |
| Petugas Layanan | Editor draft |
| Sekretaris | Verifikasi draft |
| Bendahara | Retribusi dan pembayaran |
| Kepala Dinas, Admin | Ringkasan |

Ringkasan tetap tersedia untuk semua peran. Pada layar kecil, menu Lainnya membuka seluruh navigasi sesuai peran. Pengaturan tampilan ini tidak mengubah aturan potongan 25%, urutan proses, atau perhitungan SLA.

## Pembaruan detail dan retribusi — 18 Oktober 2026

- Kode demo DPR menggantikan PKP mengikuti referensi. Nomor ini bukan format resmi penomoran Dispar.
- Tahapan menggunakan node/garis horizontal di desktop dan vertikal di mobile; judul persyaratan mencantumkan jumlah.
- Bendahara: pilih permohonan di Penetapan Retribusi, klik Edit template pada SKRD/STS/STBP, ubah uraian, lalu Simpan Template. Bentuk masing-masing dokumen mengikuti struktur foto acuan dengan data demo.
- Unggah manual menerima PDF/JPG/PNG maksimal 2 MB. Pilih file lalu Simpan berkas; setelah terbit, pratinjau membuka sumber unggahan. Simpan Template mengaktifkan kembali sumber sistem. Unggah tidak membuat status lunas atau terbit secara otomatis.
- Bukti pembayaran dapat diunggah dan dibuka sebelum konfirmasi. Simulasi kirim ulang tidak mengirim pesan eksternal.
- Berkas disimpan di storage/app/demo-uploads dengan nama acak dan dibuka lewat route yang memeriksa sesi/peran. Berkas tidak disertakan dalam ZIP source. Metadata demo tetap berbasis sesi; jangan mengandalkan demo sebagai penyimpanan arsip operasional. Reset sesi tidak menghapus berkas fisik yang pernah diunggah.
- Form Sekretariat dan tampilan umum tidak menampilkan nominal retribusi. Bendahara/Kadis/Admin memiliki ringkasan keuangan; dokumen tetap bisa diakses Sekretariat saat diperlukan.
- Hapus sementara memerlukan alasan dan pengetikan kode. Pulihkan melalui Log & Riwayat → Riwayat Hapus. Data tidak dihapus permanen.
- Arsip menyediakan cadangan metadata JSON, bukan cadangan lampiran.

Audit rinci: docs/AUDIT-FITUR-REFERENSI.md. Pengujian fitur tambahan: tests/document_smoke.py (memerlukan DEMO_TEST_PHP seperti pengujian Kernel).

## Keputusan terbaru: informasi retribusi di antrean

Informasi retribusi ditampilkan untuk semua peran di antrean dan detail. Sebelum SKRD: Belum ditetapkan, tanpa nominal estimasi. Setelah SKRD: nominal tetap dari penetapan, disertai Menunggu pembayaran atau Lunas. STS tidak menjadi syarat munculnya nominal maupun status lunas. Ringkasan agregat keuangan tetap khusus Bendahara/Kadis/Admin. Ketentuan ini menggantikan catatan sebelumnya tentang menyembunyikan nominal pada detail Sekretariat.
