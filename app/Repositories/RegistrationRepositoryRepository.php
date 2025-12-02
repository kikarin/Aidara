<?php

namespace App\Repositories;

use App\Models\PesertaRegistration;
use App\Models\User;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegistrationRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PesertaRegistration $model)
    {
        $this->model = $model;
        $this->with  = ['user'];
    }

    /**
     * Create user untuk registrasi awal (Step 0)
     */
    public function createRegistrationUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'                => $data['name'] ?? $data['email'],
                'email'               => $data['email'],
                'password'            => Hash::make($data['password']),
                'is_active'           => 0, // Belum bisa login sampai approved
                'registration_status' => 'pending',
                'current_role_id'     => null,
            ]);

            Log::info('RegistrationRepository: User created for registration', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return $user;
        });
    }

    /**
     * Get atau create PesertaRegistration untuk user
     */
    public function getOrCreateRegistration(User $user, string $pesertaType): PesertaRegistration
    {
        $registration = PesertaRegistration::where('user_id', $user->id)
            ->where('status', '!=', 'approved')
            ->first();

        if (!$registration) {
            $registration = PesertaRegistration::create([
                'user_id'      => $user->id,
                'peserta_type' => $pesertaType,
                'step_current' => 1,
                'status'       => 'draft',
                'data_json'    => [],
            ]);
        }

        return $registration;
    }

    /**
     * Save step data
     */
    public function saveStepData(PesertaRegistration $registration, int $step, array $data): PesertaRegistration
    {
        $currentData                 = $registration->data_json ?? [];
        $currentData["step_{$step}"] = $data;

        $registration->update([
            'step_current' => $step,
            'data_json'    => $currentData,
        ]);

        return $registration->fresh();
    }

    /**
     * Submit registration (final step)
     */
    public function submitRegistration(PesertaRegistration $registration): PesertaRegistration
    {
        return DB::transaction(function () use ($registration) {
            $registration->update([
                'status' => 'submitted',
            ]);

            // Update user registration status
            $registration->user->update([
                'registration_status' => 'pending',
            ]);

            Log::info('RegistrationRepository: Registration submitted', [
                'registration_id' => $registration->id,
                'user_id'         => $registration->user_id,
            ]);

            return $registration->fresh();
        });
    }

    /**
     * Approve registration - create peserta record
     */
    public function approveRegistration(PesertaRegistration $registration, int $approvedBy): array
    {
        return DB::transaction(function () use ($registration, $approvedBy) {
            $data        = $registration->data_json;
            $pesertaType = $registration->peserta_type;
            $user        = $registration->user;

            // Create peserta record berdasarkan type
            $pesertaId = null;
            $roleId    = null;

            switch ($pesertaType) {
                case 'atlet':
                    $pesertaId = $this->createAtlet($data, $user->id, $approvedBy);
                    $roleId    = 35; // Role ID Atlet
                    break;
                case 'pelatih':
                    $pesertaId = $this->createPelatih($data, $user->id, $approvedBy);
                    $roleId    = 36; // Role ID Pelatih
                    break;
                case 'tenaga_pendukung':
                    $pesertaId = $this->createTenagaPendukung($data, $user->id, $approvedBy);
                    $roleId    = 37; // Role ID Tenaga Pendukung
                    break;
            }

            // Update user
            $user->update([
                'registration_status' => 'approved',
                'is_active'           => 1,
                'current_role_id'     => $roleId,
                'peserta_type'        => $pesertaType,
                'peserta_id'          => $pesertaId,
            ]);

            // Update registration
            $registration->update([
                'status' => 'approved',
            ]);

            Log::info('RegistrationRepository: Registration approved', [
                'registration_id' => $registration->id,
                'user_id'         => $user->id,
                'peserta_id'      => $pesertaId,
                'peserta_type'    => $pesertaType,
            ]);

            return [
                'user'         => $user,
                'peserta_id'   => $pesertaId,
                'peserta_type' => $pesertaType,
            ];
        });
    }

    /**
     * Reject registration
     */
    public function rejectRegistration(PesertaRegistration $registration, string $reason, int $rejectedBy): void
    {
        DB::transaction(function () use ($registration, $reason, $rejectedBy) {
            $registration->update([
                'status'          => 'rejected',
                'rejected_reason' => $reason,
            ]);

            $registration->user->update([
                'registration_status'          => 'rejected',
                'registration_rejected_reason' => $reason,
            ]);

            Log::info('RegistrationRepository: Registration rejected', [
                'registration_id' => $registration->id,
                'user_id'         => $registration->user_id,
                'reason'          => $reason,
            ]);
        });
    }

    /**
     * Create Atlet dari registration data
     */
    private function createAtlet(array $data, int $userId, int $createdBy): int
    {
        $step2Data = $data['step_2'] ?? [];

        $atlet = \App\Models\Atlet::create(array_merge($step2Data, [
            'users_id'   => $userId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'is_active'  => 1,
        ]));

        // Handle kategori peserta
        if (isset($step2Data['kategori_pesertas']) && is_array($step2Data['kategori_pesertas'])) {
            $atlet->kategoriPesertas()->sync($step2Data['kategori_pesertas']);
        }

        // Handle sertifikat (step 3)
        if (isset($data['step_3']) && is_array($data['step_3'])) {
            foreach ($data['step_3'] as $sertifikat) {
                \App\Models\AtletSertifikat::create(array_merge($sertifikat, [
                    'atlet_id'   => $atlet->id,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]));
            }
        }

        // Handle prestasi (step 4)
        if (isset($data['step_4']) && is_array($data['step_4'])) {
            foreach ($data['step_4'] as $prestasi) {
                \App\Models\AtletPrestasi::create(array_merge($prestasi, [
                    'atlet_id'   => $atlet->id,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]));
            }
        }

        // Handle dokumen (step 5)
        if (isset($data['step_5']) && is_array($data['step_5'])) {
            foreach ($data['step_5'] as $dokumen) {
                \App\Models\AtletDokumen::create(array_merge($dokumen, [
                    'atlet_id'   => $atlet->id,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]));
            }
        }

        return $atlet->id;
    }

    /**
     * Create Pelatih dari registration data
     */
    private function createPelatih(array $data, int $userId, int $createdBy): int
    {
        $step2Data = $data['step_2'] ?? [];

        $pelatih = \App\Models\Pelatih::create(array_merge($step2Data, [
            'users_id'   => $userId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'is_active'  => 1,
        ]));

        // Handle kategori peserta
        if (isset($step2Data['kategori_pesertas']) && is_array($step2Data['kategori_pesertas'])) {
            $pelatih->kategoriPesertas()->sync($step2Data['kategori_pesertas']);
        }

        // Handle sertifikat, prestasi, dokumen (similar to atlet)
        // ... (implement similar to createAtlet)

        return $pelatih->id;
    }

    /**
     * Create Tenaga Pendukung dari registration data
     */
    private function createTenagaPendukung(array $data, int $userId, int $createdBy): int
    {
        $step2Data = $data['step_2'] ?? [];

        $tenagaPendukung = \App\Models\TenagaPendukung::create(array_merge($step2Data, [
            'users_id'   => $userId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'is_active'  => 1,
        ]));

        // Handle kategori peserta
        if (isset($step2Data['kategori_pesertas']) && is_array($step2Data['kategori_pesertas'])) {
            $tenagaPendukung->kategoriPesertas()->sync($step2Data['kategori_pesertas']);
        }

        // Handle sertifikat, prestasi, dokumen (similar to atlet)
        // ... (implement similar to createAtlet)

        return $tenagaPendukung->id;
    }

    /**
     * Get registrations untuk admin (dengan filter)
     */
    public function getRegistrationsForAdmin(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PesertaRegistration::with(['user'])
            ->whereIn('status', ['submitted', 'approved', 'rejected']);

        // Filter by status
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Filter by peserta_type
        if (isset($filters['peserta_type']) && $filters['peserta_type']) {
            $query->where('peserta_type', $filters['peserta_type']);
        }

        // Filter by date range
        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Search
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }
}
