<?php

namespace Database\Seeders;

use App\Models\Pasien;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PasienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pasien::create([
            'nama' => 'John Doe',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Contoh No. 123',
            'nomor_telepon' => '081234567890',
            'email' => 'john.doe@example.com',
        ]);

        Pasien::create([
            'nama' => 'Jane Smith',
            'tanggal_lahir' => '1985-05-15',
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Contoh No. 456',
            'nomor_telepon' => '081298765432',
            'email' => 'jane.smith@example.com',
        ]);
    }
}