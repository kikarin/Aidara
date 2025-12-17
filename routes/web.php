<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AllParameterController;
use App\Http\Controllers\AtletController;
use App\Http\Controllers\AtletDokumenController;
use App\Http\Controllers\AtletKesehatanController;
use App\Http\Controllers\AtletOrangTuaController;
use App\Http\Controllers\AtletPrestasiController;
use App\Http\Controllers\AtletSertifikatController;
use App\Http\Controllers\CaborController;
use App\Http\Controllers\CaborKategoriAtletController;
use App\Http\Controllers\CaborKategoriController;
use App\Http\Controllers\CaborKategoriPelatihController;
use App\Http\Controllers\CaborKategoriTenagaPendukungController;
use App\Http\Controllers\CategoryPermissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesaController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\MstJenisDokumenController;
use App\Http\Controllers\MstJenisPelatihController;
use App\Http\Controllers\MstJenisTenagaPendukungController;
use App\Http\Controllers\MstPosisiAtletController;
use App\Http\Controllers\MstTingkatController;
use App\Http\Controllers\MstKategoriAtletController;
use App\Http\Controllers\MstKategoriPesertaController;
use App\Http\Controllers\MstKategoriPrestasiPelatihController;
use App\Http\Controllers\MstJuaraController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PelatihController;
use App\Http\Controllers\PelatihDokumenController;
use App\Http\Controllers\PelatihKesehatanController;
use App\Http\Controllers\PelatihPrestasiController;
use App\Http\Controllers\PelatihSertifikatController;
use App\Http\Controllers\PemeriksaanController;
use App\Http\Controllers\PemeriksaanKhususController;
use App\Http\Controllers\PemeriksaanParameterController;
use App\Http\Controllers\PemeriksaanPesertaController;
use App\Http\Controllers\PemeriksaanPesertaParameterController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrestasiController;
use App\Http\Controllers\ProgramLatihanController;
use App\Http\Controllers\RekapAbsenProgramLatihanController;
use App\Http\Controllers\RefStatusPemeriksaanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenagaPendukungController;
use App\Http\Controllers\TenagaPendukungDokumenController;
use App\Http\Controllers\TenagaPendukungKesehatanController;
use App\Http\Controllers\TenagaPendukungPrestasiController;
use App\Http\Controllers\TenagaPendukungSertifikatController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UsersMenuController;
use App\Http\Controllers\MstJenisUnitPendukungController;
use App\Http\Controllers\MstParameterController;
use App\Http\Controllers\UnitPendukungController;
use App\Http\Controllers\TurnamenController;
use App\Http\Controllers\Registration\RegistrationController;
use App\Http\Controllers\Registration\RegistrationStepController;
use App\Http\Controllers\Registration\RegistrationApprovalController;
use App\Models\Cabor;
use App\Models\MstDesa;
use App\Models\MstJenisDokumen;
use App\Models\MstJenisPelatih;
use App\Models\MstJenisTenagaPendukung;
use App\Models\MstKecamatan;
use App\Models\MstPosisiAtlet;
use App\Models\MstTingkat;
use App\Models\MstKategoriPeserta;
use App\Models\MstJenisUnitPendukung;
use App\Models\MstJuara;
use App\Models\MstKategoriPrestasiPelatih;
use App\Models\MstParameter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;

// =====================
// ROUTE UTAMA
// =====================
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'ensure.email.verified', 'check.registration.status'])->name('dashboard');

// Prestasi
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    Route::get('/prestasi', [PrestasiController::class, 'index'])->name('prestasi.index');
    Route::get('/api/prestasi', [PrestasiController::class, 'apiIndex'])->name('prestasi.apiIndex');
});

// =====================
// REGISTRATION (Multi-step)
// =====================
// Guest routes untuk registrasi awal
Route::middleware('guest')->group(function () {
    Route::get('/register/success', [RegistrationController::class, 'success'])->name('registration.success');
});

// Routes untuk registration steps (user yang sudah register tapi belum approved)
Route::middleware(['auth'])->group(function () {
    Route::get('/register/steps', [RegistrationStepController::class, 'index'])->name('registration.steps');
    Route::post('/register/steps/1', [RegistrationStepController::class, 'storeStep1'])->name('registration.steps.1');
    Route::post('/register/steps/submit', [RegistrationStepController::class, 'submit'])->name('registration.steps.submit');
    Route::post('/register/steps/{step}/draft', [RegistrationStepController::class, 'saveDraft'])->name('registration.steps.draft');
});

// =====================
// API MASTER (untuk datatable & select)
// =====================
// datatable
Route::get('/api/tingkat', [MstTingkatController::class, 'apiIndex']);
Route::get('/api/kategori-atlet', [MstKategoriAtletController::class, 'apiIndex']);
Route::get('/api/kategori-peserta', [MstKategoriPesertaController::class, 'apiIndex']);
Route::get('/api/kategori-prestasi-pelatih', [MstKategoriPrestasiPelatihController::class, 'apiIndex']);
Route::get('/api/jenis-dokumen', [MstJenisDokumenController::class, 'apiIndex']);
Route::get('/api/kecamatan', [KecamatanController::class, 'apiIndex']);
Route::get('/api/desa', [DesaController::class, 'apiIndex']);
Route::get('/api/posisi-atlet', [MstPosisiAtletController::class, 'apiIndex']);
Route::get('/api/jenis-pelatih', [MstJenisPelatihController::class, 'apiIndex']);
Route::get('/api/jenis-tenaga-pendukung', [MstJenisTenagaPendukungController::class, 'apiIndex']);
Route::get('/api/jenis-unit-pendukung', [MstJenisUnitPendukungController::class, 'apiIndex']);
Route::get('/api/juara', [MstJuaraController::class, 'apiIndex']);
Route::get('/api/event', [EventController::class, 'apiIndex']);
Route::get('/api/parameter', [MstParameterController::class, 'apiIndex']);

