<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RekamMedis extends Model
{
    use HasFactory;

    protected $table = 'rekam_medis';

    protected $fillable = [
        'id_pasien',
        'id_dokter',
        'id_antrian',
        'diagnosa',
        'catatan',
        'tanggal_periksa',
    ];

    protected $casts = [
        'tanggal_periksa' => 'date', // Mengubah string tanggal menjadi objek Carbon date
        // Jika Anda memiliki kolom tanggal/datetime lain, tambahkan juga di sini
    ];


    // Relasi ke Pasien
    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien');
    }

    // Relasi ke Dokter
    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter');
    }

    // Relasi ke Antrian
    public function antrian(): BelongsTo
    {
        return $this->belongsTo(Antrian::class, 'id_antrian');
    }

    // Relasi ke Resep
    public function resepItems(): HasMany
    {
        return $this->hasMany(Resep::class, 'id_rekam_medis');
    }

    // Relasi ke Pembayaran
    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_rekam_medis');
    }
}
