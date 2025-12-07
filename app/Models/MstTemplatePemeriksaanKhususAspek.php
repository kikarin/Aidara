<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MstTemplatePemeriksaanKhususAspek extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'mst_template_pemeriksaan_khusus_aspek';

    protected $guarded = [];

    protected $fillable = [
        'cabor_id',
        'nama',
        'urutan',
        'created_by',
        'updated_by',
    ];

    // Relasi
    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function itemTes()
    {
        return $this->hasMany(MstTemplatePemeriksaanKhususItemTes::class, 'mst_template_pemeriksaan_khusus_aspek_id')
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
            ->setDescriptionForEvent(fn () => 'Template Pemeriksaan Khusus Aspek');
    }
}

