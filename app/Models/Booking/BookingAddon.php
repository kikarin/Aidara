<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingAddon extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_addons';

    protected $fillable = [
        'code',
        'name',
        'description',
        'harga',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(BookingVenue::class, 'booking_addon_venue', 'addon_id', 'venue_id');
    }
}
