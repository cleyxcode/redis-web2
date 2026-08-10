@extends('layouts.app')

@section('title', 'Notifikasi — ' . ($profil->nama_sekolah ?? 'SD Negeri Warialau'))

@section('content')

<section class="bg-white dark:bg-slate-950 border-b border-sand dark:border-slate-800 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="flex items-center gap-2 mb-4 text-xs font-bold text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-secondary transition-colors">Beranda</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-primary dark:text-accent">Notifikasi</span>
        </div>
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <span class="section-eyebrow mb-3 inline-flex">
                    <span class="material-symbols-outlined text-sm">notifications</span>
                    Pusat Notifikasi
                </span>
                <h1 class="font-display text-3xl md:text-4xl font-black text-slate-900 dark:text-white mb-1 reveal">
                    Notifikasi <span class="text-gradient">Anda</span>
                </h1>
                <p class="text-slate-400 text-sm reveal" style="transition-delay:.08s">
                    @if($unread > 0)
                        {{ $unread }} notifikasi belum dibaca.
                    @else
                        Semua notifikasi sudah dibaca.
                    @endif
                </p>
            </div>
            @if($unread > 0)
                <form action="{{ route('notifikasi.baca-semua') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-base">done_all</span>
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>

<div class="bg-cream dark:bg-background-dark min-h-screen py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        @if(session('success'))
            <div class="mb-5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 flex items-center gap-3 reveal">
                <span class="material-symbols-outlined text-green-500 text-xl shrink-0" style="font-variation-settings:'FILL' 1">check_circle</span>
                <p class="text-sm text-green-700 dark:text-green-300 font-medium">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-center gap-3 reveal">
                <span class="material-symbols-outlined text-red-500 text-xl shrink-0">error</span>
                <p class="text-sm text-red-700 dark:text-red-300 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        <div class="flex gap-2 mb-6 reveal">
            <a href="{{ route('notifikasi.index') }}"
               class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $filter === 'semua' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white dark:bg-slate-900 text-slate-600 border border-sand dark:border-slate-700' }}">
                Semua
            </a>
            <a href="{{ route('notifikasi.index', ['filter' => 'belum']) }}"
               class="px-4 py-2 rounded-xl text-sm font-bold transition-all inline-flex items-center gap-2 {{ $filter === 'belum' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white dark:bg-slate-900 text-slate-600 border border-sand dark:border-slate-700' }}">
                Belum Dibaca
                @if($unread > 0)
                    <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-black flex items-center justify-center {{ $filter === 'belum' ? 'bg-accent text-primary' : 'bg-accent/15 text-accent' }}">
                        {{ $unread > 99 ? '99+' : $unread }}
                    </span>
                @endif
            </a>
        </div>

        @if($notifikasi->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-sand dark:border-slate-800 shadow-sm p-16 text-center reveal">
                <div class="w-20 h-20 bg-primary/8 rounded-3xl flex items-center justify-center mx-auto mb-5">
                    <span class="material-symbols-outlined text-4xl text-primary/30">notifications_off</span>
                </div>
                <h3 class="font-display text-xl font-black text-slate-700 dark:text-slate-200 mb-2">
                    {{ $filter === 'belum' ? 'Semua sudah dibaca' : 'Belum ada notifikasi' }}
                </h3>
                <p class="text-slate-400 text-sm">Kami akan memberi tahu Anda jika ada informasi terbaru.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($notifikasi as $idx => $item)
                    @php
                        $icon = match($item->tipe) {
                            'pendaftaran' => 'how_to_reg',
                            'berita' => 'newspaper',
                            'pengumuman' => 'campaign',
                            'galeri' => 'photo_library',
                            'kegiatan' => 'event',
                            default => 'notifications',
                        };
                        $color = match($item->tipe) {
                            'pendaftaran' => 'text-accent bg-accent/10',
                            'berita' => 'text-primary bg-primary/10',
                            'pengumuman' => 'text-sky-600 bg-sky-50',
                            'galeri' => 'text-pink-600 bg-pink-50',
                            'kegiatan' => 'text-violet-600 bg-violet-50',
                            default => 'text-slate-500 bg-slate-100',
                        };
                        $label = match($item->tipe) {
                            'pendaftaran' => 'Pendaftaran',
                            'berita' => 'Berita',
                            'pengumuman' => 'Pengumuman',
                            'galeri' => 'Galeri',
                            'kegiatan' => 'Kegiatan',
                            default => 'Info',
                        };
                    @endphp

                    <div class="reveal flex items-stretch gap-2" style="transition-delay:{{ min($idx * 0.04, 0.32) }}s">
                    <form action="{{ route('notifikasi.baca', $item->id) }}" method="POST"
                          class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full text-left bg-white dark:bg-slate-900 rounded-2xl border {{ $item->dibaca ? 'border-sand dark:border-slate-800' : 'border-primary/20 bg-primary/[0.03]' }} shadow-sm hover:shadow-md transition-all p-4 sm:p-5 flex gap-4">
                            <div class="w-12 h-12 rounded-2xl {{ $color }} flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1">{{ $icon }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <h3 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base leading-snug {{ $item->dibaca ? '' : 'font-black' }}">
                                        {{ $item->judul }}
                                    </h3>
                                    @unless($item->dibaca)
                                        <span class="mt-1.5 w-2 h-2 rounded-full bg-accent shrink-0"></span>
                                    @endunless
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 line-clamp-2 leading-relaxed">{{ $item->pesan }}</p>
                                <div class="flex items-center gap-3 mt-3">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full {{ $color }}">
                                        {{ $label }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-semibold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">schedule</span>
                                        {{ $item->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y · H:i') }}
                                    </span>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 self-center shrink-0">chevron_right</span>
                        </button>
                    </form>
                    <form action="{{ route('notifikasi.destroy', $item->id) }}" method="POST" class="shrink-0"
                          onsubmit="return confirm('Hapus notifikasi ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="h-full px-3 rounded-xl border border-sand dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-400 hover:text-red-500 hover:border-red-200 transition-colors"
                                title="Hapus">
                            <span class="material-symbols-outlined text-xl">delete</span>
                        </button>
                    </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $notifikasi->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
