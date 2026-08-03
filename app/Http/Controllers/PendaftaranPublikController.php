<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InfoPendaftaran;
use App\Models\Pendaftaran;
use App\Services\PendaftaranService;
use Illuminate\Http\Request;

class PendaftaranPublikController extends Controller
{
    public function __construct(private PendaftaranService $service) {}

    public function index()
    {
        $infoPendaftaran = InfoPendaftaran::where('status', 'aktif')->first();
        $isGuest = ! auth()->check();

        return view('pages.pendaftaran.index', compact('infoPendaftaran', 'isGuest'));
    }

    public function store(Request $request)
    {
        $infoPendaftaran = InfoPendaftaran::where('status', 'aktif')->firstOrFail();

        $validated = $request->validate(array_merge([
            'nama_anak' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'agama' => 'required|string|max:100',
            'anak_ke' => 'nullable|integer|min:1',
            'asal_sekolah' => 'nullable|string|max:255',
            'nik' => 'nullable|string|max:20',
            'no_kk' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'nama_ayah' => 'nullable|string|max:255',
            'pekerjaan_ayah' => 'nullable|string|max:255',
            'nama_ibu' => 'nullable|string|max:255',
            'pekerjaan_ibu' => 'nullable|string|max:255',
            'nama_wali' => 'nullable|string|max:255',
            'no_hp' => 'required|string|max:20',
        ], PendaftaranService::dokumenRules(required: true)), [
            'nama_anak.required' => 'Nama anak wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'agama.required' => 'Agama wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'dokumen_kk.required' => 'Dokumen Kartu Keluarga wajib diunggah.',
            'dokumen_akta.required' => 'Dokumen Akta Kelahiran wajib diunggah.',
        ]);

        $this->service->submit(
            $request->user(),
            $validated,
            [
                'kk' => $request->file('dokumen_kk'),
                'akta' => $request->file('dokumen_akta'),
                'pas_foto' => $request->file('dokumen_pas_foto'),
            ]
        );

        return redirect()->route('pendaftaran.sukses')
            ->with('nama_anak', $validated['nama_anak'])
            ->with('no_hp', $validated['no_hp']);
    }

    public function sukses()
    {
        return view('pages.pendaftaran.sukses');
    }

    public function riwayat()
    {
        $pendaftaran = Pendaftaran::where('user_id', auth()->id())
            ->with('infoPendaftaran')
            ->latest()
            ->get();

        return view('pages.pendaftaran.riwayat', compact('pendaftaran'));
    }

    public function detail($id)
    {
        $item = Pendaftaran::where('user_id', auth()->id())
            ->with('infoPendaftaran')
            ->findOrFail($id);

        return view('pages.pendaftaran.detail', compact('item'));
    }
}
