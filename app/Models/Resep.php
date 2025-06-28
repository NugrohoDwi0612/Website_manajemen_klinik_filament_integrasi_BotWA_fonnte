<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resep extends Model
{
    use HasFactory;

    protected $table = 'reseps';

    protected $fillable = [
        'id_rekam',
        'id_obat',
        'jumlah',
        'instruksi',
        'unit_satuan',
    ];

    // Relasi ke RekamMedis
    public function rekamMedis(): BelongsTo
    {
        return $this->belongsTo(RekamMedis::class, 'id_rekam_medis');
    }

    // Relasi ke Obat
    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'id_obat');
    }
}
