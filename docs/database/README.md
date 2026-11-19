# Rancangan database SIAP-Wisata — revisi 1

Tanggal: 5 November 2026.

**Status: migration revisi 1 sudah dibuat dan dijalankan pada database lokal `siap_wisata` (MariaDB 10.4.32).** Terdapat 26 tabel rancangan dan tabel `migrations` Laravel. Integrasi CRUD demo belum dilakukan. Lihat [hasil implementasi](IMPLEMENTASI-MIGRATION.md).

## Mulai membaca

1. [Keputusan dan alur](KEPUTUSAN-DAN-ALUR.md): perubahan dari rancangan lama, kardinalitas, sumber data, sinkronisasi, transaksi, serta batas kebijakan.
2. [Kamus data](KAMUS-DATA.md): seluruh 26 tabel beserta atribut, tipe, PK/FK, keunikan, dan aturan nilainya.
3. [Audit sebelumnya](../AUDIT-KELENGKAPAN-ERD.md): kebutuhan dan kekurangan yang menjadi dasar revisi.

## Diagram

| Bagian | Gambar lengkap | Isi |
|---|---|---|
| Intake | [ERD intake](01-intake.svg) | Akun, lokasi, kegiatan, permohonan, persyaratan, unggahan |
| Surat dan dokumen | [ERD surat dan dokumen](02-surat-dokumen.svg) | Versi draft, template, penerbitan, versi dokumen, tanda tangan |
| Keuangan dan arsip | [ERD keuangan dan arsip](03-keuangan-arsip.svg) | Tarif, rincian tagihan, pembayaran, dokumen, serah terima |
| Akses dan audit | [ERD akses dan audit](04-akses-audit.svg) | Pengguna, role, permission, riwayat proses, audit |

SVG dapat diperbesar tanpa kehilangan ketajaman. Tabel yang muncul di beberapa diagram adalah tabel yang sama, bukan duplikasi database. Setiap diagram hanya menampilkan relasi dalam kelompoknya; kamus data dan keputusan alur memuat aturan lengkap. Timestamp otomatis dijelaskan dalam kamus data, tidak diulang dalam kotak diagram. Relasi pemilik dokumen memiliki syarat berdasarkan jenis dokumen; baca constraint-nya sebelum implementasi.

Sumber gambar tersedia dalam `.dot`; SVG dirender menggunakan Graphviz melalui Viz. Sumber alternatif `.puml` turut disertakan, tetapi belum diuji render PlantUML. [schema.json](schema.json) memuat definisi tabel yang digunakan untuk menyusun kamus data dan diagram, bukan konfigurasi runtime aplikasi.

## Batas implementasi berikutnya

- Struktur inti dapat menjadi dasar penulisan migration. Kebijakan yang belum dipastikan tidak boleh diaktifkan berdasarkan tebakan: kewenangan keputusan keringanan, rumus sanksi, dan paket dokumen pada pembebasan penuh.
- Arsip, kalender, dan ringkasan menggunakan data transaksi yang sama; tidak memerlukan tabel duplikasi tersendiri.
- Sesudah migration dibuat, perlu pengujian constraint dan CRUD, lalu integrasi aplikasi secara bertahap. Pemeriksaan dokumen rancangan ini belum membuktikan perilaku database atau aplikasi.

## Pemeriksaan paket

Definisi tabel diperiksa untuk nama atribut unik, PK, dan keberadaan target FK. Diagram SVG diperiksa sebagai XML dan ditinjau secara visual. Pemeriksaan ini memvalidasi konsistensi artefak rancangan; belum menjalankan migration, query, ataupun pengujian transaksi.
