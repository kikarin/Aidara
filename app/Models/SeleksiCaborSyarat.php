<?php

namespace App\Models;

use App\Blameable;
use App\Support\SeleksiPpopm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SeleksiCaborSyarat extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'seleksi_cabor_syarat';

    protected $guarded = [];

    protected $casts = [
        'tahun_lahir_min'          => 'integer',
        'tahun_lahir_max'          => 'integer',
        'tinggi_min_putra'         => 'integer',
        'tinggi_min_putri'         => 'integer',
        'tinggi_per_posisi'        => 'array',
        'posisi'                   => 'array',
        'kuota_putra'              => 'integer',
        'kuota_putri'              => 'integer',
        'kuota_detail'             => 'array',
        'jenis_kelamin_diizinkan'  => 'array',
        'wajib_piagam'             => 'boolean',
        'wajib_berenang'           => 'boolean',
        'wajib_dua_posisi'         => 'boolean',
        'prioritas_tinggi'         => 'integer',
        'toleransi_tinggi'         => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Seleksi Cabor Syarat');
    }

    public function periode()
    {
        return $this->belongsTo(SeleksiPeriode::class, 'periode_id');
    }

    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function pendaftar()
    {
        return $this->hasMany(SeleksiPendaftar::class, 'cabor_syarat_id');
    }

    public function mapLabel(): string
    {
        return SeleksiPpopm::MAP_LABELS[$this->kelompok_map] ?? $this->kelompok_map;
    }

    public function kuotaUntuk(string $jenisKelamin): int
    {
        return $jenisKelamin === 'L' ? (int) $this->kuota_putra : (int) $this->kuota_putri;
    }
}
