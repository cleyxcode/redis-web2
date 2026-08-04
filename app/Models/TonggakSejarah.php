<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TonggakSejarah extends Model
{
    protected $table = 'tonggak_sejarah';

    protected $fillable = [
        'tahun',
        'deskripsi',
        'urutan',
        'status',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('tahun');
    }
}
