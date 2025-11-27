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

class PesertaRegistration extends Model implements HasMedia
{
    use HasFactory;
    use Blameable;
    use SoftDeletes;
    use LogsActivity;
    use InteractsWithMedia;

    protected $guarded = [];
    protected $table   = 'peserta_registrations';

    protected $fillable = [
        'user_id',
        'peserta_type',
        'step_current',
        'data_json',
        'status',
        'rejected_reason',
    ];

    protected $casts = [
        'data_json'    => 'array',
        'step_current' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Peserta Registration');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')
            ->useDisk('media')
            ->singleFile();

        $this->addMediaCollection('sertifikat_files')
            ->useDisk('media');

        $this->addMediaCollection('dokumen_files')
            ->useDisk('media');
    }
}
