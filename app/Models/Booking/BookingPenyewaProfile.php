<?php

namespace App\Models\Booking;

use App\Blameable;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPenyewaProfile extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $table = 'booking_penyewa_profiles';

    protected $fillable = [
        'user_id',
        'nama',
        'nik',
        'no_hp',
        'alamat',
        'instansi',
        'kategori_default',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BookingPenyewaDocument::class, 'penyewa_profile_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'penyewa_profile_id');
    }
}
