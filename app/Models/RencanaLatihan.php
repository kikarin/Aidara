<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RencanaLatihan extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'rencana_latihan';

    protected $guarded = [];

    protected $fillable = [
        'program_latihan_id',
        'tanggal',
        'lokasi_latihan',
        'materi',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function programLatihan()
    {
        return $this->belongsTo(ProgramLatihan::class, 'program_latihan_id');
    }

    public function targetLatihan()
    {
        return $this->belongsToMany(TargetLatihan::class, 'rencana_latihan_target_latihan', 'rencana_latihan_id', 'target_latihan_id');
    }

    public function atlets()
    {
        return $this->belongsToMany(Atlet::class, 'rencana_latihan_atlet', 'rencana_latihan_id', 'atlet_id');
    }

    public function pelatihs()
    {
        return $this->belongsToMany(Pelatih::class, 'rencana_latihan_pelatih', 'rencana_latihan_id', 'pelatih_id');
    }

    public function tenagaPendukung()
    {
        return $this->belongsToMany(TenagaPendukung::class, 'rencana_latihan_tenaga_pendukung', 'rencana_latihan_id', 'tenaga_pendukung_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Rencana Latihan');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
