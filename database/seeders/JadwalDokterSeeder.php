<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Dokter; // Import Model Dokter

class JadwalDokterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dokterIds = Dokter::pluck('id')->toArray(); // Ambil semua ID dokter

        if (empty($dokterIds)) {
            $this->command->info('Tidak ada dokter yang ditemukan, lewati JadwalDokterSeeder.');
            return;
        }

        $startTimes = ['08:00:00', '10:00:00', '13:00:00', '15:00:00'];
        $durations = ['02:00:00', '03:00:00'];

        foreach ($dokterIds as $dokterId) {
            // Buat jadwal untuk beberapa hari ke depan
            for ($i = 0; $i < 5; $i++) { // 5 hari ke depan
                $date = Carbon::today()->addDays($i);

                // Buat 1-2 jadwal per hari untuk setiap dokter
                for ($j = 0; $j < rand(1, 2); $j++) {
                    $startTime = $startTimes[array_rand($startTimes)];
                    $durationString = $durations[array_rand($durations)]; // Ambil string durasi

                    // KOREKSI DI SINI: Konversi hasil explode menjadi integer
                    $durationHours = (int) explode(':', $durationString)[0];

                    $endTime = Carbon::parse($startTime)->addHours($durationHours)->format('H:i:s');

                    DB::table('jadwal_dokters')->insert([
                        'id_dokter' => $dokterId,
                        'tanggal' => $date->toDateString(),
                        'jam_mulai' => $startTime,
                        'jam_selesai' => $endTime,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
