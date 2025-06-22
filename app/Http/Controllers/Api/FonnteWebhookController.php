<?php

namespace App\Http\Controllers\Api;

use App\Models\Pasien;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Janji;
use Illuminate\Http\Request;
use App\Models\PendaftaranSession;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\JadwalDokter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FonnteWebhookController extends Controller
{
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Hapus semua karakter non-angka
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Jika dimulai dengan '62', ganti dengan '0'
        if (substr($phoneNumber, 0, 2) === '62') {
            $phoneNumber = '0' . substr($phoneNumber, 2);
        }
        // Jika tidak dimulai dengan '0' dan merupakan nomor yang cukup panjang (misal: '8123...'), tambahkan '0' di depan
        elseif (substr($phoneNumber, 0, 1) !== '0' && strlen($phoneNumber) > 5) {
            $phoneNumber = '0' . $phoneNumber;
        }

        return $phoneNumber;
    }

    public function handle(Request $request)
    {
        $rawMessage = trim($request->input('message'));
        $message = strtolower($rawMessage);
        $sender = $request->input('pengirim') ?? $request->input('sender');

        $sender = str_replace('@c.us', '', $sender);

        Log::info('✅ Webhook Masuk:', ['message' => $message, 'sender' => $sender]);

        if (!$sender && !$message) {
            return response()->json(['status' => 'success']);
        } elseif (!$sender) {
            Log::error('❌ Nomor pengirim tidak ditemukan!');
            return response()->json(['status' => 'failed', 'reason' => 'no sender'], 400);
        } elseif (!$message) {
            Log::error('❌ Pesan tidak ditemukan!');
            return response()->json(['status' => 'failed', 'reason' => 'no message'], 400);
        }

        $responseText = $this->processMessage($sender, $rawMessage, $message);

        $this->sendReply($sender, $responseText);
        return response()->json(['status' => 'success']);
    }

    private function processMessage(string $sender, string $rawMessage, string $message): string
    {
        // Cek jika ingin mengecek antrian
        if (str_contains($message, 'antrian') || str_contains($message, 'cek antrian')) {
            preg_match_all('/\d+/', $message, $matches);
            $numbers = $matches[0] ?? [];
            return $this->handleCekAntrian($numbers);
        }

        // --- TAMBAHKAN KEMBALI BLOK INI ---
        // Cek jika ingin mengecek janji temu
        if (str_contains($message, 'cek janji') || str_contains($message, 'janji saya')) {
            return $this->handleCekJanji($sender, $rawMessage);
        }

        // Cek jika ingin membatalkan janji temu
        if (str_contains($message, 'batal janji') || str_contains($message, 'cancel janji')) {
            PendaftaranSession::updateOrCreate(
                ['nomor_wa' => $sender],
                ['tahap' => 'batal_janji_identifikasi', 'data' => []]
            );
            return "❌ Pembatalan janji temu dimulai.\n"
                . "Silakan masukkan *ID Janji Temu* yang ingin Anda batalkan, atau ketik *LIHAT JANJI* untuk melihat janji Anda.";
        }
        // ------------------------------------

        // Cek session pendaftaran atau janji temu
        $session = PendaftaranSession::where('nomor_wa', $sender)->first();

        if ($session) {
            if (str_starts_with($session->tahap, 'janji_')) {
                return $this->lanjutkanJanjiTemu($session, $rawMessage);
            } elseif (str_starts_with($session->tahap, 'batal_janji')) {
                return $this->lanjutkanPembatalanJanji($session, $rawMessage);
            } else {
                return $this->lanjutkanPendaftaran($session, $rawMessage);
            }
        }

        // Mulai pendaftaran baru
        if (str_contains($message, 'daftar pasien') || str_contains($message, 'daftar')) {
            PendaftaranSession::updateOrCreate(
                ['nomor_wa' => $sender],
                ['tahap' => 'nama', 'data' => []]
            );
            return "📝 Pendaftaran dimulai.\nSilakan masukkan *nama lengkap* pasien:";
        }

        // Mulai buat janji temu baru
        if (str_contains($message, 'buat janji') || str_contains($message, 'janji temu')) {
            PendaftaranSession::updateOrCreate(
                ['nomor_wa' => $sender],
                ['tahap' => 'janji_pasien_identifikasi', 'data' => []]
            );
            return "🗓️ Pembuatan janji temu dimulai.\n"
                . "Silakan masukkan *ID Pasien Anda* (jika sudah terdaftar) atau *nama lengkap Anda* untuk dicari:";
        }

        return $this->handleFallback();
    }

    private function lanjutkanPendaftaran(PendaftaranSession $session, string $message): string
    {
        // ... (Kode ini sama persis dengan sebelumnya, tidak ada perubahan)
        $data = $session->data ?? [];
        $tahap = $session->tahap;

        switch ($tahap) {
            case 'nama':
                $validator = Validator::make(['nama' => $message], [
                    'nama' => 'required|min:3|max:100'
                ]);

                if ($validator->fails()) {
                    return "❌ Nama harus terdiri dari 3-100 karakter. Silakan masukkan nama lengkap:";
                }

                $data['nama'] = $message;
                $session->update(['tahap' => 'tanggal_lahir', 'data' => $data]);
                return "Masukkan *tanggal lahir* (format:YYYY-MM-DD):\nContoh: 1995-04-12";

            case 'tanggal_lahir':
                $validator = Validator::make(['tanggal_lahir' => $message], [
                    'tanggal_lahir' => 'required|date_format:Y-m-d'
                ]);

                if ($validator->fails()) {
                    return "❌ Format tanggal tidak valid. Gunakan formatYYYY-MM-DD.\nContoh: 1995-04-12";
                }

                $data['tanggal_lahir'] = $message;
                $session->update(['tahap' => 'jenis_kelamin', 'data' => $data]);
                return "Masukkan *jenis kelamin*:\nL = Laki-laki\nP = Perempuan";

            case 'jenis_kelamin':
                $jk = strtoupper(trim($message));
                if (!in_array($jk, ['L', 'LAKI-LAKI', 'P', 'PEREMPUAN'])) {
                    return "❌ Jenis kelamin tidak valid.\nKetik L untuk Laki-laki atau P untuk Perempuan.";
                }

                $data['jenis_kelamin'] = in_array($jk, ['L', 'LAKI-LAKI']) ? 'L' : 'P';
                $session->update(['tahap' => 'alamat', 'data' => $data]);
                return "Masukkan *alamat lengkap* pasien:";

            case 'alamat':
                $validator = Validator::make(['alamat' => $message], [
                    'alamat' => 'required|min:10|max:255'
                ]);

                if ($validator->fails()) {
                    return "❌ Alamat terlalu pendek atau panjang. Minimal 10 karakter.";
                }

                $data['alamat'] = $message;
                $session->update(['tahap' => 'nomor_telepon', 'data' => $data]);
                return "Masukkan *nomor telepon* pasien:\nContoh: 08123456789";

            case 'nomor_telepon':
                $validator = Validator::make(['nomor_telepon' => $message], [
                    'nomor_telepon' => 'required|numeric|digits_between:9,15'
                ]);

                if ($validator->fails()) {
                    return "❌ Nomor telepon tidak valid. Harus 9-15 digit angka.\nContoh: 08123456789";
                }

                $data['nomor_telepon'] = $message;

                try {
                    $pasien = Pasien::create($data);
                    $session->delete();

                    return "✅ *Pendaftaran berhasil!*\n\n"
                        . "🆔 ID Pasien: {$pasien->id}\n"
                        . "👤 Nama: {$data['nama']}\n"
                        . "📅 Tgl Lahir: {$data['tanggal_lahir']}\n"
                        . "⚥ Jenis Kelamin: " . ($data['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') . "\n"
                        . "🏠 Alamat: {$data['alamat']}\n"
                        . "📞 Telepon: {$data['nomor_telepon']}\n\n"
                        . "Terima kasih telah mendaftar!";
                } catch (\Exception $e) {
                    Log::error('Gagal membuat pasien: ' . $e->getMessage());
                    return "❌ Gagal menyimpan data pasien. Silakan coba lagi atau hubungi admin.";
                }

            default:
                $session->delete();
                return "⚠️ Sesi tidak valid. Ketik *daftar pasien* untuk memulai ulang.";
        }
    }

    private function lanjutkanJanjiTemu(PendaftaranSession $session, string $message): string
    {
        Log::info('DEBUG: Masuk ke lanjutkanJanjiTemu. Tahap: ' . $session->tahap . ', Pesan: ' . $message);

        $data = $session->data ?? [];
        $tahap = $session->tahap;

        switch ($tahap) {
            case 'janji_pasien_identifikasi':
                $pasien = null;
                if (is_numeric($message)) {
                    $pasien = Pasien::find($message);
                } else {
                    $pasien = Pasien::where('nama', 'LIKE', '%' . $message . '%')->first();
                }

                if (!$pasien) {
                    Log::warning('DEBUG: Pasien tidak ditemukan untuk identifikasi janji. Pesan: ' . $message);
                    return "❌ Pasien tidak ditemukan dengan ID atau nama tersebut. Pastikan Anda sudah terdaftar atau masukkan nama lengkap dengan benar.\n"
                        . "Ketik 'daftar' untuk mendaftar pasien baru.";
                }

                $data['id_pasien'] = $pasien->id;
                $data['nama_pasien'] = $pasien->nama;
                $session->update(['tahap' => 'janji_tanggal', 'data' => $data]);
                Log::info('DEBUG: Tahap janji_pasien_identifikasi selesai. Pindah ke janji_tanggal.');
                return "Pasien terpilih: *{$pasien->nama}*.\n"
                    . "Silakan masukkan *tanggal janji* yang diinginkan (format:YYYY-MM-DD):\nContoh: " . Carbon::now()->addDays(rand(1, 7))->format('Y-m-d');

            case 'janji_tanggal':
                Log::info('DEBUG: Masuk ke tahap janji_tanggal. Pesan: ' . $message);
                $validator = Validator::make(['tanggal_janji' => $message], [
                    'tanggal_janji' => 'required|date_format:Y-m-d|after_or_equal:today'
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors()->all();
                    Log::warning('DEBUG: Validasi tanggal janji gagal. Errors: ' . implode(', ', $errors));
                    return "❌ Format tanggal tidak valid atau tanggal sudah lewat. Gunakan formatYYYY-MM-DD.\nContoh: " . Carbon::now()->addDay()->format('Y-m-d');
                }

                $data['tanggal_janji'] = $message;
                $session->update(['tahap' => 'janji_dokter', 'data' => $data]);
                Log::info('DEBUG: Tanggal janji valid, data diperbarui. Mencari jadwal dokter.');

                // Gunakan JadwalDokter karena itu model untuk tabel jadwal_dokters
                $jadwals = JadwalDokter::with('dokter')
                    ->where('tanggal', $message)
                    // ->where('status', 'aktif') // Tetap komen ini karena kolom 'status' tidak ada
                    ->get();

                Log::info('DEBUG: Hasil query jadwal: ' . $jadwals->count() . ' jadwal ditemukan.');

                if ($jadwals->isEmpty()) {
                    Log::info('DEBUG: Tidak ada jadwal ditemukan untuk tanggal ' . $message);
                    return "Maaf, tidak ada dokter yang memiliki jadwal pada tanggal {$message}. Silakan coba tanggal lain.";
                }

                $listDokterJadwal = "🗓️ *Jadwal Dokter Tersedia pada {$message}:*\n";
                foreach ($jadwals as $jadwal) {
                    if ($jadwal->dokter) {
                        $listDokterJadwal .= "- *dr. {$jadwal->dokter->nama}* ({$jadwal->jam_mulai}-{$jadwal->jam_selesai})\n";
                    } else {
                        Log::warning('DEBUG: Jadwal ID ' . $jadwal->id . ' tidak memiliki relasi dokter.');
                    }
                }
                $listDokterJadwal .= "\nSilakan masukkan *nama dokter* dari daftar di atas yang ingin Anda temui:";

                Log::info('DEBUG: List jadwal dokter berhasil dibuat. Mengirim balasan.');
                return $listDokterJadwal;

            case 'janji_dokter': // Disini kita akan mencari Jadwal spesifik
                Log::info('DEBUG: Masuk ke tahap janji_dokter. Pesan: ' . $message);
                $tanggal_janji = $data['tanggal_janji'];

                // Cari dokter berdasarkan nama
                $dokter = Dokter::where('nama', 'LIKE', '%' . $message . '%')->first();
                if (!$dokter) {
                    Log::warning('DEBUG: Dokter tidak ditemukan untuk nama: ' . $message);
                    return "❌ Dokter tidak ditemukan. Silakan masukkan nama dokter yang valid dari daftar sebelumnya.";
                }
                Log::info('DEBUG: Dokter ditemukan: ' . $dokter->nama);

                // Cari jadwal spesifik untuk dokter tersebut pada tanggal janji
                // Gunakan JadwalDokter karena itu model untuk tabel jadwal_dokters
                $jadwal = JadwalDokter::where('id_dokter', $dokter->id) // Pastikan ini 'dokter_id' sesuai DB Anda
                    ->where('tanggal', $tanggal_janji)
                    // ->where('status', 'aktif') // Tetap komen ini
                    ->first();

                if (!$jadwal) {
                    Log::warning('DEBUG: Tidak ada jadwal untuk dokter ' . $dokter->nama . ' pada tanggal ' . $tanggal_janji);
                    return "❌ Dokter *{$dokter->nama}* tidak memiliki jadwal pada tanggal {$tanggal_janji}. Silakan pilih dokter atau tanggal lain.";
                }
                Log::info('DEBUG: Jadwal ditemukan: ID ' . $jadwal->id . ' untuk dokter ' . $dokter->nama . ' pada ' . $tanggal_janji);

                $data['id_dokter'] = $dokter->id;
                $data['nama_dokter'] = $dokter->nama;
                $data['id_jadwal_dokter'] = $jadwal->id; // <-- Kunci ini harus konsisten!
                $data['jam_mulai_jadwal'] = $jadwal->jam_mulai;
                $data['jam_selesai_jadwal'] = $jadwal->jam_selesai;

                $session->update(['tahap' => 'janji_keluhan', 'data' => $data]);
                Log::info('DEBUG: Tahap janji_dokter selesai. Pindah ke janji_keluhan.');
                return "Anda akan membuat janji dengan *dr. {$dokter->nama}* pada tanggal *{$tanggal_janji}* pukul *{$jadwal->jam_mulai} - {$jadwal->jam_selesai}*.\n"
                    . "Silakan masukkan *keluhan singkat* atau alasan janji temu:";

            case 'janji_keluhan':
                Log::info('DEBUG: Masuk ke tahap janji_keluhan. Pesan: ' . $message);
                $validator = Validator::make(['keluhan' => $message], [
                    'keluhan' => 'required|min:5|max:255'
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors()->all();
                    Log::warning('DEBUG: Validasi keluhan gagal. Errors: ' . implode(', ', $errors));
                    return "❌ Keluhan terlalu pendek atau panjang. Minimal 5 karakter.";
                }

                $data['keluhan'] = $message;
                $session->update(['tahap' => 'janji_konfirmasi', 'data' => $data]);
                Log::info('DEBUG: Tahap janji_keluhan selesai. Pindah ke janji_konfirmasi.');

                $konfirmasi = "📝 *Konfirmasi Janji Temu:*\n"
                    . "Pasien: *{$data['nama_pasien']}*\n"
                    . "Dokter: *{$data['nama_dokter']}*\n"
                    . "Tanggal: *{$data['tanggal_janji']}*\n"
                    . "Jam: *{$data['jam_mulai_jadwal']} - {$data['jam_selesai_jadwal']}*\n"
                    . "Keluhan: *{$data['keluhan']}*\n\n"
                    . "Ketik *YA* untuk konfirmasi atau *BATAL* untuk membatalkan.";
                return $konfirmasi;

            case 'janji_konfirmasi':
                Log::info('DEBUG: Masuk ke tahap janji_konfirmasi. Pesan: ' . $message);
                $konfirmasi = strtolower(trim($message));
                if ($konfirmasi === 'ya') {
                    try {
                        Janji::create([
                            'id_pasien' => $data['id_pasien'],
                            'id_jadwal_dokter' => $data['id_jadwal_dokter'], // <-- GUNAKAN KUNCI YANG KONSISTEN!
                            'keluhan' => $data['keluhan'],
                            'status' => 'menunggu_konfirmasi',
                        ]);
                        $session->delete();
                        Log::info('DEBUG: Janji temu berhasil dibuat dan sesi dihapus.');
                        return "✅ *Janji temu berhasil dibuat!*\n"
                            . "Detail janji temu Anda telah dicatat. Mohon tunggu konfirmasi dari pihak klinik.";
                    } catch (\Exception $e) {
                        Log::error('DEBUG: Gagal membuat janji temu: ' . $e->getMessage());
                        return "❌ Gagal menyimpan janji temu. Silakan coba lagi atau hubungi admin.";
                    }
                } elseif ($konfirmasi === 'batal') {
                    $session->delete();
                    Log::info('DEBUG: Pembuatan janji temu dibatalkan.');
                    return "❌ Pembuatan janji temu dibatalkan.";
                } else {
                    Log::warning('DEBUG: Pilihan konfirmasi tidak valid: ' . $message);
                    return "Pilihan tidak valid. Ketik *YA* untuk konfirmasi atau *BATAL* untuk membatalkan.";
                }

            default:
                $session->delete();
                Log::warning('DEBUG: Sesi janji temu tidak valid. Tahap: ' . $tahap);
                return "⚠️ Sesi tidak valid. Ketik *buat janji* untuk memulai ulang.";
        }
    }

    private function lanjutkanPembatalanJanji(PendaftaranSession $session, string $message): string
    {
        Log::info('DEBUG: Masuk ke lanjutkanPembatalanJanji. Tahap: ' . $session->tahap . ', Pesan: ' . $message);

        $data = $session->data ?? [];
        $tahap = $session->tahap;
        $normalizedSessionWa = $this->normalizePhoneNumber($session->nomor_wa);
        $pasien = Pasien::where('nomor_telepon', $normalizedSessionWa)->first();

        if (!$pasien) {
            $session->delete();
            return "Maaf, kami tidak dapat menemukan data pasien terkait dengan nomor Anda. Silakan coba lagi atau hubungi admin.";
        }

        switch ($tahap) {
            case 'batal_janji_identifikasi':
                $janji = null;
                if (strtolower($message) === 'lihat janji') {
                    // Jika user ketik 'lihat janji', tampilkan daftar janji aktif
                    $janjisAktif = Janji::with(['jadwal.dokter'])
                        ->where('id_pasien', $pasien->id)
                        ->whereIn('status', ['menunggu_konfirmasi', 'terjadwal']) // Hanya tampilkan yang aktif/bisa dibatalkan
                        ->orderBy('jadwal_dokters.tanggal', 'asc')
                        ->orderBy('jadwal_dokters.jam_mulai', 'asc')
                        ->join('jadwal_dokters', 'janji.id_jadwal_dokter', '=', 'jadwal_dokters.id')
                        ->select('janji.*')
                        ->get();

                    if ($janjisAktif->isEmpty()) {
                        $session->delete();
                        return "Anda tidak memiliki janji temu aktif yang bisa dibatalkan.";
                    }

                    $listJanji = "📋 *Daftar Janji Temu Aktif Anda:*\n";
                    foreach ($janjisAktif as $j) {
                        $tanggal = $j->jadwal ? Carbon::parse($j->jadwal->tanggal)->format('d F Y') : 'N/A';
                        $jam = $j->jadwal ? "{$j->jadwal->jam_mulai} - {$j->jadwal->jam_selesai}" : 'N/A';
                        $dokterNama = $j->jadwal && $j->jadwal->dokter ? $j->jadwal->dokter->nama : 'N/A';
                        $listJanji .= "\nID: *{$j->id}*\nDokter: dr. {$dokterNama}\nTanggal: {$tanggal}\nJam: {$jam}\nKeluhan: {$j->keluhan}\n";
                    }
                    $listJanji .= "\nSilakan masukkan *ID Janji Temu* yang ingin Anda batalkan.";
                    return $listJanji;
                } elseif (is_numeric($message)) {
                    $janji = Janji::with(['jadwal.dokter'])
                        ->where('id', $message)
                        ->where('id_pasien', $pasien->id) // Pastikan janji ini milik pasien yang bersangkutan
                        ->whereIn('status', ['menunggu_konfirmasi', 'terjadwal']) // Hanya yang bisa dibatalkan
                        ->first();

                    if (!$janji) {
                        return "❌ Janji temu dengan ID *{$message}* tidak ditemukan atau tidak dapat dibatalkan (mungkin sudah selesai/dibatalkan oleh admin).\n"
                            . "Silakan masukkan ID yang benar atau ketik *LIHAT JANJI* untuk daftar janji Anda.";
                    }
                    $data['id_janji'] = $janji->id;
                    $data['janji_details'] = [
                        'tanggal' => $janji->jadwal ? Carbon::parse($janji->jadwal->tanggal)->format('d F Y') : 'N/A',
                        'jam' => $janji->jadwal ? "{$janji->jadwal->jam_mulai} - {$janji->jadwal->jam_selesai}" : 'N/A',
                        'dokter' => $janji->jadwal && $janji->jadwal->dokter ? $janji->jadwal->dokter->nama : 'N/A',
                        'keluhan' => $janji->keluhan,
                    ];
                    $session->update(['tahap' => 'batal_janji_konfirmasi', 'data' => $data]);

                    $konfirmasiPesan = "Anda akan membatalkan janji temu ini:\n"
                        . "ID Janji: *{$janji->id}*\n"
                        . "Dokter: *dr. {$data['janji_details']['dokter']}*\n"
                        . "Tanggal: *{$data['janji_details']['tanggal']}*\n"
                        . "Jam: *{$data['janji_details']['jam']}*\n"
                        . "Keluhan: *{$data['janji_details']['keluhan']}*\n\n"
                        . "Ketik *YA* untuk konfirmasi pembatalan atau *TIDAK* untuk membatalkan proses.";
                    return $konfirmasiPesan;
                } else {
                    return "❌ Masukkan *ID Janji Temu* yang valid, atau ketik *LIHAT JANJI* untuk melihat daftar janji Anda.";
                }

            case 'batal_janji_konfirmasi':
                $konfirmasi = strtolower(trim($message));
                if ($konfirmasi === 'ya') {
                    try {
                        $janji = Janji::find($data['id_janji']);
                        if ($janji) {
                            $janji->status = 'batal'; // Ubah status ke 'dibatalkan'
                            $janji->save();
                            $session->delete();
                            return "✅ Janji temu ID *{$data['id_janji']}* dengan dr. *{$data['janji_details']['dokter']}* pada tanggal *{$data['janji_details']['tanggal']}* telah *DIBATALKAN*.";
                        } else {
                            $session->delete();
                            return "❌ Gagal membatalkan janji temu. Janji tidak ditemukan. Silakan coba lagi.";
                        }
                    } catch (\Exception $e) {
                        Log::error('Gagal membatalkan janji temu: ' . $e->getMessage());
                        $session->delete();
                        return "❌ Terjadi kesalahan saat membatalkan janji temu. Silakan coba lagi atau hubungi admin.";
                    }
                } elseif ($konfirmasi === 'tidak') {
                    $session->delete();
                    return "Pembatalan janji temu dibatalkan.";
                } else {
                    return "Pilihan tidak valid. Ketik *YA* untuk konfirmasi pembatalan atau *TIDAK* untuk membatalkan proses.";
                }

            default:
                $session->delete();
                return "⚠️ Sesi pembatalan janji tidak valid. Ketik *batal janji* untuk memulai ulang.";
        }
    }

    private function handleCekAntrian(array $numbers): string
    {
        // ... (Kode ini sama persis dengan sebelumnya, tidak ada perubahan)
        if (empty($numbers)) {
            return "⚠️ Format tidak valid. Contoh:\n- antrian 1\n- cek antrian 1 dan 2";
        }

        $numbers = array_unique($numbers);
        $response = '';

        foreach ($numbers as $nomor) {
            $antrian = Antrian::with(['pasien', 'jadwal.dokter']) // Pastikan 'jadwal' relasi di Antrian menunjuk ke JadwalDokter
                ->where('nomor_antrian', $nomor)
                ->first();

            if ($antrian) {
                $response .= "🩺 *Antrian #$nomor*\n"
                    . "Nama: {$antrian->pasien->nama}\n"
                    . "Dokter: " . ($antrian->jadwal && $antrian->jadwal->dokter ? $antrian->jadwal->dokter->nama : 'N/A') . "\n"
                    . "Tanggal: " . ($antrian->jadwal ? $antrian->jadwal->tanggal : 'N/A') . "\n"
                    . "Jam: " . ($antrian->jadwal ? "{$antrian->jadwal->jam_mulai} - {$antrian->jadwal->jam_selesai}" : 'N/A') . "\n"
                    . "Status: " . $this->formatStatusAntrian($antrian->status) . "\n\n";
            } else {
                $response .= "❌ Antrian dengan nomor $nomor tidak ditemukan.\n\n";
            }
        }

        return $response ?: "❌ Tidak ada data antrian yang ditemukan.";
    }

    // --- FUNGSI BARU UNTUK CEK JANJI TEMU ---
    private function handleCekJanji(string $sender, string $rawMessage): string
    {
        $messageLower = strtolower($rawMessage);
        $pasien = null;
        $response = '';

        // Kasus "cek janji saya"
        if (str_contains($messageLower, 'janji saya')) {
            $normalizedSender = $this->normalizePhoneNumber($sender);
            $pasien = Pasien::where('nomor_telepon', $normalizedSender)->first();
            if (!$pasien) {
                return "Maaf, nomor WhatsApp Anda ({$sender}) belum terdaftar sebagai pasien. Silakan daftar dulu dengan mengetik *daftar pasien* atau cek janji dengan ID pasien Anda.";
            }
        }
        // Kasus "cek janji <ID_PASIEN>"
        else {
            preg_match('/\d+/', $rawMessage, $matches);
            $pasienId = $matches[0] ?? null;

            if (!$pasienId) {
                return "⚠️ Format tidak valid. Gunakan:\n- *cek janji <ID_PASIEN>*\n- *cek janji saya*";
            }
            $pasien = Pasien::find($pasienId);

            if (!$pasien) {
                return "❌ Pasien dengan ID *{$pasienId}* tidak ditemukan. Pastikan ID pasien benar.";
            }
        }

        // Jika pasien ditemukan, ambil janji temunya
        if ($pasien) {
            // Gunakan nama relasi 'jadwal' sesuai dengan definisi di model Janji Anda
            $janjis = Janji::with(['jadwal.dokter'])
                ->where('id_pasien', $pasien->id)
                // Prioritaskan tanggal hari ini atau mendatang
                ->orderByRaw('CASE WHEN jadwal_dokters.tanggal >= CURDATE() THEN 0 ELSE 1 END')
                ->orderBy('jadwal_dokters.tanggal', 'asc')
                ->orderBy('jadwal_dokters.jam_mulai', 'asc')
                ->join('jadwal_dokters', 'janji.id_jadwal_dokter', '=', 'jadwal_dokters.id')
                ->select('janji.*') // Pilih kembali semua kolom dari tabel 'janji'
                ->get();

            if ($janjis->isEmpty()) {
                return "Pasien *{$pasien->nama}* (ID: {$pasien->id}) belum memiliki janji temu yang terjadwal.";
            }

            $response .= "🗓️ *Daftar Janji Temu {$pasien->nama} (ID: {$pasien->id}):*\n\n";
            foreach ($janjis as $janji) {
                $statusFormatted = $this->formatStatusJanji($janji->status);
                // --- PERUBAHAN DI SINI: Gunakan $janji->jadwal ---
                $tanggalJanji = $janji->jadwal ? Carbon::parse($janji->jadwal->tanggal)->format('d F Y') : 'Tanggal tidak diketahui';
                $jamJanji = $janji->jadwal ? "Pukul {$janji->jadwal->jam_mulai} - {$janji->jadwal->jam_selesai}" : 'Jam tidak diketahui';
                $dokterNama = $janji->jadwal && $janji->jadwal->dokter ? $janji->jadwal->dokter->nama : 'Dokter tidak diketahui';
                // --------------------------------------------------

                $response .= "--- Janji ID: {$janji->id} ---\n"
                    . "Dokter: *dr. {$dokterNama}*\n"
                    . "Tanggal: *{$tanggalJanji}*\n"
                    . "Jam: *{$jamJanji}*\n"
                    . "Keluhan: {$janji->keluhan}\n"
                    . "Status: *{$statusFormatted}*\n\n";
            }
        } else {
            return "Terjadi kesalahan dalam menemukan data pasien Anda. Mohon coba lagi.";
        }

        return $response;
    }

    private function formatStatusJanji(string $status): string
    {
        $statusMap = [
            'menunggu_konfirmasi' => '⏳ Menunggu Konfirmasi',
            'terjadwal' => '✅ Terjadwal',
            'selesai' => '🏁 Selesai',
            'batal' => '❌ Dibatalkan'
        ];
        return $statusMap[strtolower($status)] ?? $status;
    }

    private function formatStatusAntrian(string $status): string
    {
        $statusMap = [
            'menunggu' => '⏳ Menunggu',
            'dipanggil' => '🔊 Dipanggil',
            'dilayani' => '🩺 Sedang Dilayani',
            'selesai' => '✅ Selesai',
            'batal' => '❌ Dibatalkan'
        ];

        return $statusMap[strtolower($status)] ?? $status;
    }

    private function handleFallback(): string
    {
        return "⚠️ Maaf, saya tidak mengenali perintah Anda.\n\n"
            . "📋 *Perintah yang tersedia:*\n"
            . "- *daftar pasien* - Mulai pendaftaran pasien baru\n"
            . "- *buat janji* - Buat janji temu dengan dokter\n"
            . "- *cek janji <ID_PASIEN>* - Cek janji temu berdasarkan ID pasien\n"
            . "- *cek janji saya* - Cek janji temu Anda (berdasarkan nomor WA Anda)\n" // Perbarui pesan ini
            . "- *antrian [nomor]* - Cek status antrian\n"
            . "- *cek antrian [nomor]* - Cek status antrian\n\n"
            . "Contoh:\n"
            . "- `daftar pasien`\n"
            . "- `buat janji`\n"
            . "- `cek janji 123`\n"
            . "- `cek janji saya`\n"
            . "- `batal janji`\n"
            . "- `antrian 5`\n";
    }

    private function sendReply(string $sender, string $message): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => env('FONNTE_API_KEY'),
            ])->timeout(10)->post('https://api.fonnte.com/send', [
                'target' => $sender,
                'message' => $message,
            ]);

            Log::info('Response dari Fonnte:', $response->json());
        } catch (\Exception $e) {
            Log::error('Gagal mengirim pesan ke Fonnte: ' . $e->getMessage());
        }
    }
}
