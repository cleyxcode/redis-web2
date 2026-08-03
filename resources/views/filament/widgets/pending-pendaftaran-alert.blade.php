@php
    $count = $this->getPendingCount();
@endphp

<div class="fi-wi-widget rounded-xl border border-warning-300 bg-warning-50 p-4 shadow-sm dark:border-warning-600 dark:bg-warning-950/40">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-500/15 text-warning-600 dark:text-warning-400">
                <x-heroicon-o-bell-alert class="h-6 w-6" />
            </div>
            <div>
                <h3 class="text-sm font-semibold text-warning-800 dark:text-warning-200">
                    Ada {{ number_format($count) }} pendaftaran baru menunggu verifikasi
                </h3>
                <p class="mt-1 text-sm text-warning-700 dark:text-warning-300/90">
                    Segera tinjau data calon siswa di menu PPDB &rarr; Data Pendaftaran.
                </p>
            </div>
        </div>

        <a
            href="{{ $this->getPendaftaranIndexUrl() }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-warning-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-warning-500 focus:outline-none focus:ring-2 focus:ring-warning-500 focus:ring-offset-2"
        >
            <x-heroicon-m-arrow-right class="h-4 w-4" />
            Buka Data Pendaftaran
        </a>
    </div>
</div>
