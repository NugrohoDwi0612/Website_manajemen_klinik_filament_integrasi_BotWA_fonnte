<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poliklinik extends Model
{
    use HasFactory;

    protected $table = 'polikliniks';

    protected $fillable = [
        'nama_poliklinik',
        'deskripsi',
    ];

    // Relasi ke Dokter
    public function dokters(): HasMany
    {
        return $this->hasMany(Dokter::class, 'id_poliklinik');
    }

    // Relasi ke Inventaris
    public function inventaris(): HasMany
    {
        return $this->hasMany(Inventaris::class, 'id_poliklinik');
    }
}
