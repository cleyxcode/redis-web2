<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\InfoPendaftaranRepositoryInterface;
use App\Repositories\Contracts\PendaftaranRepositoryInterface;
use App\Rules\SafeDocumentMime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class PendaftaranService
{
    private const MAX_DOKUMEN_KB = 5120;

    public function __construct(
        private PendaftaranRepositoryInterface $repo,
        private InfoPendaftaranRepositoryInterface $infoRepo,
    ) {}

    public function getInfoAktif()
    {
        return Cache::remember('info_pendaftaran_aktif', now()->addMinutes(30), fn () => $this->infoRepo->getActive());
    }

    public function getRiwayat(User $user)
    {
        return $this->repo->getByUser($user->id);
    }

    public function getDetail(User $user, int $id)
    {
        return $this->repo->findByUser($id, $user->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{kk?: ?UploadedFile, akta?: ?UploadedFile, pas_foto?: ?UploadedFile, legacy?: ?UploadedFile}  $dokumen
     */
    public function submit(User $user, array $data, array $dokumen = [])
    {
        $info = $this->infoRepo->getActive();

        if (! $info) {
            return null;
        }

        $kk = $dokumen['kk'] ?? null;
        $akta = $dokumen['akta'] ?? null;
        $pasFoto = $dokumen['pas_foto'] ?? null;
        $legacy = $dokumen['legacy'] ?? null;

        $dokumenKkPath = $kk?->store('pendaftaran/kk', 'public');
        $dokumenAktaPath = $akta?->store('pendaftaran/akta', 'public');
        $dokumenPasFotoPath = $pasFoto?->store('pendaftaran/pas_foto', 'public');
        $dokumenLegacyPath = $legacy?->store('pendaftaran', 'public');

        // Backward compat: single dokumen maps to KK if KK empty
        if ($dokumenKkPath === null && $dokumenLegacyPath !== null) {
            $dokumenKkPath = $dokumenLegacyPath;
        }

        return $this->repo->create([
            'user_id' => $user->id,
            'info_pendaftaran_id' => $info->id,
            'nama_anak' => $data['nama_anak'],
            'tempat_lahir' => $data['tempat_lahir'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'agama' => $data['agama'],
            'anak_ke' => $data['anak_ke'] ?? null,
            'asal_sekolah' => $data['asal_sekolah'] ?? null,
            'nik' => $data['nik'] ?? null,
            'no_kk' => $data['no_kk'] ?? null,
            'alamat' => $data['alamat'],
            'nama_ayah' => $data['nama_ayah'] ?? null,
            'pekerjaan_ayah' => $data['pekerjaan_ayah'] ?? null,
            'nama_ibu' => $data['nama_ibu'] ?? null,
            'pekerjaan_ibu' => $data['pekerjaan_ibu'] ?? null,
            'nama_wali' => $data['nama_wali'] ?? null,
            'no_hp' => $data['no_hp'],
            'dokumen' => $dokumenLegacyPath ?? $dokumenKkPath,
            'dokumen_kk' => $dokumenKkPath,
            'dokumen_akta' => $dokumenAktaPath,
            'dokumen_pas_foto' => $dokumenPasFotoPath,
            'status' => 'pending',
        ]);
    }

    public static function dokumenRules(bool $required = true): array
    {
        $base = [
            'file',
            'mimes:pdf,jpg,jpeg,png',
            'mimetypes:application/pdf,image/jpeg,image/png',
            'max:'.self::MAX_DOKUMEN_KB,
            new SafeDocumentMime,
        ];

        return [
            'dokumen_kk' => array_merge([$required ? 'required' : 'nullable'], $base),
            'dokumen_akta' => array_merge([$required ? 'required' : 'nullable'], $base),
            'dokumen_pas_foto' => array_merge(['nullable'], $base),
            'dokumen' => array_merge(['nullable'], $base),
        ];
    }
}
