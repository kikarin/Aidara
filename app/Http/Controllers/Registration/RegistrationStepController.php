<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\RegistrationStep1Request;
use App\Models\PesertaRegistration;
use App\Models\Role;
use App\Repositories\RegistrationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationStepController extends Controller
{
    protected $repository;

    public function __construct(RegistrationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Show registration steps page
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('register');
        }

        // Jika user sudah punya peserta_id dan peserta_type, redirect ke edit page mereka
        if ($user->peserta_id && $user->peserta_type) {
            $editRoute = match($user->peserta_type) {
                'atlet' => 'atlet.edit',
                'pelatih' => 'pelatih.edit',
                'tenaga_pendukung' => 'tenaga-pendukung.edit',
                default => null
            };
            
            if ($editRoute) {
                return redirect()->route($editRoute, $user->peserta_id);
            }
        }

        // Check if user is in registration process (pending atau belum approved)
        // User yang baru register akan memiliki registration_status = 'pending' atau null
        // User yang sudah submit akan memiliki registration dengan status != 'approved'
        $hasPendingRegistration = PesertaRegistration::where('user_id', $user->id)
            ->where('status', '!=', 'approved')
            ->exists();

        // Jika user sudah approved atau tidak ada registration pending, redirect ke register
        if (!$hasPendingRegistration && $user->registration_status !== 'pending' && $user->is_active === 1) {
            // User sudah approved dan aktif, redirect ke dashboard
            return redirect()->route('dashboard');
        }

        // Jika user belum punya registration_status = 'pending', set it
        if ($user->registration_status !== 'pending' && !$hasPendingRegistration) {
            $user->update(['registration_status' => 'pending']);
        }

        $step = (int) $request->get('step', 1);
        $step = max(1, min(6, $step)); // Validasi step 1-6 (6 = preview)

        // Get atau create registration
        $registration = PesertaRegistration::where('user_id', $user->id)
            ->where('status', '!=', 'approved')
            ->first();

        if (!$registration && $step > 1) {
            // Jika belum ada registration dan bukan step 1, redirect ke step 1
            return redirect()->route('registration.steps', ['step' => 1]);
        }

        $data = [
            'step'             => $step,
            'registration'     => $registration,
            'registrationData' => $registration?->data_json ?? [],
        ];

        // Load data tambahan berdasarkan step
        if ($step === 2 && $registration) {
            // Load master data untuk form (kecamatan, kelurahan, kategori peserta, dll)
            $data['kecamatanOptions']       = \App\Models\MstKecamatan::select('id', 'nama')->orderBy('nama')->get();
            $data['kategoriPesertaOptions'] = \App\Models\MstKategoriPeserta::select('id', 'nama')->orderBy('nama')->get();

            if ($registration->peserta_type === 'atlet') {
                // Load parameter umum master untuk atlet
                $data['parameterUmumMaster'] = \App\Models\MstParameter::where('kategori', 'umum')->get();
            }
        }

        return Inertia::render('registration/Steps/Index', $data);
    }

    /**
     * Handle Step 1: Pilih Jenis Peserta
     * Assign role langsung dan create draft peserta, redirect ke edit page
     */
    public function storeStep1(RegistrationStep1Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('register');
        }

        try {
            $validated = $request->validated();
            $pesertaType = $validated['peserta_type'] ?? null;

            if (!$pesertaType) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['peserta_type' => 'Jenis peserta wajib dipilih.']);
            }
            
            // Assign role berdasarkan peserta type
            $roleId = match($pesertaType) {
                'atlet' => 35,
                'pelatih' => 36,
                'tenaga_pendukung' => 37,
                default => null
            };
            
            if (!$roleId) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['peserta_type' => 'Jenis peserta tidak valid.']);
            }
            
            // Update user dengan role dan peserta_type
            $user->update([
                'current_role_id' => $roleId,
                'peserta_type' => $pesertaType,
                'registration_status' => 'pending',
                'is_active' => 1, // Bisa login tapi stuck di edit page
            ]);
            
            // Assign role menggunakan Spatie Permission
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $user->assignRole($role);
                }
            }
            
            // Create peserta record kosong (draft) dengan email auto-fill
            $pesertaId = $this->repository->createDraftPeserta($user, $pesertaType);
            
            // Update user dengan peserta_id
            $user->update(['peserta_id' => $pesertaId]);
            
            // Redirect ke edit page sesuai peserta type
            $editRoute = match($pesertaType) {
                'atlet' => 'atlet.edit',
                'pelatih' => 'pelatih.edit',
                'tenaga_pendukung' => 'tenaga-pendukung.edit',
                default => 'dashboard'
            };
            
            Log::info('RegistrationStepController: Step 1 completed, redirecting to edit', [
                'user_id' => $user->id,
                'peserta_type' => $pesertaType,
                'peserta_id' => $pesertaId,
            ]);
            
            return redirect()->route($editRoute, $pesertaId)
                ->with('success', 'Silakan lengkapi data diri Anda. Data akan ditinjau oleh administrator.');
            
        } catch (\Exception $e) {
            Log::error('RegistrationStepController: Error in step 1', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'peserta_type' => $pesertaType ?? null,
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['peserta_type' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit final registration (dari preview)
     */
    public function submit(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('register');
        }

        try {
            $registration = PesertaRegistration::where('user_id', $user->id)
                ->where('status', '!=', 'approved')
                ->firstOrFail();

            // Validate semua step wajib sudah diisi
            $data = $registration->data_json ?? [];
            if (!isset($data['step_1']) || !isset($data['step_2'])) {
                return redirect()->back()
                    ->withErrors(['error' => 'Data diri wajib diisi. Silakan lengkapi step 1 dan 2.']);
            }

            $this->repository->submitRegistration($registration);

            // Logout user karena belum bisa login sampai approved
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('RegistrationStepController: Registration submitted', [
                'user_id'         => $user->id,
                'registration_id' => $registration->id,
            ]);

            return redirect()->route('registration.success')
                ->with('success', 'Pengajuan registrasi berhasil dikirim. Menunggu persetujuan admin.');
        } catch (\Exception $e) {
            Log::error('RegistrationStepController: Error submitting registration', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat mengirim pengajuan. Silakan coba lagi.']);
        }
    }

    /**
     * Save draft (auto-save dari frontend)
     */
    public function saveDraft(Request $request, int $step)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $registration = PesertaRegistration::where('user_id', $user->id)
                ->where('status', '!=', 'approved')
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Registration not found'], 404);
            }

            $data = $request->except(['_token', 'step']);
            $this->repository->saveStepData($registration, $step, $data);

            return response()->json([
                'success' => true,
                'message' => 'Draft berhasil disimpan',
            ]);
        } catch (\Exception $e) {
            Log::error('RegistrationStepController: Error saving draft', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan draft',
            ], 500);
        }
    }
}
