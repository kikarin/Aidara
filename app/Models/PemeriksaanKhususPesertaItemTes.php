<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanKhususPesertaItemTes extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_khusus_peserta_item_tes';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_khusus_id',
        'pemeriksaan_khusus_peserta_id',
        'pemeriksaan_khusus_item_tes_id',
        'nilai',
        'persentase_performa',
        'persentase_riil',
        'predikat',
        'created_by',
        'updated_by',
    ];

    // Relasi
    public function pemeriksaanKhusus()
    {
        return $this->belongsTo(PemeriksaanKhusus::class, 'pemeriksaan_khusus_id');
    }

    public function pemeriksaanKhususPeserta()
    {
        return $this->belongsTo(PemeriksaanKhususPeserta::class, 'pemeriksaan_khusus_peserta_id');
    }

    public function itemTes()
    {
        return $this->belongsTo(PemeriksaanKhususItemTes::class, 'pemeriksaan_khusus_item_tes_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Khusus Peserta Item Tes');
    }
}

