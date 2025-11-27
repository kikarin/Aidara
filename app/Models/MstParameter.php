<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstParameter extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'mst_parameter';

    protected $guarded = [];

    protected $fillable = [
        'nama',
        'satuan',
        'kategori',
        'nilai_target',
        'performa_arah',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function pemeriksaanParameters()
    {
        return $this->hasMany(PemeriksaanParameter::class, 'mst_parameter_id');
    }

    public function atletParameterUmum()
    {
        return $this->hasMany(AtletParameterUmum::class, 'mst_parameter_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Master Parameter');
    }
}
