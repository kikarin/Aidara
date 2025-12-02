<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CaborKategori extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'cabor_kategori';

    protected $guarded = [];

    protected $fillable = [
        'cabor_id',
        'nama',
        'deskripsi',
        'jenis_kelamin',
        'kategori_peserta_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function caborKategoriAtlet()
    {
        return $this->hasMany(CaborKategoriAtlet::class, 'cabor_kategori_id');
    }

    public function caborKategoriPelatih()
    {
        return $this->hasMany(CaborKategoriPelatih::class, 'cabor_kategori_id');
    }

    public function tenagaPendukung()
    {
        return $this->hasMany(CaborKategoriTenagaPendukung::class, 'cabor_kategori_id')
            ->with(['tenagaPendukung', 'created_by_user', 'updated_by_user']);
    }

    public function kategoriPeserta()
    {
        return $this->belongsTo(MstKategoriPeserta::class, 'kategori_peserta_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Cabor Kategori');
    }

    // Attributes untuk menghitung jumlah
    public function getJumlahAtletAttribute()
    {
        return $this->caborKategoriAtlet()->count();
    }

    public function getJumlahPelatihAttribute()
    {
        return $this->caborKategoriPelatih()->count();
    }
}
