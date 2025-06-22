<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JadwalDokter extends Model
{
    use HasFactory;

    protected $table = 'jadwal_dokters';

    protected $fillable = [
        'id_dokter',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
    ];

    // Relasi ke Dokter
    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'id_dokter');
    }

    // Relasi ke Antrian
    public function antrian()
    {
        return $this->hasMany(Antrian::class, 'id_jadwal_dokter');
    }

    // Relasi ke Janji
    public function janji()
    {
        return $this->hasMany(Janji::class, 'id_jadwal_dokter');
    }
}
