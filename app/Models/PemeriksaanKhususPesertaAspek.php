<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanKhususPesertaAspek extends Model
{
    use HasFactory;

    protected $table = 'pemeriksaan_khusus_peserta_aspek';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_khusus_id',
        'pemeriksaan_khusus_peserta_id',
        'pemeriksaan_khusus_aspek_id',
        'nilai_performa',
        'predikat',
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

    public function aspek()
    {
        return $this->belongsTo(PemeriksaanKhususAspek::class, 'pemeriksaan_khusus_aspek_id');
    }
}

