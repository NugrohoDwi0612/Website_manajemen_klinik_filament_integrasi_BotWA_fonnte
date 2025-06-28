<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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


    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalDokter::class, 'id_jadwal_dokter');
    }

    public function dokter(): HasOneThrough
    {
        return $this->hasOneThrough(Dokter::class, JadwalDokter::class, 'id', 'id', 'id_jadwal_dokter', 'id_dokter');
    }

    public function antrian(): HasOne
    {
        return $this->hasOne(Antrian::class, 'id_janji');
    }
}
