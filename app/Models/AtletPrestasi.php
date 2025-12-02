<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AtletPrestasi extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'atlet_prestasi';

    protected $guarded = [];

    protected $fillable = [
        'atlet_id',
        'nama_event',
        'tingkat_id',
        'tanggal',
        'peringkat',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Atlet Prestasi');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }

    public function tingkat()
    {
        return $this->belongsTo(MstTingkat::class, 'tingkat_id');
    }
}