// select
Route::get('/api/tingkat-list', function () {
    try {
        $data = MstTingkat::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/tingkat-list: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/kategori-atlet-list', function () {
    try {
        $data = MstKategoriPeserta::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/kategori-atlet-list: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/kategori-peserta-list', function () {
    try {
        $data = MstKategoriPeserta::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/kategori-peserta-list: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/kategori-prestasi-pelatih-list', function () {
    try {
        $data = MstKategoriPrestasiPelatih::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/kategori-prestasi-pelatih-list: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/jenis-dokumen-list', function () {
    try {
        $data = MstJenisDokumen::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/jenis-dokumen-list: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/kecamatan-list', function () {
    try {
        $data = MstKecamatan::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/kecamatan-list: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/kelurahan-by-kecamatan/{id_kecamatan}', function ($id_kecamatan) {
    try {
        $data = MstDesa::where('id_kecamatan', $id_kecamatan)->select('id', 'nama')->orderBy('nama')->get();
        return response()->json($data->toArray());
    } catch (\Exception $e) {
        \Log::error('Error in /api/kelurahan-by-kecamatan: ' . $e->getMessage());
        return response()->json([]);
    }
});
Route::get('/api/posisi-atlet-list', function () {
    return MstPosisiAtlet::select('id', 'nama')->orderBy('nama')->get();
});
Route::get('/api/jenis-pelatih-list', function () {
    return MstJenisPelatih::select('id', 'nama')->orderBy('nama')->get();
});
Route::get('/api/jenis-tenaga-pendukung-list', function () {
    return MstJenisTenagaPendukung::select('id', 'nama')->orderBy('nama')->get();
});
Route::get('/api/jenis-unit-pendukung-list', function () {
    return MstJenisUnitPendukung::select('id', 'nama')->orderBy('nama')->get();
});
Route::get('/api/juara-list', function () {
    return MstJuara::select('id', 'nama')->orderBy('nama')->get();
});
Route::get('/api/parameter-list', function () {
    return MstParameter::select('id', 'nama')
        ->whereNull('deleted_at')
        ->orderBy('nama')
        ->get();
});

// =====================
// REGISTRATION APPROVAL (Admin)
// =====================
Route::middleware(['auth', 'verified', 'can:Registration Approval Show'])->group(function () {
    Route::get('/registration-approval', [RegistrationApprovalController::class, 'index'])->name('registration-approval.index');
    Route::get('/registration-approval/{id}', [RegistrationApprovalController::class, 'show'])->name('registration-approval.show');
    Route::post('/registration-approval/{id}/approve', [RegistrationApprovalController::class, 'approve'])->name('registration-approval.approve');
    Route::post('/registration-approval/{id}/reject', [RegistrationApprovalController::class, 'reject'])->name('registration-approval.reject');
    Route::delete('/registration-approval/{id}', [RegistrationApprovalController::class, 'destroy'])->name('registration-approval.destroy');
    Route::post('/registration-approval/destroy-selected', [RegistrationApprovalController::class, 'destroySelected'])->name('registration-approval.destroy_selected');
    Route::get('/api/registration-approval', [RegistrationApprovalController::class, 'apiIndex'])->name('api.registration-approval');
});

// =====================
// USERS, MENU, ROLES, PERMISSIONS, LOGS
// =====================
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    Route::resource('/users', UsersController::class)->names('users');
    Route::get('/users/{id}/login-as', [UsersController::class, 'login_as'])->name('users.login-as');
    Route::post('/users/switch-role', [UsersController::class, 'switchRole'])->name('users.switch-role');
    Route::get('/api/users', [UsersController::class, 'apiIndex']);
    Route::post('/users/destroy-selected', [UsersController::class, 'destroy_selected'])->name('users.destroy_selected');

    Route::resource('/menu-permissions/menus', UsersMenuController::class)->names('menus');
    Route::get('/api/users-menu', [UsersMenuController::class, 'getMenus'])->name('api.users-menu');
    Route::get('/api/menus', [UsersMenuController::class, 'apiIndex'])->name('api.menus');
    Route::post('/menu-permissions/menus/destroy-selected', [UsersMenuController::class, 'destroy_selected'])->name('menus.destroy-selected');

    Route::resource('/menu-permissions/roles', RoleController::class)->names('roles');
    Route::get('/api/roles', [RoleController::class, 'apiIndex']);
    Route::get('/menu-permissions/roles/set-permissions/{id}', [RoleController::class, 'set_permission'])->name('roles.set-permission');
    Route::post('/menu-permissions/roles/set-permissions/{id}', [RoleController::class, 'set_permission_action'])->name('roles.set-permission-action');
    Route::post('/menu-permissions/roles/destroy-selected', [RoleController::class, 'destroy_selected'])->name('roles.destroy_selected');

    Route::middleware(['auth', 'verified'])->prefix('menu-permissions')->group(function () {
        Route::get('/permissions/create-permission', [PermissionController::class, 'create'])->name('permissions.create-permission');
        Route::post('/permissions/store-permission', [PermissionController::class, 'store'])->name('permissions.store-permission');
        Route::get('/permissions/{id}/edit-permission', [PermissionController::class, 'edit'])->name('permissions.edit-permission');
        Route::put('/permissions/update-permission/{id}', [PermissionController::class, 'update'])->name('permissions.update-permission');
        Route::get('/permissions/{id}/detail', [PermissionController::class, 'show'])->name('permissions.detail');
        Route::delete('/permissions/delete-permission/{id}', [PermissionController::class, 'destroy'])->name('permissions.delete-permission');
        Route::resource('/permissions', CategoryPermissionController::class)->names('permissions');
        Route::get('/permissions/category/{id}', [CategoryPermissionController::class, 'show'])->name('permissions.category.show');
        Route::get('/permissions/category/{id}/edit', [CategoryPermissionController::class, 'edit'])->name('permissions.category.edit');
    });

    Route::get('/menu-permissions/logs', fn () => Inertia::render('modules/activity-logs/Index'))->name('access-control.logs.index');
    Route::get('/menu-permissions/logs/{id}', [ActivityLogController::class, 'show'])->name('access-control.logs.show');
    Route::get('/api/activity-logs', [ActivityLogController::class, 'apiIndex']);
    Route::delete('/menu-permissions/logs/{id}', [ActivityLogController::class, 'destroy'])->name('access-control.logs.destroy');
    Route::post('/menu-permissions/logs/destroy-selected', [ActivityLogController::class, 'destroy_selected'])->name('access-control.logs.destroy-selected');
});

// =====================
// ATLET & PELATIH (beserta nested resource)
// =====================
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    // ATLET
    Route::get('/atlet/karakteristik', [AtletController::class, 'karakteristik'])->name('atlet.karakteristik');
    Route::post('/atlet/api-karakteristik', [AtletController::class, 'apiKarakteristik'])->name('atlet.api-karakteristik');

    Route::resource('/atlet', AtletController::class)->names('atlet');
    Route::get('/api/atlet', [AtletController::class, 'apiIndex']);
    Route::post('/atlet/destroy-selected', [AtletController::class, 'destroy_selected'])->name('atlet.destroy_selected');
    Route::post('/atlet/import', [AtletController::class, 'import'])->name('atlet.import');
    Route::prefix('atlet/{atlet_id}')->group(function () {
        Route::get('orang-tua', [AtletOrangTuaController::class, 'getByAtletId'])->name('atlet.orang-tua.show');
        Route::post('orang-tua', [AtletOrangTuaController::class, 'store'])->name('atlet.orang-tua.store');
        Route::put('orang-tua/{id}', [AtletOrangTuaController::class, 'update'])->name('atlet.orang-tua.update');
        Route::delete('orang-tua/{id}', [AtletOrangTuaController::class, 'destroy'])->name('atlet.orang-tua.destroy');
        // Sertifikat, Prestasi, Dokumen, Kesehatan nested
        Route::get('sertifikat', [AtletSertifikatController::class, 'index'])->name('atlet.sertifikat.index');
        Route::get('sertifikat/create', [AtletSertifikatController::class, 'create'])->name('atlet.sertifikat.create');
        Route::post('sertifikat', [AtletSertifikatController::class, 'store'])->name('atlet.sertifikat.store');
        Route::get('sertifikat/{id}/edit', [AtletSertifikatController::class, 'edit'])->name('atlet.sertifikat.edit');
        Route::put('sertifikat/{id}', [AtletSertifikatController::class, 'update'])->name('atlet.sertifikat.update');
        Route::delete('sertifikat/{id}', [AtletSertifikatController::class, 'destroy'])->name('atlet.sertifikat.destroy');
        Route::post('sertifikat/destroy-selected', [AtletSertifikatController::class, 'destroy_selected'])->name('atlet.sertifikat.destroy_selected');
        Route::get('sertifikat/{id}', [AtletSertifikatController::class, 'show'])->name('atlet.sertifikat.show');
        Route::get('prestasi', [AtletPrestasiController::class, 'index'])->name('atlet.prestasi.index');
        Route::get('prestasi/create', [AtletPrestasiController::class, 'create'])->name('atlet.prestasi.create');
        Route::post('prestasi', [AtletPrestasiController::class, 'store'])->name('atlet.prestasi.store');
        Route::get('prestasi/{id}/edit', [AtletPrestasiController::class, 'edit'])->name('atlet.prestasi.edit');
        Route::put('prestasi/{id}', [AtletPrestasiController::class, 'update'])->name('atlet.prestasi.update');
        Route::delete('prestasi/{id}', [AtletPrestasiController::class, 'destroy'])->name('atlet.prestasi.destroy');
        Route::post('prestasi/destroy-selected', [AtletPrestasiController::class, 'destroy_selected'])->name('atlet.prestasi.destroy_selected');
        Route::get('prestasi/{id}', [AtletPrestasiController::class, 'show'])->name('atlet.prestasi.show');
        Route::get('dokumen', [AtletDokumenController::class, 'index'])->name('atlet.dokumen.index');
        Route::get('dokumen/create', [AtletDokumenController::class, 'create'])->name('atlet.dokumen.create');
        Route::post('dokumen', [AtletDokumenController::class, 'store'])->name('atlet.dokumen.store');
        Route::get('dokumen/{id}/edit', [AtletDokumenController::class, 'edit'])->name('atlet.dokumen.edit');
        Route::put('dokumen/{id}', [AtletDokumenController::class, 'update'])->name('atlet.dokumen.update');
        Route::delete('dokumen/{id}', [AtletDokumenController::class, 'destroy'])->name('atlet.dokumen.destroy');
        Route::post('dokumen/destroy-selected', [AtletDokumenController::class, 'destroy_selected'])->name('atlet.dokumen.destroy_selected');
        Route::get('dokumen/{id}', [AtletDokumenController::class, 'show'])->name('atlet.dokumen.show');
        Route::get('kesehatan', [AtletKesehatanController::class, 'getByAtletId'])->name('atlet.kesehatan.show');
        Route::post('kesehatan', [AtletKesehatanController::class, 'store'])->name('atlet.kesehatan.store');
        Route::put('kesehatan/{id}', [AtletKesehatanController::class, 'update'])->name('atlet.kesehatan.update');
        Route::delete('kesehatan/{id}', [AtletKesehatanController::class, 'destroy'])->name('atlet.kesehatan.destroy');

        // Riwayat Pemeriksaan
        Route::get('riwayat-pemeriksaan', [AtletController::class, 'riwayatPemeriksaan'])->name('atlet.riwayat-pemeriksaan');
        Route::get('riwayat-pemeriksaan/{pemeriksaan_id}/parameter', [AtletController::class, 'parameterDetail'])->name('atlet.riwayat-pemeriksaan.parameter');

        // Akun Atlet
        Route::post('akun', [AtletController::class, 'storeAkun'])->name('atlet.akun.store');
        Route::put('akun', [AtletController::class, 'updateAkun'])->name('atlet.akun.update');
    });
    // PELATIH
    Route::get('/pelatih/karakteristik', [PelatihController::class, 'karakteristik'])->name('pelatih.karakteristik');
    Route::post('/pelatih/api-karakteristik', [PelatihController::class, 'apiKarakteristik'])->name('pelatih.api-karakteristik');

    Route::resource('/pelatih', PelatihController::class)->names('pelatih');
    Route::get('/api/pelatih', [PelatihController::class, 'apiIndex']);
    Route::post('/pelatih/destroy-selected', [PelatihController::class, 'destroy_selected'])->name('pelatih.destroy_selected');
    Route::post('/pelatih/import', [PelatihController::class, 'import'])->name('pelatih.import');
    Route::prefix('pelatih/{pelatih_id}')->group(function () {
        Route::get('sertifikat', [PelatihSertifikatController::class, 'index'])->name('pelatih.sertifikat.index');
        Route::get('sertifikat/create', [PelatihSertifikatController::class, 'create'])->name('pelatih.sertifikat.create');
        Route::post('sertifikat', [PelatihSertifikatController::class, 'store'])->name('pelatih.sertifikat.store');
        Route::get('sertifikat/{id}/edit', [PelatihSertifikatController::class, 'edit'])->name('pelatih.sertifikat.edit');
        Route::put('sertifikat/{id}', [PelatihSertifikatController::class, 'update'])->name('pelatih.sertifikat.update');
        Route::delete('sertifikat/{id}', [PelatihSertifikatController::class, 'destroy'])->name('pelatih.sertifikat.destroy');
        Route::post('sertifikat/destroy-selected', [PelatihSertifikatController::class, 'destroy_selected'])->name('pelatih.sertifikat.destroy_selected');
        Route::get('sertifikat/{id}', [PelatihSertifikatController::class, 'show'])->name('pelatih.sertifikat.show');
        Route::get('prestasi', [PelatihPrestasiController::class, 'index'])->name('pelatih.prestasi.index');
        Route::get('prestasi/create', [PelatihPrestasiController::class, 'create'])->name('pelatih.prestasi.create');
        Route::post('prestasi', [PelatihPrestasiController::class, 'store'])->name('pelatih.prestasi.store');
        Route::get('prestasi/{id}/edit', [PelatihPrestasiController::class, 'edit'])->name('pelatih.prestasi.edit');
        Route::put('prestasi/{id}', [PelatihPrestasiController::class, 'update'])->name('pelatih.prestasi.update');
        Route::delete('prestasi/{id}', [PelatihPrestasiController::class, 'destroy'])->name('pelatih.prestasi.destroy');
        Route::post('prestasi/destroy-selected', [PelatihPrestasiController::class, 'destroy_selected'])->name('pelatih.prestasi.destroy_selected');
        Route::get('prestasi/{id}', [PelatihPrestasiController::class, 'show'])->name('pelatih.prestasi.show');
        Route::get('kesehatan', [PelatihKesehatanController::class, 'getByPelatihId'])->name('pelatih.kesehatan.show');
        Route::post('kesehatan', [PelatihKesehatanController::class, 'store'])->name('pelatih.kesehatan.store');
        Route::put('kesehatan/{id}', [PelatihKesehatanController::class, 'update'])->name('pelatih.kesehatan.update');
        Route::delete('kesehatan/{id}', [PelatihKesehatanController::class, 'destroy'])->name('pelatih.kesehatan.destroy');
        Route::get('dokumen', [PelatihDokumenController::class, 'index'])->name('pelatih.dokumen.index');
        Route::get('dokumen/create', [PelatihDokumenController::class, 'create'])->name('pelatih.dokumen.create');
        Route::post('dokumen', [PelatihDokumenController::class, 'store'])->name('pelatih.dokumen.store');
        Route::get('dokumen/{id}/edit', [PelatihDokumenController::class, 'edit'])->name('pelatih.dokumen.edit');
        Route::put('dokumen/{id}', [PelatihDokumenController::class, 'update'])->name('pelatih.dokumen.update');
        Route::delete('dokumen/{id}', [PelatihDokumenController::class, 'destroy'])->name('pelatih.dokumen.destroy');
        Route::post('dokumen/destroy-selected', [PelatihDokumenController::class, 'destroy_selected'])->name('pelatih.dokumen.destroy_selected');
        Route::get('dokumen/{id}', [PelatihDokumenController::class, 'show'])->name('pelatih.dokumen.show');

        // Riwayat Pemeriksaan
        Route::get('riwayat-pemeriksaan', [PelatihController::class, 'riwayatPemeriksaan'])->name('pelatih.riwayat-pemeriksaan');
        Route::get('riwayat-pemeriksaan/{pemeriksaan_id}/parameter', [PelatihController::class, 'parameterDetail'])->name('pelatih.riwayat-pemeriksaan.parameter');

        // Akun Pelatih
        Route::post('akun', [PelatihController::class, 'storeAkun'])->name('pelatih.akun.store');
        Route::put('akun', [PelatihController::class, 'updateAkun'])->name('pelatih.akun.update');
    });
    // TENAGA PENDUKUNG
    Route::get('/tenaga-pendukung/karakteristik', [TenagaPendukungController::class, 'karakteristik'])->name('tenaga-pendukung.karakteristik');
    Route::post('/tenaga-pendukung/api-karakteristik', [TenagaPendukungController::class, 'apiKarakteristik'])->name('tenaga-pendukung.api-karakteristik');

    Route::resource('/tenaga-pendukung', TenagaPendukungController::class)->names('tenaga-pendukung');
    Route::get('/api/tenaga-pendukung', [TenagaPendukungController::class, 'apiIndex']);
    Route::post('/tenaga-pendukung/destroy-selected', [TenagaPendukungController::class, 'destroy_selected'])->name('tenaga-pendukung.destroy_selected');
    Route::post('/tenaga-pendukung/import', [TenagaPendukungController::class, 'import'])->name('tenaga-pendukung.import');
    Route::prefix('tenaga-pendukung/{tenaga_pendukung_id}')->group(function () {
        Route::get('sertifikat', [TenagaPendukungSertifikatController::class, 'index'])->name('tenaga-pendukung.sertifikat.index');
        Route::get('sertifikat/create', [TenagaPendukungSertifikatController::class, 'create'])->name('tenaga-pendukung.sertifikat.create');
        Route::post('sertifikat', [TenagaPendukungSertifikatController::class, 'store'])->name('tenaga-pendukung.sertifikat.store');
        Route::get('sertifikat/{id}/edit', [TenagaPendukungSertifikatController::class, 'edit'])->name('tenaga-pendukung.sertifikat.edit');
        Route::put('sertifikat/{id}', [TenagaPendukungSertifikatController::class, 'update'])->name('tenaga-pendukung.sertifikat.update');
        Route::delete('sertifikat/{id}', [TenagaPendukungSertifikatController::class, 'destroy'])->name('tenaga-pendukung.sertifikat.destroy');
        Route::post('sertifikat/destroy-selected', [TenagaPendukungSertifikatController::class, 'destroy_selected'])->name('tenaga-pendukung.sertifikat.destroy_selected');
        Route::get('sertifikat/{id}', [TenagaPendukungSertifikatController::class, 'show'])->name('tenaga-pendukung.sertifikat.show');

        // PRESTASI
        Route::get('prestasi', [TenagaPendukungPrestasiController::class, 'index'])->name('tenaga-pendukung.prestasi.index');
        Route::get('prestasi/create', [TenagaPendukungPrestasiController::class, 'create'])->name('tenaga-pendukung.prestasi.create');
        Route::post('prestasi', [TenagaPendukungPrestasiController::class, 'store'])->name('tenaga-pendukung.prestasi.store');
        Route::get('prestasi/{id}/edit', [TenagaPendukungPrestasiController::class, 'edit'])->name('tenaga-pendukung.prestasi.edit');
        Route::put('prestasi/{id}', [TenagaPendukungPrestasiController::class, 'update'])->name('tenaga-pendukung.prestasi.update');
        Route::delete('prestasi/{id}', [TenagaPendukungPrestasiController::class, 'destroy'])->name('tenaga-pendukung.prestasi.destroy');
        Route::post('prestasi/destroy-selected', [TenagaPendukungPrestasiController::class, 'destroy_selected'])->name('tenaga-pendukung.prestasi.destroy_selected');
        Route::get('prestasi/{id}', [TenagaPendukungPrestasiController::class, 'show'])->name('tenaga-pendukung.prestasi.show');

        // KESEHATAN
        Route::get('kesehatan', [TenagaPendukungKesehatanController::class, 'getByTenagaPendukungId'])->name('tenaga-pendukung.kesehatan.show');
        Route::post('kesehatan', [TenagaPendukungKesehatanController::class, 'store'])->name('tenaga-pendukung.kesehatan.store');
        Route::put('kesehatan/{id}', [TenagaPendukungKesehatanController::class, 'update'])->name('tenaga-pendukung.kesehatan.update');
        Route::delete('kesehatan/{id}', [TenagaPendukungKesehatanController::class, 'destroy'])->name('tenaga-pendukung.kesehatan.destroy');
        // DOKUMEN TENAGA PENDUKUNG
        Route::get('dokumen', [TenagaPendukungDokumenController::class, 'index'])->name('tenaga-pendukung.dokumen.index');
        Route::get('dokumen/create', [TenagaPendukungDokumenController::class, 'create'])->name('tenaga-pendukung.dokumen.create');
        Route::post('dokumen', [TenagaPendukungDokumenController::class, 'store'])->name('tenaga-pendukung.dokumen.store');
        Route::get('dokumen/{id}/edit', [TenagaPendukungDokumenController::class, 'edit'])->name('tenaga-pendukung.dokumen.edit');
        Route::put('dokumen/{id}', [TenagaPendukungDokumenController::class, 'update'])->name('tenaga-pendukung.dokumen.update');
        Route::delete('dokumen/{id}', [TenagaPendukungDokumenController::class, 'destroy'])->name('tenaga-pendukung.dokumen.destroy');
        Route::post('dokumen/destroy-selected', [TenagaPendukungDokumenController::class, 'destroy_selected'])->name('tenaga-pendukung.dokumen.destroy_selected');
        Route::get('dokumen/{id}', [TenagaPendukungDokumenController::class, 'show'])->name('tenaga-pendukung.dokumen.show');

        // Riwayat Pemeriksaan
        Route::get('riwayat-pemeriksaan', [TenagaPendukungController::class, 'riwayatPemeriksaan'])->name('tenaga-pendukung.riwayat-pemeriksaan');
        Route::get('riwayat-pemeriksaan/{pemeriksaan_id}/parameter', [TenagaPendukungController::class, 'parameterDetail'])->name('tenaga-pendukung.riwayat-pemeriksaan.parameter');

        // Akun Tenaga Pendukung
        Route::post('akun', [TenagaPendukungController::class, 'storeAkun'])->name('tenaga-pendukung.akun.store');
        Route::put('akun', [TenagaPendukungController::class, 'updateAkun'])->name('tenaga-pendukung.akun.update');
    });
    // Unit Pendukung
    Route::resource('/unit-pendukung', UnitPendukungController::class)->names('unit-pendukung');
    Route::get('/api/unit-pendukung', [UnitPendukungController::class, 'apiIndex']);
    Route::post('/unit-pendukung/destroy-selected', [UnitPendukungController::class, 'destroy_selected'])->name('unit-pendukung.destroy_selected');


    // CABOR
    Route::resource('/cabor', CaborController::class)->names('cabor');
    Route::get('/cabor/{id}/peserta/{tipe}', [CaborController::class, 'getPeserta'])->name('cabor.peserta');
    Route::get('/cabor/{id}/peserta/{tipe}/create', [CaborController::class, 'createMultiplePeserta'])->name('cabor.peserta.create');
    Route::post('/cabor/{id}/peserta/{tipe}/store', [CaborController::class, 'storeMultiplePeserta'])->name('cabor.peserta.store');
    Route::delete('/cabor/{id}/peserta/{tipe}/{pesertaId}', [CaborController::class, 'destroyPeserta'])->name('cabor.peserta.destroy');
    Route::get('/api/cabor', [CaborController::class, 'apiIndex']);
    Route::get('/api/cabor/{cabor_id}/perbandingan-multi-tes', [CaborController::class, 'apiPerbandinganMultiTes'])->name('api.cabor.perbandingan-multi-tes');
    Route::get('/api/cabor/{cabor_id}/ranking', [CaborController::class, 'apiRanking'])->name('api.cabor.ranking');
    Route::post('/cabor/destroy-selected', [CaborController::class, 'destroy_selected'])->name('cabor.destroy_selected');
    // KATEGORI (CaborKategori)
    Route::resource('/cabor-kategori', CaborKategoriController::class)->names('cabor-kategori');
    Route::get('/api/cabor-kategori', [CaborKategoriController::class, 'apiIndex']);
    Route::post('/cabor-kategori/destroy-selected', [CaborKategoriController::class, 'destroy_selected'])->name('cabor-kategori.destroy_selected');
    // API select option
    Route::get('/api/cabor-list', function () {
        try {
            $query = Cabor::select('id', 'nama', 'kategori_peserta_id')
                ->orderBy('nama');
            
            // Filter berdasarkan kategori_peserta_id jika ada (untuk filtering saat tambah peserta ke cabor)
            $kategoriPesertaId = request('kategori_peserta_id');
            if ($kategoriPesertaId && $kategoriPesertaId !== 'all') {
                $query->where('kategori_peserta_id', $kategoriPesertaId);
            }
            
            // Filter berdasarkan cabor yang dimiliki pelatih/tenaga pendukung
            // Kecuali jika dipanggil dari form pelatih/tenaga pendukung (parameter for_peserta_form)
            $forPesertaForm = request('for_peserta_form', false);
            if (!$forPesertaForm) {
                $auth = Auth::user();
                if ($auth && (int) $auth->current_role_id === 36) {
                    // Pelatih hanya melihat cabor yang mereka miliki
                    if ($auth->pelatih && $auth->pelatih->id) {
                        $caborIds = DB::table('cabor_kategori_pelatih')
                            ->where('pelatih_id', $auth->pelatih->id)
                            ->whereNull('deleted_at')
                            ->pluck('cabor_id')
                            ->unique()
                            ->toArray();
                        
                        if (!empty($caborIds)) {
                            $query->whereIn('id', $caborIds);
                        } else {
                            // Jika pelatih tidak punya cabor, return empty
                            return response()->json([]);
                        }
                    }
                } elseif ($auth && (int) $auth->current_role_id === 37) {
                    // Tenaga Pendukung hanya melihat cabor yang mereka miliki
                    if ($auth->tenagaPendukung && $auth->tenagaPendukung->id) {
                        $caborIds = DB::table('cabor_kategori_tenaga_pendukung')
                            ->where('tenaga_pendukung_id', $auth->tenagaPendukung->id)
                            ->whereNull('deleted_at')
                            ->pluck('cabor_id')
                            ->unique()
                            ->toArray();
                        
                        if (!empty($caborIds)) {
                            $query->whereIn('id', $caborIds);
                        } else {
                            // Jika tenaga pendukung tidak punya cabor, return empty
                            return response()->json([]);
                        }
                    }
                }
            }
            
            $data = $query->get();
            // Pastikan selalu return array dengan kategori_peserta_id
            return response()->json($data->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kategori_peserta_id' => $item->kategori_peserta_id,
                ];
            })->toArray());
        } catch (\Exception $e) {
            \Log::error('Error in /api/cabor-list: ' . $e->getMessage());
            return response()->json([]);
        }
    });
    Route::get('/api/cabor/{id}', function ($id) {
        $cabor = Cabor::select('id', 'nama', 'kategori_peserta_id')->find($id);
        return response()->json($cabor);
    });
    Route::get('/api/cabor-kategori-list', [CaborKategoriController::class, 'list']);
    Route::get('/api/cabor-kategori-by-cabor/{cabor_id}', [CaborKategoriController::class, 'listByCabor']);

    // CABOR KATEGORI ATLET
    Route::resource('/cabor-kategori-atlet', CaborKategoriAtletController::class)->names('cabor-kategori-atlet');
    Route::get('/api/cabor-kategori-atlet', [CaborKategoriAtletController::class, 'apiIndex']);
    Route::post('/cabor-kategori-atlet/destroy-selected', [CaborKategoriAtletController::class, 'destroy_selected'])->name('cabor-kategori-atlet.destroy_selected');

    // Routes untuk daftar atlet per kategori
    Route::get('/cabor-kategori/{cabor_kategori_id}/atlet', [CaborKategoriAtletController::class, 'atletByKategori'])->name('cabor-kategori-atlet.atlet-by-kategori');
    Route::get('/cabor-kategori/{cabor_kategori_id}/atlet/create-multiple', [CaborKategoriAtletController::class, 'createMultiple'])->name('cabor-kategori-atlet.create-multiple');
    Route::post('/cabor-kategori/{cabor_kategori_id}/atlet/store-multiple', [CaborKategoriAtletController::class, 'storeMultiple'])->name('cabor-kategori-atlet.store-multiple');

    // CABOR KATEGORI PELATIH
    Route::resource('/cabor-kategori-pelatih', CaborKategoriPelatihController::class)->names('cabor-kategori-pelatih');
    Route::get('/api/cabor-kategori-pelatih', [CaborKategoriPelatihController::class, 'apiIndex']);
    Route::post('/cabor-kategori-pelatih/destroy-selected', [CaborKategoriPelatihController::class, 'destroy_selected'])->name('cabor-kategori-pelatih.destroy_selected');

    // Routes untuk daftar pelatih per kategori
    Route::get('/cabor-kategori/{cabor_kategori_id}/pelatih', [CaborKategoriPelatihController::class, 'pelatihByKategori'])->name('cabor-kategori-pelatih.pelatih-by-kategori');
    Route::get('/cabor-kategori/{cabor_kategori_id}/pelatih/create-multiple', [CaborKategoriPelatihController::class, 'createMultiple'])->name('cabor-kategori-pelatih.create-multiple');
    Route::post('/cabor-kategori/{cabor_kategori_id}/pelatih/store-multiple', [CaborKategoriPelatihController::class, 'storeMultiple'])->name('cabor-kategori-pelatih.store-multiple');

    // CABOR KATEGORI TENAGA PENDUKUNG
    Route::resource('/cabor-kategori-tenaga-pendukung', CaborKategoriTenagaPendukungController::class)->names('cabor-kategori-tenaga-pendukung');
    Route::get('/api/cabor-kategori-tenaga-pendukung', [CaborKategoriTenagaPendukungController::class, 'apiIndex']);
    Route::post('/cabor-kategori-tenaga-pendukung/destroy-selected', [CaborKategoriTenagaPendukungController::class, 'destroy_selected'])->name('cabor-kategori-tenaga-pendukung.destroy_selected');

    // Routes untuk daftar tenaga pendukung per kategori
    Route::get('/cabor-kategori/{cabor_kategori_id}/tenaga-pendukung', [CaborKategoriTenagaPendukungController::class, 'tenagaPendukungByKategori'])->name('cabor-kategori-tenaga-pendukung.tenaga-by-kategori');
    Route::get('/cabor-kategori/{cabor_kategori_id}/tenaga-pendukung/create-multiple', [CaborKategoriTenagaPendukungController::class, 'createMultiple'])->name('cabor-kategori-tenaga-pendukung.create-multiple');
    Route::post('/cabor-kategori/{cabor_kategori_id}/tenaga-pendukung/store-multiple', [CaborKategoriTenagaPendukungController::class, 'storeMultiple'])->name('cabor-kategori-tenaga-pendukung.store-multiple');
});

