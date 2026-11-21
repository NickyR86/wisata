# Tahap 6: Persyaratan sesuai lembar periksa dinas dan cetak lembar periksa

Dasar: foto lembar periksa kelengkapan persyaratan yang dipakai di lapangan (sembilan baris, ditandatangani Petugas Pelayanan Perizinan dan Kepala Bidang Destinasi Pariwisata dan Industri Pariwisata). Foto dibaca dari gambar yang miring dan buram; mohon dicek ulang terhadap formulir aslinya.

## Perbandingan dengan sistem sebelumnya
| Formulir dinas | Sistem sebelumnya | Sekarang |
|---|---|---|
| Surat permohonan persetujuan menyelenggarakan acara | "Surat permohonan" | Nama mengikuti formulir |
| Proposal Kegiatan/Rundown | "Proposal / rundown" | Nama mengikuti formulir |
| Fotocopy jasa impresariat/promotor dari PTSP (jika menggunakan jasa) | Tidak ada | **Baru, opsional** (REQ-6, kondisi `jika_impresariat`) |
| Fotocopi KTP Pemohon | "Identitas penanggung jawab" | "Fotokopi KTP pemohon" |
| Surat Rekomendasi dari Dinas Lingkungan Hidup | "Rekomendasi DLH" | Nama mengikuti formulir; tetap bersyarat per lokasi |
| Bukti TF, SKRD, STS, STBP | Dokumen hasil sistem | Ditampilkan sebagai baris pada lembar periksa |

Skema database sudah menyiapkan nilai `jika_impresariat` dan `jika_keringanan` pada `jenis_persyaratan.kondisi_wajib`, sehingga tidak perlu kolom baru.

## Perubahan
| Berkas | Perubahan |
|---|---|
| `app/Support/DemoData.php` | Nama persyaratan baru; konstanta `DLH_REQUIREMENT`, `PROMOTER_REQUIREMENT` |
| `app/Support/PermitRequirements.php` | Penyaring DLH memakai konstanta; fungsi `checklist()` menyusun baris lembar periksa |
| `app/Http/Controllers/DemoController.php` | Unggah opsional `promoter_doc`; aksi `checklist` dan rute `/permohonan/{id}/lembar-periksa` |
| `app/Services/BusinessStore.php` | REQ-6 sebagai persyaratan tidak wajib (`wajib=false`) |
| `database/migrations/2026_11_22_000001_align_requirement_catalog_with_checklist.php` (baru) | Menamai ulang REQ-1..4, REQ-5 menjadi `jika_keringanan`, menambah REQ-6 |
| `resources/views/permohonan/create.blade.php` | Baris unggah opsional jasa impresariat/promotor |
| `resources/views/dokumen/checklist.blade.php` (baru), `show.blade.php`, `public/css/siptr-blade.css` | Halaman cetak lembar periksa, tautan di detail, gaya cetak A4 |
| `tests/demo_smoke.py`, `tests/dlh_requirements_smoke.py` | Pemeriksaan lembar periksa dan nama baru |

## Lembar periksa (halaman cetak)
- Dibuka dari detail permohonan, panel "Dokumen terbit", tautan "Lembar periksa kelengkapan". Aksesnya sama dengan dokumen lain (mengikuti `canSeeFiles`).
- Baris mengikuti urutan formulir dinas: lima persyaratan, baris dokumen pendukung keringanan bila diajukan, lalu Bukti transfer (TF), SKRD, STS, STBP.
- Penanda: ☑ ada, ☐ wajib tetapi belum ada, − tidak berlaku atau tidak diunggah. Keterangan terisi otomatis ("ada", "belum ada", "-").
- Pada bebas biaya, empat baris terakhir bertuliskan "- (bebas biaya)".
- Kolom tanda tangan: Petugas Pelayanan Perizinan dan Kepala Bidang Destinasi Pariwisata dan Industri Pariwisata (nama dan tanda tangan diisi manual).

## Dampak
- Wajib `php artisan migrate`.
- Permohonan di database yang sudah ada tetap menyimpan nama berkas lama (snapshot) dan tampil apa adanya; lembar periksa mencocokkan berkas berdasarkan nama baru, jadi berkas lama bernama lama akan tampil sebagai belum ada. Belum ada data produksi, jadi dampak ini hanya relevan bila ada data uji yang ingin dipertahankan.
- Persyaratan DLH tetap ditentukan per lokasi dari Data Master (Tahap 3).

## Belum mencakup
- Verifikasi per berkas oleh petugas (kolom `status_verifikasi` ada, belum ada layar pemeriksaan).
- Nama petugas dan pejabat otomatis pada kolom tanda tangan.
- Daftar persyaratan yang dapat diubah dari menu (masih berupa konstanta kode).

## Verifikasi (belum dijalankan oleh penyusun: sandbox tanpa PHP)
1. Jalankan `tests/demo_smoke.py` dan `tests/dlh_requirements_smoke.py` (mode demo).
2. Buka formulir Permohonan Baru: ada baris opsional "Fotokopi jasa impresariat/promotor dari PTSP"; mendaftar tanpa mengunggahnya tetap berhasil, dengan mengunggah tersimpan.
3. Buka lembar periksa dari detail permohonan, pratinjau cetak A4: tabel dua kolom, kotak penanda, dan dua kolom tanda tangan tampil rapi.
4. Mode database setelah `migrate`: tabel `jenis_persyaratan` berisi REQ-1..REQ-6 dengan nama baru; REQ-5 `jika_keringanan`, REQ-6 `jika_impresariat`.

## Membatalkan
`git revert <hash Tahap 6>`, lalu `php artisan migrate:rollback --step=1`.
