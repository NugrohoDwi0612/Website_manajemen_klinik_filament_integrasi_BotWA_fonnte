<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter');
    }

    // Relasi ke Antrian
    public function antrian(): HasMany
    {
        return $this->hasMany(Antrian::class, 'id_jadwal_dokter');
    }

    // Relasi ke Janji
    public function janji(): HasMany
    {
        return $this->hasMany(Janji::class, 'id_jadwal_dokter');
    }
}
