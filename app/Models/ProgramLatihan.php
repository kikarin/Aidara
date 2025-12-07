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
        'tahap',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['periode_hitung'];

    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function caborKategori()
    {
        return $this->belongsTo(CaborKategori::class, 'cabor_kategori_id');
    }

    public function rekapAbsen()
    {
        return $this->hasMany(RekapAbsenProgramLatihan::class, 'program_latihan_id');
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

    public function getPeriodeHitungAttribute()
    {
        if (!$this->periode_mulai || !$this->periode_selesai) {
            return null;
        }

        $start = new \DateTime($this->periode_mulai);
        $end = new \DateTime($this->periode_selesai);
        $diff = $start->diff($end);
        
        $result = [];
        
        if ($diff->y > 0) {
            $result[] = $diff->y . ' tahun';
        }
        if ($diff->m > 0) {
            $result[] = $diff->m . ' bulan';
        }
        if ($diff->d > 0) {
            $result[] = $diff->d . ' hari';
        }
        
        // Jika hanya hari saja (kurang dari 1 bulan)
        if ($diff->y == 0 && $diff->m == 0 && $diff->d > 0) {
            return $diff->d . ' hari';
        }
        
        // Jika hanya bulan saja (kurang dari 1 tahun)
        if ($diff->y == 0 && $diff->m > 0 && $diff->d == 0) {
            return $diff->m . ' bulan';
        }
        
        // Jika hanya tahun saja
        if ($diff->y > 0 && $diff->m == 0 && $diff->d == 0) {
            return $diff->y . ' tahun';
        }
        
        // Kombinasi
        return implode(' ', $result);
    }
}
