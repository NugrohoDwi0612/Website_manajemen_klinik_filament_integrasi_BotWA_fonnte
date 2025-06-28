<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RekamMedis;
use App\Models\Obat;
use Faker\Factory as Faker;

class ResepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $rekamMedisIds = RekamMedis::pluck('id')->toArray();
        $obatIds = Obat::pluck('id')->toArray();

        if (empty($rekamMedisIds) || empty($obatIds)) {
            $this->command->info('Tidak ada rekam medis atau obat yang cukup untuk ResepSeeder.');
            return;
        }

        foreach ($rekamMedisIds as $rekamMedisId) {
            // Setiap rekam medis bisa memiliki 1-3 jenis obat
            for ($i = 0; $i < rand(1, 3); $i++) {
                $randomObatId = $faker->randomElement($obatIds);
                DB::table('reseps')->insert([
                    'id_rekam_medis' => $rekamMedisId,
                    'id_obat' => $randomObatId,
                    'jumlah' => $faker->numberBetween(1, 10),
                    'unit_satuan' => $faker->randomElement(['1x sehari', '2x sehari setelah makan']),
                    'instruksi' => $faker->sentence(5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
