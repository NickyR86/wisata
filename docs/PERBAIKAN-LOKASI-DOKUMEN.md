# Perbaikan lookup lokasi dokumen — 25 Oktober 2026

Tahap: bug lokasi dan tarif awal, sebelum perubahan perilaku sinkronisasi SKRD.

## Masalah dan perubahan

`FinanceDocument::data()` memakai ID lokasi 1–4 sebagai indeks array 0–3. Akibatnya nilai awal lokasi/tarif bergeser dan ID 4 tidak ditemukan. Kini lokasi dicari berdasarkan atribut `id`, menggunakan master `demo.locations` pada session yang juga digunakan saat penetapan. Jika session master belum tersedia, digunakan master contoh bawaan.

Lokasi yang benar-benar tidak ditemukan menghasilkan respons 422 dengan penjelasan, bukan mengambil lokasi pengganti. Perbaikan berlaku pada helper bersama SKRD, STS, dan STBP.

Nominal tagihan yang sudah ditetapkan tetap memakai `bill`; nilai awal tanpa tagihan mengikuti master yang benar. Uraian yang telah disimpan tidak ditimpa. Jika uraian lama sudah tersimpan dengan lokasi yang salah, perbaikan ini tidak otomatis memperbaiki teks tersebut: perlu ditinjau pada tahap perilaku/sinkronisasi berikutnya.

## Pemeriksaan

- `tests/finance_location_regression.php`: 42 pemeriksaan lulus. Empat lokasi × tiga dokumen, master dengan urutan berbeda dan ID tambahan, pembaruan tarif session, keringanan 25%, penjagaan nominal tagihan/uraian tersimpan, serta lokasi tak ditemukan.
- `tests/finance_format_smoke.py`: 20 request integrasi lulus, termasuk simpan dan baca kembali ketiga template, validasi jumlah rincian, dan pembatasan peran.
- `git diff --check`: lulus.

Tes regresi memakai session array di memori, sementara tes integrasi memakai session pengujian sendiri. Tidak mereset session browser pengguna. Pemeriksaan visual seluruh halaman dan printer fisik tidak dilakukan pada tahap ini.

## Titik tinjau sebelum tahap berikutnya

Perilaku override uraian, snapshot dokumen setelah terbit, catatan kosong, serta kalkulasi denda/kenaikan belum diubah. Laporan audit sebelumnya tetap dipertahankan sebagai catatan kondisi saat audit; perbaikan ini dicatat melalui commit baru.
