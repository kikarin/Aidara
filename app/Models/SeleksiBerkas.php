<?php

namespace App\Models;

use App\Blameable;
use App\Support\SeleksiPpopm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SeleksiBerkas extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'seleksi_berkas';

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Seleksi Berkas');
    }

    public function pendaftar()
    {
        return $this->belongsTo(SeleksiPendaftar::class, 'pendaftar_id');
    }

    public function label(): string
    {
        return SeleksiPpopm::BERKAS_LABELS[$this->jenis] ?? $this->jenis;
    }

    public function url(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }
}
