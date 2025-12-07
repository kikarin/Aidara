<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanKhusus extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_khusus';

    protected $guarded = [];

    protected $fillable = [
        'cabor_id',
        'cabor_kategori_id',
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

    public function pemeriksaanKhususPeserta()
    {
        return $this->hasMany(PemeriksaanKhususPeserta::class, 'pemeriksaan_khusus_id');
    }

    public function aspek()
    {
        return $this->hasMany(PemeriksaanKhususAspek::class, 'pemeriksaan_khusus_id')
            ->whereNull('deleted_at')
            ->orderBy('urutan');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Khusus');
    }
}

