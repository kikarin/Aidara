<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CategoryPermission extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;

    protected $table = 'category_permissions';

    protected $fillable = [
        'name',
        'sequence',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Category Permission');
    }

    public function permission()
    {
        return $this->hasMany(Permission::class, 'category_permission_id');
    }
}
