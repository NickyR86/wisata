# Tahap 3: Data Master (lokasi, jenis kegiatan, tarif)

Dasar: temuan T-12 (tidak ada jalur data master di produksi) dan permintaan pemilik agar admin dapat mengisi master sendiri.

## Ringkasan
Menu **Data Master** (`/admin/lokasi`, hanya admin) kini dapat menambah dan mengelola lokasi, jenis kegiatan, dan tarif pada **mode database**. Data tampil otomatis di formulir Permohonan Baru (dropdown lokasi dan jenis kegiatan), termasuk tarif, status dikelola Dispar, dan kewajiban rekomendasi DLH. Pada mode demo (sesi) hanya tarif contoh yang bisa diubah, seperti sebelumnya.

## Fitur baru
| Fitur | Aturan |
|---|---|
| Tambah jenis kegiatan | Nama unik (tanpa membedakan huruf besar/kecil dan spasi ganda). Tarif lokasi yang sudah ada otomatis disalin ke jenis kegiatan baru. |
| Aktif/nonaktifkan jenis kegiatan | Tidak ada hapus. Minimal satu jenis kegiatan harus tetap aktif. |
| Tambah lokasi | Nama unik, tarif awal per hari, dikelola Dispar, wajib rekomendasi DLH. Ditolak bila belum ada jenis kegiatan aktif (agar tarif tersimpan). |
| Ubah lokasi | Nama, dikelola Dispar, wajib DLH, aktif/nonaktif. Lokasi nonaktif tidak muncul di formulir tetapi tetap tampil di data lama. |
| Hapus lokasi | Hanya bila belum pernah dipakai permohonan dan tarifnya belum dirujuk tagihan; selain itu ditolak dengan saran menonaktifkan. |
| Tarif per hari | Mekanisme versi bertanggal yang sudah ada dipertahankan. |
| Jejak audit | Setiap perubahan tercatat di `audit_aktivitas` (siapa, aksi, sebelum, sesudah). |

## Perubahan teknis
| Berkas | Perubahan |
|---|---|
| `database/migrations/2026_11_20_000001_add_master_data_flags.php` (baru) | Kolom `lokasi.butuh_rekomendasi_dlh`; mengisi nilainya dari daftar nama di `config/persyaratan.php` untuk data lama; mengisi katalog persyaratan REQ-1..REQ-4 bila belum ada. Aman dijalankan berulang. |
| `app/Services/BusinessStore.php` | `createLocation`, `updateLocation`, `deleteLocation`, `addActivity`, `setActivityActive`, `activities`; audit pada perubahan tarif; `locations()` membawa penanda `dlh`. |
| `app/Http/Controllers/DemoController.php` | Aksi `addLocation`, `editLocation`, `removeLocation`, `addActivity`, `toggleActivity` (hanya admin; pelanggaran aturan tampil sebagai pesan, bukan halaman error). Validasi `activity` pada pendaftaran kini membaca master. |
| `routes/web.php` | 5 rute baru di bawah `/admin/lokasi` dan `/admin/kegiatan`. |
| `resources/views/admin/lokasi/index.blade.php` | Tampilan baru (tanpa JavaScript inline, kompatibel CSP). |
| `resources/views/permohonan/create.blade.php` | Dropdown jenis kegiatan membaca master. |
| `app/Support/PermitRequirements.php`, `Workspace.php`, `PrepareBusinessDemo.php` | Penanda DLH dari kolom bila ada (cadangan: daftar nama di config untuk mode demo). |
| `tests/master_data_smoke.py` (baru) | Tes HTTP mode database. |

## Efek yang perlu diketahui
- Migrasi ini tidak bergantung pada Tahap 1-2, tetapi **wajib dijalankan** (`php artisan migrate`) sebelum menu baru dipakai.
- Di mode database, kewajiban rekomendasi DLH kini ditentukan kolom, bukan daftar nama di config. Untuk lokasi lama nilainya diisi otomatis oleh migrasi.
- Jenis kegiatan lama yang tidak ada di tabel `jenis_kegiatan` tidak muncul di formulir (sebelumnya daftar tetap di kode). Pada instalasi dengan perintah demo, tabel itu sudah terisi.

## Tidak termasuk (tahap berikutnya)
- **Lokasi di luar master (input manual nama/alamat)**: butuh kolom baru pada permohonan dan keputusan kebijakan retribusi; dikerjakan sebagai tahap tersendiri.
- Pengelolaan katalog persyaratan lewat menu.
- Tarif berbeda per jenis kegiatan di satu lokasi (skema mendukung; tampilan masih satu tarif per lokasi).

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. `php artisan migrate` pada salinan database; pastikan kolom `lokasi.butuh_rekomendasi_dlh` ada dan tabel `jenis_persyaratan` berisi REQ-1..4.
2. Login admin, buka Data Master: tambah jenis kegiatan, tambah lokasi, ubah, nonaktifkan, hapus lokasi baru.
3. Login sekretariat, buka Permohonan Baru: lokasi dan jenis kegiatan baru muncul; memilih lokasi bertanda DLH memunculkan kolom Rekomendasi DLH.
4. Jalankan `tests/master_data_smoke.py` (lihat petunjuk di kepala berkas) pada **database uji**.
5. Jalankan ulang tes lama (`demo_smoke.py` dan suite DB) untuk memastikan tidak ada regresi.

## Membatalkan
`git revert <hash Tahap 3>` lalu `php artisan migrate:rollback --step=1` bila migrasi sudah dijalankan.
