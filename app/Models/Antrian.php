<?php

namespace App\Models;

use App\Models\Pasien;
use App\Models\JadwalDokter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrians';

    protected $fillable = [
        'id_pasien',
        'id_jadwal_dokter',
        'nomor_antrian',
        'status',
    ];

    // Relasi ke Pasien
    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'id_pasien');
    }

    // Relasi ke JadwalDokter
    public function jadwal()
    {
        return $this->belongsTo(JadwalDokter::class, 'id_jadwal_dokter');
    }

    // Relasi ke RekamMedis
    public function rekamMedis()
    {
        return $this->hasOne(RekamMedis::class, 'id_antrian');
    }
}
