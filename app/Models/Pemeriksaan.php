<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pemeriksaan extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan';

    protected $guarded = [];

    protected $fillable = [
        'cabor_id',
        'cabor_kategori_id',
        'tenaga_pendukung_id',
        'nama_pemeriksaan',
        'tanggal_pemeriksaan',
        'status',
        'created_by',
        'updated_by',
    ];

    // Relasi
    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function caborKategori()
    {
        return $this->belongsTo(CaborKategori::class, 'cabor_kategori_id');
    }

    public function tenagaPendukung()
    {
        return $this->belongsTo(TenagaPendukung::class, 'tenaga_pendukung_id');
    }

    public function pemeriksaanParameter()
    {
        return $this->hasMany(PemeriksaanParameter::class, 'pemeriksaan_id');
    }

    public function pemeriksaanPeserta()
    {
        return $this->hasMany(PemeriksaanPeserta::class, 'pemeriksaan_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan');
    }
}
