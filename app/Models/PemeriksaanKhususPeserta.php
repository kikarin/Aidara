<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanKhususPeserta extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_khusus_peserta';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_khusus_id',
        'peserta_id',
        'peserta_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function pemeriksaanKhusus()
    {
        return $this->belongsTo(PemeriksaanKhusus::class, 'pemeriksaan_khusus_id');
    }

    public function peserta()
    {
        return $this->morphTo()->withTrashed();
    }

    public function hasilItemTes()
    {
        return $this->hasMany(PemeriksaanKhususPesertaItemTes::class, 'pemeriksaan_khusus_peserta_id');
    }

    public function pemeriksaanKhususPesertaItemTes()
    {
        return $this->hasMany(PemeriksaanKhususPesertaItemTes::class, 'pemeriksaan_khusus_peserta_id');
    }

    public function hasilAspek()
    {
        return $this->hasMany(PemeriksaanKhususPesertaAspek::class, 'pemeriksaan_khusus_peserta_id');
    }

    public function hasilKeseluruhan()
    {
        return $this->hasOne(PemeriksaanKhususPesertaKeseluruhan::class, 'pemeriksaan_khusus_peserta_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Khusus Peserta');
    }
}

