<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use App\Support\MapEmbed;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page as ResourcePage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class EditAllSettings extends ResourcePage implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.edit-all-settings';

    protected static ?string $title = 'Pengaturan Sekolah';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::query()->pluck('value', 'key')->toArray();

        if (empty($settings['latitude'])) {
            $settings['latitude'] = (string) MapEmbed::DEFAULT_LAT;
        }
        if (empty($settings['longitude'])) {
            $settings['longitude'] = (string) MapEmbed::DEFAULT_LNG;
        }

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Pengaturan')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('Tampilan Web')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make('Branding')
                                    ->description('Logo, background, dan favicon yang tampil di website.')
                                    ->schema([
                                        FileUpload::make('logo')
                                            ->label('Logo')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('settings')
                                            ->helperText('Disarankan PNG transparan, rasio 1:1.'),
                                        FileUpload::make('background')
                                            ->label('Background')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('settings'),
                                        FileUpload::make('favicon')
                                            ->label('Favicon')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings'),
                                    ])
                                    ->columns(3),
                            ]),
                        Tabs\Tab::make('Info Sekolah')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Section::make('Kontak & Alamat')
                                    ->schema([
                                        Textarea::make('alamat_sekolah')
                                            ->label('Alamat Sekolah')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('no_telp')
                                            ->label('No. Telepon / WhatsApp')
                                            ->tel()
                                            ->prefixIcon('heroicon-o-phone'),
                                        TextInput::make('email_sekolah')
                                            ->label('Email Sekolah')
                                            ->email()
                                            ->prefixIcon('heroicon-o-envelope'),
                                        Textarea::make('jam_operasional')
                                            ->label('Jam Operasional')
                                            ->rows(2)
                                            ->helperText('Ditampilkan di halaman profil website & aplikasi. Satu baris per baris teks.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make('Media Sosial')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Tautan Sosial')
                                    ->schema([
                                        TextInput::make('facebook_url')
                                            ->label('Facebook URL')
                                            ->url()
                                            ->prefixIcon('heroicon-o-globe-alt'),
                                        TextInput::make('instagram_url')
                                            ->label('Instagram URL')
                                            ->url()
                                            ->prefixIcon('heroicon-o-camera'),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make('Lokasi')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Peta Interaktif')
                                    ->description('Pilih titik lokasi sekolah. Embed peta untuk website akan dibuat otomatis.')
                                    ->schema([
                                        ViewField::make('location_picker')
                                            ->label('')
                                            ->view('filament.forms.components.location-map-picker')
                                            ->viewData(function ($get): array {
                                                return [
                                                    'lat' => (float) ($get('latitude') ?: MapEmbed::DEFAULT_LAT),
                                                    'lng' => (float) ($get('longitude') ?: MapEmbed::DEFAULT_LNG),
                                                ];
                                            })
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                        Grid::make(2)->schema([
                                            TextInput::make('latitude')
                                                ->label('Latitude')
                                                ->numeric()
                                                ->required()
                                                ->step(0.0000001)
                                                ->prefixIcon('heroicon-o-arrows-up-down')
                                                ->helperText('Contoh: -5.7725000'),
                                            TextInput::make('longitude')
                                                ->label('Longitude')
                                                ->numeric()
                                                ->required()
                                                ->step(0.0000001)
                                                ->prefixIcon('heroicon-o-arrows-right-left')
                                                ->helperText('Contoh: 134.5833000'),
                                        ]),
                                        Placeholder::make('maps_preview_hint')
                                            ->label('Pratinjau Embed')
                                            ->content(function ($get) {
                                                $lat = MapEmbed::toFloat($get('latitude'));
                                                $lng = MapEmbed::toFloat($get('longitude'));
                                                $url = MapEmbed::googleMapsUrl($lat, $lng);

                                                if (!$url) {
                                                    return 'Isi koordinat untuk melihat tautan peta.';
                                                }

                                                return new HtmlString(
                                                    '<a href="'.e($url).'" target="_blank" rel="noopener" class="text-primary-600 underline font-medium">Buka di Google Maps</a>'
                                                    .' <span class="text-gray-500 text-sm">· Embed OSM dibuat otomatis saat disimpan.</span>'
                                                );
                                            })
                                            ->columnSpanFull(),
                                        Textarea::make('maps_embed')
                                            ->label('Embed HTML (opsional / otomatis)')
                                            ->rows(3)
                                            ->helperText('Biasanya diisi otomatis dari koordinat. Boleh diganti manual jika perlu iframe khusus.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        unset($data['location_picker'], $data['maps_preview_hint']);

        $lat = MapEmbed::toFloat($data['latitude'] ?? null);
        $lng = MapEmbed::toFloat($data['longitude'] ?? null);

        if ($lat !== null && $lng !== null) {
            $data['latitude'] = number_format($lat, 7, '.', '');
            $data['longitude'] = number_format($lng, 7, '.', '');
            $data['maps_url'] = MapEmbed::googleMapsUrl($lat, $lng);
            $data['maps_embed'] = MapEmbed::iframe($lat, $lng) ?? ($data['maps_embed'] ?? null);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => match (true) {
                        in_array($key, ['logo', 'background', 'favicon'], true) => 'image',
                        str_ends_with($key, '_url') || $key === 'maps_url' => 'url',
                        default => 'text',
                    },
                ]
            );
        }

        Cache::forget('profil_sekolah');
        Cache::forget('banner_aktif');
        Cache::forget('settings_public');

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->body('Lokasi dan tampilan web sudah diperbarui.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