// API untuk sertifikat, prestasi, dokumen per atlet/pelatih
Route::get('/api/atlet/{atlet_id}/sertifikat', [AtletSertifikatController::class, 'apiIndex']);
Route::get('/api/atlet/{atlet_id}/prestasi', [AtletPrestasiController::class, 'apiIndex']);
Route::get('/api/atlet/{atlet_id}/dokumen', [AtletDokumenController::class, 'apiIndex']);
Route::get('/api/atlet/{atlet_id}/riwayat-pemeriksaan', [AtletController::class, 'apiRiwayatPemeriksaan']);
Route::get('/api/atlet/{id}/parameter-umum', [AtletController::class, 'apiParameterUmum']);
Route::get('/api/atlet/{id}/rekap-parameter-khusus', [AtletController::class, 'apiRekapParameterKhusus']);
Route::post('/api/atlet/{id}/parameter-umum', [AtletController::class, 'apiUpdateParameterUmum']);
Route::get('/api/pelatih/{pelatih_id}/sertifikat', [PelatihSertifikatController::class, 'apiIndex']);
Route::get('/api/pelatih/{pelatih_id}/prestasi', [PelatihPrestasiController::class, 'apiIndex']);
Route::get('/api/pelatih/{pelatih_id}/dokumen', [PelatihDokumenController::class, 'apiIndex']);
Route::get('/api/pelatih/{pelatih_id}/riwayat-pemeriksaan', [PelatihController::class, 'apiRiwayatPemeriksaan']);

