<?php

namespace Database\Seeders;

use App\Models\Poliklinik;
use Illuminate\Database\Seeder;

class PoliklinikSeeder extends Seeder
{
    public function run()
    {
        Poliklinik::create([
            'nama_poliklinik' => 'Poli Umum',
            'deskripsi' => 'Pelayanan kesehatan umum',
        ]);

        Poliklinik::create([
            'nama_poliklinik' => 'Poli Gigi',
            'deskripsi' => 'Pelayanan kesehatan gigi dan mulut',
        ]);

        Poliklinik::create([
            'nama_poliklinik' => 'Poli Anak',
            'deskripsi' => 'Pelayanan kesehatan anak',
        ]);

        Poliklinik::create([
            'nama_poliklinik' => 'Poli Penyakit Dalam',
            'deskripsi' => 'Pelayanan kesehatan penyakit dalam',
        ]);
    }
}
