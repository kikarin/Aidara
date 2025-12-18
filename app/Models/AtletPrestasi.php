<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AtletPrestasi extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'atlet_prestasi';

    protected $guarded = [];

    protected $fillable = [
        'atlet_id',
        'kategori_peserta_id',
        'jenis_prestasi',
        'juara',
        'medali',
        'prestasi_group_id',
        'nama_event',
        'tingkat_id',
        'tanggal',
        'peringkat',
        'keterangan',
        'bonus',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Atlet Prestasi');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }

    public function tingkat()
    {
        return $this->belongsTo(MstTingkat::class, 'tingkat_id');
    }

    public function kategoriPeserta()
    {
        return $this->belongsTo(MstKategoriPeserta::class, 'kategori_peserta_id');
    }

    public function anggotaBeregu()
    {
        return $this->hasMany(AtletPrestasiBeregu::class, 'prestasi_group_id', 'id');
    }

    public function prestasiGroup()
    {
        return $this->belongsTo(AtletPrestasi::class, 'prestasi_group_id');
    }

    public function anggotaGroup()
    {
        return $this->hasMany(AtletPrestasi::class, 'prestasi_group_id', 'id');
    }
}
