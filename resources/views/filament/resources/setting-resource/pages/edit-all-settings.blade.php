<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="mr-auto hidden text-sm text-gray-500 dark:text-gray-400 sm:block">
                Perubahan langsung tampil di website &amp; API setelah disimpan.
            </p>
            <x-filament::button type="submit" icon="heroicon-o-check-circle">
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
