<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum StatusAntrianEnum: string implements HasLabel
{
    case Menunggu = 'menunggu';
    case Diperiksa = 'diperiksa';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Diperiksa => 'Diperiksa',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }
}
