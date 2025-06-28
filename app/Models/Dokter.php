<?php

namespace App\Models;

use App\Models\Poliklinik;
use App\Models\RekamMedis;
use App\Models\JadwalDokter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function poliklinik(): BelongsTo
    {
        return $this->belongsTo(Poliklinik::class, 'id_poliklinik');
    }

    // Relasi ke JadwalDokter
    public function jadwalDokters(): HasMany
    {
        return $this->hasMany(JadwalDokter::class, 'id_dokter');
    }

    // Relasi ke RekamMedis
    public function rekamMedis(): HasMany
    {
        return $this->hasMany(RekamMedis::class, 'id_dokter');
    }
}
