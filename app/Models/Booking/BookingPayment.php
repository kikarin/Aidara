<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPayment extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_payments';

    protected $fillable = [
        'booking_id',
        'gateway',
        'amount',
        'status',
        'bank',
        'rekening',
        'atas_nama',
        'bukti_path',
        'gateway_ref',
        'paid_at',
        'verified_at',
        'verified_by',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
