<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 3 (Data Master):
 * 1. Penanda "butuh rekomendasi DLH" pindah dari daftar nama di config ke kolom lokasi,
 *    agar admin dapat mengaturnya dari menu Data Master.
 * 2. Katalog dasar persyaratan (REQ-1 sampai REQ-4) diisi bila belum ada, karena pendaftaran
 *    permohonan mensyaratkannya. Isinya bukan data demo dan aman dijalankan berulang.
 */
return new class extends Migration {
    private function norm(string $name): string { return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name))); }

    public function up(): void
    {
        if (!Schema::hasColumn('lokasi', 'butuh_rekomendasi_dlh')) {
            Schema::table('lokasi', function (Blueprint $t) { $t->boolean('butuh_rekomendasi_dlh')->default(false); });
        }
        $dlh = array_map(fn($n) => $this->norm($n), (array) config('persyaratan.dlh_locations', []));
        foreach (DB::table('lokasi')->get() as $lokasi) {
            if (in_array($this->norm((string) $lokasi->nama_lokasi), $dlh, true)) {
                DB::table('lokasi')->where('lokasi_id', $lokasi->lokasi_id)->update(['butuh_rekomendasi_dlh' => true]);
            }
        }
        foreach (['Surat permohonan', 'Proposal / rundown', 'Identitas penanggung jawab', 'Rekomendasi DLH'] as $i => $nama) {
            if (!DB::table('jenis_persyaratan')->where('kode', 'REQ-' . ($i + 1))->exists()) {
                DB::table('jenis_persyaratan')->insert([
                    'kode' => 'REQ-' . ($i + 1), 'nama' => $nama, 'kondisi_wajib' => 'selalu', 'aktif' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lokasi', 'butuh_rekomendasi_dlh')) {
            Schema::table('lokasi', fn(Blueprint $t) => $t->dropColumn('butuh_rekomendasi_dlh'));
        }
    }
};
