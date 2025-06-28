<?php

namespace Database\Seeders;

use App\Models\Pasien;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;

class PasienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Menggunakan Faker Indonesia

        for ($i = 0; $i < 20; $i++) { // Membuat 20 data pasien
            DB::table('pasiens')->insert([
                'nama' => $faker->name,
                'tanggal_lahir' => $faker->dateTimeBetween('-60 years', '-1 year')->format('Y-m-d'),
                'jenis_kelamin' => $faker->randomElement(['L', 'P']),
                'alamat' => $faker->address,
                'nomor_telepon' => $faker->phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
