<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddTourismLocations extends Command
{
    protected $signature = 'siap:tambah-lokasi-wisata';
    protected $description = 'Tambahkan lokasi wisata tanpa mengubah tarif, persyaratan, atau data lokasi lama';

    public function handle(): int
    {
        // Empat lokasi Dispar sudah tersedia. Jembatan Gantung adalah alias
        // Jeramba Gantung, sehingga tidak ditambahkan sebagai lokasi terpisah.
        $locations = [
            'Kolong Reterasi Kacang Pedang' => ['Kolong Retensi Kacang Pedang'],
            'Taman Mandara' => [],
            'Sungai Rangkui' => [],
            'Kawasan Sungai Pangkalbalam' => [],
            'Kawasan Sungai Selindung' => [],
            'Kawasan Tampuk Pinang Pura' => [],
            'Hutan Raya Tua Tunu / Rimba Daerah' => ['Hutan Raya Tua Tunu', 'Hutan Kota Tuatunu', 'Hutan Raya Tuatunu', 'Rimba Daerah'],
        ];
        $added = DB::transaction(function () use ($locations) {
            $count = 0;
            foreach ($locations as $name => $aliases) {
                if (DB::table('lokasi')->whereIn('nama_lokasi', [$name, ...$aliases])->exists()) {
                    continue;
                }
                DB::table('lokasi')->insert([
                    'nama_lokasi' => $name,
                    'status_aktif' => true,
                    // Berdasarkan daftar empat lokasi kelolaan Dispar dari pengguna.
                    // Tidak digunakan untuk menyimpulkan kewajiban rekomendasi DLH.
                    'dikelola_dispar' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
            return $count;
        });
        $this->info("Database: ".DB::connection()->getDatabaseName()."; lokasi ditambahkan: {$added}.");
        return self::SUCCESS;
    }
}
