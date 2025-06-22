<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;

    protected $table = 'dokters';

    protected $fillable = [
        'nama',
        'spesialisasi',
        'nomor_telepon',
        'email',
        'id_poliklinik', // Tambahkan kolom ini
    ];

    // Relasi ke Poliklinik
    public function poliklinik()
    {
        return $this->belongsTo(Poliklinik::class, 'id_poliklinik');
    }

    // Relasi ke JadwalDokter
    public function jadwalDokters()
    {
        return $this->hasMany(JadwalDokter::class, 'id_dokter');
    }

    // Relasi ke RekamMedis
    public function rekamMedis()
    {
        return $this->hasMany(RekamMedis::class, 'id_dokter');
    }
}
