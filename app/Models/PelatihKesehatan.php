<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PelatihKesehatan extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'pelatih_kesehatan';

    protected $fillable = [
        'pelatih_id',
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
            ->setDescriptionForEvent(fn (string $eventName) => 'Pelatih Kesehatan');
    }

    public function pelatih()
    {
        return $this->belongsTo(Pelatih::class, 'pelatih_id');
    }
}
