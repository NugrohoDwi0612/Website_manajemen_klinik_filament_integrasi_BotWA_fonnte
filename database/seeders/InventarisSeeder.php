<?php

namespace Database\Seeders;

use App\Models\Inventaris;
use Illuminate\Database\Seeder;

class InventarisSeeder extends Seeder
{
    public function run()
    {
        Inventaris::create([
            'nama_barang' => 'Meja Dokter',
            'jumlah' => 5,
            'kondisi' => 'Baik',
            'tanggal_pembelian' => '2023-01-01',
            'keterangan' => 'Meja untuk ruang dokter',
            'id_poliklinik' => 1, // Relasikan ke Poliklinik Umum
        ]);

        Inventaris::create([
            'nama_barang' => 'Kursi Tunggu',
            'jumlah' => 20,
            'kondisi' => 'Baik',
            'tanggal_pembelian' => '2023-02-15',
            'keterangan' => 'Kursi untuk ruang tunggu pasien',
            'id_poliklinik' => 2, // Relasikan ke Poliklinik Gigi
        ]);
    }
}
