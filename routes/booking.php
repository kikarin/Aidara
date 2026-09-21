<?php

use App\Http\Controllers\Booking\Admin\BookingAdminController;
use App\Http\Controllers\Booking\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Booking\Admin\MasterAdminController;
use App\Http\Controllers\Booking\Admin\PaymentAdminController;
use App\Http\Controllers\Booking\AuthController;
use App\Http\Controllers\Booking\Penyewa\BookingController as PenyewaBookingController;
use App\Http\Controllers\Booking\Penyewa\ProfileController as PenyewaProfileController;
use App\Http\Controllers\Booking\Public\AvailabilityController;
use App\Http\Controllers\Booking\Public\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E-Booking API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/booking
| Dipisah dari routes/api.php (mobile Aidara).
|
*/

Route::get('/health', HealthController::class);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('public')->group(function () {
    Route::get('/venues', [AvailabilityController::class, 'venues']);
    Route::get('/venues/{venueId}/areas', [AvailabilityController::class, 'areas'])->whereNumber('venueId');
    Route::get('/tarifs', [AvailabilityController::class, 'tarifs']);
    Route::get('/addons', [AvailabilityController::class, 'addons']);
    Route::get('/settings', [AvailabilityController::class, 'settings']);
    Route::get('/terms', [AvailabilityController::class, 'terms']);
    Route::get('/availability', [AvailabilityController::class, 'check']);
    Route::post('/quote', [AvailabilityController::class, 'quote']);
});

Route::middleware(['auth:sanctum', 'booking.role:penyewa,admin_upt'])->group(function () {
    Route::prefix('penyewa')->group(function () {
        Route::get('/profile', [PenyewaProfileController::class, 'show']);
        Route::put('/profile', [PenyewaProfileController::class, 'update']);
        Route::post('/documents/ktp', [PenyewaProfileController::class, 'uploadKtp']);

        Route::post('/quote', [PenyewaBookingController::class, 'quote']);
        Route::get('/bookings', [PenyewaBookingController::class, 'index']);
        Route::post('/bookings', [PenyewaBookingController::class, 'store']);
        Route::get('/bookings/{id}', [PenyewaBookingController::class, 'show'])->whereNumber('id');
        Route::get('/bookings/{id}/payment', [PenyewaBookingController::class, 'paymentInfo'])->whereNumber('id');
        Route::post('/bookings/{id}/payment/bukti', [PenyewaBookingController::class, 'uploadBukti'])->whereNumber('id');
        Route::post('/bookings/{id}/cancel', [PenyewaBookingController::class, 'cancel'])->whereNumber('id');
        Route::post('/bookings/{id}/reschedule', [PenyewaBookingController::class, 'reschedule'])->whereNumber('id');
    });
});

Route::middleware(['auth:sanctum', 'booking.role:admin_upt'])->prefix('admin')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class);

    Route::get('/bookings', [BookingAdminController::class, 'index']);
    Route::get('/bookings/{id}', [BookingAdminController::class, 'show'])->whereNumber('id');
    Route::get('/bookings/{id}/conflict', [BookingAdminController::class, 'conflict'])->whereNumber('id');
    Route::post('/bookings/{id}/approve', [BookingAdminController::class, 'approve'])->whereNumber('id');
    Route::post('/bookings/{id}/reject', [BookingAdminController::class, 'reject'])->whereNumber('id');
    Route::post('/bookings/{id}/klarifikasi', [BookingAdminController::class, 'klarifikasi'])->whereNumber('id');
    Route::post('/bookings/{id}/incidents/rain', [\App\Http\Controllers\Booking\Admin\IncidentAdminController::class, 'rain'])->whereNumber('id');
    Route::post('/bookings/{id}/incidents/charge', [\App\Http\Controllers\Booking\Admin\IncidentAdminController::class, 'charge'])->whereNumber('id');
    Route::post('/bookings/{id}/reschedule', [\App\Http\Controllers\Booking\Admin\IncidentAdminController::class, 'reschedule'])->whereNumber('id');
    Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Booking\Admin\IncidentAdminController::class, 'cancel'])->whereNumber('id');

    Route::post('/payments/{paymentId}/verify', [PaymentAdminController::class, 'verify'])->whereNumber('paymentId');
    Route::post('/payments/{paymentId}/reject', [PaymentAdminController::class, 'reject'])->whereNumber('paymentId');

    Route::match(['get', 'post'], '/venues', [MasterAdminController::class, 'venues']);
    Route::put('/venues/{id}', [MasterAdminController::class, 'updateVenue'])->whereNumber('id');
    Route::match(['get', 'post'], '/areas', [MasterAdminController::class, 'areas']);
    Route::put('/areas/{id}', [MasterAdminController::class, 'updateArea'])->whereNumber('id');
    Route::match(['get', 'post'], '/tarifs', [MasterAdminController::class, 'tarifs']);
    Route::put('/tarifs/{id}', [MasterAdminController::class, 'updateTarif'])->whereNumber('id');
    Route::match(['get', 'post'], '/addons', [MasterAdminController::class, 'addons']);
    Route::put('/addons/{id}', [MasterAdminController::class, 'updateAddon'])->whereNumber('id');
    Route::match(['get', 'post'], '/rules', [MasterAdminController::class, 'rules']);
    Route::put('/rules/{id}', [MasterAdminController::class, 'updateRule'])->whereNumber('id');
    Route::match(['get', 'post', 'put'], '/settings', [MasterAdminController::class, 'settings']);
    Route::get('/priority-rules', [MasterAdminController::class, 'priorityRules']);
});
