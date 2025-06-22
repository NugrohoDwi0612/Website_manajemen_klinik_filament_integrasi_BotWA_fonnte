<?php

namespace App\Filament\Resources\AntrianResource\Pages;

use Filament\Actions;
use App\Models\Antrian;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\AntrianResource;

class CreateAntrian extends CreateRecord
{
    protected static string $resource = AntrianResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ambil nomor antrian terakhir untuk jadwal yang sama
        $lastNumber = Antrian::where('id_jadwal_dokter', $data['id_jadwal_dokter'])->max('nomor_antrian');

        $data['nomor_antrian'] = $lastNumber ? $lastNumber + 1 : 1;

        return $data;
    }
}
