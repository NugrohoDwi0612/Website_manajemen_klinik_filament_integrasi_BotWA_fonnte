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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => '$2y$12$lDFdpmvQup71QD2XyM4cfuNkl7zeUSxAV5QbD2PNhc3RmHEMXCESq',
        ]);

        $this->call([
            PasienSeeder::class,
            RekamMedisSeeder::class,
            ObatSeeder::class,
            ResepSeeder::class,
            InventarisSeeder::class,
            PoliklinikSeeder::class,
        ]);
    }
}
