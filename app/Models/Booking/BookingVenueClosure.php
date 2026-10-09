<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingVenueClosure extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_venue_closures';

    protected $fillable = [
        'venue_id',
        'area_id',
        'starts_at',
        'ends_at',
        'reason',
        'batch_id',
        'is_full_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_full_day' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(BookingVenue::class, 'venue_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(BookingArea::class, 'area_id');
    }

    /**
     * Overlap aktif untuk venue (+ area opsional).
     * Closure tanpa area_id berlaku untuk seluruh venue.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverlapping(Builder $query, int $venueId, $startsAt, $endsAt, ?int $areaId = null): Builder
    {
        $query
            ->where('venue_id', $venueId)
            ->where('is_active', true)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($areaId) {
            $query->where(function (Builder $q) use ($areaId) {
                $q->whereNull('area_id')->orWhere('area_id', $areaId);
            });
        }

        return $query;
    }
}
