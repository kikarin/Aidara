<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
<<<<<<< HEAD
=======
use App\Http\Controllers\Auth\EmailOtpVerificationController;
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Registration\RegistrationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Registration baru (multi-step)
    Route::get('register', [RegistrationController::class, 'create'])
        ->name('register');

    Route::post('register', [RegistrationController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
<<<<<<< HEAD
=======
    // Email OTP Verification Routes
    Route::get('email/otp/verify', [EmailOtpVerificationController::class, 'show'])
        ->name('email.otp.verify');

    Route::post('email/otp/verify', [EmailOtpVerificationController::class, 'verify'])
        ->name('email.otp.verify');

    Route::post('email/otp/resend', [EmailOtpVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('email.otp.resend');

>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
