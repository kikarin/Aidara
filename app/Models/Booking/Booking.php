<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'bookings';

    protected $fillable = [
        'nomor',
        'user_id',
        'penyewa_profile_id',
        'venue_id',
        'area_id',
        'priority_rule_id',
        'kategori_tarif',
        'tujuan',
        'keterangan',
        'status',
        'priority_flag',
        'starts_at',
        'ends_at',
        'buffer_before_days',
        'buffer_after_days',
        'luas_m2',
        'qty',
        'subtotal',
        'addon_total',
        'grand_total',
        'terms_accepted_at',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'confirmed_at',
        'admin_notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'luas_m2' => 'decimal:2',
            'qty' => 'integer',
            'subtotal' => 'integer',
            'addon_total' => 'integer',
            'grand_total' => 'integer',
            'buffer_before_days' => 'integer',
            'buffer_after_days' => 'integer',
            'terms_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penyewaProfile(): BelongsTo
    {
        return $this->belongsTo(BookingPenyewaProfile::class, 'penyewa_profile_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(BookingVenue::class, 'venue_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(BookingArea::class, 'area_id');
    }

    public function priorityRule(): BelongsTo
    {
        return $this->belongsTo(BookingPriorityRule::class, 'priority_rule_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'booking_id');
    }

    public function addonSelected(): HasMany
    {
        return $this->hasMany(BookingAddonSelected::class, 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class, 'booking_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(BookingIncident::class, 'booking_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class, 'booking_id');
    }
}
