<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Activitylog\Facades\CauserResolver;

class PendaftaranVerificationService
{
    public function terima(Pendaftaran $pendaftaran, User $admin, ?string $catatan = null): Pendaftaran
    {
        return $this->updateStatus($pendaftaran, $admin, 'diterima', $catatan);
    }

    public function tolak(Pendaftaran $pendaftaran, User $admin, string $alasan): Pendaftaran
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        return $this->updateStatus($pendaftaran, $admin, 'ditolak', $alasan);
    }

    private function updateStatus(Pendaftaran $pendaftaran, User $admin, string $status, ?string $catatan): Pendaftaran
    {
        return DB::transaction(function () use ($pendaftaran, $admin, $status, $catatan) {
            CauserResolver::setCauser($admin);

            $pendaftaran->update([
                'status' => $status,
                'catatan_verifikasi' => $catatan,
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            return $pendaftaran->fresh();
        });
    }
}
