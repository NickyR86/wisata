<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 8: kegiatan di lokasi di luar yang dikelola Dispar.
 * - lokasi.lainnya menandai satu lokasi bawaan "Lainnya" yang dipilih pada formulir.
 * - permohonan.lokasi_lain_nama/alamat menyimpan lokasi sebenarnya yang diisi manual.
 * Aman dijalankan berulang.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('lokasi', 'lainnya')) {
            Schema::table('lokasi', function (Blueprint $t) { $t->boolean('lainnya')->default(false); });
        }
        if (!Schema::hasColumn('permohonan', 'lokasi_lain_nama')) {
            Schema::table('permohonan', function (Blueprint $t) {
                $t->string('lokasi_lain_nama', 150)->nullable();
                $t->string('lokasi_lain_alamat', 255)->nullable();
            });
        }
        if (!DB::table('lokasi')->where('lainnya', true)->exists()) {
            DB::table('lokasi')->insert([
                'nama_lokasi' => 'Lainnya (di luar lokasi Dispar)', 'status_aktif' => true, 'dikelola_dispar' => false,
                'butuh_rekomendasi_dlh' => false, 'lainnya' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $id = DB::table('lokasi')->where('lainnya', true)->value('lokasi_id');
        if ($id && !DB::table('permohonan')->where('lokasi_id', $id)->exists()) {
            DB::table('lokasi')->where('lokasi_id', $id)->delete();
        }
        if (Schema::hasColumn('permohonan', 'lokasi_lain_nama')) {
            Schema::table('permohonan', function (Blueprint $t) { $t->dropColumn(['lokasi_lain_nama', 'lokasi_lain_alamat']); });
        }
        if (Schema::hasColumn('lokasi', 'lainnya')) {
            Schema::table('lokasi', fn(Blueprint $t) => $t->dropColumn('lainnya'));
        }
    }
};