// API endpoint untuk sertifikat tenaga pendukung
Route::get('/api/tenaga-pendukung/{tenaga_pendukung_id}/sertifikat', [TenagaPendukungSertifikatController::class, 'apiIndex']);

// API endpoint untuk prestasi tenaga pendukung
Route::get('/api/tenaga-pendukung/{tenaga_pendukung_id}/prestasi', [TenagaPendukungPrestasiController::class, 'apiIndex']);

// API endpoint untuk prestasi tenaga pendukung
Route::get('/api/tenaga-pendukung/{tenaga_pendukung_id}/dokumen', [TenagaPendukungDokumenController::class, 'apiIndex']);
Route::get('/api/tenaga-pendukung/{tenaga_pendukung_id}/riwayat-pemeriksaan', [TenagaPendukungController::class, 'apiRiwayatPemeriksaan']);

// =====================
// PROGRAM LATIHAN
// =====================
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    // Rekap Absen Program Latihan (harus SEBELUM resource route)
    Route::prefix('program-latihan/{program_id}/rekap-absen')->group(function () {
        Route::get('/', [RekapAbsenProgramLatihanController::class, 'index'])->name('program-latihan.rekap-absen.index');
        Route::post('/', [RekapAbsenProgramLatihanController::class, 'store'])->name('program-latihan.rekap-absen.store');
        Route::put('/{rekap_id}', [RekapAbsenProgramLatihanController::class, 'update'])->name('program-latihan.rekap-absen.update');
    });
    
    // Route delete media di luar prefix group untuk menghindari conflict dengan resource route
    Route::post('/program-latihan/{program_id}/rekap-absen/{rekap_id}/media/{media_id}/delete', [RekapAbsenProgramLatihanController::class, 'deleteMedia'])
        ->name('program-latihan.rekap-absen.delete-media');
    
    Route::resource('/program-latihan', ProgramLatihanController::class)->names('program-latihan');
    Route::get('/api/program-latihan', [ProgramLatihanController::class, 'apiIndex']);
    Route::post('/program-latihan/destroy-selected', [ProgramLatihanController::class, 'destroy_selected'])->name('program-latihan.destroy_selected');
});

