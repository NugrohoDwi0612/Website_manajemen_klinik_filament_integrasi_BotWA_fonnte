<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendaftaranSession extends Model
{
    protected $table = 'pendaftaran_sessions';

    protected $fillable = [
        'nomor_wa',
        'tahap',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];
}
