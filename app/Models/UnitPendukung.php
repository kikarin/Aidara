<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UnitPendukung extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'unit_pendukung';

    protected $guarded = [];

    protected $fillable = [
        'nama',
        'jenis_unit_pendukung_id',
        'deskripsi',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Unit Pendukung');
    }
    public function jenisUnitPendukung()
    {
        return $this->belongsTo(MstJenisUnitPendukung::class, 'jenis_unit_pendukung_id');
    }
}
