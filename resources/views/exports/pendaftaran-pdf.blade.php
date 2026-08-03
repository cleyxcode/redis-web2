<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Pendaftaran — {{ $school }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { margin-bottom: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #0B7B8B; color: #fff; font-size: 9px; }
        tr:nth-child(even) td { background: #f5fafa; }
        .badge { font-weight: bold; text-transform: uppercase; font-size: 8px; }
    </style>
</head>
<body>
    <h1>Data Pendaftaran PPDB</h1>
    <div class="meta">
        {{ $school }} &mdash; Diekspor {{ $generatedAt->format('d/m/Y H:i') }} &mdash; Total: {{ $items->count() }} data
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Anak</th>
                <th>JK</th>
                <th>Tgl Lahir</th>
                <th>Asal Sekolah</th>
                <th>Nama Ayah</th>
                <th>No HP</th>
                <th>Status</th>
                <th>Tgl Daftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nama_anak }}</td>
                    <td>{{ $item->jenis_kelamin }}</td>
                    <td>{{ $item->tanggal_lahir }}</td>
                    <td>{{ $item->asal_sekolah }}</td>
                    <td>{{ $item->nama_ayah }}</td>
                    <td>{{ $item->no_hp }}</td>
                    <td class="badge">{{ $item->status }}</td>
                    <td>{{ optional($item->created_at)?->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada data pendaftaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
