# Baseline Git lokal

Baseline ini mencatat kondisi proyek saat Git mulai digunakan. Ini bukan versi awal pengembangan, bukan rekonstruksi seluruh riwayat lama, dan bukan pernyataan penerimaan pengguna atau kesiapan produksi.

Perubahan sebelum baseline dibandingkan secara terpisah melalui artefak pada folder workspace `outputs/perbandingan-kode-zip-ke-sekarang`. Artefak itu berada di luar repositori aplikasi dan bukan commit historis.

Commit berikutnya sebaiknya mencakup satu tujuan perubahan beserta hasil pemeriksaannya. Status edit kode, hasil pengujian, dan penerimaan pengguna perlu dibedakan.

## Perintah sehari-hari

- `git status`: melihat perubahan yang belum dicatat.
- `git diff`: melihat perubahan kode yang belum di-stage.
- `git add <path>`: memilih file untuk commit.
- `git diff --cached`: memeriksa perubahan yang akan masuk commit.
- `git commit -m "fix: jelaskan perbaikan"`: mencatat perubahan lokal.
- `git log --oneline`: melihat riwayat commit.

Belum ada remote atau push ke GitHub. File .env, dependency, session, cache, log, database lokal dan unggahan demo tidak dicatat. .env.example hanya berisi contoh konfigurasi tanpa APP_KEY.
