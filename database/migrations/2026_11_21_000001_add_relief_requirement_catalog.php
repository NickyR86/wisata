<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 5: dokumen pendukung bebas biaya / keringanan disimpan sebagai persyaratan REQ-5.
 * Aman dijalankan berulang.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!DB::table('jenis_persyaratan')->where('kode', 'REQ-5')->exists()) {
            DB::table('jenis_persyaratan')->insert([
                'kode' => 'REQ-5', 'nama' => 'Dokumen pendukung keringanan', 'kondisi_wajib' => 'selalu', 'aktif' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('jenis_persyaratan')->where('kode', 'REQ-5')->delete();
    }
};
