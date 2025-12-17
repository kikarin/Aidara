<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OptionsController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (tidak perlu authentication)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp']);

// Protected routes (perlu authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    // Options/Dropdown routes (public untuk authenticated user)
    Route::prefix('options')->group(function () {
        Route::get('/kecamatan', [OptionsController::class, 'getKecamatan']);
        Route::get('/kelurahan/{kecamatanId}', [OptionsController::class, 'getKelurahanByKecamatan']);
        Route::get('/tingkat', [OptionsController::class, 'getTingkat']);
        Route::get('/kategori-prestasi-pelatih', [OptionsController::class, 'getKategoriPrestasiPelatih']);
        Route::get('/kategori-atlet', [OptionsController::class, 'getKategoriAtlet']);
        Route::get('/jenis-dokumen', [OptionsController::class, 'getJenisDokumen']);
        Route::get('/posisi-atlet', [OptionsController::class, 'getPosisiAtlet']);
        Route::get('/kategori-peserta', [OptionsController::class, 'getKategoriPeserta']);
        Route::get('/cabor', [OptionsController::class, 'getCabor']);
        Route::get('/cabor-kategori/{caborId}', [OptionsController::class, 'getCaborKategoriByCabor']);
        Route::get('/all', [OptionsController::class, 'getAllOptions']); // Get all options sekaligus
    });
    
    // Profile routes (auto-detect role: atlet/pelatih/tenaga_pendukung)
    Route::prefix('profile')->group(function () {
        // Biodata
        Route::get('/biodata', [ProfileController::class, 'getBiodata']);
        Route::put('/biodata', [ProfileController::class, 'updateBiodata']);
        
        // Sertifikat
        Route::get('/sertifikat', [ProfileController::class, 'getSertifikat']);
        Route::post('/sertifikat', [ProfileController::class, 'storeSertifikat']);
        Route::delete('/sertifikat/{id}', [ProfileController::class, 'deleteSertifikat']);
        
        // Prestasi
        Route::get('/prestasi', [ProfileController::class, 'getPrestasi']);
        Route::post('/prestasi', [ProfileController::class, 'storePrestasi']);
        Route::delete('/prestasi/{id}', [ProfileController::class, 'deletePrestasi']);
        
        // Dokumen
        Route::get('/dokumen', [ProfileController::class, 'getDokumen']);
        Route::post('/dokumen', [ProfileController::class, 'storeDokumen']);
        Route::delete('/dokumen/{id}', [ProfileController::class, 'deleteDokumen']);
    });
    
    // Tambahkan API module lain di sini nanti
});