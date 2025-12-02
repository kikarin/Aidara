<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UsersMenu extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'nama',
        'kode',
        'icon',
        'rel',
        'urutan',
        'url',
        'permission_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Users Menu');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'rel')->orderBy('urutan', 'asc');
    }

    public function rel_users_menu()
    {
        return $this->belongsTo(UsersMenu::class, 'rel');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id')->withDefault(['name' => null]);
    }
}
