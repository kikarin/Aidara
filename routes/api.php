<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaborController;
use App\Http\Controllers\Api\OptionsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProgramLatihanController;
use App\Http\Controllers\Api\RekapAbsenController;
use App\Http\Controllers\Api\PemeriksaanController;
use App\Http\Controllers\Api\PemeriksaanKhususController;
use App\Http\Controllers\Api\PrestasiController;
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
        Route::get('/tenaga-pendukung', [OptionsController::class, 'getTenagaPendukung']);
        Route::get('/parameter-pemeriksaan', [OptionsController::class, 'getParameterPemeriksaan']);
        Route::get('/ref-status-pemeriksaan', [OptionsController::class, 'getRefStatusPemeriksaan']);
        Route::get('/all', [OptionsController::class, 'getAllOptions']); // Get all options sekaligus
    });
    
    // Profile routes (auto-detect role: atlet/pelatih/tenaga_pendukung)
    Route::prefix('profile')->group(function () {
        // Biodata
        Route::get('/biodata', [ProfileController::class, 'getBiodata']);
        Route::put('/biodata', [ProfileController::class, 'updateBiodata']);
        Route::post('/biodata', [ProfileController::class, 'updateBiodata']); // POST untuk file upload (dengan _method=PUT atau langsung POST)
        
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
    
    // Program Latihan routes (mobile API)
    Route::prefix('v1/program-latihan')->group(function () {
        Route::get('/', [ProgramLatihanController::class, 'index']);
        Route::post('/', [ProgramLatihanController::class, 'store']);
        Route::put('/{id}', [ProgramLatihanController::class, 'update']);
        Route::delete('/{id}', [ProgramLatihanController::class, 'destroy']);
        
        // Filter endpoints (bisa digunakan untuk filter dan form dropdown)
        Route::get('/filter/cabor', [ProgramLatihanController::class, 'getCaborList']);
        Route::get('/filter/kategori/{caborId}', [ProgramLatihanController::class, 'getKategoriByCabor']);
        
        // Rekap Absen routes
        Route::prefix('{programId}/rekap-absen')->group(function () {
            Route::get('/', [RekapAbsenController::class, 'index']);
            Route::get('/today', [RekapAbsenController::class, 'getToday']);
            Route::post('/', [RekapAbsenController::class, 'store']);
            Route::post('/{rekapId}', [RekapAbsenController::class, 'update']); // POST untuk update (PUT tidak reliable untuk file upload)
            Route::delete('/{rekapId}/media/{mediaId}', [RekapAbsenController::class, 'deleteMedia']);
        });
    });
    
    // Pemeriksaan routes (mobile API)
    Route::prefix('v1/pemeriksaan')->group(function () {
        Route::get('/', [PemeriksaanController::class, 'index']);
        Route::get('/statistik/parameter/{parameterId}', [PemeriksaanController::class, 'getStatistikParameter']);
        Route::get('/{id}', [PemeriksaanController::class, 'show']);
        Route::post('/', [PemeriksaanController::class, 'store']);
        Route::put('/{id}', [PemeriksaanController::class, 'update']);
        Route::delete('/{id}', [PemeriksaanController::class, 'destroy']);
        
        // Peserta dengan parameter
        Route::get('/{id}/peserta', [PemeriksaanController::class, 'getPesertaWithParameter']);
        Route::post('/{id}/peserta-parameter/bulk-update', [PemeriksaanController::class, 'bulkUpdatePesertaParameter']);
    });
    
    // Cabor routes (mobile API)
    Route::prefix('v1/cabor')->group(function () {
        Route::get('/', [CaborController::class, 'index']);
        Route::get('/{id}/peserta', [CaborController::class, 'getPeserta']);
        Route::get('/{id}/perbandingan', [CaborController::class, 'getPerbandingan']);
        Route::get('/{id}/ranking', [CaborController::class, 'getRanking']);
    });

    // Pemeriksaan Khusus routes (mobile API)
    Route::prefix('v1/pemeriksaan-khusus')->group(function () {
        Route::get('/', [PemeriksaanKhususController::class, 'index']);
        Route::post('/', [PemeriksaanKhususController::class, 'store']);
        
        // Template routes (harus sebelum route dengan {id} untuk menghindari konflik)
        Route::get('/template/{caborId}', [PemeriksaanKhususController::class, 'getTemplate']);
        Route::post('/template', [PemeriksaanKhususController::class, 'saveTemplate']);
        Route::post('/clone-template', [PemeriksaanKhususController::class, 'cloneFromTemplate']);
        Route::post('/setup', [PemeriksaanKhususController::class, 'saveSetup']);
        Route::post('/hasil-tes', [PemeriksaanKhususController::class, 'saveHasilTes']);
        
        // Routes dengan {id} - urutkan dari yang paling spesifik ke yang paling umum
        Route::get('/{id}/peserta-visualisasi', [PemeriksaanKhususController::class, 'getPesertaVisualisasi']);
        Route::get('/{id}/visualisasi/{pesertaId}', [PemeriksaanKhususController::class, 'getVisualisasiPeserta']);
        Route::get('/{id}/peserta-hasil-tes', [PemeriksaanKhususController::class, 'getPesertaForInputHasilTes']);
        Route::get('/{id}/setup/{pesertaId}', [PemeriksaanKhususController::class, 'getSetupForPeserta']);
        Route::get('/{id}/hasil-tes', [PemeriksaanKhususController::class, 'getHasilTes']);
        Route::get('/{id}/setup', [PemeriksaanKhususController::class, 'getSetup']);
        Route::put('/{id}', [PemeriksaanKhususController::class, 'update']);
        Route::delete('/{id}', [PemeriksaanKhususController::class, 'destroy']);
    });
    
    // Prestasi routes (mobile API)
    Route::prefix('v1/prestasi')->group(function () {
        Route::get('/', [PrestasiController::class, 'index']);
    });
    
    // Tambahkan API module lain di sini nanti
});