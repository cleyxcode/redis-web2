<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class PendaftaranException extends Exception
{
    public static function duplicateUser(): self
    {
        return new self('Anda sudah memiliki pendaftaran aktif pada periode ini.');
    }

    public static function duplicateNik(): self
    {
        return new self('NIK ini sudah terdaftar pada periode pendaftaran aktif.');
    }

    public static function notOpen(): self
    {
        return new self('Pendaftaran sedang tidak dibuka.');
    }

    public static function cannotResubmit(): self
    {
        return new self('Dokumen hanya dapat diperbarui jika status perlu perbaikan.');
    }

    public static function notFound(): self
    {
        return new self('Data pendaftaran tidak ditemukan.');
    }
}
