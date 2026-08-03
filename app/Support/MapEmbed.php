<?php

declare(strict_types=1);

namespace App\Support;

final class MapEmbed
{
    /**
     * Default: sekitar Kepulauan Aru / Warialau, Maluku.
     */
    public const DEFAULT_LAT = -5.7725000;
    public const DEFAULT_LNG = 134.5833000;

    public static function iframe(?float $lat, ?float $lng, int $height = 360): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $delta = 0.012;
        $west = $lng - $delta;
        $south = $lat - $delta;
        $east = $lng + $delta;
        $north = $lat + $delta;

        $src = sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%F%%2C%F%%2C%F%%2C%F&amp;layer=mapnik&amp;marker=%F%%2C%F',
            $west,
            $south,
            $east,
            $north,
            $lat,
            $lng
        );

        return sprintf(
            '<iframe title="Peta Lokasi Sekolah" src="%s" style="border:0;width:100%%;height:100%%;min-height:%dpx" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>',
            $src,
            $height
        );
    }

    public static function googleMapsUrl(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        return sprintf('https://www.google.com/maps?q=%F,%F', $lat, $lng);
    }

    public static function resolveEmbed(?string $existingEmbed, mixed $lat, mixed $lng): ?string
    {
        $parsedLat = self::toFloat($lat);
        $parsedLng = self::toFloat($lng);

        if ($parsedLat !== null && $parsedLng !== null) {
            return self::iframe($parsedLat, $parsedLng);
        }

        return filled($existingEmbed) ? $existingEmbed : null;
    }

    public static function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
