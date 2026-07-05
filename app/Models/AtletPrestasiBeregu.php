<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtletPrestasiBeregu extends Model
{
    use HasFactory;

    protected $table = 'atlet_prestasi_beregu';

    protected $fillable = [
        'prestasi_group_id',
        'atlet_id',
        'atlet_prestasi_id',
    ];

    public function prestasiGroup()
    {
        return $this->belongsTo(AtletPrestasi::class, 'prestasi_group_id');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }

    public function atletPrestasi()
    {
        return $this->belongsTo(AtletPrestasi::class, 'atlet_prestasi_id');
    }
}

