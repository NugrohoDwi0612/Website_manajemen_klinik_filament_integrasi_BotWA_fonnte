<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembayaranResource\Pages;
use App\Models\Pembayaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Grid;
use Filament\Support\RawJs;

class PembayaranResource extends Resource
{
    protected static ?string $model = Pembayaran::class;
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $pluralLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('id_rekam_medis')
                            ->label('Rekam Medis')
                            ->relationship('rekamMedis', 'id')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->pasien->nama} ({$record->tanggal_periksa->format('d M Y')})")
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(1),
                        Select::make('id_pasien')
                            ->label('Pasien')
                            ->relationship('pasien', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('total_biaya')
                            ->label('Total Biaya')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input)')) // masking client-side
                            ->required()
                            ->dehydrateStateUsing(function ($state) {
                                // Hilangkan semua karakter kecuali angka dan titik/koma
                                $state = str_replace(',', '', $state); // hilangkan koma (misal: "120,000" jadi "120000")
                                return (float) preg_replace('/[^\d.]/', '', $state); // buang non-numeric
                            })
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')) // untuk saat edit ditampilkan dalam format rupiah
                            ->live(),
                        Select::make('status_pembayaran')
                            ->label('Status Pembayaran')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Lunas',
                                'canceled' => 'Dibatalkan',
                                'partial' => 'Sebagian',
                            ])
                            ->default('pending')
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer Bank',
                                'card' => 'Kartu Kredit/Debit',
                                'e-wallet' => 'E-Wallet',
                            ])
                            ->nullable(),
                        DatePicker::make('tanggal_pembayaran')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rekamMedis.pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rekamMedis.tanggal_periksa')
                    ->label('Tgl Periksa')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_biaya')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status_pembayaran')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'canceled' => 'danger',
                        'partial' => 'info',
                    })
                    ->sortable(),
                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->sortable(),
                TextColumn::make('tanggal_pembayaran')
                    ->label('Tanggal Bayar')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Lunas',
                        'canceled' => 'Dibatalkan',
                        'partial' => 'Sebagian',
                    ])
                    ->label('Filter Status'),
                Tables\Filters\SelectFilter::make('metode_pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer Bank',
                        'card' => 'Kartu Kredit/Debit',
                        'e-wallet' => 'E-Wallet',
                    ])
                    ->label('Filter Metode'),
                Tables\Filters\Filter::make('tanggal_pembayaran')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('tanggal_pembayaran', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('tanggal_pembayaran', '<=', $date),
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
            'index' => Pages\ListPembayarans::route('/'),
            'create' => Pages\CreatePembayaran::route('/create'),
            'edit' => Pages\EditPembayaran::route('/{record}/edit'),
        ];
    }
}
