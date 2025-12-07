<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstTemplatePemeriksaanKhususItemTes extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'mst_template_pemeriksaan_khusus_item_tes';

    protected $guarded = [];

    protected $fillable = [
        'mst_template_pemeriksaan_khusus_aspek_id',
        'nama',
        'satuan',
        'target_laki_laki',
        'target_perempuan',
        'performa_arah',
        'urutan',
        'created_by',
        'updated_by',
    ];

    // Relasi
    public function aspek()
    {
        return $this->belongsTo(MstTemplatePemeriksaanKhususAspek::class, 'mst_template_pemeriksaan_khusus_aspek_id');
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
            ->setDescriptionForEvent(fn () => 'Template Pemeriksaan Khusus Item Tes');
    }
}

