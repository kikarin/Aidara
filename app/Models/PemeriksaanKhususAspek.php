<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PemeriksaanKhususAspek extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemeriksaan_khusus_aspek';

    protected $guarded = [];

    protected $fillable = [
        'pemeriksaan_khusus_id',
        'nama',
        'urutan',
        'mst_template_aspek_id',
        'created_by',
        'updated_by',
    ];

    // Relasi
    public function pemeriksaanKhusus()
    {
        return $this->belongsTo(PemeriksaanKhusus::class, 'pemeriksaan_khusus_id');
    }

    public function itemTes()
    {
        return $this->hasMany(PemeriksaanKhususItemTes::class, 'pemeriksaan_khusus_aspek_id')
            ->whereNull('deleted_at')
            ->orderBy('urutan');
    }

    public function templateAspek()
    {
        return $this->belongsTo(MstTemplatePemeriksaanKhususAspek::class, 'mst_template_aspek_id');
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
            ->setDescriptionForEvent(fn () => 'Pemeriksaan Khusus Aspek');
    }
}

