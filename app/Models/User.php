<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Blameable;
use App\Notifications\RegisterNotification;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class User extends Authenticatable implements HasMedia
{
    use Blameable;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasSlug;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'tanggal_lahir',
        'no_hp',
        'is_active',
        'file',
        'slug',
        'deskripsi',
        'verification_token',
        'is_verifikasi',
        'reset_password_token',
        'current_role_id',
        'registration_status',
        'registration_rejected_reason',
        'peserta_type',
        'peserta_id',
        'last_login',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            unset($user->role_id);
        });
        static::updating(function ($user) {
            unset($user->role_id);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'User');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name', 'id'])
            ->saveSlugsTo('slug');
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
            ->quality(25) // Optional: set the quality of the webp image
            ->performOnCollections('images');
    }

    // Todo: Relation
    public function role()
    {
        return $this->belongsTo(Role::class, 'current_role_id')->select(['id', 'name', 'init_page_login', 'is_allow_login', 'bg', 'is_vertical_menu'])->withDefault(['name' => null]);
    }

    public function users_role()
    {
        return $this->hasMany(UsersRole::class, 'users_id');
    }

    public function atlet()
    {
        return $this->hasOne(Atlet::class, 'users_id');
    }

    public function pelatih()
    {
        return $this->hasOne(Pelatih::class, 'users_id');
    }

    public function tenagaPendukung()
    {
        return $this->hasOne(TenagaPendukung::class, 'users_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Todo: End Relation

    // Todo: Attribute
    public function getCreatedAtDiffAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getFileUrlImageAttribute()
    {
        // $file_url = getFileUrlAndPath($this->file, 'users')['url'];
        $media = $this->getFirstMedia('images');
        if ($media) {
            if ($media->hasGeneratedConversion('webp')) {
                $file_url = $media->getUrl('webp');
            } else {
                $file_url = $media->getUrl();
            }

            return '<img src="'.$file_url.'" alt="user-avatar" class="d-block flex-shrink-0 rounded-circle me-sm-3 me-2" height="32" width="32" loading="lazy">';
        } else {
            return '<div class="avatar avatar-sm d-block flex-shrink-0 me-sm-3 me-2">
			<span class="avatar-initial rounded-circle bg-label-success">'.InitialName($this->name).'</span>
		  </div>';
        }
    }

    public function getFileUrlAttribute()
    {

        // $file_url = getFileUrlAndPath($this->file, 'users')['url'];
        // if ($file_url == null) {
        // }
        $media = $this->getFirstMedia('images');
        if ($media) {
            if ($media->hasGeneratedConversion('webp')) {
                $file_url = $media->getUrl('webp');
            } else {
                $file_url = $media->getUrl();
            }
        } else {
            $file_url = asset('assets/img/no-image.jpeg');
        }

        return $file_url;
    }

    public function getFilePathAttribute()
    {
        return getFileUrlAndPath($this->file, 'users')['path'];
    }

    public function getIsActiveBadgeAttribute()
    {
        $text     = ($this->is_active == 1) ? 'Aktif' : 'Nonaktif';
        $badge_bg = ($this->is_active == 0) ? 'bg-label-danger' : 'bg-label-primary';

        return "<span class='badge $badge_bg'>$text</span>";
    }

    public function getIsVerifikasiBadgeAttribute()
    {
        return ($this->is_verifikasi == 1) ? "<span class='badge bg-label-primary'>Sudah</span>" : "<span class='badge bg-label-danger'>Belum</span>";
    }

    public function getUsersRoleIdArrayAttribute()
    {
        return $this->users_role()->pluck('role_id')->toArray();
    }

    public function getListRoleNameStrAttribute()
    {
        $role_name_array = [];
        foreach ($this->users_role as $key => $value) {
            $role_name_array[] = $value->role->name;
        }

        return implode(', ', $role_name_array);
    }
    // Todo: End Attribute

    // Todo: Scope
    public function scopeIdInNotIn($query, $data)
    {
        if (isset($data['id_not_in'])) {
            $query->whereNotIn('id', $data['id_not_in']);
        }
        if (isset($data['id_in'])) {
            $query->whereIn('id', $data['id_in']);
        }
    }

    public function scopeFilterUsersRole($query, $role_id)
    {
        $query->whereHas('users_role', function ($query) use ($role_id) {
            if (is_array($role_id)) {
                $query->whereIn('role_id', $role_id);
            } else {
                $query->where('role_id', $role_id);
            }
        });
    }

    public function scopeFilter($query, $data)
    {
        if (@$data['role_id'] != null) {
            $query->filterUsersRole($data['role_id']);
        }

        if (@$data['filter_start_date'] != null and @$data['filter_end_date'] != null) {
            $query->whereBetween('created_at', [$data['filter_start_date'], $data['filter_end_date']]);
        }
    }
    // Todo: End Scope

    public function sendEmailVerificationNotification()
    {
        $this->notify(new RegisterNotification()); // Ini akan mengirim email verifikasi
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
