<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Activitylog\Facades\CauserResolver;

class PendaftaranVerificationService
{
    public function terima(Pendaftaran $pendaftaran, User $admin, ?string $catatan = null): Pendaftaran
    {
        return $this->updateStatus($pendaftaran, $admin, 'diterima', $catatan, createSiswa: true);
    }

    public function tolak(Pendaftaran $pendaftaran, User $admin, string $alasan): Pendaftaran
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        return $this->updateStatus($pendaftaran, $admin, 'ditolak', $alasan);
    }

    public function mintaPerbaikan(Pendaftaran $pendaftaran, User $admin, string $catatan): Pendaftaran
    {
        $catatan = trim($catatan);
        if ($catatan === '') {
            throw new InvalidArgumentException('Catatan perbaikan wajib diisi.');
        }

        if (! in_array($pendaftaran->status, ['pending', 'perlu_perbaikan'], true)) {
            throw new InvalidArgumentException('Hanya pendaftaran pending yang dapat diminta perbaikan.');
        }

        return $this->updateStatus($pendaftaran, $admin, 'perlu_perbaikan', $catatan, setVerified: false);
    }

    private function updateStatus(
        Pendaftaran $pendaftaran,
        User $admin,
        string $status,
        ?string $catatan,
        bool $createSiswa = false,
        bool $setVerified = true,
    ): Pendaftaran {
        return DB::transaction(function () use ($pendaftaran, $admin, $status, $catatan, $createSiswa, $setVerified) {
            CauserResolver::setCauser($admin);

            $payload = [
                'status' => $status,
                'catatan_verifikasi' => $catatan,
            ];

            if ($setVerified) {
                $payload['verified_at'] = now();
                $payload['verified_by'] = $admin->id;
            } else {
                $payload['verified_at'] = null;
                $payload['verified_by'] = $admin->id;
            }

            $pendaftaran->update($payload);
            $pendaftaran = $pendaftaran->fresh() ?? $pendaftaran;

            if ($createSiswa) {
                $this->ensureSiswaFromPendaftaran($pendaftaran);
            }

            return $pendaftaran->fresh() ?? $pendaftaran;
        });
    }

    private function ensureSiswaFromPendaftaran(Pendaftaran $pendaftaran): Siswa
    {
        $pendaftaran->loadMissing('infoPendaftaran');

        $siswa = Siswa::query()->firstOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama' => $pendaftaran->nama_anak,
                'jenis_kelamin' => $pendaftaran->jenis_kelamin,
                'tahun_ajaran' => $pendaftaran->infoPendaftaran?->tahun_ajaran,
                'foto' => $pendaftaran->dokumen_pas_foto,
                'kelas' => '1',
                'status' => 'aktif',
                'nis' => null,
            ]
        );

        return $siswa;
    }
}
