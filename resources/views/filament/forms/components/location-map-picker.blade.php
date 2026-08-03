{{-- Interactive map picker for Filament settings (Leaflet + OpenStreetMap) --}}
@php
    $lat = (float) ($get('latitude') ?: \App\Support\MapEmbed::DEFAULT_LAT);
    $lng = (float) ($get('longitude') ?: \App\Support\MapEmbed::DEFAULT_LNG);
@endphp

@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        [x-cloak] { display: none !important; }
        .fi-location-map .leaflet-container { font: inherit; border-radius: 0; }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.__locationMapPickerRegistered) return;
            window.__locationMapPickerRegistered = true;

            Alpine.data('locationMapPicker', (config) => ({
                lat: config.initialLat,
                lng: config.initialLng,
                map: null,
                marker: null,
                locating: false,
                error: '',
                setLat: config.setLat,
                setLng: config.setLng,
                defaultLat: config.defaultLat,
                defaultLng: config.defaultLng,

                init() {
                    this.$nextTick(() => this.buildMap());
                },

                buildMap() {
                    if (typeof L === 'undefined') {
                        this.error = 'Leaflet gagal dimuat. Periksa koneksi internet.';
                        return;
                    }

                    this.map = L.map(this.$refs.map, {
                        zoomControl: true,
                        scrollWheelZoom: true,
                    }).setView([this.lat, this.lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(this.map);

                    this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);

                    this.marker.on('dragend', () => {
                        const pos = this.marker.getLatLng();
                        this.applyCoords(pos.lat, pos.lng, false);
                    });

                    this.map.on('click', (e) => {
                        this.applyCoords(e.latlng.lat, e.latlng.lng, true);
                    });

                    setTimeout(() => this.map.invalidateSize(), 250);
                },

                applyCoords(lat, lng, moveMarker = true) {
                    this.lat = lat;
                    this.lng = lng;
                    this.error = '';
                    if (moveMarker && this.marker) {
                        this.marker.setLatLng([lat, lng]);
                        this.map.panTo([lat, lng]);
                    }
                    this.setLat(Number(lat).toFixed(7));
                    this.setLng(Number(lng).toFixed(7));
                },

                useCurrentLocation() {
                    if (!navigator.geolocation) {
                        this.error = 'Browser tidak mendukung geolokasi.';
                        return;
                    }
                    this.locating = true;
                    this.error = '';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.locating = false;
                            this.applyCoords(pos.coords.latitude, pos.coords.longitude, true);
                            this.map.setZoom(17);
                        },
                        (err) => {
                            this.locating = false;
                            this.error = err.code === 1
                                ? 'Izin lokasi ditolak. Izinkan akses lokasi di browser.'
                                : 'Gagal mengambil lokasi saat ini.';
                        },
                        { enableHighAccuracy: true, timeout: 12000 }
                    );
                },

                resetDefault() {
                    this.applyCoords(this.defaultLat, this.defaultLng, true);
                    this.map.setZoom(15);
                },
            }));
        });
    </script>
@endassets

<div
    wire:ignore
    class="fi-location-map space-y-4"
    x-data="locationMapPicker({
        initialLat: {{ $lat }},
        initialLng: {{ $lng }},
        defaultLat: {{ \App\Support\MapEmbed::DEFAULT_LAT }},
        defaultLng: {{ \App\Support\MapEmbed::DEFAULT_LNG }},
        setLat: (v) => $wire.set('data.latitude', v),
        setLng: (v) => $wire.set('data.longitude', v),
    })"
    x-init="init()"
>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Pilih Lokasi di Peta</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Klik peta atau geser pin. Atau gunakan lokasi perangkat saat ini.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="useCurrentLocation()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/40"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                    <span x-text="locating ? 'Mengambil lokasi…' : 'Lokasi Saat Ini'"></span>
                </button>
                <button
                    type="button"
                    @click="resetDefault()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Reset Warialau
                </button>
            </div>
        </div>

        <div class="relative">
            <div x-ref="map" class="h-80 w-full"></div>
            <div
                x-show="error"
                x-cloak
                class="absolute inset-x-3 bottom-3 rounded-lg bg-danger-600/95 px-3 py-2 text-xs font-medium text-white shadow"
                x-text="error"
            ></div>
        </div>

        <div class="grid grid-cols-1 gap-3 border-t border-gray-100 bg-gray-50/60 px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-950/30 dark:text-gray-300 sm:grid-cols-2">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-gray-800 dark:text-gray-100">Latitude:</span>
                <span x-text="Number(lat).toFixed(7)"></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-semibold text-gray-800 dark:text-gray-100">Longitude:</span>
                <span x-text="Number(lng).toFixed(7)"></span>
            </div>
        </div>
    </div>
</div>
