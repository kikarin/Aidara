<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AtletParameterUmum extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'atlet_parameter_umum';

    protected $guarded = [];

    protected $fillable = [
        'atlet_id',
        'mst_parameter_id',
        'nilai',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }

    public function mstParameter()
    {
        return $this->belongsTo(MstParameter::class, 'mst_parameter_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Atlet Parameter Umum');
    }
}
