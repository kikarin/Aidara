<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingVenue extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_venues';

    protected $fillable = [
        'code',
        'name',
        'description',
        'cover_path',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'cover_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        return asset('storage/'.$this->cover_path);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(BookingArea::class, 'venue_id');
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(BookingTarif::class, 'venue_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(BookingRule::class, 'venue_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'venue_id');
    }

    public function closures(): HasMany
    {
        return $this->hasMany(BookingVenueClosure::class, 'venue_id');
    }
}
