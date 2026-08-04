<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TonggakSejarahResource\Pages;
use App\Models\TonggakSejarah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TonggakSejarahResource extends Resource
{
    protected static ?string $model = TonggakSejarah::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Tonggak Sejarah';

    protected static ?string $modelLabel = 'Tonggak Sejarah';

    protected static ?string $pluralModelLabel = 'Tonggak Sejarah';

    protected static ?string $navigationGroup = 'Data Sekolah';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tahun')
                    ->required()
                    ->maxLength(10)
                    ->helperText('Contoh: 1985 atau 2020-an'),
                Forms\Components\TextInput::make('urutan')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\Textarea::make('deskripsi')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Non Aktif',
                    ])
                    ->default('aktif')
                    ->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('tahun')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('urutan')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'aktif',
                        'danger' => 'nonaktif',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Non Aktif',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTonggakSejarahs::route('/'),
            'create' => Pages\CreateTonggakSejarah::route('/create'),
            'edit' => Pages\EditTonggakSejarah::route('/{record}/edit'),
        ];
    }
}
