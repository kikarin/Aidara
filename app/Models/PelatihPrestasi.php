<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PelatihPrestasi extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pelatih_prestasi';

    protected $guarded = [];

    protected $fillable = [
        'pelatih_id',
        'kategori_prestasi_pelatih_id',
        'kategori_atlet_id',
        'nama_event',
        'tingkat_id',
        'tanggal',
        'peringkat',
        'keterangan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Pelatih Prestasi');
    }

    public function pelatih()
    {
        return $this->belongsTo(Pelatih::class, 'pelatih_id');
    }

    public function tingkat()
    {
        return $this->belongsTo(MstTingkat::class, 'tingkat_id');
    }

    public function kategoriPrestasiPelatih()
    {
        return $this->belongsTo(MstKategoriPrestasiPelatih::class, 'kategori_prestasi_pelatih_id');
    }

    public function kategoriAtlet()
    {
        return $this->belongsTo(MstKategoriAtlet::class, 'kategori_atlet_id');
    }
}
