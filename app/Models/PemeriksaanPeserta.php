<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanPeserta extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_peserta';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_id',
        'peserta_id',
        'peserta_type',
        'ref_status_pemeriksaan_id',
        'catatan_umum',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function pemeriksaan()
    {
        return $this->belongsTo(Pemeriksaan::class);
    }

    public function status()
    {
        return $this->belongsTo(RefStatusPemeriksaan::class, 'ref_status_pemeriksaan_id');
    }

    public function peserta()
    {
        return $this->morphTo()->withTrashed();
    }

    public function pemeriksaanPesertaParameter()
    {
        return $this->hasMany(PemeriksaanPesertaParameter::class, 'pemeriksaan_peserta_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Peserta');
    }
}
