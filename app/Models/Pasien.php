<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pasien extends Model
{
    use HasFactory;

    protected $table = 'pasiens';

    protected $fillable = [
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'nomor_telepon',
    ];

    // Relasi ke Antrian
    public function antrian()
    {
        return $this->hasMany(Antrian::class, 'id_pasien');
    }

    // Relasi ke RekamMedis
    public function rekamMedis()
    {
        return $this->hasMany(RekamMedis::class, 'id_pasien');
    }

    // Relasi ke Pembayaran
    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'id_pasien');
    }
}
