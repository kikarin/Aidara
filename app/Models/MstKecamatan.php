<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstKecamatan extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;

    protected $table = 'mst_kecamatan';

    protected $fillable = [
        'nama',
        'longitude',
        'latitude',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Master Kecamatan');
    }

    public function desa()
    {
        return $this->hasMany(MstDesa::class, 'id_kecamatan');
    }
}
