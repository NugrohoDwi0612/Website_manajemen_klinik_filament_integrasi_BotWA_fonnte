<?php

namespace App\Filament\Resources\PoliklinikResource\Pages;

use App\Filament\Resources\PoliklinikResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolikliniks extends ListRecords
{
    protected static string $resource = PoliklinikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
