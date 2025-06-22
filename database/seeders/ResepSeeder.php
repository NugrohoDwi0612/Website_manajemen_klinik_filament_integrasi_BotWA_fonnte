<?php

namespace Database\Seeders;

use App\Models\Resep;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ResepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Resep::create([
            'id_rekam' => 1,
            'id_obat' => 1,
            'jumlah' => 10,
            'instruksi' => 'Diminum setelah makan',
        ]);

        Resep::create([
            'id_rekam' => 2,
            'id_obat' => 2,
            'jumlah' => 5,
            'instruksi' => 'Diminum sebelum makan',
        ]);
    }
}
