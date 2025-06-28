<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('obats')->insert([
            ['nama_obat' => 'Paracetamol 500mg', 'stok' => 500, 'harga' => 2000, 'tanggal_kadaluarsa' => '2025-12-10', 'kategori_obat' => 'Panas', 'created_at' => now(), 'updated_at' => now()],
            ['nama_obat' => 'Amoxicillin 250mg', 'stok' => 300, 'harga' => 3500,  'tanggal_kadaluarsa' => '2026-12-10', 'kategori_obat' => 'Panas', 'created_at' => now(), 'updated_at' => now()],
            ['nama_obat' => 'Vitamin C 100mg', 'stok' => 1000, 'harga' => 1500,  'tanggal_kadaluarsa' => '2025-12-10', 'kategori_obat' => 'Panas', 'created_at' => now(), 'updated_at' => now()],
            ['nama_obat' => 'Antasida Doen', 'stok' => 400, 'harga' => 2500,  'tanggal_kadaluarsa' => '2023-12-10', 'kategori_obat' => 'Panas', 'created_at' => now(), 'updated_at' => now()],
            ['nama_obat' => 'Sirup Batuk Anak', 'stok' => 150, 'harga' => 15000,  'tanggal_kadaluarsa' => '2025-12-10', 'kategori_obat' => 'Panas', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
