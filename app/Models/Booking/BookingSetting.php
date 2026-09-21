<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;

class BookingSetting extends Model
{
    use Blameable;

    protected $table = 'booking_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function setValue(string $key, mixed $value, ?string $description = null): void
    {
        $payload = ['value' => $value];

        if ($description !== null) {
            $payload['description'] = $description;
        }

        static::query()->updateOrCreate(['key' => $key], $payload);
    }
}
