# Implementasi migration revisi 1

Tanggal: 5 November 2026.

Migration `2026_11_05_000001_create_siap_wisata_revision_one.php` telah dijalankan di database lokal siap_wisata, MariaDB 10.4.32 XAMPP, menggunakan PHP 8.3 portabel proyek. Database memiliki 26 tabel bisnis/akses dan tabel migrations Laravel. Tidak mengambil migration ZIP rekan.

## Pemeriksaan

- Sintaks PHP dan migrate:status berhasil; migration tercatat batch 1.
- Semua tabel dan atribut schema.json tersedia.
- Database mencatat 48 FK, 24 UNIQUE, 27 PRIMARY KEY (termasuk migrations), dan 59 CHECK (termasuk validasi JSON bawaan MariaDB).
- tests/database_schema_smoke.php lulus: delapan penolakan meliputi username ganda, status akun invalid, periode tarif ganda, tarif negatif, tanggal terbalik, FK tanpa induk, penghapusan pengguna yang dirujuk, serta dokumen tanpa pemilik.
- Fixture uji dibatalkan melalui rollback transaksi; tidak ada akun demo yang ditambahkan.
- Nama indeks eksplisit dipendekkan agar memenuhi batas identifier MariaDB. Percobaan awal gagal karena nama otomatis terlalu panjang; hanya tabel kosong hasil percobaan itu yang dibuat ulang.

Jalankan ulang pemeriksaan dengan PHP 8.2+ yang memiliki pdo_mysql: `php tests/database_schema_smoke.php`. Uji ini tidak menjalankan ulang atau menghapus migration.

## Batas pekerjaan

Migration menetapkan tipe, nullability, PK, FK RESTRICT, unique, indeks dan constraint per baris. Aturan lintas tabel seperti subtotal sama dengan jumlah rincian, kesesuaian pemilik draft, role pelaku, tarif tidak tumpang tindih, urutan persetujuan, dan imutabilitas dokumen masih harus diterapkan melalui layanan aplikasi/transaksi. Kolom status proses belum dibatasi daftar final workflow.

CRUD demo masih memakai implementasi sebelumnya, belum otomatis menggunakan tabel baru. Belum ada model autentikasi pengguna, seed role/permission, atau integrasi Spatie. Tabel polymorphic menggunakan REF tanpa FK pengguna sesuai rancangan. Belum diuji pada MySQL maupun rollback penuh migration; jangan menganggap keduanya terverifikasi.

Catatan rancangan pada KAMUS-DATA.md dan KEPUTUSAN-DAN-ALUR.md tetap menjelaskan kondisi saat penyusunan. Status implementasi terkini mengacu pada dokumen ini.
