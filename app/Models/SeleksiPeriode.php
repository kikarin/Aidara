<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SeleksiPeriode extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'seleksi_periode';

    protected $guarded = [];

    protected $casts = [
        'tahun'                     => 'integer',
        'tanggal_daftar_mulai'      => 'date',
        'tanggal_daftar_selesai'    => 'date',
        'tanggal_pengumuman_admin'  => 'date',
        'tanggal_tes_mulai'         => 'date',
        'tanggal_tes_selesai'       => 'date',
        'tanggal_pleno'             => 'date',
        'tanggal_pengumuman_hasil'  => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Seleksi Periode');
    }

    public function caborSyarat()
    {
        return $this->hasMany(SeleksiCaborSyarat::class, 'periode_id')->orderBy('nama_cabor');
    }

    public function pendaftar()
    {
        return $this->hasMany(SeleksiPendaftar::class, 'periode_id');
    }

    public function isPendaftaranBuka(): bool
    {
        return $this->status === 'buka';
    }
}
