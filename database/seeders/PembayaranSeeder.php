<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RekamMedis;
use App\Models\Pasien;
use Faker\Factory as Faker;

class PembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $rekamMedis = RekamMedis::with(['pasien'])->get(); // Ambil rekam medis beserta pasiennya

        if ($rekamMedis->isEmpty()) {
            $this->command->info('Tidak ada rekam medis yang cukup untuk PembayaranSeeder.');
            return;
        }

        foreach ($rekamMedis as $rm) {
            $totalBiaya = $faker->numberBetween(50000, 500000); // Biaya acak

            DB::table('pembayarans')->insert([
                'id_rekam_medis' => $rm->id,
                'id_pasien' => $rm->id_pasien,
                'tanggal_pembayaran' => $faker->dateTimeBetween($rm->created_at, 'now'),
                'total_biaya' => $totalBiaya,
                'status_pembayaran' => $faker->randomElement(['lunas', 'pending', 'batal']),
                'metode_pembayaran' => $faker->randomElement(['Tunai', 'Kartu Debit/Debit', 'Transfer Bank', 'E-Wallet']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
