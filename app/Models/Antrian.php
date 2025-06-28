<?php

namespace App\Models;

use App\Models\Pasien;
use App\Models\JadwalDokter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrians';

    protected $fillable = [
        'id_janji',
        'id_pasien',
        'id_jadwal_dokter',
        'nomor_antrian',
        'status',
        'waktu_masuk',
        'waktu_dipanggil',
        'waktu_selesai',
    ];

    // Relasi ke Pasien
    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien');
    }

    // Relasi ke JadwalDokter
    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalDokter::class, 'id_jadwal_dokter');
    }

    // Relasi ke RekamMedis
    public function rekamMedis(): HasOne
    {
        return $this->hasOne(RekamMedis::class, 'id_antrian');
    }

    // Relasi ke Janji
    public function janji(): BelongsTo
    {
        return $this->belongsTo(Janji::class, 'id_janji');
    }
}
