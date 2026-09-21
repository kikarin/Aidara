<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingDocumentType extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_document_types';

    protected $fillable = [
        'code',
        'name',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
