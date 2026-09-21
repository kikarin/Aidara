<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingTarif extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_tarifs';

    protected $fillable = [
        'venue_id',
        'area_id',
        'code',
        'uraian',
        'satuan',
        'tarif_pemerintah',
        'tarif_non_pemerintah',
        'time_slot',
        'audience_type',
        'day_type',
        'vehicle_class',
        'event_level',
        'category',
        'effective_from',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'tarif_pemerintah' => 'integer',
            'tarif_non_pemerintah' => 'integer',
            'effective_from' => 'date',
            'is_active' => 'boolean',
            'meta' => 'array',
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
}
