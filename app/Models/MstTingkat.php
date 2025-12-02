<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstTingkat extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'mst_tingkat';

    protected $guarded = [];

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'nama',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Add getActivitylogOptions for Spatie Activitylog
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Master Tingkat');
    }
}
