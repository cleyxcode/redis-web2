@extends('layouts.app')

@section('title', 'Detail Pendaftaran - ' . $item->nama_anak)

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-background-dark py-10 px-4">
    <div class="max-w-3xl mx-auto">

        {{-- Back --}}
        <a href="{{ route('pendaftaran.riwayat') }}"
           class="inline-flex items-center gap-1.5 text-slate-400 hover:text-primary transition-colors text-sm font-medium mb-6 group">
            <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Kembali ke Riwayat
        </a>

        @php
            $statusConfig = match($item->status) {
                'diterima' => ['bg'=>'bg-green-500', 'light'=>'bg-green-50 dark:bg-green-900/20', 'border'=>'border-green-200 dark:border-green-800', 'text'=>'text-green-700 dark:text-green-300', 'icon'=>'check_circle', 'label'=>'DITERIMA'],
                'ditolak'  => ['bg'=>'bg-red-500',   'light'=>'bg-red-50 dark:bg-red-900/20',     'border'=>'border-red-200 dark:border-red-800',     'text'=>'text-red-700 dark:text-red-300',     'icon'=>'cancel',       'label'=>'DITOLAK'],
                'perlu_perbaikan' => ['bg'=>'bg-sky-500', 'light'=>'bg-sky-50 dark:bg-sky-900/20', 'border'=>'border-sky-200 dark:border-sky-800', 'text'=>'text-sky-700 dark:text-sky-300', 'icon'=>'upload_file', 'label'=>'PERLU PERBAIKAN'],
                default    => ['bg'=>'bg-amber-400',  'light'=>'bg-amber-50 dark:bg-amber-900/20', 'border'=>'border-amber-200 dark:border-amber-800', 'text'=>'text-amber-700 dark:text-amber-300', 'icon'=>'schedule',     'label'=>'MENUNGGU VERIFIKASI'],
            };
        @endphp

        {{-- Status Banner --}}
        <div class="{{ $statusConfig['light'] }} border {{ $statusConfig['border'] }} rounded-2xl p-5 mb-6 flex items-center gap-4">
            <div class="{{ $statusConfig['bg'] }} w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-lg">
                <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:'FILL' 1">{{ $statusConfig['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold {{ $statusConfig['text'] }} opacity-70 uppercase tracking-widest">Status Pendaftaran</p>
                <p class="text-lg font-black {{ $statusConfig['text'] }}">{{ $statusConfig['label'] }}</p>
                @if($item->nomor_registrasi)
                    <p class="text-xs text-slate-500 mt-1 font-mono">{{ $item->nomor_registrasi }}</p>
                @endif
            </div>
            <div class="ml-auto text-right hidden sm:block">
                <p class="text-xs text-slate-400">Tahun Ajaran</p>
                <p class="font-bold text-slate-700 dark:text-slate-200">{{ $item->infoPendaftaran->tahun_ajaran ?? '-' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-6">
            <a href="{{ route('pendaftaran.bukti-pdf', $item->id) }}"
               class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 border border-sand dark:border-slate-700 px-4 py-2.5 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-200 hover:border-primary/40 transition-colors">
                <span class="material-symbols-outlined text-base text-primary">picture_as_pdf</span>
                Unduh Bukti PDF
            </a>
            @if(!empty($whatsappSekolah))
                <a href="{{ $whatsappSekolah }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-colors">
                    <span class="material-symbols-outlined text-base">chat</span>
                    Hubungi Sekolah (WA)
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-medium">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-medium">{{ session('error') }}</div>
        @endif

        @include('pages.pendaftaran._timeline')

        @if($item->status === 'perlu_perbaikan')
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-sky-200 dark:border-sky-800 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-black text-slate-800 dark:text-white mb-1">Unggah Ulang Dokumen</h3>
            <p class="text-xs text-slate-500 mb-4">
                @if($item->catatan_verifikasi)
                    Catatan sekolah: {{ $item->catatan_verifikasi }}
                @else
                    Unggah dokumen yang diminta, lalu kirim ulang.
                @endif
            </p>
            <form action="{{ route('pendaftaran.resubmit-dokumen', $item->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid sm:grid-cols-3 gap-3">
                    <label class="block text-xs font-bold text-slate-500">
                        Kartu Keluarga
                        <input type="file" name="dokumen_kk" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm">
                    </label>
                    <label class="block text-xs font-bold text-slate-500">
                        Akta Kelahiran
                        <input type="file" name="dokumen_akta" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm">
                    </label>
                    <label class="block text-xs font-bold text-slate-500">
                        Pas Foto
                        <input type="file" name="dokumen_pas_foto" accept=".jpg,.jpeg,.png" class="mt-1 block w-full text-sm">
                    </label>
                </div>
                @error('pendaftaran')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-xl text-sm font-bold">
                    Kirim Ulang Dokumen
                </button>
            </form>
        </div>
        @endif

        {{-- Content card --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">

            {{-- Header anak --}}
            <div class="bg-gradient-to-r from-primary to-primary/80 p-6 flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-3xl" style="font-variation-settings:'FILL' 1">
                        {{ $item->jenis_kelamin === 'L' ? 'boy' : 'girl' }}
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-black text-white">{{ $item->nama_anak }}</h2>
                    <p class="text-white/70 text-sm mt-0.5">
                        Didaftarkan {{ $item->created_at->translatedFormat('d F Y, H:i') }} WIT
                    </p>
                </div>
            </div>

            {{-- Sections --}}
            <div class="divide-y divide-slate-100 dark:divide-slate-700">

                {{-- Data Anak --}}
                <div class="p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-primary">child_care</span>
                        Data Anak
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @include('pages.pendaftaran._row', ['label'=>'Nama Lengkap',   'value'=>$item->nama_anak])
                        @include('pages.pendaftaran._row', ['label'=>'Tempat Lahir',   'value'=>$item->tempat_lahir])
                        @include('pages.pendaftaran._row', ['label'=>'Tanggal Lahir',  'value'=>\Carbon\Carbon::parse($item->tanggal_lahir)->translatedFormat('d F Y')])
                        @include('pages.pendaftaran._row', ['label'=>'Jenis Kelamin',  'value'=>$item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'])
                        @include('pages.pendaftaran._row', ['label'=>'Agama',          'value'=>$item->agama])
                        @include('pages.pendaftaran._row', ['label'=>'Anak Ke',        'value'=>$item->anak_ke])
                        @include('pages.pendaftaran._row', ['label'=>'Asal Sekolah',   'value'=>$item->asal_sekolah])
                        @include('pages.pendaftaran._row', ['label'=>'NIK',            'value'=>$item->nik])
                        @include('pages.pendaftaran._row', ['label'=>'No. KK',         'value'=>$item->no_kk])
                    </div>
                </div>

                {{-- Alamat --}}
                <div class="p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-primary">home</span>
                        Alamat
                    </h3>
                    <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">{{ $item->alamat ?: '-' }}</p>
                </div>

                {{-- Data Orang Tua --}}
                <div class="p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-primary">family_restroom</span>
                        Data Orang Tua / Wali
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @include('pages.pendaftaran._row', ['label'=>'Nama Ayah',        'value'=>$item->nama_ayah])
                        @include('pages.pendaftaran._row', ['label'=>'Pekerjaan Ayah',   'value'=>$item->pekerjaan_ayah])
                        @include('pages.pendaftaran._row', ['label'=>'Nama Ibu',         'value'=>$item->nama_ibu])
                        @include('pages.pendaftaran._row', ['label'=>'Pekerjaan Ibu',    'value'=>$item->pekerjaan_ibu])
                        @include('pages.pendaftaran._row', ['label'=>'Nama Wali',        'value'=>$item->nama_wali])
                        @include('pages.pendaftaran._row', ['label'=>'No. HP',           'value'=>$item->no_hp])
                    </div>
                </div>

                {{-- Dokumen --}}
                <div class="p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-primary">description</span>
                        Dokumen Pendukung
                        @if($item->dokumen_lengkap)
                            <span class="ml-auto text-[10px] uppercase tracking-wide bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">Lengkap</span>
                        @else
                            <span class="ml-auto text-[10px] uppercase tracking-wide bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">Belum lengkap</span>
                        @endif
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach([
                            'Kartu Keluarga' => $item->dokumen_kk_url,
                            'Akta Kelahiran' => $item->dokumen_akta_url,
                            'Pas Foto' => $item->dokumen_pas_foto_url,
                            'Dokumen lama' => $item->dokumen_url,
                        ] as $label => $url)
                            @if($url)
                                <a href="{{ $url }}" target="_blank"
                                   class="inline-flex items-center gap-2 bg-primary/8 hover:bg-primary text-primary hover:text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                                    <span class="material-symbols-outlined text-base">open_in_new</span>
                                    {{ $label }}
                                </a>
                            @endif
                        @endforeach
                        @if(! $item->dokumen_kk_url && ! $item->dokumen_akta_url && ! $item->dokumen_pas_foto_url && ! $item->dokumen_url)
                            <p class="text-sm text-slate-400">Belum ada dokumen diunggah.</p>
                        @endif
                    </div>
                </div>

                @if($item->catatan_verifikasi)
                <div class="p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-primary">sticky_note_2</span>
                        Catatan Verifikasi
                    </h3>
                    <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">{{ $item->catatan_verifikasi }}</p>
                </div>
                @endif

            </div>
        </div>

        {{-- Info box --}}
        @if($item->status === 'pending')
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-500 text-base shrink-0 mt-0.5" style="font-variation-settings:'FILL' 1">info</span>
            <p class="text-sm text-blue-700 dark:text-blue-300">
                Pendaftaran Anda sedang dalam proses verifikasi oleh pihak sekolah. Silakan tunggu pengumuman selanjutnya.
            </p>
        </div>
        @elseif($item->status === 'perlu_perbaikan')
        <div class="mt-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-sky-500 text-base shrink-0 mt-0.5" style="font-variation-settings:'FILL' 1">upload_file</span>
            <p class="text-sm text-sky-700 dark:text-sky-300">
                Sekolah meminta perbaikan dokumen.
                @if($item->catatan_verifikasi) Catatan: {{ $item->catatan_verifikasi }} @endif
            </p>
        </div>
        @elseif($item->status === 'diterima')
        <div class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-green-500 text-base shrink-0 mt-0.5" style="font-variation-settings:'FILL' 1">celebration</span>
            <p class="text-sm text-green-700 dark:text-green-300">
                Selamat! Pendaftaran <strong>{{ $item->nama_anak }}</strong> telah diterima.
                @if($item->catatan_verifikasi) Catatan: {{ $item->catatan_verifikasi }} @endif
            </p>
        </div>
        @elseif($item->status === 'ditolak')
        <div class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-red-500 text-base shrink-0 mt-0.5" style="font-variation-settings:'FILL' 1">info</span>
            <p class="text-sm text-red-700 dark:text-red-300">
                Mohon maaf, pendaftaran tidak dapat diproses.
                @if($item->catatan_verifikasi) Alasan: {{ $item->catatan_verifikasi }} @else Silakan hubungi pihak sekolah. @endif
            </p>
        </div>
        @endif

    </div>
</div>
@endsection
