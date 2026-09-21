<?php

namespace App\Models\Booking;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAddonSelected extends Model
{
    protected $table = 'booking_addon_selected';

    protected $fillable = [
        'booking_id',
        'addon_id',
        'name',
        'qty',
        'unit_price',
        'line_total',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'snapshot' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(BookingAddon::class, 'addon_id');
    }
}
