<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'obats';

    protected $fillable = [
        'nama_obat',
        'stok',
        'harga',
        'tanggal_kadaluarsa',
        'kategori_obat',
    ];

    // Relasi ke Resep
    public function reseps()
    {
        return $this->hasMany(Resep::class, 'id_obat');
    }
}
