<?php

namespace App;


use Filament\Support\Contracts\HasLabel;

enum JenisKelaminEnum: string implements HasLabel
{
    case LakiLaki = 'L';
    case Perempuan = 'P';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LakiLaki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }
}
