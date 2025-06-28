<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AntrianResource\Pages;
use App\Filament\Resources\AntrianResource\RelationManagers;
use App\Models\Antrian;
use App\Models\Janji;
use App\Models\JadwalDokter;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;

class AntrianResource extends Resource
{
    protected static ?string $model = Antrian::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'Manajemen Klinik';
    protected static ?string $label = 'Antrian';
    protected static ?string $pluralLabel = 'Antrian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_janji')
                    ->label('Janji Temu (opsional)')
                    ->relationship('janji', 'id')
                    ->getOptionLabelUsing(function ($value) {
                        $janji = Janji::with(['pasien', 'jadwal.dokter'])->find($value);

                        if (!$janji) {
                            return 'Janji tidak ditemukan';
                        }

                        $pasienNama = $janji->pasien->nama ?? 'N/A';
                        $dokterNama = $janji->jadwal?->dokter->nama ?? 'N/A';
                        $tanggal = $janji->jadwal?->tanggal ? Carbon::parse($janji->jadwal->tanggal)->format('d M Y') : 'N/A';
                        $jam = ($janji->jadwal?->jam_mulai && $janji->jadwal?->jam_selesai) ? "{$janji->jadwal->jam_mulai}-{$janji->jadwal->jam_selesai}" : 'N/A';


                        return "{$pasienNama} - dr. {$dokterNama} ({$tanggal} {$jam})";
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // Reset fields jika janji dibatalkan pilihannya
                        if (empty($state)) {
                            $set('id_pasien', null);
                            $set('id_jadwal_dokter', null);
                            $set('nomor_antrian', null);
                            return;
                        }

                        // Jika id_janji dipilih, otomatis isi id_pasien dan id_jadwal_dokter
                        // Eager load di sini juga untuk memastikan data lengkap
                        $janji = Janji::with(['pasien', 'jadwal'])->find($state);
                        if ($janji) {
                            $set('id_pasien', $janji->id_pasien);
                            $set('id_jadwal_dokter', $janji->id_jadwal_dokter);

                            // Hitung nomor antrian otomatis berdasarkan jadwal dokter dari janji
                            if ($janji->id_jadwal_dokter && $janji->jadwal) {
                                $maxNomorAntrian = Antrian::where('id_jadwal_dokter', $janji->id_jadwal_dokter)
                                    ->whereDate('waktu_masuk', $janji->jadwal->tanggal) // Filter berdasarkan tanggal jadwal
                                    ->max('nomor_antrian');

                                $set('nomor_antrian', ($maxNomorAntrian ?? 0) + 1);
                            } else {
                                $set('nomor_antrian', null);
                            }
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\Select::make('id_pasien')
                    ->label('Pasien')
                    ->relationship('pasien', 'nama')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Otomatis terisi jika memilih Janji Temu.'),

                Forms\Components\Select::make('id_jadwal_dokter')
                    ->label('Jadwal Dokter')
                    ->options(function () {
                        return JadwalDokter::with('dokter')
                            ->orderBy('tanggal', 'asc')
                            ->orderBy('jam_mulai', 'asc')
                            ->get()
                            ->mapWithKeys(function ($jadwal) {
                                $date = Carbon::parse($jadwal->tanggal)->format('d M Y');
                                $time = "{$jadwal->jam_mulai} - {$jadwal->jam_selesai}";
                                $doctorName = $jadwal->dokter ? $jadwal->dokter->nama : 'N/A';
                                return [$jadwal->id => "dr. {$doctorName} - {$date} ({$time})"];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->reactive() // Membuat ini reaktif jika dipilih langsung (misal untuk walk-in)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        // Hanya hitung jika id_janji belum dipilih atau jika ini adalah perubahan langsung id_jadwal_dokter
                        if (!$get('id_janji') && $state) { // Jika id_janji kosong dan id_jadwal_dokter dipilih
                            $jadwalDokter = JadwalDokter::find($state);
                            if ($jadwalDokter) {
                                $maxNomorAntrian = Antrian::where('id_jadwal_dokter', $state)
                                    ->whereDate('waktu_masuk', $jadwalDokter->tanggal)
                                    ->max('nomor_antrian');

                                $set('nomor_antrian', ($maxNomorAntrian ?? 0) + 1);
                            } else {
                                $set('nomor_antrian', null);
                            }
                        } elseif (empty($state)) {
                            $set('nomor_antrian', null); // Kosongkan nomor antrian jika jadwal tidak dipilih
                        }
                    })
                    ->helperText('Otomatis terisi jika memilih Janji Temu.'),

                Forms\Components\TextInput::make('nomor_antrian')
                    ->label('Nomor Antrian')
                    ->numeric()
                    ->required()
                    ->readOnly() // Penting: Membuat bidang ini hanya-baca agar tidak diubah manual
                    ->placeholder('Akan terisi otomatis'), // Memberikan petunjuk kepada pengguna

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'dipanggil' => 'Dipanggil',
                        'selesai' => 'Selesai',
                        'tidak_hadir' => 'Tidak Hadir',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->required()
                    ->default('menunggu'),

                Forms\Components\DateTimePicker::make('waktu_masuk')
                    ->label('Waktu Masuk')
                    ->default(now())
                    ->required(),

                Forms\Components\DateTimePicker::make('waktu_dipanggil')
                    ->label('Waktu Dipanggil')
                    ->nullable(),

                Forms\Components\DateTimePicker::make('waktu_selesai')
                    ->label('Waktu Selesai')
                    ->nullable(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('janji.id')
                    ->label('ID Janji')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('patient_name')
                    ->label('Pasien')
                    ->getStateUsing(function (Antrian $record): ?string {
                        return $record->janji ? ($record->janji->pasien->nama ?? 'N/A') : ($record->pasien->nama ?? 'N/A');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('pasien', fn($q) => $q->where('nama', 'like', "%{$search}%"))
                            ->orWhereHas('janji.pasien', fn($q) => $q->where('nama', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('doctor_name')
                    ->label('Dokter')
                    ->getStateUsing(function (Antrian $record): ?string {
                        // KOREKSI: Gunakan $record->jadwal untuk relasi di model Antrian
                        if ($record->janji && $record->janji->jadwal && $record->janji->jadwal->dokter) {
                            return $record->janji->jadwal->dokter->nama;
                        }
                        if ($record->jadwal && $record->jadwal->dokter) { // <--- DIUBAH DARI $record->jadwalDokter
                            return $record->jadwal->dokter->nama;
                        }
                        return 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // KOREKSI: Gunakan 'jadwal.dokter' di whereHas
                        return $query->whereHas('jadwal.dokter', fn($q) => $q->where('nama', 'like', "%{$search}%")) // <--- DIUBAH DARI 'jadwalDokter.dokter'
                            ->orWhereHas('janji.jadwal.dokter', fn($q) => $q->where('nama', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('nomor_antrian')
                    ->label('No. Antrian')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'info' => 'menunggu',
                        'warning' => 'dipanggil',
                        'success' => 'selesai',
                        'danger' => 'tidak_hadir',
                        'secondary' => 'dibatalkan',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_masuk')
                    ->label('Waktu Masuk')
                    ->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'dipanggil' => 'Dipanggil',
                        'selesai' => 'Selesai',
                        'tidak_hadir' => 'Tidak Hadir',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->label('Filter Status'),
                SelectFilter::make('dokter')
                    // KOREKSI: Gunakan 'jadwal.dokter' di relationship
                    ->relationship('jadwal.dokter', 'nama') // <--- DIUBAH DARI 'jadwalDokter.dokter'
                    ->label('Filter Berdasarkan Dokter (Langsung)'),
                SelectFilter::make('dokter_janji')
                    ->relationship('janji.jadwal.dokter', 'nama')
                    ->label('Filter Berdasarkan Dokter (Janji)'),
                Filter::make('tanggal_antrian')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_dari')
                            ->placeholder('Dari Tanggal'),
                        Forms\Components\DatePicker::make('tanggal_sampai')
                            ->placeholder('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tanggal_dari'],
                                // KOREKSI: Gunakan 'jadwal' di whereHas
                                fn(Builder $query, $date): Builder => $query->whereHas('jadwal', fn($q) => $q->whereDate('tanggal', '>=', $date)) // <--- DIUBAH DARI 'jadwalDokter'
                                    ->orWhereHas('janji.jadwal', fn($q) => $q->whereDate('tanggal', '>=', $date)),
                            )
                            ->when(
                                $data['tanggal_sampai'],
                                // KOREKSI: Gunakan 'jadwal' di whereHas
                                fn(Builder $query, $date): Builder => $query->whereHas('jadwal', fn($q) => $q->whereDate('tanggal', '<=', $date)) // <--- DIUBAH DARI 'jadwalDokter'
                                    ->orWhereHas('janji.jadwal', fn($q) => $q->whereDate('tanggal', '<=', $date)),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAntrians::route('/'),
            'create' => Pages\CreateAntrian::route('/create'),
            'edit' => Pages\EditAntrian::route('/{record}/edit'),
        ];
    }
}
