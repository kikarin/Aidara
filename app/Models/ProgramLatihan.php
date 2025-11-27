<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProgramLatihan extends Model implements HasMedia
{
    use Blameable;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'program_latihan';

    protected $guarded = [];

    protected $fillable = [
        'cabor_id',
        'nama_program',
        'cabor_kategori_id',
        'periode_mulai',
        'periode_selesai',
        'jenis_periode',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['file_url'];

    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function caborKategori()
    {
        return $this->belongsTo(CaborKategori::class, 'cabor_kategori_id');
    }

    public function targetLatihan()
    {
        return $this->hasMany(TargetLatihan::class, 'program_latihan_id');
    }

    public function rencanaLatihan()
    {
        return $this->hasMany(RencanaLatihan::class, 'program_latihan_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Program Latihan');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('program_files')
            ->useDisk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(25)
            ->performOnCollections('program_files');
    }

    public function getFileUrlAttribute()
    {
        $media = $this->getFirstMedia('program_files');
        return $media ? $media->getUrl() : null;
    }
}