// =====================
// DATA MASTER (CRUD)
// =====================
Route::middleware(['auth', 'verified', 'check.registration.status'])->prefix('data-master')->group(function () {
    // Master Tingkat
    Route::resource('/tingkat', MstTingkatController::class)->names('tingkat');
    Route::post('/tingkat/destroy-selected', [MstTingkatController::class, 'destroy_selected'])->name('tingkat.destroy_selected');
    // Master Kategori Peserta (menggantikan Kategori Atlet)
    Route::resource('/kategori-peserta', MstKategoriPesertaController::class)->names('kategori-peserta');
    Route::post('/kategori-peserta/destroy-selected', [MstKategoriPesertaController::class, 'destroy_selected'])->name('kategori-peserta.destroy_selected');
    // Master Kategori Atlet (backward compatibility - deprecated)
    Route::resource('/kategori-atlet', MstKategoriAtletController::class)->names('kategori-atlet');
    Route::post('/kategori-atlet/destroy-selected', [MstKategoriAtletController::class, 'destroy_selected'])->name('kategori-atlet.destroy_selected');
    // Master Kategori Prestasi Pelatih
    Route::resource('/kategori-prestasi-pelatih', MstKategoriPrestasiPelatihController::class)->names('kategori-prestasi-pelatih');
    Route::post('/kategori-prestasi-pelatih/destroy-selected', [MstKategoriPrestasiPelatihController::class, 'destroy_selected'])->name('kategori-prestasi-pelatih.destroy_selected');
    // Master Jenis Dokumen
    Route::resource('/jenis-dokumen', MstJenisDokumenController::class)->names('jenis-dokumen');
    Route::post('/jenis-dokumen/destroy-selected', [MstJenisDokumenController::class, 'destroy_selected'])->name('jenis-dokumen.destroy_selected');
    // Master Kecamatan (hanya index & show)
    Route::get('/kecamatan', [KecamatanController::class, 'index'])->name('kecamatan.index');
    Route::get('/kecamatan/{id}', [KecamatanController::class, 'show'])->name('kecamatan.show');
    // Master Desa (hanya index & show)
    Route::get('/desa', [DesaController::class, 'index'])->name('desa.index');
    Route::get('/desa/{id}', [DesaController::class, 'show'])->name('desa.show');
    // Master Posisi Atlet
    Route::resource('/posisi-atlet', MstPosisiAtletController::class)->names('posisi-atlet');
    Route::post('/posisi-atlet/destroy-selected', [MstPosisiAtletController::class, 'destroy_selected'])->name('posisi-atlet.destroy_selected');
    // Master Jenis Pelatih
    Route::resource('/jenis-pelatih', MstJenisPelatihController::class)->names('jenis-pelatih');
    Route::post('/jenis-pelatih/destroy-selected', [MstJenisPelatihController::class, 'destroy_selected'])->name('jenis-pelatih.destroy_selected');
    // Master Jenis Tenaga Pendukung
    Route::resource('/jenis-tenaga-pendukung', MstJenisTenagaPendukungController::class)->names('jenis-tenaga-pendukung');
    Route::post('/jenis-tenaga-pendukung/destroy-selected', [MstJenisTenagaPendukungController::class, 'destroy_selected'])->name('jenis-tenaga-pendukung.destroy_selected');
    // Master Jenis Unit Pendukung
    Route::resource('/jenis-unit-pendukung', MstJenisUnitPendukungController::class)->names('jenis-unit-pendukung');
    Route::post('/jenis-unit-pendukung/destroy-selected', [MstJenisUnitPendukungController::class, 'destroy_selected'])->name('jenis-unit-pendukung.destroy_selected');
    // Master Juara
    Route::resource('/juara', MstJuaraController::class)->names('juara');
    Route::post('/juara/destroy-selected', [MstJuaraController::class, 'destroy_selected'])->name('juara.destroy_selected');
    // Master Parameter
    Route::resource('/parameter', MstParameterController::class)->names('parameter');
    Route::post('/parameter/destroy-selected', [MstParameterController::class, 'destroy_selected'])->name('parameter.destroy_selected');

});

