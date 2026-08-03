<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PendaftaranResource\Pages;
use App\Models\Pendaftaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendaftaranResource extends Resource
{
    protected static ?string $model = Pendaftaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Data Pendaftaran';

    protected static ?string $navigationGroup = 'PPDB';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Pendaftaran::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Pendaftaran::query()->where('status', 'pending')->exists()
            ? 'warning'
            : 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pending = Pendaftaran::query()->where('status', 'pending')->count();

        return $pending > 0
            ? "Total pendaftaran · {$pending} menunggu verifikasi"
            : 'Total data pendaftaran';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status Pendaftaran')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'diterima' => 'Diterima',
                                'ditolak' => 'Ditolak',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('catatan_verifikasi')
                            ->label('Catatan verifikasi')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Data Anak')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_anak'),
                        Infolists\Components\TextEntry::make('tempat_lahir'),
                        Infolists\Components\TextEntry::make('tanggal_lahir')->date(),
                        Infolists\Components\TextEntry::make('jenis_kelamin'),
                        Infolists\Components\TextEntry::make('agama'),
                        Infolists\Components\TextEntry::make('anak_ke'),
                        Infolists\Components\TextEntry::make('asal_sekolah'),
                        Infolists\Components\TextEntry::make('nik'),
                        Infolists\Components\TextEntry::make('no_kk'),
                    ])->columns(3),

                Infolists\Components\Section::make('Alamat')
                    ->schema([
                        Infolists\Components\TextEntry::make('alamat')->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Data Orang Tua')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_ayah'),
                        Infolists\Components\TextEntry::make('pekerjaan_ayah'),
                        Infolists\Components\TextEntry::make('nama_ibu'),
                        Infolists\Components\TextEntry::make('pekerjaan_ibu'),
                        Infolists\Components\TextEntry::make('nama_wali'),
                        Infolists\Components\TextEntry::make('no_hp'),
                    ])->columns(3),

                Infolists\Components\Section::make('Dokumen')
                    ->schema([
                        Infolists\Components\IconEntry::make('dokumen_lengkap')
                            ->label('Kelengkapan')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-exclamation-triangle')
                            ->trueColor('success')
                            ->falseColor('warning'),
                        Infolists\Components\TextEntry::make('dokumen_kk')
                            ->label('Kartu Keluarga')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat file' : '—')
                            ->url(fn (Pendaftaran $record): ?string => $record->dokumen_kk_url, true)
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('dokumen_akta')
                            ->label('Akta Kelahiran')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat file' : '—')
                            ->url(fn (Pendaftaran $record): ?string => $record->dokumen_akta_url, true)
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('dokumen_pas_foto')
                            ->label('Pas Foto')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat file' : '—')
                            ->url(fn (Pendaftaran $record): ?string => $record->dokumen_pas_foto_url, true)
                            ->color('primary'),
                        Infolists\Components\ImageEntry::make('dokumen_pas_foto')
                            ->label('Preview Pas Foto')
                            ->disk('public')
                            ->visible(fn (Pendaftaran $record): bool => filled($record->dokumen_pas_foto)
                                && preg_match('/\.(jpe?g|png)$/i', (string) $record->dokumen_pas_foto) === 1),
                    ])->columns(2),

                Infolists\Components\Section::make('Status Pendaftaran')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'diterima' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('catatan_verifikasi')
                            ->label('Catatan / alasan')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('verifier.name')
                            ->label('Diverifikasi oleh')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Waktu verifikasi')
                            ->dateTime()
                            ->placeholder('—'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_anak')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nama_ayah')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('no_hp')->searchable(),
                Tables\Columns\IconColumn::make('dokumen_lengkap')
                    ->label('Dokumen')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'diterima' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'diterima' => 'Diterima',
                        'ditolak' => 'Ditolak',
                    ]),
                SelectFilter::make('dokumen_lengkap')
                    ->label('Kelengkapan dokumen')
                    ->options([
                        '1' => 'Lengkap',
                        '0' => 'Belum lengkap',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            '1' => $query->whereNotNull('dokumen_kk')->whereNotNull('dokumen_akta'),
                            '0' => $query->where(fn (Builder $q) => $q->whereNull('dokumen_kk')->orWhereNull('dokumen_akta')),
                            default => $query,
                        };
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendaftarans::route('/'),
            'view' => Pages\ViewPendaftaran::route('/{record}'),
            'edit' => Pages\EditPendaftaran::route('/{record}/edit'),
        ];
    }
}
