# Persyaratan DLH per lokasi — 15 November 2026

Keputusan operasional yang diberikan pengguna: Rekomendasi DLH wajib untuk Pantai Pasir Padi dan Jeramba Gantung. Nama Kawasan Jembatan Gantung/Jembatan Gantung diperlakukan sebagai alias Jeramba Gantung. Standar pelayanan mencantumkan rekomendasi DLH tanpa merinci pengecualian lokasi; cakupan dua lokasi mengikuti klarifikasi pengguna.

Aturan terpusat di `config/persyaratan.php`, dibaca oleh `PermitRequirements`. Status pengelola lokasi, tarif, dan keringanan tidak menjadi penentu kewajiban DLH dan tidak diubah oleh revisi ini. Lokasi baru perlu ditinjau dalam konfigurasi persyaratan.

- Sebelum memilih lokasi, unggahan DLH disembunyikan dan dinonaktifkan.
- Kedua lokasi tersebut menampilkan DLH wajib; lokasi lain hanya memakai tiga persyaratan dasar.
- Mengganti lokasi ke lokasi tanpa DLH membersihkan pilihan berkas dan memberi pemberitahuan.
- Validasi server, berkas contoh, serta checklist yang disimpan mengikuti aturan yang sama. Upload DLH tambahan pada lokasi tanpa kewajiban tidak disimpan.
- Checklist permohonan lama tidak diubah. Identitas jenis persyaratan diambil dari katalog tetap, bukan posisi berkas setelah filter.
- Tidak ada perubahan skema atau migration. Pengujian mencakup kernel Laravel dengan unggahan, browser headless, dan penyimpanan SQLite sementara; tidak mengubah database development.
