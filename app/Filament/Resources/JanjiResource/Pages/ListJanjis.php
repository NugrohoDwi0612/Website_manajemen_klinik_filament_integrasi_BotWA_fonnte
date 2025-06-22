<?php

namespace App\Filament\Resources\JanjiResource\Pages;

use App\Filament\Resources\JanjiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJanjis extends ListRecords
{
    protected static string $resource = JanjiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
