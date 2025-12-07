<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanKhususItemTes extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_khusus_item_tes';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_khusus_aspek_id',
        'nama',
        'satuan',
        'target_laki_laki',
        'target_perempuan',
        'performa_arah',
        'urutan',
        'mst_template_item_tes_id',
        'created_by',
        'updated_by',
    ];

    // Relasi
    public function aspek()
    {
        return $this->belongsTo(PemeriksaanKhususAspek::class, 'pemeriksaan_khusus_aspek_id');
    }

    public function templateItemTes()
    {
        return $this->belongsTo(MstTemplatePemeriksaanKhususItemTes::class, 'mst_template_item_tes_id');
    }

    public function hasilTes()
    {
        return $this->hasMany(PemeriksaanKhususPesertaItemTes::class, 'pemeriksaan_khusus_item_tes_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Khusus Item Tes');
    }
}

