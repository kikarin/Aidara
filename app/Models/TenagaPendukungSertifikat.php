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

class TenagaPendukungSertifikat extends Model implements HasMedia
{
    use Blameable;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'tenaga_pendukung_sertifikat';

    protected $guarded = [];

    protected $fillable = [
        'tenaga_pendukung_id',
        'nama_sertifikat',
        'penyelenggara',
        'tanggal_terbit',
        'file',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['file_url'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Tenaga Pendukung Sertifikat');
    }

    public function tenaga_pendukung()
    {
        return $this->belongsTo(TenagaPendukung::class, 'tenaga_pendukung_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('sertifikat_file')
            ->useDisk('media')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(25)
            ->performOnCollections('sertifikat_file');
    }

    public function getFileUrlAttribute()
    {
        $media = $this->getFirstMedia('sertifikat_file');
        if ($media) {
            if ($media->hasGeneratedConversion('webp')) {
                return $media->getUrl('webp');
            } else {
                return $media->getUrl();
            }
        }

        return null;
    }
}
