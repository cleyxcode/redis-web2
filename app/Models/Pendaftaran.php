<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pendaftaran extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pendaftaran';

    protected $fillable = [
        'user_id',
        'info_pendaftaran_id',
        'nomor_registrasi',
        'nama_anak',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'anak_ke',
        'asal_sekolah',
        'nik',
        'no_kk',
        'alamat',
        'nama_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'nama_wali',
        'no_hp',
        'dokumen',
        'dokumen_kk',
        'dokumen_akta',
        'dokumen_pas_foto',
        'status',
        'catatan_verifikasi',
        'verified_at',
        'verified_by',
    ];

    protected $appends = [
        'dokumen_lengkap',
        'dokumen_kk_url',
        'dokumen_akta_url',
        'dokumen_pas_foto_url',
        'dokumen_url',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'catatan_verifikasi', 'verified_by', 'verified_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function infoPendaftaran(): BelongsTo
    {
        return $this->belongsTo(InfoPendaftaran::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function siswa(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Siswa::class);
    }

    public function getDokumenLengkapAttribute(): bool
    {
        return filled($this->dokumen_kk) && filled($this->dokumen_akta);
    }

    public function getDokumenKkUrlAttribute(): ?string
    {
        return $this->publicUrl($this->dokumen_kk);
    }

    public function getDokumenAktaUrlAttribute(): ?string
    {
        return $this->publicUrl($this->dokumen_akta);
    }

    public function getDokumenPasFotoUrlAttribute(): ?string
    {
        return $this->publicUrl($this->dokumen_pas_foto);
    }

    public function getDokumenUrlAttribute(): ?string
    {
        return $this->publicUrl($this->dokumen);
    }

    private function publicUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }
}
