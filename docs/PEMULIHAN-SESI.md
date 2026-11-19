# Pemulihan sesi dan formulir — 13 November 2026

## Perilaku

- Sesi Laravel tetap memiliki batas waktu. Kredensial browser terpisah memulihkan identitas setelah idle/sleep melalui GET yang aman; POST tetap memerlukan CSRF yang valid.
- Browser memeriksa `/akses/status` ketika halaman kembali aktif/online dan sebelum submit. Tidak ada heartbeat, polling berkala, WebSocket, atau pengulangan otomatis POST bisnis.
- Default kredensial pemulihan: 30 hari sejak akses terakhir (`SIAP_BROWSER_ACCESS_MINUTES=43200`). Tidak menjanjikan login tanpa batas atau logout ketika tab ditutup. Logout mencabut kredensial di server.
- Mode akun memeriksa akun aktif, perubahan password, dan peran. Akun/peran halaman harus cocok dengan sesi sebelum submit. Mode pemilih role hanya dipulihkan pada local/testing.
- Draft teks disimpan sementara dalam `sessionStorage` per tab, identitas, URL, dan form. Pengguna memilih tombol pemulihan; nilai server tidak otomatis ditimpa. Draft dibuang setelah penyimpanan sukses/logout, dan tidak ditawarkan setelah 24 jam. Ini bukan arsip database.
- Password, token/field tersembunyi, dan isi file tidak masuk draft. File yang masih berada pada halaman tetap terpilih ketika pemeriksaan akses berlangsung; setelah reload perlu dipilih ulang.
- Jika akses ditolak atau jaringan gagal, submit dihentikan dan isian pada halaman dipertahankan. Halaman 419 menyediakan jalur kembali untuk kasus token berubah di sela pemeriksaan dan submit.

## Penyimpanan dan operasional

Cookie pemulihan bersifat HttpOnly, SameSite=Lax, terenkripsi oleh middleware Laravel, dan Secure ketika request HTTPS. Token acak 256-bit; nama berkas server memakai SHA-256 token. Kredensial disimpan privat pada `storage/app/browser-access`, dikecualikan Git. Akun nyata harus diakses melalui HTTPS.

Implementasi ini ditujukan untuk satu server seperti lingkungan saat ini. Beberapa server perlu penyimpanan kredensial dan sesi bersama. Pembersihan kredensial tidak aktif dilakukan berkala secara probabilistik; file runtime tidak diikutkan dalam source code.

## Validasi

- `tests/browser_access_smoke.py`: kernel Laravel dengan sesi terisolasi, pemulihan sesi, CSRF lama ditolak, submit, data demo sesi dipertahankan, cookie lama tidak berlaku setelah logout. Tidak menulis database development.
- `tests/browser_access_account.php`: SQLite in-memory untuk perubahan password, akun nonaktif, dan pencabutan kredensial.
- `tests/browser_access_ui.cjs`: Playwright headless dengan server tiruan; draft, penolakan akses/role berbeda, token baru, dan pencegahan double-submit. Memerlukan Playwright tersedia pada NODE_PATH; TEST_BROWSER opsional untuk executable browser.

Belum merupakan pengujian deployment HTTPS atau beban produksi. Pengujian kedaluwarsa mempercepat batas sesi secara terkontrol, bukan menunggu sleep fisik selama dua jam.
