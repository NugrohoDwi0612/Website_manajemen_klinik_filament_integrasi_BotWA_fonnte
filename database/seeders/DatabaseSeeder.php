<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                // Tabel Tanpa Dependensi atau Dependensi Awal
                UserSeeder::class,
                PasienSeeder::class,
                PoliklinikSeeder::class, // PENTING: Panggil PoliklinikSeeder sebelum DokterSeeder
                ObatSeeder::class,

                // Tabel dengan Dependensi Menengah
                DokterSeeder::class, // Sekarang Dokter bergantung pada Poliklinik dan Spesialisasi
                JadwalDokterSeeder::class,
                JanjiSeeder::class,

                // Tabel dengan Dependensi Lanjut
                AntrianSeeder::class,
                RekamMedisSeeder::class,

                // Tabel dengan Dependensi Akhir
                ResepSeeder::class,
                PembayaranSeeder::class,
            ]
        );
    }
}
