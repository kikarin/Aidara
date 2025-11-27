<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Identity extends Model
{
    use Blameable;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'identity';

    protected $guarded = [];

    public const TYPE_TEXT = 'Text';

    public const TYPE_TEXTAREA = 'Textarea';

    public const TYPE_FILE = 'File';

    public function listType(): array
    {
        return [
            self::TYPE_TEXT     => self::TYPE_TEXT,
            self::TYPE_TEXTAREA => self::TYPE_TEXTAREA,
            self::TYPE_FILE     => self::TYPE_FILE,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Identity');
    }

    public function getFileFotoAttribute()
    {
        $file_path = 'identity/photo/'.$this->value;
        $result    = asset('assets/img/no-image.jpeg');
        if ($this->type == self::TYPE_FILE) {
            if ($this->value != '' and Storage::disk('public')->exists($file_path)) {
                $result = url(Storage::url($file_path));
            }
        }

        return $result;
    }
}
