<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPriorityRule extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_priority_rules';

    protected $fillable = [
        'code',
        'name',
        'priority_order',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
