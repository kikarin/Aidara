<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AtletOrangTua extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'atlet_orang_tua';

    protected $guarded = [];

    protected $fillable = [
        'atlet_id',
        'nama_ibu_kandung',
        'tempat_lahir_ibu',
        'tanggal_lahir_ibu',
        'alamat_ibu',
        'no_hp_ibu',
        'pekerjaan_ibu',
        'nama_ayah_kandung',
        'tempat_lahir_ayah',
        'tanggal_lahir_ayah',
        'alamat_ayah',
        'no_hp_ayah',
        'pekerjaan_ayah',
        'nama_wali',
        'tempat_lahir_wali',
        'tanggal_lahir_wali',
        'alamat_wali',
        'no_hp_wali',
        'pekerjaan_wali',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Atlet Orang Tua');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }
}
