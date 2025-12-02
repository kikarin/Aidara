<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TargetLatihan extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'target_latihan';

    protected $guarded = [];

    protected $fillable = [
        'program_latihan_id',
        'jenis_target',
        'peruntukan',
        'deskripsi',
        'satuan',
        'nilai_target',
        'performa_arah',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'jenis_target' => 'string',
        'peruntukan'   => 'string',
    ];

    public function programLatihan()
    {
        return $this->belongsTo(ProgramLatihan::class, 'program_latihan_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Target Latihan');
    }
}
