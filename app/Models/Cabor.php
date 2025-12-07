<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cabor extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'cabor';

    protected $guarded = [];

    protected $fillable = [
        'nama',
        'deskripsi',
        'kategori_peserta_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function kategoriPeserta()
    {
        return $this->belongsTo(MstKategoriPeserta::class, 'kategori_peserta_id');
    }

    public function kategori()
    {
        return $this->hasMany(CaborKategori::class, 'cabor_id');
    }

    /**
     * Relasi langsung ke CaborKategoriAtlet (pivot table)
     */
    public function caborAtlet()
    {
        return $this->hasMany(CaborKategoriAtlet::class, 'cabor_id');
    }

    /**
     * Relasi langsung ke CaborKategoriPelatih (pivot table)
     */
    public function caborPelatih()
    {
        return $this->hasMany(CaborKategoriPelatih::class, 'cabor_id');
    }

    /**
     * Relasi langsung ke CaborKategoriTenagaPendukung (pivot table)
     */
    public function caborTenagaPendukung()
    {
        return $this->hasMany(CaborKategoriTenagaPendukung::class, 'cabor_id');
    }

    /**
     * Relasi many-to-many ke Atlet melalui pivot
     */
    public function atlets()
    {
        return $this->belongsToMany(Atlet::class, 'cabor_kategori_atlet', 'cabor_id', 'atlet_id')
            ->withPivot(['cabor_kategori_id', 'posisi_atlet', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Relasi many-to-many ke Pelatih melalui pivot
     */
    public function pelatihs()
    {
        return $this->belongsToMany(Pelatih::class, 'cabor_kategori_pelatih', 'cabor_id', 'pelatih_id')
            ->withPivot(['cabor_kategori_id', 'jenis_pelatih', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Relasi many-to-many ke TenagaPendukung melalui pivot
     */
    public function tenagaPendukungs()
    {
        return $this->belongsToMany(TenagaPendukung::class, 'cabor_kategori_tenaga_pendukung', 'cabor_id', 'tenaga_pendukung_id')
            ->withPivot(['cabor_kategori_id', 'jenis_tenaga_pendukung', 'is_active'])
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Cabor');
    }
}
