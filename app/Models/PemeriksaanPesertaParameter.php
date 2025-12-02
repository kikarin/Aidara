<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanPesertaParameter extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_peserta_parameter';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_id',
        'pemeriksaan_peserta_id',
        'pemeriksaan_parameter_id',
        'nilai',
        'trend',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function pemeriksaan()
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }

    public function pemeriksaanPeserta()
    {
        return $this->belongsTo(PemeriksaanPeserta::class, 'pemeriksaan_peserta_id');
    }

    public function pemeriksaanParameter()
    {
        return $this->belongsTo(PemeriksaanParameter::class, 'pemeriksaan_parameter_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Peserta Parameter');
    }
}
