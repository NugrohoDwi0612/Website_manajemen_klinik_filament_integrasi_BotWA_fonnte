<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Dokter;
use App\Models\Pasien;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AdminWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Users', User::count()),
            Card::make('Users Registered Today', User::whereDate('created_at', today())->count()),
            Card::make('Total Dokters', Dokter::count()),
            Card::make('Total Pasiens', Pasien::count()),
        ];
    }
}
