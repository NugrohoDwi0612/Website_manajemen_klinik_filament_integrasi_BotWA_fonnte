<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;

class DokterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $poliklinikIds = DB::table('polikliniks')->pluck('id')->toArray(); // Ambil semua ID poliklinik

        // Ambil ID user Budi Santoso yang sudah dibuat di UserSeeder
        $poliUmumId = DB::table('polikliniks')->where('nama_poliklinik', 'Poli Umum')->value('id');

        DB::table('dokters')->insert([
            [
                'id_poliklinik' => $poliUmumId, // Kaitkan dengan Poli Umum
                'nama' => 'Dr. Budi Santoso',
                'spesialisasi' => 'Spesialisasi 1',
                'nomor_telepon' => $faker->phoneNumber,
                'email' => 'budi.santoso@dokter.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Tambahkan dokter lain secara acak
            [
                'id_poliklinik' => $faker->randomElement($poliklinikIds), // Kaitkan dengan poliklinik acak
                'nama' => 'Dr. ' . $faker->firstNameMale . ' ' . $faker->lastName,
                'spesialisasi' => $faker->randomElement(['Spesialisasi 1', 'Spesialisasi 2', 'Spesialisasi 3']),
                'nomor_telepon' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_poliklinik' => $faker->randomElement($poliklinikIds), // Kaitkan dengan poliklinik acak
                'nama' => 'Dr. ' . $faker->firstNameFemale . ' ' . $faker->lastName,
                'spesialisasi' => $faker->randomElement(['Spesialisasi 1', 'Spesialisasi 2', 'Spesialisasi 3']),
                'nomor_telepon' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
