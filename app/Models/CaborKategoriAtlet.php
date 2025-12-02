<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CaborKategoriAtlet extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'cabor_kategori_atlet';

    protected $guarded = [];

    protected $fillable = [
        'cabor_id',
        'cabor_kategori_id',
        'atlet_id',
        'posisi_atlet',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getIsActiveBadgeAttribute()
    {
        $text  = $this->is_active ? 'Aktif' : 'Nonaktif';
        $badge = $this->is_active ? 'bg-label-primary' : 'bg-label-danger';

        return "<span class='badge $badge'>$text</span>";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'CaborKategoriAtlet');
    }

    // Relations
    public function cabor()
    {
        return $this->belongsTo(Cabor::class, 'cabor_id');
    }

    public function caborKategori()
    {
        return $this->belongsTo(CaborKategori::class, 'cabor_kategori_id');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'atlet_id');
    }


    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleted_by_user()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
