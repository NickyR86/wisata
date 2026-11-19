<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ERD revisi 1. Aturan lintas tabel/workflow tetap memerlukan layanan aplikasi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->bigIncrements('pengguna_id');
            $table->string('nama', 150);
            $table->string('nip', 20)->nullable()->unique();
            $table->string('username', 100)->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password', 255);
            $table->string('unit_kerja', 150)->nullable();
            $table->string('status_akun', 20);
            $table->timestamps();
        });
        Schema::create('lokasi', function (Blueprint $table) {
            $table->bigIncrements('lokasi_id');
            $table->string('nama_lokasi', 150);
            $table->boolean('status_aktif');
            $table->boolean('dikelola_dispar');
            $table->timestamps();
        });
        Schema::create('jenis_kegiatan', function (Blueprint $table) {
            $table->bigIncrements('jenis_kegiatan_id');
            $table->string('nama', 150)->unique();
            $table->boolean('aktif');
            $table->timestamps();
        });
        Schema::create('tarif_retribusi', function (Blueprint $table) {
            $table->bigIncrements('tarif_retribusi_id');
            $table->unsignedBigInteger('lokasi_id');
            $table->unsignedBigInteger('jenis_kegiatan_id');
            $table->decimal('tarif', 18,2);
            $table->string('satuan', 20);
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->unsignedBigInteger('ditetapkan_oleh');
            $table->timestamps();
            $table->unique(['lokasi_id', 'jenis_kegiatan_id', 'berlaku_mulai'], 'r1_unique_0');
        });
        Schema::create('permohonan', function (Blueprint $table) {
            $table->bigIncrements('permohonan_id');
            $table->string('nomor_tracking', 50)->unique();
            $table->string('nama_pemohon', 150);
            $table->string('penanggung_jawab', 150);
            $table->string('kontak_pemohon', 30);
            $table->string('email_pemohon', 150)->nullable();
            $table->text('alamat_pemohon');
            $table->unsignedBigInteger('lokasi_id');
            $table->unsignedBigInteger('jenis_kegiatan_id');
            $table->date('tanggal_kegiatan');
            $table->integer('jumlah_hari');
            $table->dateTime('diterima_pada');
            $table->string('status', 40);
            $table->unsignedBigInteger('diinput_oleh');
            $table->string('jenis_keringanan', 20);
            $table->string('status_keringanan', 20);
            $table->string('kategori_keringanan', 100)->nullable();
            $table->text('alasan_keringanan')->nullable();
            $table->decimal('persentase_disetujui', 5,2)->default(0);
            $table->text('catatan_keputusan_keringanan')->nullable();
            $table->unsignedBigInteger('keringanan_diputuskan_oleh')->nullable();
            $table->dateTime('keringanan_diputuskan_pada')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->text('alasan_hapus')->nullable();
            $table->timestamps();
        });
        Schema::create('jenis_persyaratan', function (Blueprint $table) {
            $table->bigIncrements('jenis_persyaratan_id');
            $table->string('kode', 50)->unique();
            $table->string('nama', 150);
            $table->string('kondisi_wajib', 40);
            $table->boolean('aktif');
            $table->timestamps();
        });
        Schema::create('persyaratan_permohonan', function (Blueprint $table) {
            $table->bigIncrements('persyaratan_permohonan_id');
            $table->unsignedBigInteger('permohonan_id');
            $table->unsignedBigInteger('jenis_persyaratan_id');
            $table->string('nama_snapshot', 150);
            $table->boolean('wajib');
            $table->text('alasan_penerapan')->nullable();
            $table->timestamps();
            $table->unique(['permohonan_id', 'jenis_persyaratan_id'], 'r1_unique_1');
        });
        Schema::create('dokumen_persyaratan', function (Blueprint $table) {
            $table->bigIncrements('dokumen_persyaratan_id');
            $table->unsignedBigInteger('persyaratan_permohonan_id');
            $table->integer('versi');
            $table->string('nama_asli', 255);
            $table->string('disk', 50);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('ukuran_byte');
            $table->char('sha256', 64);
            $table->unsignedBigInteger('diunggah_oleh');
            $table->dateTime('diunggah_pada');
            $table->string('status_verifikasi', 20);
            $table->unsignedBigInteger('diverifikasi_oleh')->nullable();
            $table->dateTime('diverifikasi_pada')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
            $table->unique(['persyaratan_permohonan_id', 'versi'], 'r1_unique_2');
        });
        Schema::create('template_surat', function (Blueprint $table) {
            $table->bigIncrements('template_surat_id');
            $table->string('jenis', 30);
            $table->integer('versi');
            $table->json('isi_template');
            $table->unsignedBigInteger('dibuat_oleh');
            $table->dateTime('dipublikasikan_pada')->nullable();
            $table->timestamps();
            $table->unique(['jenis', 'versi'], 'r1_unique_3');
        });
        Schema::create('draft_surat', function (Blueprint $table) {
            $table->bigIncrements('draft_surat_id');
            $table->unsignedBigInteger('permohonan_id');
            $table->unsignedBigInteger('template_surat_id');
            $table->integer('versi');
            $table->string('nomor_draft', 150)->nullable();
            $table->date('tanggal_surat');
            $table->string('perihal', 200);
            $table->string('lampiran', 200);
            $table->text('pembuka');
            $table->text('isi_surat');
            $table->text('ketentuan');
            $table->text('penutup');
            $table->string('status', 30);
            $table->text('alasan_revisi')->nullable();
            $table->unsignedBigInteger('disusun_oleh');
            $table->dateTime('dikirim_pada')->nullable();
            $table->timestamps();
            $table->unique(['permohonan_id', 'versi'], 'r1_unique_4');
        });
        Schema::create('surat_persetujuan', function (Blueprint $table) {
            $table->bigIncrements('surat_persetujuan_id');
            $table->unsignedBigInteger('permohonan_id')->unique();
            $table->unsignedBigInteger('draft_surat_id');
            $table->unsignedBigInteger('dirilis_oleh');
            $table->dateTime('dirilis_pada');
            $table->timestamps();
        });
        Schema::create('tagihan_retribusi', function (Blueprint $table) {
            $table->bigIncrements('tagihan_retribusi_id');
            $table->unsignedBigInteger('permohonan_id')->unique();
            $table->date('periode_retribusi');
            $table->string('npwr', 100)->nullable();
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->string('keterangan_jatuh_tempo', 200)->nullable();
            $table->decimal('subtotal', 18,2);
            $table->decimal('persentase_potongan', 5,2);
            $table->decimal('nominal_potongan', 18,2);
            $table->decimal('persentase_denda', 5,2)->default(0);
            $table->decimal('nominal_denda', 18,2)->default(0);
            $table->decimal('nominal_kenaikan', 18,2)->default(0);
            $table->text('dasar_sanksi')->nullable();
            $table->decimal('jumlah_tagihan', 18,2);
            $table->string('status', 25);
            $table->unsignedBigInteger('diterbitkan_oleh')->nullable();
            $table->dateTime('diterbitkan_pada')->nullable();
            $table->text('catatan_penetapan')->nullable();
            $table->timestamps();
        });
        Schema::create('rincian_tagihan_retribusi', function (Blueprint $table) {
            $table->bigIncrements('rincian_tagihan_retribusi_id');
            $table->unsignedBigInteger('tagihan_retribusi_id');
            $table->integer('urutan');
            $table->unsignedBigInteger('tarif_retribusi_id')->nullable();
            $table->string('kode_rekening', 100);
            $table->text('uraian');
            $table->decimal('kuantitas', 12,2);
            $table->string('satuan', 20);
            $table->decimal('tarif_satuan', 18,2);
            $table->decimal('jumlah', 18,2);
            $table->text('alasan_tarif_khusus')->nullable();
            $table->timestamps();
            $table->unique(['tagihan_retribusi_id', 'urutan'], 'r1_unique_5');
        });
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->bigIncrements('pembayaran_id');
            $table->unsignedBigInteger('tagihan_retribusi_id')->unique();
            $table->decimal('jumlah_bayar', 18,2);
            $table->date('tanggal_bayar');
            $table->string('metode', 20);
            $table->string('bank', 100)->nullable();
            $table->string('nomor_rekening', 100)->nullable();
            $table->string('referensi_transaksi', 150)->nullable();
            $table->string('bukti_disk', 50)->nullable();
            $table->string('bukti_path', 500)->nullable();
            $table->string('bukti_nama', 255)->nullable();
            $table->string('bukti_mime', 100)->nullable();
            $table->unsignedBigInteger('bukti_ukuran_byte')->nullable();
            $table->char('bukti_sha256', 64)->nullable();
            $table->string('status', 20);
            $table->unsignedBigInteger('diinput_oleh');
            $table->unsignedBigInteger('diverifikasi_oleh')->nullable();
            $table->dateTime('diverifikasi_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
        Schema::create('dokumen_terbit', function (Blueprint $table) {
            $table->bigIncrements('dokumen_terbit_id');
            $table->string('jenis', 30);
            $table->unsignedBigInteger('surat_persetujuan_id')->nullable()->unique();
            $table->unsignedBigInteger('tagihan_retribusi_id')->nullable()->unique();
            $table->unsignedBigInteger('pembayaran_id')->nullable();
            $table->string('nomor_dokumen', 150)->nullable();
            $table->integer('tahun_nomor')->nullable();
            $table->string('status', 20);
            $table->timestamps();
            $table->unique(['pembayaran_id', 'jenis'], 'r1_unique_6');
            $table->unique(['jenis', 'tahun_nomor', 'nomor_dokumen'], 'r1_unique_7');
        });
        Schema::create('versi_dokumen', function (Blueprint $table) {
            $table->bigIncrements('versi_dokumen_id');
            $table->unsignedBigInteger('dokumen_terbit_id');
            $table->unsignedBigInteger('template_surat_id')->nullable();
            $table->integer('versi');
            $table->date('tanggal_dokumen');
            $table->string('sumber', 20);
            $table->json('data_snapshot');
            $table->string('disk', 50)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('nama_asli', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('ukuran_byte')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->string('status', 20);
            $table->unsignedBigInteger('dibuat_oleh');
            $table->text('alasan_revisi')->nullable();
            $table->dateTime('final_pada')->nullable();
            $table->timestamps();
            $table->unique(['dokumen_terbit_id', 'versi'], 'r1_unique_8');
        });
        Schema::create('tanda_tangan_dokumen', function (Blueprint $table) {
            $table->bigIncrements('tanda_tangan_dokumen_id');
            $table->unsignedBigInteger('versi_dokumen_id');
            $table->integer('urutan');
            $table->unsignedBigInteger('pengguna_id')->nullable();
            $table->string('nama_snapshot', 150);
            $table->string('nip_snapshot', 20)->nullable();
            $table->string('jabatan_snapshot', 200);
            $table->string('pangkat_snapshot', 100)->nullable();
            $table->string('metode', 25);
            $table->string('status', 20);
            $table->dateTime('ditandatangani_pada')->nullable();
            $table->string('referensi_penyedia', 200)->nullable();
            $table->char('hash_dokumen_ditandatangani', 64)->nullable();
            $table->timestamps();
            $table->unique(['versi_dokumen_id', 'urutan'], 'r1_unique_9');
        });
        Schema::create('riwayat_disposisi', function (Blueprint $table) {
            $table->bigIncrements('riwayat_disposisi_id');
            $table->unsignedBigInteger('permohonan_id');
            $table->unsignedBigInteger('aktor_id');
            $table->string('role_snapshot', 100);
            $table->string('status_sebelum', 40)->nullable();
            $table->string('status_sesudah', 40);
            $table->string('aksi', 50);
            $table->unsignedBigInteger('draft_surat_id')->nullable();
            $table->unsignedBigInteger('versi_dokumen_id')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('terjadi_pada');
            $table->index(['permohonan_id', 'terjadi_pada', 'riwayat_disposisi_id'], 'r1_index_10');
        });
        Schema::create('serah_terima', function (Blueprint $table) {
            $table->bigIncrements('serah_terima_id');
            $table->unsignedBigInteger('permohonan_id')->unique();
            $table->string('nama_penerima', 150);
            $table->string('hubungan_penerima', 100)->nullable();
            $table->unsignedBigInteger('diserahkan_oleh');
            $table->dateTime('diserahkan_pada');
            $table->boolean('kelengkapan_diperiksa');
            $table->string('bukti_disk', 50)->nullable();
            $table->string('bukti_path', 500)->nullable();
            $table->char('bukti_sha256', 64)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
        Schema::create('serah_terima_dokumen', function (Blueprint $table) {
            $table->unsignedBigInteger('serah_terima_id');
            $table->unsignedBigInteger('versi_dokumen_id');
            $table->primary(['serah_terima_id', 'versi_dokumen_id']);
        });
        Schema::create('audit_aktivitas', function (Blueprint $table) {
            $table->bigIncrements('audit_aktivitas_id');
            $table->unsignedBigInteger('aktor_id');
            $table->string('aksi', 50);
            $table->string('jenis_objek', 100);
            $table->unsignedBigInteger('objek_id');
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->text('alasan')->nullable();
            $table->dateTime('terjadi_pada');
            $table->index(['jenis_objek', 'objek_id', 'terjadi_pada'], 'r1_index_11');
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('role_id');
            $table->string('name', 100);
            $table->string('guard_name', 50);
            $table->timestamps();
            $table->unique(['name', 'guard_name'], 'r1_unique_12');
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('permission_id');
            $table->string('name', 150);
            $table->string('guard_name', 50);
            $table->timestamps();
            $table->unique(['name', 'guard_name'], 'r1_unique_13');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 150);
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type'], 'r1_index_14');
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 150);
            $table->primary(['permission_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type'], 'r1_index_15');
        });
        Schema::table('tarif_retribusi', function (Blueprint $table) {
            $table->foreign('lokasi_id', 'fk_r1_3_0')->references('lokasi_id')->on('lokasi')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('jenis_kegiatan_id', 'fk_r1_3_1')->references('jenis_kegiatan_id')->on('jenis_kegiatan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('ditetapkan_oleh', 'fk_r1_3_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('permohonan', function (Blueprint $table) {
            $table->foreign('lokasi_id', 'fk_r1_4_0')->references('lokasi_id')->on('lokasi')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('jenis_kegiatan_id', 'fk_r1_4_1')->references('jenis_kegiatan_id')->on('jenis_kegiatan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diinput_oleh', 'fk_r1_4_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('keringanan_diputuskan_oleh', 'fk_r1_4_3')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('deleted_by', 'fk_r1_4_4')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('persyaratan_permohonan', function (Blueprint $table) {
            $table->foreign('permohonan_id', 'fk_r1_6_0')->references('permohonan_id')->on('permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('jenis_persyaratan_id', 'fk_r1_6_1')->references('jenis_persyaratan_id')->on('jenis_persyaratan')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('dokumen_persyaratan', function (Blueprint $table) {
            $table->foreign('persyaratan_permohonan_id', 'fk_r1_7_0')->references('persyaratan_permohonan_id')->on('persyaratan_permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diunggah_oleh', 'fk_r1_7_1')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diverifikasi_oleh', 'fk_r1_7_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('template_surat', function (Blueprint $table) {
            $table->foreign('dibuat_oleh', 'fk_r1_8_0')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('draft_surat', function (Blueprint $table) {
            $table->foreign('permohonan_id', 'fk_r1_9_0')->references('permohonan_id')->on('permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('template_surat_id', 'fk_r1_9_1')->references('template_surat_id')->on('template_surat')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('disusun_oleh', 'fk_r1_9_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('surat_persetujuan', function (Blueprint $table) {
            $table->foreign('permohonan_id', 'fk_r1_10_0')->references('permohonan_id')->on('permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('draft_surat_id', 'fk_r1_10_1')->references('draft_surat_id')->on('draft_surat')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('dirilis_oleh', 'fk_r1_10_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('tagihan_retribusi', function (Blueprint $table) {
            $table->foreign('permohonan_id', 'fk_r1_11_0')->references('permohonan_id')->on('permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diterbitkan_oleh', 'fk_r1_11_1')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('rincian_tagihan_retribusi', function (Blueprint $table) {
            $table->foreign('tagihan_retribusi_id', 'fk_r1_12_0')->references('tagihan_retribusi_id')->on('tagihan_retribusi')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('tarif_retribusi_id', 'fk_r1_12_1')->references('tarif_retribusi_id')->on('tarif_retribusi')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreign('tagihan_retribusi_id', 'fk_r1_13_0')->references('tagihan_retribusi_id')->on('tagihan_retribusi')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diinput_oleh', 'fk_r1_13_1')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diverifikasi_oleh', 'fk_r1_13_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('dokumen_terbit', function (Blueprint $table) {
            $table->foreign('surat_persetujuan_id', 'fk_r1_14_0')->references('surat_persetujuan_id')->on('surat_persetujuan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('tagihan_retribusi_id', 'fk_r1_14_1')->references('tagihan_retribusi_id')->on('tagihan_retribusi')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('pembayaran_id', 'fk_r1_14_2')->references('pembayaran_id')->on('pembayaran')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('versi_dokumen', function (Blueprint $table) {
            $table->foreign('dokumen_terbit_id', 'fk_r1_15_0')->references('dokumen_terbit_id')->on('dokumen_terbit')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('template_surat_id', 'fk_r1_15_1')->references('template_surat_id')->on('template_surat')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('dibuat_oleh', 'fk_r1_15_2')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('tanda_tangan_dokumen', function (Blueprint $table) {
            $table->foreign('versi_dokumen_id', 'fk_r1_16_0')->references('versi_dokumen_id')->on('versi_dokumen')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('pengguna_id', 'fk_r1_16_1')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('riwayat_disposisi', function (Blueprint $table) {
            $table->foreign('permohonan_id', 'fk_r1_17_0')->references('permohonan_id')->on('permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('aktor_id', 'fk_r1_17_1')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('draft_surat_id', 'fk_r1_17_2')->references('draft_surat_id')->on('draft_surat')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('versi_dokumen_id', 'fk_r1_17_3')->references('versi_dokumen_id')->on('versi_dokumen')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('serah_terima', function (Blueprint $table) {
            $table->foreign('permohonan_id', 'fk_r1_18_0')->references('permohonan_id')->on('permohonan')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('diserahkan_oleh', 'fk_r1_18_1')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('serah_terima_dokumen', function (Blueprint $table) {
            $table->foreign('serah_terima_id', 'fk_r1_19_0')->references('serah_terima_id')->on('serah_terima')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('versi_dokumen_id', 'fk_r1_19_1')->references('versi_dokumen_id')->on('versi_dokumen')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('audit_aktivitas', function (Blueprint $table) {
            $table->foreign('aktor_id', 'fk_r1_20_0')->references('pengguna_id')->on('pengguna')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->foreign('role_id', 'fk_r1_23_0')->references('role_id')->on('roles')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->foreign('role_id', 'fk_r1_24_0')->references('role_id')->on('roles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('permission_id', 'fk_r1_24_1')->references('permission_id')->on('permissions')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->foreign('permission_id', 'fk_r1_25_0')->references('permission_id')->on('permissions')->restrictOnDelete()->restrictOnUpdate();
        });
        DB::statement("ALTER TABLE `pengguna` ADD CONSTRAINT `ck_r1_0_0` CHECK (`status_akun` IN ('aktif', 'nonaktif'))");
        DB::statement("ALTER TABLE `lokasi` ADD CONSTRAINT `ck_r1_1_0` CHECK (`status_aktif` IN (0,1))");
        DB::statement("ALTER TABLE `lokasi` ADD CONSTRAINT `ck_r1_1_1` CHECK (`dikelola_dispar` IN (0,1))");
        DB::statement("ALTER TABLE `jenis_kegiatan` ADD CONSTRAINT `ck_r1_2_0` CHECK (`aktif` IN (0,1))");
        DB::statement("ALTER TABLE `tarif_retribusi` ADD CONSTRAINT `ck_r1_3_0` CHECK (`tarif` >= 0)");
        DB::statement("ALTER TABLE `tarif_retribusi` ADD CONSTRAINT `ck_r1_3_1` CHECK (berlaku_sampai IS NULL OR berlaku_sampai >= berlaku_mulai)");
        DB::statement("ALTER TABLE `permohonan` ADD CONSTRAINT `ck_r1_4_0` CHECK (`jumlah_hari` > 0)");
        DB::statement("ALTER TABLE `permohonan` ADD CONSTRAINT `ck_r1_4_1` CHECK (`persentase_disetujui` BETWEEN 0 AND 100)");
        DB::statement("ALTER TABLE `permohonan` ADD CONSTRAINT `ck_r1_4_2` CHECK ((keringanan_diputuskan_oleh IS NULL AND keringanan_diputuskan_pada IS NULL) OR (keringanan_diputuskan_oleh IS NOT NULL AND keringanan_diputuskan_pada IS NOT NULL))");
        DB::statement("ALTER TABLE `permohonan` ADD CONSTRAINT `ck_r1_4_3` CHECK (`jenis_keringanan` IN ('normal', 'pengurangan', 'pembebasan'))");
        DB::statement("ALTER TABLE `permohonan` ADD CONSTRAINT `ck_r1_4_4` CHECK (`status_keringanan` IN ('tidak_diajukan', 'diajukan', 'disetujui', 'ditolak'))");
        DB::statement("ALTER TABLE `jenis_persyaratan` ADD CONSTRAINT `ck_r1_5_0` CHECK (`aktif` IN (0,1))");
        DB::statement("ALTER TABLE `jenis_persyaratan` ADD CONSTRAINT `ck_r1_5_1` CHECK (`kondisi_wajib` IN ('selalu', 'jika_impresariat', 'jika_keringanan', 'opsional'))");
        DB::statement("ALTER TABLE `persyaratan_permohonan` ADD CONSTRAINT `ck_r1_6_0` CHECK (`wajib` IN (0,1))");
        DB::statement("ALTER TABLE `dokumen_persyaratan` ADD CONSTRAINT `ck_r1_7_0` CHECK (`versi` > 0)");
        DB::statement("ALTER TABLE `dokumen_persyaratan` ADD CONSTRAINT `ck_r1_7_1` CHECK (`ukuran_byte` >= 0)");
        DB::statement("ALTER TABLE `dokumen_persyaratan` ADD CONSTRAINT `ck_r1_7_2` CHECK ((diverifikasi_oleh IS NULL AND diverifikasi_pada IS NULL) OR (diverifikasi_oleh IS NOT NULL AND diverifikasi_pada IS NOT NULL))");
        DB::statement("ALTER TABLE `dokumen_persyaratan` ADD CONSTRAINT `ck_r1_7_3` CHECK (`status_verifikasi` IN ('menunggu', 'diterima', 'ditolak'))");
        DB::statement("ALTER TABLE `template_surat` ADD CONSTRAINT `ck_r1_8_0` CHECK (`versi` > 0)");
        DB::statement("ALTER TABLE `template_surat` ADD CONSTRAINT `ck_r1_8_1` CHECK (`jenis` IN ('persetujuan', 'skrd', 'stbp', 'sts'))");
        DB::statement("ALTER TABLE `draft_surat` ADD CONSTRAINT `ck_r1_9_0` CHECK (`versi` > 0)");
        DB::statement("ALTER TABLE `draft_surat` ADD CONSTRAINT `ck_r1_9_1` CHECK (`status` IN ('disusun', 'diajukan', 'perlu_revisi', 'disetujui'))");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_0` CHECK (`subtotal` >= 0)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_1` CHECK (`persentase_potongan` BETWEEN 0 AND 100)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_2` CHECK (`nominal_potongan` >= 0)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_3` CHECK (`persentase_denda` BETWEEN 0 AND 100)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_4` CHECK (`nominal_denda` >= 0)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_5` CHECK (`nominal_kenaikan` >= 0)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_6` CHECK (`jumlah_tagihan` >= 0)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_7` CHECK (nominal_potongan <= subtotal AND jumlah_tagihan = subtotal - nominal_potongan + nominal_denda + nominal_kenaikan)");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_8` CHECK ((diterbitkan_oleh IS NULL AND diterbitkan_pada IS NULL) OR (diterbitkan_oleh IS NOT NULL AND diterbitkan_pada IS NOT NULL))");
        DB::statement("ALTER TABLE `tagihan_retribusi` ADD CONSTRAINT `ck_r1_11_9` CHECK (`status` IN ('draft', 'terbit', 'lunas', 'dibebaskan', 'dibatalkan'))");
        DB::statement("ALTER TABLE `rincian_tagihan_retribusi` ADD CONSTRAINT `ck_r1_12_0` CHECK (`urutan` > 0)");
        DB::statement("ALTER TABLE `rincian_tagihan_retribusi` ADD CONSTRAINT `ck_r1_12_1` CHECK (`kuantitas` > 0)");
        DB::statement("ALTER TABLE `rincian_tagihan_retribusi` ADD CONSTRAINT `ck_r1_12_2` CHECK (`tarif_satuan` >= 0)");
        DB::statement("ALTER TABLE `rincian_tagihan_retribusi` ADD CONSTRAINT `ck_r1_12_3` CHECK (`jumlah` >= 0)");
        DB::statement("ALTER TABLE `rincian_tagihan_retribusi` ADD CONSTRAINT `ck_r1_12_4` CHECK (jumlah = ROUND(kuantitas * tarif_satuan, 2))");
        DB::statement("ALTER TABLE `pembayaran` ADD CONSTRAINT `ck_r1_13_0` CHECK (`jumlah_bayar` > 0)");
        DB::statement("ALTER TABLE `pembayaran` ADD CONSTRAINT `ck_r1_13_1` CHECK ((diverifikasi_oleh IS NULL AND diverifikasi_pada IS NULL) OR (diverifikasi_oleh IS NOT NULL AND diverifikasi_pada IS NOT NULL))");
        DB::statement("ALTER TABLE `pembayaran` ADD CONSTRAINT `ck_r1_13_2` CHECK (`metode` IN ('tunai', 'transfer'))");
        DB::statement("ALTER TABLE `pembayaran` ADD CONSTRAINT `ck_r1_13_3` CHECK (`status` IN ('menunggu', 'terverifikasi', 'ditolak'))");
        DB::statement("ALTER TABLE `dokumen_terbit` ADD CONSTRAINT `ck_r1_14_0` CHECK ((jenis = 'persetujuan' AND surat_persetujuan_id IS NOT NULL AND tagihan_retribusi_id IS NULL AND pembayaran_id IS NULL) OR (jenis = 'skrd' AND surat_persetujuan_id IS NULL AND tagihan_retribusi_id IS NOT NULL AND pembayaran_id IS NULL) OR (jenis IN ('stbp','sts') AND surat_persetujuan_id IS NULL AND tagihan_retribusi_id IS NULL AND pembayaran_id IS NOT NULL))");
        DB::statement("ALTER TABLE `dokumen_terbit` ADD CONSTRAINT `ck_r1_14_1` CHECK ((nomor_dokumen IS NULL AND tahun_nomor IS NULL) OR (nomor_dokumen IS NOT NULL AND tahun_nomor IS NOT NULL))");
        DB::statement("ALTER TABLE `dokumen_terbit` ADD CONSTRAINT `ck_r1_14_2` CHECK (`jenis` IN ('persetujuan', 'skrd', 'stbp', 'sts'))");
        DB::statement("ALTER TABLE `dokumen_terbit` ADD CONSTRAINT `ck_r1_14_3` CHECK (`status` IN ('draft', 'terbit', 'dibatalkan'))");
        DB::statement("ALTER TABLE `versi_dokumen` ADD CONSTRAINT `ck_r1_15_0` CHECK (`versi` > 0)");
        DB::statement("ALTER TABLE `versi_dokumen` ADD CONSTRAINT `ck_r1_15_1` CHECK (sumber <> 'sistem' OR template_surat_id IS NOT NULL)");
        DB::statement("ALTER TABLE `versi_dokumen` ADD CONSTRAINT `ck_r1_15_2` CHECK ((sumber <> 'unggah' AND status <> 'final') OR (disk IS NOT NULL AND file_path IS NOT NULL AND nama_asli IS NOT NULL AND mime_type IS NOT NULL AND ukuran_byte IS NOT NULL AND sha256 IS NOT NULL))");
        DB::statement("ALTER TABLE `versi_dokumen` ADD CONSTRAINT `ck_r1_15_3` CHECK (`sumber` IN ('sistem', 'unggah'))");
        DB::statement("ALTER TABLE `versi_dokumen` ADD CONSTRAINT `ck_r1_15_4` CHECK (`status` IN ('draft', 'final'))");
        DB::statement("ALTER TABLE `tanda_tangan_dokumen` ADD CONSTRAINT `ck_r1_16_0` CHECK (`urutan` > 0)");
        DB::statement("ALTER TABLE `tanda_tangan_dokumen` ADD CONSTRAINT `ck_r1_16_1` CHECK (`metode` IN ('simulasi', 'unggahan_manual', 'elektronik'))");
        DB::statement("ALTER TABLE `tanda_tangan_dokumen` ADD CONSTRAINT `ck_r1_16_2` CHECK (`status` IN ('menunggu', 'berhasil', 'gagal'))");
        DB::statement("ALTER TABLE `serah_terima` ADD CONSTRAINT `ck_r1_18_0` CHECK (`kelengkapan_diperiksa` IN (0,1))");
        DB::statement("ALTER TABLE `serah_terima` ADD CONSTRAINT `ck_r1_18_1` CHECK (kelengkapan_diperiksa = 1)");
    }

    public function down(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropForeign('fk_r1_25_0');
        });
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign('fk_r1_24_0');
            $table->dropForeign('fk_r1_24_1');
        });
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign('fk_r1_23_0');
        });
        Schema::table('audit_aktivitas', function (Blueprint $table) {
            $table->dropForeign('fk_r1_20_0');
        });
        Schema::table('serah_terima_dokumen', function (Blueprint $table) {
            $table->dropForeign('fk_r1_19_0');
            $table->dropForeign('fk_r1_19_1');
        });
        Schema::table('serah_terima', function (Blueprint $table) {
            $table->dropForeign('fk_r1_18_0');
            $table->dropForeign('fk_r1_18_1');
        });
        Schema::table('riwayat_disposisi', function (Blueprint $table) {
            $table->dropForeign('fk_r1_17_0');
            $table->dropForeign('fk_r1_17_1');
            $table->dropForeign('fk_r1_17_2');
            $table->dropForeign('fk_r1_17_3');
        });
        Schema::table('tanda_tangan_dokumen', function (Blueprint $table) {
            $table->dropForeign('fk_r1_16_0');
            $table->dropForeign('fk_r1_16_1');
        });
        Schema::table('versi_dokumen', function (Blueprint $table) {
            $table->dropForeign('fk_r1_15_0');
            $table->dropForeign('fk_r1_15_1');
            $table->dropForeign('fk_r1_15_2');
        });
        Schema::table('dokumen_terbit', function (Blueprint $table) {
            $table->dropForeign('fk_r1_14_0');
            $table->dropForeign('fk_r1_14_1');
            $table->dropForeign('fk_r1_14_2');
        });
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropForeign('fk_r1_13_0');
            $table->dropForeign('fk_r1_13_1');
            $table->dropForeign('fk_r1_13_2');
        });
        Schema::table('rincian_tagihan_retribusi', function (Blueprint $table) {
            $table->dropForeign('fk_r1_12_0');
            $table->dropForeign('fk_r1_12_1');
        });
        Schema::table('tagihan_retribusi', function (Blueprint $table) {
            $table->dropForeign('fk_r1_11_0');
            $table->dropForeign('fk_r1_11_1');
        });
        Schema::table('surat_persetujuan', function (Blueprint $table) {
            $table->dropForeign('fk_r1_10_0');
            $table->dropForeign('fk_r1_10_1');
            $table->dropForeign('fk_r1_10_2');
        });
        Schema::table('draft_surat', function (Blueprint $table) {
            $table->dropForeign('fk_r1_9_0');
            $table->dropForeign('fk_r1_9_1');
            $table->dropForeign('fk_r1_9_2');
        });
        Schema::table('template_surat', function (Blueprint $table) {
            $table->dropForeign('fk_r1_8_0');
        });
        Schema::table('dokumen_persyaratan', function (Blueprint $table) {
            $table->dropForeign('fk_r1_7_0');
            $table->dropForeign('fk_r1_7_1');
            $table->dropForeign('fk_r1_7_2');
        });
        Schema::table('persyaratan_permohonan', function (Blueprint $table) {
            $table->dropForeign('fk_r1_6_0');
            $table->dropForeign('fk_r1_6_1');
        });
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropForeign('fk_r1_4_0');
            $table->dropForeign('fk_r1_4_1');
            $table->dropForeign('fk_r1_4_2');
            $table->dropForeign('fk_r1_4_3');
            $table->dropForeign('fk_r1_4_4');
        });
        Schema::table('tarif_retribusi', function (Blueprint $table) {
            $table->dropForeign('fk_r1_3_0');
            $table->dropForeign('fk_r1_3_1');
            $table->dropForeign('fk_r1_3_2');
        });
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('audit_aktivitas');
        Schema::dropIfExists('serah_terima_dokumen');
        Schema::dropIfExists('serah_terima');
        Schema::dropIfExists('riwayat_disposisi');
        Schema::dropIfExists('tanda_tangan_dokumen');
        Schema::dropIfExists('versi_dokumen');
        Schema::dropIfExists('dokumen_terbit');
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('rincian_tagihan_retribusi');
        Schema::dropIfExists('tagihan_retribusi');
        Schema::dropIfExists('surat_persetujuan');
        Schema::dropIfExists('draft_surat');
        Schema::dropIfExists('template_surat');
        Schema::dropIfExists('dokumen_persyaratan');
        Schema::dropIfExists('persyaratan_permohonan');
        Schema::dropIfExists('jenis_persyaratan');
        Schema::dropIfExists('permohonan');
        Schema::dropIfExists('tarif_retribusi');
        Schema::dropIfExists('jenis_kegiatan');
        Schema::dropIfExists('lokasi');
        Schema::dropIfExists('pengguna');
    }
};
