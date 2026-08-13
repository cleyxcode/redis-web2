<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pendaftaran — {{ $item->nomor_registrasi ?? $item->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 32px; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #0B7B8B; }
        .sub { color: #555; margin-bottom: 20px; }
        .box { border: 1px solid #cde; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .row { margin-bottom: 8px; }
        .label { color: #666; width: 140px; display: inline-block; }
        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 999px;
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            background: #e8f6f7; color: #0B7B8B;
        }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.bad { background: #fee2e2; color: #991b1b; }
        .badge.fix { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 28px; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $school }}</h1>
    <div class="sub">Bukti Pendaftaran PPDB {{ $item->infoPendaftaran->tahun_ajaran ?? '' }}</div>

    <div class="box">
        <div class="row"><span class="label">No. Registrasi</span>
            <strong>{{ $item->nomor_registrasi ?? '—' }}</strong>
        </div>
        <div class="row"><span class="label">Nama Anak</span>{{ $item->nama_anak }}</div>
        <div class="row"><span class="label">Tempat/Tgl Lahir</span>
            {{ $item->tempat_lahir ?? '—' }},
            {{ optional($item->tanggal_lahir)?->format('d/m/Y') ?? '—' }}
        </div>
        <div class="row"><span class="label">Jenis Kelamin</span>
            {{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
        </div>
        <div class="row"><span class="label">NIK</span>{{ $item->nik ?? '—' }}</div>
        <div class="row"><span class="label">No. HP</span>{{ $item->no_hp }}</div>
        <div class="row"><span class="label">Tanggal Daftar</span>
            {{ optional($item->created_at)?->format('d/m/Y H:i') ?? '—' }}
        </div>
        <div class="row"><span class="label">Status</span>
            @php
                $cls = match ($item->status) {
                    'diterima' => 'ok',
                    'ditolak' => 'bad',
                    'perlu_perbaikan' => 'fix',
                    default => '',
                };
                $label = match ($item->status) {
                    'diterima' => 'Diterima',
                    'ditolak' => 'Ditolak',
                    'perlu_perbaikan' => 'Perlu Perbaikan',
                    default => 'Menunggu Verifikasi',
                };
            @endphp
            <span class="badge {{ $cls }}">{{ $label }}</span>
        </div>
        @if(filled($item->catatan_verifikasi))
            <div class="row" style="margin-top:12px">
                <span class="label">Catatan</span>{{ $item->catatan_verifikasi }}
            </div>
        @endif
    </div>

    <p>Simpan bukti ini sebagai referensi. Tunjukkan nomor registrasi saat menghubungi sekolah.</p>

    <div class="footer">
        Dicetak {{ $generatedAt->format('d/m/Y H:i') }} — {{ $school }}
    </div>
</body>
</html>
