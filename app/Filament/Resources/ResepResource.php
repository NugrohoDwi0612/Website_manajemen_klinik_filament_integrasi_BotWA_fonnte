<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResepResource\Pages;
use App\Models\Obat;
use App\Models\RekamMedis;
use App\Models\Resep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ResepResource extends Resource
{
    protected static ?string $model = Resep::class;
    protected static ?string $navigationGroup = 'Farmasi';
    protected static ?string $navigationLabel = 'Detail Resep';
    protected static ?string $pluralLabel = 'Detail Resep';
    protected static ?string $modelLabel = 'Detail Resep';

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_rekam_medis')
                    ->label('Rekam Medis')
                    ->options(RekamMedis::all()->pluck('id', 'id')) // Bisa disempurnakan dengan nama pasien/dokter
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('id_obat')
                    ->label('Obat')
                    ->options(Obat::all()->pluck('nama_obat', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('jumlah')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('unit_satuan')
                    ->maxLength(50)
                    ->nullable(),
                Forms\Components\Textarea::make('instruksi')
                    ->maxLength(65535)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rekamMedis.id') // Atau 'rekamMedis.pasien.nama_pasien' jika Anda menyertakan relasi nested
                    ->label('ID RM')
                    ->sortable()
                    ->url(fn(Resep $record): string => RekamMedisResource::getUrl('edit', ['record' => $record->rekamMedis])),
                Tables\Columns\TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_satuan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('instruksi')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('obat')
                    ->relationship('obat', 'nama_obat'),
                Tables\Filters\SelectFilter::make('rekamMedis')
                    ->relationship('rekamMedis', 'id') // Bisa diubah untuk mencari berdasarkan nama pasien di rekam medis
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReseps::route('/'),
            'create' => Pages\CreateResep::route('/create'),
            'edit' => Pages\EditResep::route('/{record}/edit'),
        ];
    }
}
