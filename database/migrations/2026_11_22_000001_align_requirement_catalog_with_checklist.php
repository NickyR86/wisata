<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 6: katalog persyaratan disesuaikan dengan lembar periksa kelengkapan dinas.
 * - REQ-1..REQ-4 diberi nama sesuai formulir.
 * - REQ-5 (dokumen pendukung keringanan) bersyarat: jika_keringanan.
 * - REQ-6 (fotokopi jasa impresariat/promotor dari PTSP) bersyarat: jika_impresariat.
 * Aman dijalankan berulang.
 */
return new class extends Migration {
    public function up(): void
    {
        $names = [
            'REQ-1' => 'Surat permohonan persetujuan menyelenggarakan acara',
            'REQ-2' => 'Proposal Kegiatan/Rundown',
            'REQ-3' => 'Fotokopi KTP pemohon',
            'REQ-4' => 'Surat Rekomendasi dari Dinas Lingkungan Hidup',
        ];
        foreach ($names as $kode => $nama) {
            DB::table('jenis_persyaratan')->where('kode', $kode)->update(['nama' => $nama, 'updated_at' => now()]);
        }
        DB::table('jenis_persyaratan')->where('kode', 'REQ-5')->update(['kondisi_wajib' => 'jika_keringanan', 'updated_at' => now()]);
        if (!DB::table('jenis_persyaratan')->where('kode', 'REQ-6')->exists()) {
            DB::table('jenis_persyaratan')->insert([
                'kode' => 'REQ-6', 'nama' => 'Fotokopi jasa impresariat/promotor dari PTSP (jika menggunakan jasa)',
                'kondisi_wajib' => 'jika_impresariat', 'aktif' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('jenis_persyaratan')->where('kode', 'REQ-6')->delete();
        DB::table('jenis_persyaratan')->where('kode', 'REQ-5')->update(['kondisi_wajib' => 'selalu', 'updated_at' => now()]);
    }
};
