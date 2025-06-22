<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Janji extends Model
{
    use HasFactory;

    protected $table = 'janji';

    protected $fillable = [
        'id_pasien',
        'id_jadwal_dokter',
        'keluhan',
        'status',
    ];


    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'id_pasien');
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalDokter::class, 'id_jadwal_dokter');
    }

    public function dokter()
    {
        return $this->hasOneThrough(Dokter::class, JadwalDokter::class, 'id', 'id', 'id_jadwal_dokter', 'id_dokter');
    }
}