// =====================
// PEMERIKSAAN
// =====================
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    Route::resource('/pemeriksaan', PemeriksaanController::class)->names('pemeriksaan');
    Route::get('/api/pemeriksaan', [PemeriksaanController::class, 'apiIndex']);
    Route::post('/pemeriksaan/destroy-selected', [PemeriksaanController::class, 'destroy_selected'])->name('pemeriksaan.destroy_selected');

    // Nested Pemeriksaan Parameter
    Route::prefix('pemeriksaan/{pemeriksaan}')->group(function () {
        Route::get('pemeriksaan-parameter', [PemeriksaanParameterController::class, 'index'])->name('pemeriksaan.parameter.index');
        Route::get('pemeriksaan-parameter/create', [PemeriksaanParameterController::class, 'create'])->name('pemeriksaan.parameter.create');
        Route::post('pemeriksaan-parameter', [PemeriksaanParameterController::class, 'store'])->name('pemeriksaan.parameter.store');
        Route::get('pemeriksaan-parameter/{id}', [PemeriksaanParameterController::class, 'show'])->name('pemeriksaan.parameter.show');
        Route::get('pemeriksaan-parameter/{id}/edit', [PemeriksaanParameterController::class, 'edit'])->name('pemeriksaan.parameter.edit');
        Route::put('pemeriksaan-parameter/{id}', [PemeriksaanParameterController::class, 'update'])->name('pemeriksaan.parameter.update');
        Route::delete('pemeriksaan-parameter/{id}', [PemeriksaanParameterController::class, 'destroy'])->name('pemeriksaan.parameter.destroy');
        Route::post('pemeriksaan-parameter/destroy-selected', [PemeriksaanParameterController::class, 'destroy_selected'])->name('pemeriksaan.parameter.destroy_selected');
    });

    // All Parameter Routes
    Route::prefix('pemeriksaan-parameter')->group(function () {
        Route::get('AllParameter', [AllParameterController::class, 'index'])->name('all.parameter.index');
        Route::get('AllParameter/{parameter_id}/statistik', [AllParameterController::class, 'show'])->name('all.parameter.statistik');
    });

    // Nested Pemeriksaan Peserta - Web Routes
    Route::prefix('pemeriksaan/{pemeriksaan}/peserta')->group(function () {
        Route::get('/', [PemeriksaanPesertaController::class, 'index'])->name('pemeriksaan.peserta.index');
        Route::get('/create', [PemeriksaanPesertaController::class, 'create'])->name('pemeriksaan.peserta.create');
        Route::post('/', [PemeriksaanPesertaController::class, 'store'])->name('pemeriksaan.peserta.store');
        Route::get('/{id}', [PemeriksaanPesertaController::class, 'show'])->name('pemeriksaan.peserta.show');
        Route::get('/{id}/edit', [PemeriksaanPesertaController::class, 'edit'])->name('pemeriksaan.peserta.edit');
        Route::put('/{id}', [PemeriksaanPesertaController::class, 'update'])->name('pemeriksaan.peserta.update');
        Route::delete('/{id}', [PemeriksaanPesertaController::class, 'destroy'])->name('pemeriksaan.peserta.destroy');
        Route::post('/destroy-selected', [PemeriksaanPesertaController::class, 'destroy_selected'])->name('pemeriksaan.peserta.destroy_selected');
    });

    // Nested Pemeriksaan Peserta Parameter - Web Routes
    Route::prefix('pemeriksaan/{pemeriksaan}/peserta/{peserta}/parameter')->group(function () {
        Route::get('/', [PemeriksaanPesertaParameterController::class, 'index'])->name('pemeriksaan.peserta.parameter.index');
        Route::get('/create', [PemeriksaanPesertaParameterController::class, 'create'])->name('pemeriksaan.peserta.parameter.create');
        Route::post('/', [PemeriksaanPesertaParameterController::class, 'store'])->name('pemeriksaan.peserta.parameter.store');
        Route::get('/{id}', [PemeriksaanPesertaParameterController::class, 'show'])->name('pemeriksaan.peserta.parameter.show');
        Route::get('/{id}/edit', [PemeriksaanPesertaParameterController::class, 'edit'])->name('pemeriksaan.peserta.parameter.edit');
        Route::put('/{id}', [PemeriksaanPesertaParameterController::class, 'update'])->name('pemeriksaan.peserta.parameter.update');
        Route::delete('/{id}', [PemeriksaanPesertaParameterController::class, 'destroy'])->name('pemeriksaan.peserta.parameter.destroy');
        Route::post('/destroy-selected', [PemeriksaanPesertaParameterController::class, 'destroy_selected'])->name('pemeriksaan.peserta.parameter.destroy_selected');
    });

    // Tambahkan setelah nested pemeriksaan peserta parameter
    Route::get('/pemeriksaan/{pemeriksaan}/peserta-parameter/kelola', [PemeriksaanPesertaParameterController::class, 'massEdit'])->name('pemeriksaan.peserta.parameter.mass-edit');
    Route::post('/pemeriksaan/{pemeriksaan}/peserta-parameter/bulk-update', [PemeriksaanPesertaParameterController::class, 'bulkUpdate'])->name('pemeriksaan.peserta.parameter.bulk-update');
});

Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    // API nested
    Route::get('/api/pemeriksaan/{pemeriksaan}/pemeriksaan-parameter', [PemeriksaanParameterController::class, 'apiIndex']);
    // API untuk All Parameter
    Route::get('/api/pemeriksaan-parameter/AllParameter', [AllParameterController::class, 'apiIndex']);
    Route::get('/api/pemeriksaan-parameter/AllParameter/{parameter_id}/statistik', [AllParameterController::class, 'apiStatistik']);
});

