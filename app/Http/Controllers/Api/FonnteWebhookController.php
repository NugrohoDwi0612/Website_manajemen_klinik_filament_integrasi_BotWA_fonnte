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
        // Cek jika ingin mengecek jadwal dokter hari ini (BARU DITAMBAH)
        if (str_contains($message, 'cek jadwal dokter hari ini') || str_contains($message, 'jadwal dokter hari ini')) {
            return $this->handleCekJadwalDokterHariIni();
        }

        // Cek jika ingin mengecek antrian
        if (str_contains($message, 'antrian') || str_contains($message, 'cek antrian')) {
            if (str_contains($message, 'antrian saya') || str_contains($message, 'cek antrian saya')) {
                return $this->handleCekAntrianBySender($sender);
            } elseif (str_contains($message, 'antrian menunggu') || str_contains($message, 'cek antrian menunggu')) {
                return $this->handleCekAntrianMenunggu();
            } elseif (str_contains($message, 'antrian hari ini') || str_contains($message, 'cek antrian hari ini')) {
                return $this->handleCekAntrianHariIni();
            } else {
                return $this->handleFallback();
            }
        }

        // Cek jika ingin mengecek janji temu
        if (str_contains($message, 'cek janji') || str_contains($message, 'janji saya')) {
            return $this->handleCekJanjiBySender($sender);
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

                $jadwals = JadwalDokter::with('dokter')
                    ->where('tanggal', $message)
                    ->get();

                Log::info('DEBUG: Hasil query jadwal: ' . $jadwals->count() . ' jadwal ditemukan.');

                if ($jadwals->isEmpty()) {
                    Log::info('DEBUG: Tidak ada jadwal ditemukan untuk tanggal ' . $message);
                    return "Maaf, tidak ada dokter yang memiliki jadwal pada tanggal {$message}. Silakan coba tanggal lain.";
                }

                $listDokterJadwal = "🗓️ *Jadwal Dokter Tersedia pada {$message}:*\n";
                foreach ($jadwals as $jadwal) {
                    if ($jadwal->dokter) {
                        $listDokterJadwal .= "- *Dr. {$jadwal->dokter->nama}* ({$jadwal->jam_mulai}-{$jadwal->jam_selesai})\n";
                    } else {
                        Log::warning('DEBUG: Jadwal ID ' . $jadwal->id . ' tidak memiliki relasi dokter.');
                    }
                }
                $listDokterJadwal .= "\nSilakan masukkan *nama dokter* dari daftar di atas yang ingin Anda temui:";

                Log::info('DEBUG: List jadwal dokter berhasil dibuat. Mengirim balasan.');
                return $listDokterJadwal;

            case 'janji_dokter':
                Log::info('DEBUG: Masuk ke tahap janji_dokter. Pesan: ' . $message);
                $tanggal_janji = $data['tanggal_janji'];

                $dokter = Dokter::where('nama', 'LIKE', '%' . $message . '%')->first();
                if (!$dokter) {
                    Log::warning('DEBUG: Dokter tidak ditemukan untuk nama: ' . $message);
                    return "❌ Dokter tidak ditemukan. Silakan masukkan nama dokter yang valid dari daftar sebelumnya.";
                }
                Log::info('DEBUG: Dokter ditemukan: ' . $dokter->nama);

                $jadwal = JadwalDokter::where('id_dokter', $dokter->id)
                    ->where('tanggal', $tanggal_janji)
                    ->first();

                if (!$jadwal) {
                    Log::warning('DEBUG: Tidak ada jadwal untuk dokter ' . $dokter->nama . ' pada tanggal ' . $tanggal_janji);
                    return "❌ Dokter *{$dokter->nama}* tidak memiliki jadwal pada tanggal {$tanggal_janji}. Silakan pilih dokter atau tanggal lain.";
                }
                Log::info('DEBUG: Jadwal ditemukan: ID ' . $jadwal->id . ' untuk dokter ' . $dokter->nama . ' pada ' . $tanggal_janji);

                $data['id_dokter'] = $dokter->id;
                $data['nama_dokter'] = $dokter->nama;
                $data['id_jadwal_dokter'] = $jadwal->id;
                $data['jam_mulai_jadwal'] = $jadwal->jam_mulai;
                $data['jam_selesai_jadwal'] = $jadwal->jam_selesai;

                $session->update(['tahap' => 'janji_keluhan', 'data' => $data]);
                Log::info('DEBUG: Tahap janji_dokter selesai. Pindah ke janji_keluhan.');
                return "Anda akan membuat janji dengan *Dr. {$dokter->nama}* pada tanggal *{$tanggal_janji}* pukul *{$jadwal->jam_mulai} - {$jadwal->jam_selesai}*.\n"
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
                            'id_jadwal_dokter' => $data['id_jadwal_dokter'],
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
                Log::warning('DEBUG: Sesi janji temu tidak valid. Tahap tidak dikenali: ' . $tahap);
                return "⚠️ Sesi tidak valid. Ketik *buat janji* untuk memulai ulang.";
        }
    }

    private function lanjutkanPembatalanJanji(PendaftaranSession $session, string $message): string
    {
        $data = $session->data ?? [];
        $tahap = $session->tahap;

        switch ($tahap) {
            case 'batal_janji_identifikasi':
                if (strtolower(trim($message)) === 'lihat janji') {
                    $sender = $session->nomor_wa;
                    $pasien = Pasien::where('nomor_telepon', $this->normalizePhoneNumber($sender))->first();

                    if (!$pasien) {
                        return "Anda belum memiliki data pasien yang terdaftar dengan nomor WhatsApp ini.";
                    }

                    $janjis = Janji::with(['jadwal.dokter'])
                        ->where('id_pasien', $pasien->id)
                        ->whereIn('status', ['menunggu_konfirmasi', 'dikonfirmasi'])
                        ->get();

                    if ($janjis->isEmpty()) {
                        return "Anda tidak memiliki janji temu yang dapat dibatalkan.";
                    }

                    $response = "🗓️ *Daftar Janji Temu Anda:*\n\n";
                    foreach ($janjis as $janji) {
                        $dokterNama = $janji->jadwal && $janji->jadwal->dokter ? $janji->jadwal->dokter->nama : 'N/A';
                        $tanggal = $janji->jadwal ? Carbon::parse($janji->jadwal->tanggal)->format('d F Y') : 'N/A';
                        $jam = $janji->jadwal ? "{$janji->jadwal->jam_mulai} - {$janji->jadwal->jam_selesai}" : 'N/A';

                        $response .= "--- ID Janji: {$janji->id} ---\n"
                            . "Dokter: *Dr. {$dokterNama}*\n"
                            . "Tanggal: *{$tanggal}*\n"
                            . "Jam: *{$jam}*\n"
                            . "Keluhan: *{$janji->keluhan}*\n"
                            . "Status: *{$this->formatStatusJanji($janji->status)}*\n\n";
                    }
                    $response .= "Silakan masukkan *ID Janji Temu* yang ingin Anda batalkan.";
                    return $response;
                } elseif (is_numeric($message)) {
                    $janji_id = (int) $message;
                    $janji = Janji::find($janji_id);

                    if (!$janji) {
                        return "❌ ID Janji Temu tidak ditemukan. Silakan masukkan ID yang valid.";
                    }

                    $sender_normalized = $this->normalizePhoneNumber($session->nomor_wa);
                    $pasien = Pasien::where('id', $janji->id_pasien)
                        ->where('nomor_telepon', $sender_normalized)
                        ->first();

                    if (!$pasien) {
                        return "❌ Anda tidak memiliki izin untuk membatalkan janji temu ini.";
                    }


                    if (!in_array($janji->status, ['menunggu_konfirmasi', 'dikonfirmasi'])) {
                        return "❌ Janji temu dengan ID {$janji->id} tidak dapat dibatalkan (status: {$this->formatStatusJanji($janji->status)}).";
                    }

                    $data['id_janji_batal'] = $janji->id;
                    $data['janji_detail'] = [
                        'dokter' => $janji->jadwal->dokter->nama ?? 'N/A',
                        'tanggal' => Carbon::parse($janji->jadwal->tanggal)->format('d F Y') ?? 'N/A',
                        'jam' => ($janji->jadwal->jam_mulai ?? 'N/A') . ' - ' . ($janji->jadwal->jam_selesai ?? 'N/A'),
                        'keluhan' => $janji->keluhan,
                    ];

                    $session->update(['tahap' => 'batal_janji_konfirmasi', 'data' => $data]);
                    return "Anda yakin ingin membatalkan janji temu ini?\n"
                        . "Dokter: *Dr. {$data['janji_detail']['dokter']}*\n"
                        . "Tanggal: *{$data['janji_detail']['tanggal']}*\n"
                        . "Jam: *{$data['janji_detail']['jam']}*\n"
                        . "Keluhan: *{$data['janji_detail']['keluhan']}*\n\n"
                        . "Ketik *YA* untuk konfirmasi atau *BATAL* untuk membatalkan seluruh proses.";
                } else {
                    return "Pilihan tidak valid. Silakan masukkan *ID Janji Temu* atau ketik *LIHAT JANJI*.";
                }

            case 'batal_janji_konfirmasi':
                $konfirmasi = strtolower(trim($message));
                if ($konfirmasi === 'ya') {
                    $janji_id = $data['id_janji_batal'];
                    $janji = Janji::find($janji_id);
                    if ($janji) {
                        $janji->update(['status' => 'dibatalkan']);
                        $session->delete();
                        return "✅ Janji temu dengan ID *{$janji_id}* berhasil dibatalkan.";
                    } else {
                        $session->delete();
                        return "❌ Janji temu tidak ditemukan. Pembatalan gagal.";
                    }
                } elseif ($konfirmasi === 'batal') {
                    $session->delete();
                    return "❌ Pembatalan janji temu dibatalkan.";
                } else {
                    return "Pilihan tidak valid. Ketik *YA* untuk konfirmasi atau *BATAL* untuk membatalkan.";
                }

            default:
                $session->delete();
                return "⚠️ Sesi tidak valid. Ketik *batal janji* untuk memulai ulang.";
        }
    }

    private function handleCekAntrianBySender(string $sender): string
    {
        $pasien = Pasien::where('nomor_telepon', $this->normalizePhoneNumber($sender))->first();

        if (!$pasien) {
            return "Anda belum memiliki data pasien yang terdaftar dengan nomor WhatsApp ini. Silakan ketik *daftar* untuk mendaftar.";
        }

        $today = Carbon::today()->toDateString();
        $antrians = Antrian::with(['jadwal.dokter'])
            ->where('id_pasien', $pasien->id)
            ->whereHas('jadwal', function ($query) use ($today) {
                $query->where('tanggal', $today);
            })
            ->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])
            ->orderBy('nomor_antrian', 'asc')
            ->get();

        if ($antrians->isEmpty()) {
            return "Anda tidak memiliki antrian aktif hari ini.";
        }

        $response = "📋 *Antrian Anda Hari Ini, " . Carbon::parse($today)->format('d F Y') . ":*\n\n";
        foreach ($antrians as $antrian) {
            $dokterNama = $antrian->jadwal && $antrian->jadwal->dokter ? $antrian->jadwal->dokter->nama : 'N/A';
            $jam = $antrian->jadwal ? "{$antrian->jadwal->jam_mulai} - {$antrian->jadwal->jam_selesai}" : 'N/A';
            $response .= "--- Antrian #{$antrian->nomor_antrian} ---\n"
                . "Dokter: *Dr. {$dokterNama}*\n"
                . "Jam: *{$jam}*\n"
                . "Status: *{$this->formatStatusAntrian($antrian->status)}*\n\n";
        }
        return $response;
    }

    private function handleCekJanjiBySender(string $sender): string
    {
        $pasien = Pasien::where('nomor_telepon', $this->normalizePhoneNumber($sender))->first();

        if (!$pasien) {
            return "Anda belum memiliki data pasien yang terdaftar dengan nomor WhatsApp ini. Silakan ketik *daftar* untuk mendaftar.";
        }

        $janjis = Janji::with(['jadwal.dokter'])
            ->where('id_pasien', $pasien->id)
            ->whereIn('status', ['menunggu_konfirmasi', 'dikonfirmasi'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($janjis->isEmpty()) {
            return "Anda tidak memiliki janji temu yang aktif.";
        }

        $response = "🗓️ *Daftar Janji Temu Anda:*\n\n";
        foreach ($janjis as $janji) {
            $dokterNama = $janji->jadwal && $janji->jadwal->dokter ? $janji->jadwal->dokter->nama : 'N/A';
            $tanggal = $janji->jadwal ? Carbon::parse($janji->jadwal->tanggal)->format('d F Y') : 'N/A';
            $jam = $janji->jadwal ? "{$janji->jadwal->jam_mulai} - {$janji->jadwal->jam_selesai}" : 'N/A';

            $response .= "--- ID Janji: {$janji->id} ---\n"
                . "Dokter: *Dr. {$dokterNama}*\n"
                . "Tanggal: *{$tanggal}*\n"
                . "Jam: *{$jam}*\n"
                . "Keluhan: *{$janji->keluhan}*\n"
                . "Status: *{$this->formatStatusJanji($janji->status)}*\n\n";
        }
        return $response;
    }

    private function handleCekAntrianMenunggu(): string
    {
        $antrians = Antrian::with(['pasien', 'jadwal.dokter'])
            ->where('status', 'menunggu')
            ->orderBy('nomor_antrian', 'asc')
            ->get();

        if ($antrians->isEmpty()) {
            return "Tidak ada antrian dengan status 'menunggu' saat ini.";
        }

        $response = "📋 *Daftar Antrian Status 'Menunggu':*\n\n";
        foreach ($antrians as $antrian) {
            $dokterNama = $antrian->jadwal && $antrian->jadwal->dokter ? $antrian->jadwal->dokter->nama : 'N/A';
            $tanggal = $antrian->jadwal ? Carbon::parse($antrian->jadwal->tanggal)->format('d F Y') : 'N/A';
            $jam = $antrian->jadwal ? "{$antrian->jadwal->jam_mulai} - {$antrian->jadwal->jam_selesai}" : 'N/A';

            $response .= "--- Antrian #{$antrian->nomor_antrian} ---\n"
                . "Pasien: *{$antrian->pasien->nama}*\n"
                . "Dokter: *Dr. {$dokterNama}*\n"
                . "Tanggal Jadwal: *{$tanggal}*\n"
                . "Jam Jadwal: *{$jam}*\n"
                . "Status: *{$this->formatStatusAntrian($antrian->status)}*\n\n";
        }
        return $response;
    }

    private function handleCekAntrianHariIni(): string
    {
        $today = Carbon::today()->toDateString();
        $antrians = Antrian::with(['pasien', 'jadwal.dokter'])
            ->whereHas('jadwal', function ($query) use ($today) {
                $query->where('tanggal', $today);
            })
            ->whereIn('status', ['menunggu', 'dipanggil', 'dilayani'])
            ->orderBy('nomor_antrian', 'asc')
            ->get();

        if ($antrians->isEmpty()) {
            return "Tidak ada antrian aktif untuk hari ini, " . Carbon::parse($today)->format('d F Y') . ".";
        }

        $response = "📋 *Daftar Antrian Aktif Hari Ini (" . Carbon::parse($today)->format('d F Y') . "):*\n\n";
        foreach ($antrians as $antrian) {
            $dokterNama = $antrian->jadwal && $antrian->jadwal->dokter ? $antrian->jadwal->dokter->nama : 'N/A';
            $jam = $antrian->jadwal ? "{$antrian->jadwal->jam_mulai} - {$antrian->jadwal->jam_selesai}" : 'N/A';

            $response .= "--- Antrian #{$antrian->nomor_antrian} ---\n"
                . "Pasien: *{$antrian->pasien->nama}*\n"
                . "Dokter: *Dr. {$dokterNama}*\n"
                . "Jam: *{$jam}*\n"
                . "Status: *{$this->formatStatusAntrian($antrian->status)}*\n\n";
        }
        return $response;
    }

    // --- FUNGSI BARU: CEK JADWAL DOKTER HARI INI ---
    private function handleCekJadwalDokterHariIni(): string
    {
        $today = Carbon::today()->toDateString();
        $jadwals = JadwalDokter::with('dokter')
            ->where('tanggal', $today)
            ->orderBy('id_dokter', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->get();

        if ($jadwals->isEmpty()) {
            return "Tidak ada jadwal dokter untuk hari ini, " . Carbon::parse($today)->format('d F Y') . ".";
        }

        $response = "🗓️ *Jadwal Dokter Hari Ini (" . Carbon::parse($today)->format('d F Y') . "):*\n\n";
        $currentDokter = null;

        foreach ($jadwals as $jadwal) {
            if ($jadwal->dokter) {
                if ($currentDokter !== $jadwal->dokter->nama) {
                    $response .= "--- *Dr. {$jadwal->dokter->nama}* ---\n";
                    $currentDokter = $jadwal->dokter->nama;
                }
                $response .= "Pukul: *{$jadwal->jam_mulai} - {$jadwal->jam_selesai}*\n\n";
            } else {
                Log::warning('Jadwal ID ' . $jadwal->id . ' tidak memiliki relasi dokter.');
            }
        }
        return $response;
    }
    // --- AKHIR FUNGSI ---


    private function formatStatusAntrian(string $status): string
    {
        switch ($status) {
            case 'menunggu':
                return 'Menunggu';
            case 'dipanggil':
                return 'Dipanggil';
            case 'dilayani':
                return 'Dilayani';
            case 'selesai':
                return 'Selesai';
            case 'dibatalkan':
                return 'Dibatalkan';
            default:
                return ucfirst($status);
        }
    }

    private function formatStatusJanji(string $status): string
    {
        switch ($status) {
            case 'menunggu_konfirmasi':
                return 'Menunggu Konfirmasi';
            case 'dikonfirmasi':
                return 'Dikonfirmasi';
            case 'selesai':
                return 'Selesai';
            case 'dibatalkan':
                return 'Dibatalkan';
            default:
                return ucfirst($status);
        }
    }


    private function sendReply(string $to, string $message): void
    {
        $fonnteToken = env('FONNTE_API_KEY');
        if (!$fonnteToken) {
            Log::error('❌ FONNTE_API_KEY tidak ditemukan di .env');
            return;
        }

        Http::withHeaders([
            'Authorization' => $fonnteToken
        ])->post('https://api.fonnte.com/send', [
            'target' => $to,
            'message' => $message,
        ]);

        Log::info('📤 Balasan terkirim:', ['target' => $to, 'message' => $message]);
    }

    private function handleFallback(): string
    {
        return "Halo! 👋 Saya adalah Bot Klinik Sehat Selalu. Berikut adalah beberapa perintah yang bisa Anda gunakan:\n\n"
            . "📝 *DAFTAR PASIEN* - Untuk mendaftar sebagai pasien baru.\n"
            . "🗓️ *BUAT JANJI* - Untuk membuat janji temu dengan dokter.\n"
            . "👀 *CEK JANJI* atau *JANJI SAYA* - Untuk melihat daftar janji temu Anda yang aktif.\n"
            . "❌ *BATAL JANJI* - Untuk membatalkan janji temu yang sudah dibuat.\n"
            . "📋 *CEK ANTRIAN SAYA* - Untuk melihat antrian Anda hari ini.\n"
            . "📋 *CEK ANTRIAN MENUNGGU* - Untuk melihat semua antrian dengan status 'menunggu'.\n"
            . "📋 *CEK ANTRIAN HARI INI* - Untuk melihat semua antrian aktif hari ini.\n"
            . "👩‍⚕️ *CEK JADWAL DOKTER HARI INI* - Untuk melihat jadwal praktek dokter hari ini.\n\n"
            . "Silakan ketik perintah yang Anda inginkan.";
    }
}
