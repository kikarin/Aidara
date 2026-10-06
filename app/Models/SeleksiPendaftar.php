<?php

namespace App\Models;

use App\Blameable;
use App\Support\SeleksiPpopm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SeleksiPendaftar extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'seleksi_pendaftar';

    protected $guarded = [];

    protected $casts = [
        'tanggal_lahir'            => 'date',
        'asal_kabupaten_bogor'     => 'boolean',
        'tinggi_badan'             => 'float',
        'berat_badan'              => 'float',
        'vertical_jump'            => 'float',
        'bisa_dua_posisi'          => 'boolean',
        'bisa_berenang'            => 'boolean',
        'kuasai_poomsae'           => 'boolean',
        'bersedia_pindah_domisili' => 'boolean',
        'setuju_perjanjian'        => 'boolean',
        'skor_kecabangan_mentah'   => 'float',
        'skor_kecabangan'          => 'float',
        'skor_fisik'               => 'float',
        'skor_akademik'            => 'float',
        'skor_psikologi'           => 'float',
        'psikologi_rekomendasi'    => 'boolean',
        'kesehatan_layak'          => 'boolean',
        'antropometri_layak'       => 'boolean',
        'nilai_akhir'              => 'float',
        'ranking'                  => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Seleksi Pendaftar');
    }

    public function periode()
    {
        return $this->belongsTo(SeleksiPeriode::class, 'periode_id');
    }

    public function caborSyarat()
    {
        return $this->belongsTo(SeleksiCaborSyarat::class, 'cabor_syarat_id');
    }

    public function berkas()
    {
        return $this->hasMany(SeleksiBerkas::class, 'pendaftar_id');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusLabel(): string
    {
        return SeleksiPpopm::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function tahunLahir(): int
    {
        return (int) $this->tanggal_lahir?->format('Y');
    }

    public function hasCompleteScores(): bool
    {
        return $this->skor_kecabangan !== null
            && $this->skor_fisik !== null
            && $this->skor_akademik !== null
            && $this->skor_psikologi !== null;
    }
}
