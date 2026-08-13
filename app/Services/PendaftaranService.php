<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PendaftaranException;
use App\Models\InfoPendaftaran;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Repositories\Contracts\InfoPendaftaranRepositoryInterface;
use App\Repositories\Contracts\PendaftaranRepositoryInterface;
use App\Rules\SafeDocumentMime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendaftaranService
{
    private const MAX_DOKUMEN_KB = 5120;

    /** Status yang masih dianggap "aktif" — tidak boleh daftar ulang. */
    public const ACTIVE_STATUSES = ['pending', 'perlu_perbaikan', 'diterima'];

    public function __construct(
        private PendaftaranRepositoryInterface $repo,
        private InfoPendaftaranRepositoryInterface $infoRepo,
    ) {}

    public function getInfoAktif()
    {
        if (Cache::has('info_pendaftaran_aktif')) {
            return Cache::get('info_pendaftaran_aktif');
        }

        $info = $this->infoRepo->getActive();
        if ($info !== null) {
            Cache::put('info_pendaftaran_aktif', $info, now()->addMinutes(30));
        }

        return $info;
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
     *
     * @throws PendaftaranException
     */
    public function submit(User $user, array $data, array $dokumen = []): Pendaftaran
    {
        $info = $this->infoRepo->getActive();

        if (! $info) {
            throw PendaftaranException::notOpen();
        }

        $this->assertNoDuplicate($user, $info, isset($data['nik']) ? (string) $data['nik'] : null);

        return DB::transaction(function () use ($user, $data, $dokumen, $info) {
            $kk = $dokumen['kk'] ?? null;
            $akta = $dokumen['akta'] ?? null;
            $pasFoto = $dokumen['pas_foto'] ?? null;
            $legacy = $dokumen['legacy'] ?? null;

            $dokumenKkPath = $kk?->store('pendaftaran/kk', 'public');
            $dokumenAktaPath = $akta?->store('pendaftaran/akta', 'public');
            $dokumenPasFotoPath = $pasFoto?->store('pendaftaran/pas_foto', 'public');
            $dokumenLegacyPath = $legacy?->store('pendaftaran', 'public');

            if ($dokumenKkPath === null && $dokumenLegacyPath !== null) {
                $dokumenKkPath = $dokumenLegacyPath;
            }

            $pendaftaran = $this->repo->create([
                'user_id' => $user->id,
                'info_pendaftaran_id' => $info->id,
                'nomor_registrasi' => $this->generateNomorRegistrasi($info),
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

            return $pendaftaran->fresh() ?? $pendaftaran;
        });
    }

    /**
     * Unggah ulang dokumen saat status perlu_perbaikan → kembali ke pending.
     *
     * @param  array{kk?: ?UploadedFile, akta?: ?UploadedFile, pas_foto?: ?UploadedFile}  $dokumen
     *
     * @throws PendaftaranException
     */
    public function resubmitDokumen(User $user, int $id, array $dokumen): Pendaftaran
    {
        $pendaftaran = $this->repo->findByUser($id, $user->id);
        if ($pendaftaran === null) {
            throw PendaftaranException::notFound();
        }

        if ($pendaftaran->status !== 'perlu_perbaikan') {
            throw PendaftaranException::cannotResubmit();
        }

        $kk = $dokumen['kk'] ?? null;
        $akta = $dokumen['akta'] ?? null;
        $pasFoto = $dokumen['pas_foto'] ?? null;

        if ($kk === null && $akta === null && $pasFoto === null) {
            throw new PendaftaranException('Unggah minimal satu dokumen yang diperbaiki.');
        }

        return DB::transaction(function () use ($pendaftaran, $kk, $akta, $pasFoto) {
            $updates = [
                'status' => 'pending',
                'verified_at' => null,
                'verified_by' => null,
            ];

            if ($kk !== null) {
                $this->deleteStored($pendaftaran->dokumen_kk);
                $path = $kk->store('pendaftaran/kk', 'public');
                $updates['dokumen_kk'] = $path;
                $updates['dokumen'] = $path;
            }

            if ($akta !== null) {
                $this->deleteStored($pendaftaran->dokumen_akta);
                $updates['dokumen_akta'] = $akta->store('pendaftaran/akta', 'public');
            }

            if ($pasFoto !== null) {
                $this->deleteStored($pendaftaran->dokumen_pas_foto);
                $updates['dokumen_pas_foto'] = $pasFoto->store('pendaftaran/pas_foto', 'public');
            }

            $pendaftaran->update($updates);

            return $pendaftaran->fresh() ?? $pendaftaran;
        });
    }

    public function buktiPdf(User $user, int $id): StreamedResponse
    {
        $pendaftaran = $this->repo->findByUser($id, $user->id);
        if ($pendaftaran === null) {
            throw PendaftaranException::notFound();
        }

        $pendaftaran->loadMissing('infoPendaftaran');

        $filename = 'bukti-'.($pendaftaran->nomor_registrasi ?? $pendaftaran->id).'.pdf';

        $pdf = Pdf::loadView('exports.bukti-pendaftaran-pdf', [
            'item' => $pendaftaran,
            'generatedAt' => now(),
            'school' => 'SD Negeri Warialau',
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            static fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @throws PendaftaranException
     */
    private function assertNoDuplicate(User $user, InfoPendaftaran $info, ?string $nik): void
    {
        $existsForUser = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->where('info_pendaftaran_id', $info->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if ($existsForUser) {
            throw PendaftaranException::duplicateUser();
        }

        $nik = $nik !== null ? trim($nik) : '';
        if ($nik !== '') {
            $existsNik = Pendaftaran::query()
                ->where('info_pendaftaran_id', $info->id)
                ->where('nik', $nik)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->exists();

            if ($existsNik) {
                throw PendaftaranException::duplicateNik();
            }
        }
    }

    private function generateNomorRegistrasi(InfoPendaftaran $info): string
    {
        $tahun = preg_replace('/\D+/', '', (string) $info->tahun_ajaran) ?: date('Y');
        $prefix = 'PPDB-'.$tahun;

        $last = Pendaftaran::query()
            ->withTrashed()
            ->where('info_pendaftaran_id', $info->id)
            ->where('nomor_registrasi', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('nomor_registrasi');

        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m) === 1) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%04d', $prefix, $seq);
    }

    private function deleteStored(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
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
