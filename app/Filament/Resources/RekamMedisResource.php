<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekamMedisResource\Pages;
use App\Models\Dokter; // Pastikan model Dokter sudah diperbarui dengan nama tabel 'dokters' dan kolom 'nama' atau 'nama_dokter'
use App\Models\Pasien; // Pastikan model Pasien sudah diperbarui dengan nama tabel 'pasien' dan kolom 'nama'
use App\Models\RekamMedis; // Pastikan model RekamMedis sudah diperbarui dengan relasi 'resepItems'
use App\Models\Antrian; // Import Antrian model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;

class RekamMedisResource extends Resource
{
    protected static ?string $model = RekamMedis::class;
    protected static ?string $navigationGroup = 'Pelayanan';
    protected static ?string $navigationLabel = 'Rekam Medis';
    protected static ?string $pluralLabel = 'Rekam Medis';
    protected static ?string $modelLabel = 'Rekam Medis';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2) // Membuat layout 2 kolom
                    ->schema([
                        Select::make('id_pasien')
                            ->label('Pasien')
                            // Menggunakan 'nama' karena di model Pasien, kolom nama adalah 'nama'
                            ->relationship('pasien', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('id_dokter')
                            ->label('Dokter')
                            // Asumsi nama kolom untuk nama dokter adalah 'nama'.
                            // Jika di model Dokter dan tabel 'dokters' namanya 'nama_dokter', gunakan 'nama_dokter'.
                            // Mohon konfirmasi nama kolom di tabel 'dokters' Anda.
                            ->relationship('dokter', 'nama') // <-- Sesuaikan dengan nama kolom aktual di tabel 'dokters'
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('id_antrian')
                            ->label('Antrian')
                            // Pastikan model Antrian memiliki kolom 'nomor_antrian'
                            ->options(Antrian::whereDoesntHave('rekamMedis')->pluck('nomor_antrian', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        DatePicker::make('tanggal_periksa')
                            ->required()
                            ->default(now()),
                    ]),

                Textarea::make('diagnosa')
                    ->maxLength(65535)
                    ->nullable()
                    ->columnSpanFull(),
                Textarea::make('catatan')
                    ->maxLength(65535)
                    ->nullable()
                    ->columnSpanFull(),

                // Repeater untuk Resep (Nested Form)
                Repeater::make('resepItems') // Konsisten dengan nama relasi di model RekamMedis
                    ->relationship('resepItems') // Konsisten dengan nama relasi di model RekamMedis
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('id_obat')
                                    ->label('Obat')
                                    ->options(\App\Models\Obat::all()->pluck('nama_obat', 'id')) // Pastikan kolom nama di model Obat adalah 'nama_obat'
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(), // Agar obat tidak duplikat dalam satu resep
                                TextInput::make('jumlah')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    // Menggunakan `unit_satuan` yang diinput di repeater itu sendiri
                                    ->suffix(fn(Forms\Get $get) => $get('unit_satuan') ?? null),
                                TextInput::make('unit_satuan')
                                    ->maxLength(50)
                                    ->placeholder('e.g., tablet, ml')
                                    // Helper text lebih akurat jika unit_satuan akan diinput langsung di resep, bukan dari master obat
                                    ->helperText('Contoh: tablet, ml, bungkus, sendok takar'),
                            ]),
                        Textarea::make('instruksi')
                            ->maxLength(65535)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->label('Resep Obat')
                    ->columns(1)
                    ->collapsed() // Memulai dengan keadaan terlipat
                    ->cloneable()
                    ->collapsible()
                    // Menggunakan `unit_satuan` dari state repeater untuk itemLabel
                    ->itemLabel(fn(array $state): ?string => \App\Models\Obat::find($state['id_obat'])?->nama_obat . ' (' . $state['jumlah'] . ' ' . ($state['unit_satuan'] ?? '') . ')')
                    ->defaultItems(1) // Memulai dengan satu item resep
                    ->minItems(0), // Memungkinkan resep kosong
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pasien.nama') // UBAH INI: Konsisten dengan `Pasien.php` yang menggunakan kolom 'nama'
                    ->label('Pasien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dokter.nama') // UBAH INI: Asumsi nama kolom di tabel 'dokters' adalah 'nama'
                    ->label('Dokter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('antrian.nomor_antrian')
                    ->label('No. Antrian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_periksa')
                    ->date()
                    ->sortable(),
                TextColumn::make('diagnosa')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pasien')
                    ->relationship('pasien', 'nama'), // UBAH INI: Konsisten dengan `Pasien.php`
                Tables\Filters\SelectFilter::make('dokter')
                    ->relationship('dokter', 'nama'), // UBAH INI: Asumsi nama kolom di tabel 'dokters'
                Tables\Filters\Filter::make('tanggal_periksa')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_dari'),
                        Forms\Components\DatePicker::make('tanggal_sampai'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['tanggal_dari'],
                                fn(\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('tanggal_periksa', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn(\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('tanggal_periksa', '<=', $date),
                            );
                    }),
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
            // Relasi Resep tidak perlu di sini karena sudah dihandle di form Repeater
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRekamMedis::route('/'),
            'create' => Pages\CreateRekamMedis::route('/create'),
            'edit' => Pages\EditRekamMedis::route('/{record}/edit'),
        ];
    }
}
