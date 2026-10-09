<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingIncident extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_incidents';

    protected $fillable = [
        'booking_id',
        'type',
        'play_elapsed_minutes',
        'play_started_at',
        'decision',
        'notes',
        'resolved_by',
        'resolved_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'play_elapsed_minutes' => 'integer',
            'play_started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
