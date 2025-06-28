<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Antrian;
use Faker\Factory as Faker;
use Carbon\Carbon;

class RekamMedisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $pasienIds = Pasien::pluck('id')->toArray();
        $dokterIds = Dokter::pluck('id')->toArray();
        // Ambil antrian yang statusnya 'selesai' dan yang sudah dipanggil
        $selesaiAntrianIds = Antrian::where('status', 'selesai')
            ->whereNotNull('waktu_dipanggil')
            ->pluck('id')
            ->toArray();

        if (empty($pasienIds) || empty($dokterIds) || empty($selesaiAntrianIds)) {
            $this->command->info('Tidak ada data pasien, dokter, atau antrian selesai yang cukup untuk RekamMedisSeeder.');
            return;
        }

        foreach ($selesaiAntrianIds as $antrianId) {
            $antrian = Antrian::find($antrianId);
            if ($antrian) {
                // Gunakan id_pasien dan id_dokter dari antrian
                $idPasien = $antrian->id_pasien;
                $idDokter = $antrian->jadwalDokter->id_dokter ?? $faker->randomElement($dokterIds); // Fallback jika tidak ada dokter di jadwal antrian

                // Pastikan pasien dan dokter valid
                if (!in_array($idPasien, $pasienIds)) {
                    $idPasien = $faker->randomElement($pasienIds); // Ambil pasien acak jika tidak valid
                }
                if (!in_array($idDokter, $dokterIds)) {
                    $idDokter = $faker->randomElement($dokterIds); // Ambil dokter acak jika tidak valid
                }

                DB::table('rekam_medis')->insert([
                    'id_pasien' => $idPasien,
                    'id_dokter' => $idDokter,
                    'id_antrian' => $antrianId,
                    'tanggal_periksa' => Carbon::parse($antrian->waktu_selesai)->toDateString(),
                    'diagnosa' => $faker->sentence(3),
                    'catatan' => $faker->paragraph(1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
