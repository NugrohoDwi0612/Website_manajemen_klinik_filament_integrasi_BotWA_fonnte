<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JanjiResource\Pages;
use App\Filament\Resources\JanjiResource\RelationManagers;
use App\Models\Janji;
use App\Models\Pasien; // Import model Pasien
use App\Models\JadwalDokter; // Import model JadwalDokter
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon; // Untuk memformat tanggal
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class JanjiResource extends Resource
{
    protected static ?string $model = Janji::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days'; // Icon untuk navigasi sidebar
    protected static ?string $navigationGroup = 'Manajemen Klinik'; // Kelompokkan di sidebar
    protected static ?string $label = 'Janji Temu'; // Label singular
    protected static ?string $pluralLabel = 'Janji Temu'; // Label plural

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_pasien')
                    ->label('Pasien')
                    ->relationship('pasien', 'nama') // 'pasien' adalah nama method relasi di model Janji, 'nama' adalah kolom yang ditampilkan
                    ->searchable() // Memungkinkan pencarian pasien
                    ->preload() // Memuat semua pasien di awal (hati-hati jika data sangat banyak)
                    ->required()
                    ->columnSpanFull(), // Menggunakan lebar penuh kolom

                Forms\Components\Select::make('id_jadwal_dokter')
                    ->label('Jadwal Dokter')
                    ->options(function () {
                        // Mengambil data jadwal_dokter dengan informasi dokter terkait
                        return JadwalDokter::with('dokter')
                            ->orderBy('tanggal', 'asc')
                            ->orderBy('jam_mulai', 'asc')
                            ->get()
                            ->mapWithKeys(function ($jadwal) {
                                // Memformat tampilan jadwal agar mudah dibaca
                                $date = Carbon::parse($jadwal->tanggal)->format('d M Y');
                                $time = "{$jadwal->jam_mulai} - {$jadwal->jam_selesai}";
                                $doctorName = $jadwal->dokter ? $jadwal->dokter->nama : 'N/A';
                                return [$jadwal->id => "dr. {$doctorName} - {$date} ({$time})"];
                            });
                    })
                    ->searchable() // Memungkinkan pencarian jadwal
                    ->preload() // Memuat semua jadwal di awal (hati-hati jika data sangat banyak)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('keluhan')
                    ->label('Keluhan')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'terjadwal' => 'Terjadwal',
                        'selesai' => 'Selesai',
                        'batal' => 'Dibatalkan',
                    ])
                    ->required()
                    ->default('menunggu_konfirmasi') // Default status saat membuat janji baru
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('pasien.nama') // Menampilkan nama pasien dari relasi
                    ->label('Pasien')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('jadwal.dokter.nama') // Menampilkan nama dokter melalui relasi jadwal
                    ->label('Dokter')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('jadwal.tanggal')
                    ->label('Tanggal')
                    ->date('d M Y') // Format tanggal
                    ->sortable(),
                Tables\Columns\TextColumn::make('jadwal.jam_mulai')
                    ->label('Jam Mulai')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jadwal.jam_selesai')
                    ->label('Jam Selesai')
                    ->sortable(),
                Tables\Columns\TextColumn::make('keluhan')
                    ->label('Keluhan')
                    ->limit(50) // Batasi panjang teks keluhan di tabel
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge() // Tampilkan sebagai badge dengan warna
                    ->colors([
                        'primary' => 'menunggu_konfirmasi', // Warna biru muda
                        'success' => 'terjadwal',           // Warna hijau
                        'info' => 'selesai',                // Warna biru
                        'danger' => 'dibatalkan',           // Warna merah
                        'warning' => 'tidak_hadir',         // Warna kuning
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i') // Format tanggal dan waktu pembuatan
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa disembunyikan/ditampilkan
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'terjadwal' => 'Terjadwal',
                        'selesai' => 'Selesai',
                        'batal' => 'Dibatalkan',
                    ])
                    ->label('Filter Status'),

                SelectFilter::make('dokter')
                    ->relationship('jadwal.dokter', 'nama') // Filter berdasarkan nama dokter
                    ->label('Filter Berdasarkan Dokter'),

                Filter::make('tanggal_janji') // Filter berdasarkan rentang tanggal janji
                    ->form([
                        DatePicker::make('tanggal_dari')
                            ->placeholder('Dari Tanggal'),
                        DatePicker::make('tanggal_sampai')
                            ->placeholder('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tanggal_dari'],
                                // Pastikan filter bekerja pada kolom 'tanggal' di tabel 'jadwal_dokters'
                                fn(Builder $query, $date): Builder => $query->whereHas('jadwal', fn($q) => $q->whereDate('tanggal', '>=', $date)),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                fn(Builder $query, $date): Builder => $query->whereHas('jadwal', fn($q) => $q->whereDate('tanggal', '<=', $date)),
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
            // Anda bisa menambahkan Relation Managers di sini jika ada relasi yang ingin ditampilkan sebagai tab
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJanjis::route('/'),
            'create' => Pages\CreateJanji::route('/create'),
            'edit' => Pages\EditJanji::route('/{record}/edit'),
        ];
    }
}
