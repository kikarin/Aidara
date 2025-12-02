<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstKategoriAtlet extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'mst_kategori_atlet';

    protected $guarded = [];

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'nama',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Add getActivitylogOptions for Spatie Activitylog
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Master Kategori Atlet');
    }

    public function atlets()
    {
        return $this->belongsToMany(Atlet::class, 'atlet_kategori_atlet', 'mst_kategori_atlet_id', 'atlet_id')
            ->withTimestamps();
    }
}
