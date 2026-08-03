<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportService
{
    /**
     * @param  Builder<Pendaftaran>|null  $query
     */
    public function pendaftaranExcel(?Builder $query = null): StreamedResponse
    {
        $rows = ($query ?? Pendaftaran::query())->latest()->get();
        $filename = 'pendaftaran-'.now()->format('Ymd-His').'.xlsx';

        return $this->xlsxDownload($filename, $this->pendaftaranHeaders(), $rows, function (Pendaftaran $item): array {
            return [
                $item->id,
                $item->nama_anak,
                $item->tempat_lahir ?? '',
                (string) ($item->tanggal_lahir ?? ''),
                $item->jenis_kelamin ?? '',
                $item->agama ?? '',
                $item->anak_ke ?? '',
                $item->asal_sekolah ?? '',
                $item->nik ?? '',
                $item->no_kk ?? '',
                $item->alamat ?? '',
                $item->nama_ayah ?? '',
                $item->pekerjaan_ayah ?? '',
                $item->nama_ibu ?? '',
                $item->pekerjaan_ibu ?? '',
                $item->nama_wali ?? '',
                $item->no_hp ?? '',
                $item->status ?? '',
                optional($item->created_at)?->format('Y-m-d H:i') ?? '',
            ];
        });
    }

    /**
     * @param  Builder<Pendaftaran>|null  $query
     */
    public function pendaftaranPdf(?Builder $query = null): StreamedResponse
    {
        $items = ($query ?? Pendaftaran::query())->latest()->get();
        $filename = 'pendaftaran-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('exports.pendaftaran-pdf', [
            'items' => $items,
            'generatedAt' => now(),
            'school' => 'SD Negeri Warialau',
        ])->setPaper('a4', 'landscape');

        // StreamedResponse agar Livewire mengenali sebagai file download
        // (Illuminate\Http\Response biasa berisi binary → Malformed UTF-8).
        return response()->streamDownload(
            static fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @param  Builder<Siswa>|null  $query
     */
    public function siswaExcel(?Builder $query = null): StreamedResponse
    {
        $rows = ($query ?? Siswa::query())->orderBy('kelas')->orderBy('nama')->get();
        $filename = 'siswa-'.now()->format('Ymd-His').'.xlsx';

        return $this->xlsxDownload($filename, $this->siswaHeaders(), $rows, function (Siswa $item): array {
            return [
                $item->id,
                $item->nama ?? '',
                $item->nis ?? '',
                $item->kelas ?? '',
                $item->jenis_kelamin ?? '',
                $item->tahun_ajaran ?? '',
                $item->status ?? '',
                optional($item->created_at)?->format('Y-m-d H:i') ?? '',
            ];
        });
    }

    /**
     * @param  Builder<Siswa>|null  $query
     */
    public function siswaPdf(?Builder $query = null): StreamedResponse
    {
        $items = ($query ?? Siswa::query())->orderBy('kelas')->orderBy('nama')->get();
        $filename = 'siswa-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('exports.siswa-pdf', [
            'items' => $items,
            'generatedAt' => now(),
            'school' => 'SD Negeri Warialau',
        ])->setPaper('a4', 'portrait');

        // StreamedResponse agar Livewire mengenali sebagai file download
        // (Illuminate\Http\Response biasa berisi binary → Malformed UTF-8).
        return response()->streamDownload(
            static fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return list<string>
     */
    private function pendaftaranHeaders(): array
    {
        return [
            'ID', 'Nama Anak', 'Tempat Lahir', 'Tanggal Lahir', 'JK', 'Agama', 'Anak Ke',
            'Asal Sekolah', 'NIK', 'No KK', 'Alamat', 'Nama Ayah', 'Pekerjaan Ayah',
            'Nama Ibu', 'Pekerjaan Ibu', 'Nama Wali', 'No HP', 'Status', 'Tanggal Daftar',
        ];
    }

    /**
     * @return list<string>
     */
    private function siswaHeaders(): array
    {
        return ['ID', 'Nama', 'NIS', 'Kelas', 'JK', 'Tahun Ajaran', 'Status', 'Dibuat'];
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @param  callable(mixed): list<null|scalar>  $mapper
     * @param  list<string>  $headers
     */
    private function xlsxDownload(string $filename, array $headers, Collection $rows, callable $mapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $mapper): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($mapper($row)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
