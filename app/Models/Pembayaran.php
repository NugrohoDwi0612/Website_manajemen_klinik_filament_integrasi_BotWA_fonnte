<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayarans';

    protected $fillable = [
        'id_pasien',
        'id_rekam_medis',
        'total_biaya',
        'metode_pembayaran',
        'status_pembayaran',
        'tanggal_pembayaran',
    ];

    // Relasi ke Pasien
    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien');
    }

    // Relasi ke RekamMedis
    public function rekamMedis(): BelongsTo
    {
        return $this->belongsTo(RekamMedis::class, 'id_rekam_medis');
    }
}
