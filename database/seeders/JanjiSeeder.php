<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Pasien;
use Faker\Factory as Faker;
use App\Models\JadwalDokter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JanjiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $pasienIds = Pasien::pluck('id')->toArray();
        // Ambil jadwal dokter yang belum lewat hari ini atau di masa depan
        $jadwalDokterIds = JadwalDokter::where('tanggal', '>=', Carbon::today()->toDateString())
            ->pluck('id')->toArray();

        if (empty($pasienIds) || empty($jadwalDokterIds)) {
            $this->command->info('Tidak ada pasien atau jadwal dokter yang tersedia untuk JanjiSeeder.');
            return;
        }

        for ($i = 0; $i < 15; $i++) { // Membuat 15 janji temu
            $randomPasienId = $faker->randomElement($pasienIds);
            $randomJadwalId = $faker->randomElement($jadwalDokterIds);

            // Ambil detail jadwal untuk menentukan waktu janji
            $jadwal = JadwalDokter::find($randomJadwalId);

            DB::table('janji')->insert([
                'id_pasien' => $randomPasienId,
                'id_jadwal_dokter' => $randomJadwalId,
                'status' => $faker->randomElement(['menunggu_konfirmasi', 'terjadwal', 'selesai']),
                'keluhan' => $faker->sentence(5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
