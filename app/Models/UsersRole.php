<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UsersRole extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Users Role');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
