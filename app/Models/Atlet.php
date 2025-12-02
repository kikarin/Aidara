<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Atlet extends Model implements HasMedia
{
    use Blameable;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'atlets';

    protected $fillable = [
        'nik',
        'nisn',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'tanggal_bergabung',
        'alamat',
        'sekolah',
        'kelas_sekolah',
        'ukuran_baju',
        'ukuran_celana',
        'ukuran_sepatu',
        'kecamatan_id',
        'kelurahan_id',
        'no_hp',
        'email',
        'is_active',
        'foto',
        'users_id',
        'kategori_atlet_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Atlet');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('media')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(25)
            ->performOnCollections('images');
    }

    // Accessor untuk foto
    public function getFotoAttribute($value)
    {
        $media = $this->getFirstMedia('images');
        if ($media) {
            return $media->getUrl();
        }

        return null;
    }

    // Accessor untuk foto thumbnail
    public function getFotoThumbnailAttribute()
    {
        $media = $this->getFirstMedia('images');
        if ($media) {
            return $media->getUrl('webp');
        }

        return null;
    }

    public function atletOrangTua()
    {
        return $this->hasOne(AtletOrangTua::class, 'atlet_id');
    }

    public function sertifikat()
    {
        return $this->hasMany(AtletSertifikat::class, 'atlet_id')
            ->with(['created_by_user', 'updated_by_user']);
    }

    public function prestasi()
    {
        return $this->hasMany(AtletPrestasi::class, 'atlet_id')
            ->with(['created_by_user', 'updated_by_user', 'tingkat']);
    }

    public function dokumen()
    {
        return $this->hasMany(AtletDokumen::class, 'atlet_id')
            ->with(['created_by_user', 'updated_by_user', 'jenis_dokumen']);
    }

    public function kecamatan()
    {
        return $this->belongsTo(MstKecamatan::class, 'kecamatan_id')->select(['id', 'nama']);
    }

    public function kelurahan()
    {
        return $this->belongsTo(MstDesa::class, 'kelurahan_id')->select(['id', 'nama']);
    }

    public function kesehatan()
    {
        return $this->hasOne(AtletKesehatan::class, 'atlet_id');
    }

    public function pemeriksaanPeserta()
    {
        return $this->morphMany(PemeriksaanPeserta::class, 'peserta');
    }

    public function turnamen()
    {
        return $this->morphedByMany(Turnamen::class, 'peserta', 'turnamen_peserta', 'peserta_id', 'turnamen_id')
            ->withTimestamps();
    }

    public function posisiAtlet()
    {
        return $this->belongsTo(MstPosisiAtlet::class, 'posisi_atlet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function caborKategoriAtlet()
    {
        return $this->hasMany(CaborKategoriAtlet::class, 'atlet_id');
    }

    public function kategoriAtlet()
    {
        return $this->belongsTo(MstKategoriPeserta::class, 'kategori_atlet_id')->select(['id', 'nama']);
    }

    public function kategoriPesertas()
    {
        return $this->belongsToMany(MstKategoriPeserta::class, 'atlet_kategori_peserta', 'atlet_id', 'mst_kategori_peserta_id')
            ->withTimestamps()
            ->select(['mst_kategori_peserta.id', 'mst_kategori_peserta.nama']);
    }

    // Backward compatibility
    public function kategoriAtlets()
    {
        return $this->kategoriPesertas();
    }

    /**
     * Scope filter untuk tanggal
     */
    public function scopeFilter($query, $data)
    {
        if (isset($data['filter_start_date']) && $data['filter_start_date']) {
            $query->where('created_at', '>=', $data['filter_start_date']);
        }

        if (isset($data['filter_end_date']) && $data['filter_end_date']) {
            $query->where('created_at', '<=', $data['filter_end_date'] . ' 23:59:59');
        }

        return $query;
    }
}
