<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingArea extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_areas';

    protected $fillable = [
        'venue_id',
        'code',
        'name',
        'is_tentative',
        'is_active',
        'meta',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_tentative' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(BookingVenue::class, 'venue_id');
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(BookingTarif::class, 'area_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'area_id');
    }
}
