<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TenagaPendukungKesehatan extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'tenaga_pendukung_kesehatan';

    protected $fillable = [
        'tenaga_pendukung_id',
        'tinggi_badan',
        'berat_badan',
        'penglihatan',
        'pendengaran',
        'riwayat_penyakit',
        'alergi',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Tenaga Pendukung Kesehatan');
    }

    public function tenaga_pendukung()
    {
        return $this->belongsTo(TenagaPendukung::class, 'tenaga_pendukung_id');
    }
}
