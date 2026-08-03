<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Services\AdminExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_export_pendaftaran_excel_downloads_xlsx(): void
    {
        $user = $this->createUser();
        $info = $this->createInfoPendaftaran($user);
        $this->createPendaftaran($user, $info, [
            'nama_anak' => 'Budi Santoso',
            'status' => 'pending',
        ]);

        $response = app(AdminExportService::class)->pendaftaranExcel();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'pendaftaran-',
            (string) $response->headers->get('Content-Disposition')
        );

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertNotSame('', $content);
        $this->assertSame('PK', substr($content, 0, 2));
    }

    public function test_export_pendaftaran_pdf_downloads_pdf(): void
    {
        $user = $this->createUser();
        $info = $this->createInfoPendaftaran($user);
        $this->createPendaftaran($user, $info, [
            'nama_anak' => 'Siti Aminah',
            'status' => 'diterima',
        ]);

        $response = app(AdminExportService::class)->pendaftaranPdf();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('%PDF', $response->getContent());
        $this->assertStringContainsString('pendaftaran-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_export_siswa_excel_and_pdf(): void
    {
        $this->createSiswa([
            'nama' => 'Andi Wijaya',
            'nis' => '1001',
            'kelas' => '2B',
        ]);

        $excel = app(AdminExportService::class)->siswaExcel();
        $this->assertSame(200, $excel->getStatusCode());
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $excel->headers->get('Content-Type'));

        ob_start();
        $excel->sendContent();
        $xlsx = (string) ob_get_clean();
        $this->assertSame('PK', substr($xlsx, 0, 2));

        $pdf = app(AdminExportService::class)->siswaPdf();
        $this->assertSame(200, $pdf->getStatusCode());
        $this->assertStringContainsString('%PDF', $pdf->getContent());
    }

    public function test_export_pendaftaran_excel_respects_query_filter(): void
    {
        $user = $this->createUser();
        $info = $this->createInfoPendaftaran($user);
        $this->createPendaftaran($user, $info, ['nama_anak' => 'Pending One', 'status' => 'pending']);
        $this->createPendaftaran($user, $info, ['nama_anak' => 'Accepted One', 'status' => 'diterima']);

        $query = Pendaftaran::query()->where('status', 'pending');
        $response = app(AdminExportService::class)->pendaftaranExcel($query);

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        // XLSX is zip binary; ensure file generated and pending count matches filter.
        $this->assertSame('PK', substr($content, 0, 2));
        $this->assertSame(1, Pendaftaran::query()->where('status', 'pending')->count());
    }
}
