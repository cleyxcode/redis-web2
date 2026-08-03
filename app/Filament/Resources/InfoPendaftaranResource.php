<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InfoPendaftaranResource\Pages;
use App\Models\InfoPendaftaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InfoPendaftaranResource extends Resource
{
    protected static ?string $model = InfoPendaftaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Info Pendaftaran';

    protected static ?string $modelLabel = 'Info Pendaftaran';

    protected static ?string $pluralModelLabel = 'Info Pendaftaran';

    protected static ?string $navigationGroup = 'PPDB';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total info periode pendaftaran';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Periode Pendaftaran')
                    ->description('Atur tahun ajaran, tanggal, dan kuota PPDB.')
                    ->schema([
                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->placeholder('2026/2027')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('kuota')
                            ->label('Kuota Siswa')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_buka')
                            ->label('Tanggal Buka')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('tanggal_tutup')
                            ->label('Tanggal Tutup')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('tanggal_buka'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Syarat Pendaftaran')
                    ->description('Syarat ini ditampilkan di website publik dan aplikasi mobile.')
                    ->schema([
                        Forms\Components\Textarea::make('syarat')
                            ->label('Daftar Syarat')
                            ->helperText('Tulis satu syarat per baris. Boleh diawali nomor (1. 2. 3.) atau tanpa nomor.')
                            ->placeholder("1. Berusia minimal 6 tahun pada 1 Juli\n2. Fotokopi Akta Kelahiran (2 lembar)\n3. Fotokopi Kartu Keluarga (2 lembar)\n4. Pas foto 3x4 sebanyak 3 lembar")
                            ->rows(10)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Periode')
                            ->options([
                                'aktif' => 'Aktif (dibuka untuk pendaftaran)',
                                'nonaktif' => 'Nonaktif',
                            ])
                            ->helperText('Hanya satu periode yang boleh aktif. Mengaktifkan periode ini akan menonaktifkan yang lain.')
                            ->default('nonaktif')
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('tanggal_buka')
                    ->label('Buka')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_tutup')
                    ->label('Tutup')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kuota')
                    ->label('Kuota')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('syarat')
                    ->label('Syarat')
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || trim($state) === '') {
                            return '—';
                        }

                        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $state) ?: [])));

                        return count($lines).' item';
                    })
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => $state === 'aktif' ? 'Aktif' : 'Nonaktif'),
            ])
            ->defaultSort('tanggal_buka', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInfoPendaftarans::route('/'),
            'create' => Pages\CreateInfoPendaftaran::route('/create'),
            'edit' => Pages\EditInfoPendaftaran::route('/{record}/edit'),
        ];
    }
}
