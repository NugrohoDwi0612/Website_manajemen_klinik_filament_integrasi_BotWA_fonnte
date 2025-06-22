<?php

namespace Database\Seeders;

use App\Models\RekamMedis;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RekamMedisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RekamMedis::create([
            'id_pasien' => 1,
            'id_dokter' => 1,
            'diagnosa' => 'Flu',
            'resep' => 'Paracetamol 3x1',
            'catatan' => 'Istirahat yang cukup',
            'tanggal' => '2023-10-15',
        ]);

        RekamMedis::create([
            'id_pasien' => 2,
            'id_dokter' => 2,
            'diagnosa' => 'Sakit Gigi',
            'resep' => 'Antibiotik 2x1',
            'catatan' => 'Hindari makanan manis',
            'tanggal' => '2023-10-16',
        ]);
    }
}