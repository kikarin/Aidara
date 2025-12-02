<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Turnamen extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'turnamen';

    protected $guarded = [];

    protected $fillable = [
        'nama',
        'foto',
        'cabor_kategori_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'tingkat_id',
        'lokasi',
        'juara_id',
        'hasil',
        'evaluasi',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function setTanggalMulaiAttribute($value)
    {
        if ($value) {
            $this->attributes['tanggal_mulai'] = Carbon::parse($value)->format('Y-m-d');
        }
    }

    public function setTanggalSelesaiAttribute($value)
    {
        if ($value) {
            $this->attributes['tanggal_selesai'] = Carbon::parse($value)->format('Y-m-d');
        }
    }

    public function getTanggalMulaiAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    public function getTanggalSelesaiAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    public function caborKategori()
    {
        return $this->belongsTo(CaborKategori::class, 'cabor_kategori_id');
    }

    public function tingkat()
    {
        return $this->belongsTo(MstTingkat::class, 'tingkat_id');
    }

    public function juara()
    {
        return $this->belongsTo(MstJuara::class, 'juara_id');
    }

    public function peserta()
    {
        return $this->morphedByMany(Atlet::class, 'peserta', 'turnamen_peserta', 'turnamen_id', 'peserta_id')
            ->withTimestamps();
    }

    public function pelatihPeserta()
    {
        return $this->morphedByMany(Pelatih::class, 'peserta', 'turnamen_peserta', 'turnamen_id', 'peserta_id')
            ->withTimestamps();
    }

    public function tenagaPendukungPeserta()
    {
        return $this->morphedByMany(TenagaPendukung::class, 'peserta', 'turnamen_peserta', 'turnamen_id', 'peserta_id')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Turnamen');
    }
}
