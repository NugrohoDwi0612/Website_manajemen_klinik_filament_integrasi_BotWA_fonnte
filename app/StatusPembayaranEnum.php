<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum StatusPembayaranEnum: string implements HasLabel
{
    case Pending = 'pending';
    case Lunas = 'lunas';
    case Batal = 'batal';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Lunas => 'Lunas',
            self::Batal => 'Batal',
        };
    }
}
