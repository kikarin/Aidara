<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'event';

    protected $guarded = [];

    protected $fillable = [
        'nama_event',
        'deskripsi',
        'foto',
        'kategori_event_id',
        'tingkat_event_id',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function kategoriEvent()
    {
        return $this->belongsTo(CaborKategori::class, 'kategori_event_id');
    }

    public function tingkatEvent()
    {
        return $this->belongsTo(MstTingkat::class, 'tingkat_event_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Event');
    }
}

