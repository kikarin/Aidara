<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPenyewaDocument extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_penyewa_documents';

    protected $fillable = [
        'penyewa_profile_id',
        'document_type_id',
        'file_path',
        'original_name',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BookingPenyewaProfile::class, 'penyewa_profile_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(BookingDocumentType::class, 'document_type_id');
    }
}
