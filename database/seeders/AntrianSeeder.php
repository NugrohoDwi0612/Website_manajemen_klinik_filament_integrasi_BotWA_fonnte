<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pasien;
use App\Models\JadwalDokter;
use App\Models\Janji;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AntrianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $pasienIds = Pasien::pluck('id')->toArray();
        $jadwalDokterIds = JadwalDokter::pluck('id')->toArray();
        $janjiIds = Janji::where('status', 'terjadwal')->pluck('id')->toArray();

        if (empty($pasienIds) || empty($jadwalDokterIds)) {
            $this->command->info('Tidak ada pasien atau jadwal dokter yang tersedia untuk AntrianSeeder.');
            return;
        }

        $antrianData = [];
        $nomorAntrianPerJadwal = []; // Untuk melacak nomor antrian per jadwal dan tanggal

        // Antrian dari Janji
        foreach ($janjiIds as $janjiId) {
            $janji = Janji::find($janjiId);
            if ($janji && $janji->jadwal) {
                $tanggalJadwal = Carbon::parse($janji->jadwal->tanggal)->toDateString();
                $jadwalDokterId = $janji->id_jadwal_dokter;

                // Inisialisasi nomor antrian untuk jadwal/tanggal ini
                if (!isset($nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal])) {
                    $nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal] = 0;
                }
                $nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal]++;

                $status = $faker->randomElement(['menunggu', 'dipanggil', 'selesai']);
                $waktuMasuk = Carbon::parse($janji->waktu_janji)->subMinutes(rand(5, 15)); // Masuk sebelum janji
                $waktuDipanggil = ($status == 'dipanggil' || $status == 'selesai') ? $waktuMasuk->copy()->addMinutes(rand(5, 10)) : null;
                $waktuSelesai = ($status == 'selesai') ? $waktuDipanggil->copy()->addMinutes(rand(10, 20)) : null;

                $antrianData[] = [
                    'id_janji' => $janjiId,
                    'id_pasien' => $janji->id_pasien, // Ambil dari janji
                    'id_jadwal_dokter' => $jadwalDokterId, // Ambil dari janji
                    'nomor_antrian' => $nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal],
                    'status' => $status,
                    'waktu_masuk' => $waktuMasuk,
                    'waktu_dipanggil' => $waktuDipanggil,
                    'waktu_selesai' => $waktuSelesai,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Antrian Walk-in (tanpa Janji)
        for ($i = 0; $i < 10; $i++) { // Tambahkan 10 antrian walk-in
            $randomPasienId = $faker->randomElement($pasienIds);
            $randomJadwalId = $faker->randomElement($jadwalDokterIds);
            $jadwal = JadwalDokter::find($randomJadwalId);

            if ($jadwal) {
                $tanggalJadwal = Carbon::parse($jadwal->tanggal)->toDateString();
                $jadwalDokterId = $randomJadwalId;

                if (!isset($nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal])) {
                    $nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal] = 0;
                }
                $nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal]++;

                $status = $faker->randomElement(['menunggu', 'dipanggil', 'selesai', 'tidak_hadir']);
                $waktuMasuk = Carbon::parse($jadwal->tanggal . ' ' . $faker->time('H:i:s', $jadwal->jam_selesai)); // Random waktu masuk di hari jadwal
                $waktuDipanggil = ($status == 'dipanggil' || $status == 'selesai') ? $waktuMasuk->copy()->addMinutes(rand(5, 10)) : null;
                $waktuSelesai = ($status == 'selesai') ? $waktuDipanggil->copy()->addMinutes(rand(10, 20)) : null;

                $antrianData[] = [
                    'id_janji' => null, // Walk-in tidak punya janji
                    'id_pasien' => $randomPasienId,
                    'id_jadwal_dokter' => $jadwalDokterId,
                    'nomor_antrian' => $nomorAntrianPerJadwal[$jadwalDokterId][$tanggalJadwal],
                    'status' => $status,
                    'waktu_masuk' => $waktuMasuk,
                    'waktu_dipanggil' => $waktuDipanggil,
                    'waktu_selesai' => $waktuSelesai,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('antrians')->insert($antrianData);
    }
}
