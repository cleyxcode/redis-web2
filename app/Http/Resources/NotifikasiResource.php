<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notifikasi
 */
class NotifikasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'judul' => (string) $this->judul,
            'pesan' => (string) $this->pesan,
            'tipe' => (string) $this->tipe,
            'referensi_id' => $this->referensi_id !== null ? (int) $this->referensi_id : null,
            'dibaca' => (bool) $this->dibaca,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