// API untuk pemeriksaan peserta parameter
Route::get('/api/pemeriksaan/{pemeriksaan}/peserta/{peserta}/parameter', [PemeriksaanPesertaParameterController::class, 'apiIndex']);

// API untuk Pemeriksaan Peserta
Route::get('/api/pemeriksaan/{pemeriksaan}/peserta/{jenis_peserta?}', [PemeriksaanPesertaController::class, 'apiIndex'])->name('api.pemeriksaan.peserta.index');
Route::put('/pemeriksaan/{pemeriksaan}/peserta/{peserta}', [PemeriksaanPesertaController::class, 'update'])->name('pemeriksaan.peserta.update');
Route::delete('/pemeriksaan/{pemeriksaan}/peserta/{peserta}', [PemeriksaanPesertaController::class, 'destroy'])->name('pemeriksaan.peserta.destroy');
Route::post('/pemeriksaan/{pemeriksaan}/peserta/{peserta}', [PemeriksaanPesertaController::class, 'update']);

// API untuk detail atlet, pelatih, dan tenaga pendukung
Route::get('/api/atlet/{id}', [AtletController::class, 'apiShow']);
Route::get('/api/pelatih/{id}', [PelatihController::class, 'apiShow']);
Route::get('/api/tenaga-pendukung/{id}', [TenagaPendukungController::class, 'apiShow']);

