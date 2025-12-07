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

class RekapAbsenProgramLatihan extends Model implements HasMedia
{
    use Blameable;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'rekap_absen_program_latihan';

    protected $guarded = [];

    protected $fillable = [
        'program_latihan_id',
        'tanggal',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function programLatihan()
    {
        return $this->belongsTo(ProgramLatihan::class, 'program_latihan_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Rekap Absen Program Latihan');
    }

    public function registerMediaCollections(): void
    {
        // Collection untuk foto absen (bisa multiple)
        $this->addMediaCollection('foto_absen')
            ->useDisk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif']);
        
        // Collection untuk file nilai atlet (PDF, Excel - bisa multiple)
        $this->addMediaCollection('file_nilai')
            ->useDisk('media')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);
    }
}

