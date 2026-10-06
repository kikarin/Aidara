<?php

namespace App\Models\Booking;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    protected $table = 'booking_items';

    protected $fillable = [
        'booking_id',
        'tarif_id',
        'uraian',
        'satuan',
        'qty',
        'luas_m2',
        'duration_value',
        'unit_price',
        'line_total',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'luas_m2' => 'decimal:2',
            'duration_value' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'snapshot' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(BookingTarif::class, 'tarif_id');
    }
}
