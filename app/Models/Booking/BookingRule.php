<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingRule extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_rules';

    protected $fillable = [
        'venue_id',
        'key',
        'value',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(BookingVenue::class, 'venue_id');
    }
}