// Endpoint API khusus untuk pemeriksaan peserta
Route::get('/api/cabor-kategori-atlet/available-for-pemeriksaan', [CaborKategoriAtletController::class, 'apiAvailableForPemeriksaan']);
Route::get('/api/cabor-kategori-pelatih/available-for-pemeriksaan', [CaborKategoriPelatihController::class, 'apiAvailableForPemeriksaan']);
Route::get('/api/cabor-kategori-tenaga-pendukung/available-for-pemeriksaan', [CaborKategoriTenagaPendukungController::class, 'apiAvailableForPemeriksaan']);

// API untuk get semua peserta (Atlet, Pelatih, Tenaga Pendukung) by cabor kategori
Route::get('/api/peserta-by-cabor-kategori/{cabor_kategori_id}', [PemeriksaanController::class, 'apiPesertaByCaborKategori']);

// Route untuk ref status pemeriksaan (untuk MassEdit)
Route::get('/api/ref-status-pemeriksaan', [RefStatusPemeriksaanController::class, 'index']);
// Route untuk bulk update parameter peserta (untuk MassEdit)
Route::post('/pemeriksaan/{pemeriksaan}/peserta-parameter/bulk-update', [PemeriksaanPesertaParameterController::class, 'bulkUpdate']);

// =====================
// PEMERIKSAAN KHUSUS
// =====================
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    Route::resource('/pemeriksaan-khusus', PemeriksaanKhususController::class)->names('pemeriksaan-khusus');
    Route::get('/pemeriksaan-khusus/{id}/setup', [PemeriksaanKhususController::class, 'setup'])->name('pemeriksaan-khusus.setup');
    Route::get('/pemeriksaan-khusus/{id}/input-hasil-tes', [PemeriksaanKhususController::class, 'inputHasilTes'])->name('pemeriksaan-khusus.input-hasil-tes');
    Route::get('/api/pemeriksaan-khusus', [PemeriksaanKhususController::class, 'apiIndex']);
    Route::post('/pemeriksaan-khusus/destroy-selected', [PemeriksaanKhususController::class, 'destroy_selected'])->name('pemeriksaan-khusus.destroy_selected');
});

// API untuk get semua peserta (Atlet, Pelatih, Tenaga Pendukung) by cabor kategori untuk pemeriksaan khusus
Route::get('/api/pemeriksaan-khusus/peserta-by-cabor-kategori/{cabor_kategori_id}', [PemeriksaanKhususController::class, 'apiPesertaByCaborKategori']);

// API untuk template dan aspek-item tes
Route::get('/api/pemeriksaan-khusus/check-template/{cabor_id}', [PemeriksaanKhususController::class, 'apiCheckTemplate'])->name('api.pemeriksaan-khusus.check-template');
Route::get('/api/pemeriksaan-khusus/{pemeriksaan_khusus_id}/aspek-item-tes', [PemeriksaanKhususController::class, 'apiGetAspekItemTes'])->name('api.pemeriksaan-khusus.aspek-item-tes');
Route::post('/api/pemeriksaan-khusus/clone-from-template', [PemeriksaanKhususController::class, 'apiCloneFromTemplate'])->name('api.pemeriksaan-khusus.clone-template');
Route::post('/api/pemeriksaan-khusus/save-aspek-item-tes', [PemeriksaanKhususController::class, 'apiSaveAspekItemTes'])->name('api.pemeriksaan-khusus.save-aspek-item-tes');
Route::post('/api/pemeriksaan-khusus/save-as-template', [PemeriksaanKhususController::class, 'apiSaveAsTemplate'])->name('api.pemeriksaan-khusus.save-as-template');
Route::get('/api/pemeriksaan-khusus/{id}/peserta', [PemeriksaanKhususController::class, 'apiGetPeserta'])->name('api.pemeriksaan-khusus.get-peserta');
Route::get('/api/pemeriksaan-khusus/{id}/peserta/available', [PemeriksaanKhususController::class, 'apiAvailablePeserta'])->name('api.pemeriksaan-khusus.peserta.available');
Route::post('/pemeriksaan-khusus/{id}/peserta', [PemeriksaanKhususController::class, 'storePeserta'])->name('pemeriksaan-khusus.peserta.store');
Route::delete('/pemeriksaan-khusus/{id}/peserta/{pesertaId}', [PemeriksaanKhususController::class, 'destroyPeserta'])->name('pemeriksaan-khusus.peserta.destroy');
Route::get('/api/pemeriksaan-khusus/{id}/hasil-tes', [PemeriksaanKhususController::class, 'apiGetHasilTes'])->name('api.pemeriksaan-khusus.get-hasil-tes');
Route::post('/api/pemeriksaan-khusus/save-hasil-tes', [PemeriksaanKhususController::class, 'apiSaveHasilTes'])->name('api.pemeriksaan-khusus.save-hasil-tes');
Route::get('/api/pemeriksaan-khusus/{id}/visualisasi', [PemeriksaanKhususController::class, 'apiGetVisualisasi'])->name('api.pemeriksaan-khusus.get-visualisasi');


// TURNAMEN
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    Route::resource('/turnamen', TurnamenController::class)->names('turnamen');
    Route::get('/api/turnamen', [TurnamenController::class, 'apiIndex']);
    Route::post('/turnamen/destroy-selected', [TurnamenController::class, 'destroy_selected'])->name('turnamen.destroy_selected');
    
    // API untuk peserta turnamen (menggunakan prefix /web-api/ untuk menghindari konflik dengan routes/api.php)
    Route::get('/web-api/turnamen/peserta-by-cabor-kategori', [TurnamenController::class, 'apiPesertaByCaborKategori']);
    Route::get('/web-api/turnamen/{turnamen_id}/peserta', [TurnamenController::class, 'apiPesertaTurnamen']);
    Route::post('/web-api/turnamen/{turnamen_id}/peserta', [TurnamenController::class, 'storePeserta']);
    Route::delete('/web-api/turnamen/{turnamen_id}/peserta/{jenis_peserta}/{peserta_id}', [TurnamenController::class, 'destroyPeserta']);
    Route::post('/web-api/turnamen/{turnamen_id}/peserta/{jenis_peserta}/destroy-selected', [TurnamenController::class, 'destroySelectedPeserta']);
    
    // Halaman peserta turnamen
    Route::get('/turnamen/{turnamen_id}/peserta', [TurnamenController::class, 'pesertaIndex'])->name('turnamen.peserta.index');
});

// EVENT
Route::middleware(['auth', 'verified', 'check.registration.status'])->group(function () {
    Route::resource('/event', EventController::class)->names('event');
    Route::get('/api/event', [EventController::class, 'apiIndex']);
    Route::post('/event/destroy-selected', [EventController::class, 'destroy_selected'])->name('event.destroy_selected');
});

// =====================
// DEVELOPMENT/UTILITY ROUTES
// =====================
// Route untuk migrate fresh dengan seed (HANYA UNTUK DEVELOPMENT!)
Route::get('/dev/migrate-fresh-seed', function () {
    // Hanya bisa diakses di local/development environment
    if (app()->environment('production')) {
        abort(403, 'Route ini tidak tersedia di production environment.');
    }

    try {
        // Jalankan migrate:fresh --seed
        Artisan::call('migrate:fresh', ['--seed' => true]);
        
        $output = Artisan::output();
        
        return response()->json([
            'success' => true,
            'message' => 'Migrate fresh dengan seed berhasil dijalankan.',
            'output' => $output,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat menjalankan migrate fresh seed.',
            'error' => $e->getMessage(),
        ], 500);
    }
})->name('dev.migrate-fresh-seed');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

