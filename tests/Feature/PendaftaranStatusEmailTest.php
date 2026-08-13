<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PendaftaranStatusMail;
use App\Services\PendaftaranVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PendaftaranStatusEmailTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_verification_queues_status_email(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $parent = $this->createUser(['email' => 'ortu@example.com']);
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        app(PendaftaranVerificationService::class)->terima($pendaftaran, $admin, 'Lolos seleksi');

        Mail::assertQueued(PendaftaranStatusMail::class, function (PendaftaranStatusMail $mail) use ($pendaftaran) {
            return $mail->pendaftaran->id === $pendaftaran->id
                && $mail->pendaftaran->status === 'diterima';
        });
    }
}
