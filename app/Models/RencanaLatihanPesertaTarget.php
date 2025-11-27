<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RencanaLatihanPesertaTarget extends Model
{
    use HasFactory;

    protected $table = 'rencana_latihan_peserta_target';

    protected $fillable = [
        'rencana_latihan_id',
        'target_latihan_id',
        'peserta_id',
        'peserta_type',
        'nilai',
        'trend',
    ];

    public function rencanaLatihan()
    {
        return $this->belongsTo(RencanaLatihan::class, 'rencana_latihan_id');
    }

    public function targetLatihan()
    {
        return $this->belongsTo(TargetLatihan::class, 'target_latihan_id');
    }

    public function peserta()
    {
        return $this->morphTo();
    }
}
