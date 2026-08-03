<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Siswa — {{ $school }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { margin-bottom: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #0B7B8B; color: #fff; font-size: 10px; }
        tr:nth-child(even) td { background: #f5fafa; }
        .badge { font-weight: bold; text-transform: uppercase; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Data Siswa</h1>
    <div class="meta">
        {{ $school }} &mdash; Diekspor {{ $generatedAt->format('d/m/Y H:i') }} &mdash; Total: {{ $items->count() }} data
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>JK</th>
                <th>Tahun Ajaran</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->nis }}</td>
                    <td>{{ $item->kelas }}</td>
                    <td>{{ $item->jenis_kelamin }}</td>
                    <td>{{ $item->tahun_ajaran }}</td>
                    <td class="badge">{{ $item->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada data siswa.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
