<x-mail::message>
# Update Status Pendaftaran

Halo,

Status pendaftaran atas nama **{{ $pendaftaran->nama_anak }}** kini: **{{ $statusLabel }}**.

@if($pendaftaran->status === 'diterima')
Selamat! Anak Anda telah diterima. Silakan cek aplikasi atau website untuk detail selanjutnya.
@elseif($pendaftaran->status === 'ditolak')
Mohon maaf, pendaftaran belum dapat kami terima pada periode ini.
@endif

@if(filled($pendaftaran->catatan_verifikasi))
**Catatan sekolah:**  
{{ $pendaftaran->catatan_verifikasi }}
@endif

<x-mail::button :url="url('/pendaftaran/riwayat/'.$pendaftaran->id)">
Lihat Detail Pendaftaran
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
