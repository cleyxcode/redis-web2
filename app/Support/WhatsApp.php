<?php

declare(strict_types=1);

namespace App\Support;

final class WhatsApp
{
    /**
     * Normalisasi nomor ke format internasional tanpa plus (62...).
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62') && strlen($digits) >= 9) {
            $digits = '62'.$digits;
        }

        return $digits !== '' ? $digits : null;
    }

    public static function url(?string $phone, ?string $message = null): ?string
    {
        $normalized = self::normalize($phone);
        if ($normalized === null) {
            return null;
        }

        $url = 'https://wa.me/'.$normalized;
        if ($message !== null && trim($message) !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    public static function schoolContact(): ?string
    {
        $fromSetting = \App\Models\Setting::query()->where('key', 'no_telp')->value('value');
        if (filled($fromSetting)) {
            return (string) $fromSetting;
        }

        $fromProfil = \App\Models\ProfilSekolah::query()->value('kontak');

        return filled($fromProfil) ? (string) $fromProfil : null;
    }
}
