<?php

namespace App\Filament\Resources\JanjiResource\Pages;

use App\Filament\Resources\JanjiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJanji extends EditRecord
{
    protected static string $resource = JanjiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
