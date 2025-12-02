<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstKategoriPeserta extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'mst_kategori_peserta';

    protected $guarded = [];

    protected $fillable = [
        'nama',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Master Kategori Peserta');
    }

    public function atlets()
    {
        return $this->belongsToMany(Atlet::class, 'atlet_kategori_peserta', 'mst_kategori_peserta_id', 'atlet_id')
            ->withTimestamps();
    }

    public function pelatihs()
    {
        return $this->belongsToMany(Pelatih::class, 'pelatih_kategori_peserta', 'mst_kategori_peserta_id', 'pelatih_id')
            ->withTimestamps();
    }

    public function tenagaPendukungs()
    {
        return $this->belongsToMany(TenagaPendukung::class, 'tenaga_pendukung_kategori_peserta', 'mst_kategori_peserta_id', 'tenaga_pendukung_id')
            ->withTimestamps();
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name']);
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }
}
