{{-- Timeline status pendaftaran --}}
@php
    $steps = [
        [
            'key' => 'diajukan',
            'label' => 'Diajukan',
            'desc' => $item->created_at?->translatedFormat('d M Y, H:i') ?? '-',
            'done' => true,
        ],
        [
            'key' => 'verifikasi',
            'label' => 'Verifikasi',
            'desc' => $item->status === 'pending'
                ? 'Sedang diproses sekolah'
                : ($item->verified_at?->translatedFormat('d M Y, H:i') ?? 'Selesai diverifikasi'),
            'done' => in_array($item->status, ['diterima', 'ditolak'], true) || filled($item->verified_at),
            'active' => $item->status === 'pending',
        ],
        [
            'key' => 'hasil',
            'label' => match ($item->status) {
                'diterima' => 'Diterima',
                'ditolak' => 'Ditolak',
                default => 'Hasil',
            },
            'desc' => match ($item->status) {
                'diterima' => 'Selamat, pendaftaran diterima',
                'ditolak' => 'Pendaftaran tidak diterima',
                default => 'Menunggu pengumuman',
            },
            'done' => in_array($item->status, ['diterima', 'ditolak'], true),
            'danger' => $item->status === 'ditolak',
        ],
    ];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-2xl border border-sand dark:border-slate-700 shadow-sm p-5 mb-6">
    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
        <span class="material-symbols-outlined text-sm text-primary">timeline</span>
        Alur Status
    </h3>
    <ol class="relative space-y-0">
        @foreach($steps as $i => $step)
            @php
                $done = $step['done'] ?? false;
                $active = $step['active'] ?? false;
                $danger = $step['danger'] ?? false;
                $dot = $danger ? 'bg-red-500' : ($done ? 'bg-emerald-500' : ($active ? 'bg-amber-400' : 'bg-slate-300'));
                $text = $danger ? 'text-red-600' : ($done || $active ? 'text-slate-900 dark:text-white' : 'text-slate-400');
            @endphp
            <li class="relative flex gap-4 {{ !$loop->last ? 'pb-6' : '' }}">
                @unless($loop->last)
                    <span class="absolute left-[11px] top-6 bottom-0 w-0.5 {{ $done ? 'bg-emerald-200' : 'bg-slate-200 dark:bg-slate-700' }}"></span>
                @endunless
                <span class="relative z-10 w-6 h-6 rounded-full {{ $dot }} flex items-center justify-center shrink-0 ring-4 ring-white dark:ring-slate-800">
                    @if($done)
                        <span class="material-symbols-outlined text-white text-[14px]" style="font-variation-settings:'FILL' 1">
                            {{ $danger ? 'close' : 'check' }}
                        </span>
                    @elseif($active)
                        <span class="w-2 h-2 rounded-full bg-white"></span>
                    @endif
                </span>
                <div class="min-w-0 pt-0.5">
                    <p class="text-sm font-bold {{ $text }}">{{ $step['label'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $step['desc'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</div>
