<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingFacility extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_facilities';

    protected $fillable = [
        'code',
        'name',
        'icon',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(BookingVenue::class, 'booking_facility_venue', 'facility_id', 'venue_id');
    }
}
