<?php

namespace Database\Seeders;

use App\Models\Obat;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ObatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Obat::create([
            'nama_obat' => 'Paracetamol',
            'stok' => 100,
            'harga' => 5000,
        ]);

        Obat::create([
            'nama_obat' => 'Amoxicillin',
            'stok' => 50,
            'harga' => 10000,
        ]);
    }
}
