<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventaris extends Model
{
    use HasFactory;

    protected $table = 'inventaris';

    protected $fillable = [
        'nama_barang',
        'jumlah',
        'kondisi',
        'tanggal_pembelian',
        'keterangan',
        'id_poliklinik',
    ];

    // Relasi ke Poliklinik
    public function poliklinik(): BelongsTo
    {
        return $this->belongsTo(Poliklinik::class, 'id_poliklinik');
    }
}
