<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProgramLatihanAbsenAtlet extends Model implements HasMedia
{
    use Blameable;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'program_latihan_absen_atlet';

    protected $guarded = [];

    protected $fillable = [
        'program_latihan_id',
        'rekap_absen_program_latihan_id',
        'atlet_id',
        'tanggal',
        'status',
        'waktu_foto',
        'lokasi',
        'latitude',
        'longitude',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function programLatihan()
    {
        return $this->belongsTo(ProgramLatihan::class, 'program_latihan_id');
    }

    public function rekapAbsen()
    {
        return $this->belongsTo(RekapAbsenProgramLatihan::class, 'rekap_absen_program_latihan_id');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Absen Atlet Program Latihan');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('foto_absen_atlet')
            ->useDisk('media')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }
}
