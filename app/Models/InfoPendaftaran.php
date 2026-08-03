<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfoPendaftaran extends Model
{
    protected $table = 'info_pendaftaran';

    protected $fillable = [
        'user_id',
        'tahun_ajaran',
        'tanggal_buka',
        'tanggal_tutup',
        'kuota',
        'syarat',
        'status',
    ];

    protected $casts = [
        'tanggal_buka' => 'date',
        'tanggal_tutup' => 'date',
        'kuota' => 'integer',
    ];

    /**
     * @return list<string>
     */
    public static function parseSyaratList(?string $syarat): array
    {
        if ($syarat === null || trim($syarat) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $syarat) ?: [];

        $items = [];
        foreach ($lines as $line) {
            $cleaned = trim(preg_replace('/^(\d+[\.\)]\s*|[-•*]\s+)/u', '', trim($line)) ?? '');
            if ($cleaned !== '') {
                $items[] = $cleaned;
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function getSyaratListAttribute(): array
    {
        return self::parseSyaratList($this->syarat);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }
}
