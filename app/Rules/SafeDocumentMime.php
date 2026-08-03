<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validate real MIME type via fileinfo (not only client-reported extension).
 */
final class SafeDocumentMime implements ValidationRule
{
    /** @var list<string> */
    private const ALLOWED = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('File tidak valid.');

            return;
        }

        $path = $value->getRealPath() ?: $value->getPathname();
        $finfoMime = null;

        if (is_string($path) && $path !== '' && is_readable($path)) {
            $raw = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            if (is_string($raw) && $raw !== '') {
                $finfoMime = $this->normalize($raw);
            }
        }

        // Prefer magic-byte detection when available.
        if ($finfoMime !== null) {
            if (! in_array($finfoMime, self::ALLOWED, true)) {
                $fail('Tipe file tidak diizinkan. Gunakan PDF, JPG, atau PNG asli.');
            }

            return;
        }

        $fallback = $this->normalize((string) ($value->getMimeType() ?: $value->getClientMimeType()));
        if (! in_array($fallback, self::ALLOWED, true)) {
            $fail('Tipe file tidak diizinkan. Gunakan PDF, JPG, atau PNG asli.');
        }
    }

    private function normalize(string $mime): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        return match ($mime) {
            'image/jpg' => 'image/jpeg',
            default => $mime,
        };
    }
}
