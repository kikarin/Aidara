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
        $currentData = $registration->data_json ?? [];

        // Handle file uploads untuk step 2 (profile photo)
        if ($step === 2 && isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
            $registration->clearMediaCollection('profile_photo');
            $media = $registration->addMedia($data['file'])
                ->usingName($data['nama'] ?? 'Profile Photo')
                ->toMediaCollection('profile_photo');
            $data['file_url'] = $media->getUrl();
            unset($data['file']); // Remove file object from data
        }

        // Handle file uploads untuk step 3 (sertifikat)
        if ($step === 3 && isset($data['sertifikat_files']) && is_array($data['sertifikat_files'])) {
            $sertifikatData = [];
            foreach ($data['sertifikat_files'] as $index => $sertifikat) {
                if (isset($sertifikat['file']) && $sertifikat['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $media = $registration->addMedia($sertifikat['file'])
                        ->usingName($sertifikat['nama_sertifikat'] ?? "Sertifikat {$index}")
                        ->toMediaCollection('sertifikat_files');

                    $sertifikatData[] = [
                        'nama_sertifikat' => $sertifikat['nama_sertifikat'] ?? null,
                        'penyelenggara'   => $sertifikat['penyelenggara']   ?? null,
                        'tanggal_terbit'  => $sertifikat['tanggal_terbit']  ?? null,
                        'file_url'        => $media->getUrl(),
                        'media_id'        => $media->id,
                    ];
                }
            }
            $data['sertifikat'] = $sertifikatData;
            unset($data['sertifikat_files']);
        }

        // Handle file uploads untuk step 5 (dokumen)
        if ($step === 5 && isset($data['dokumen_files']) && is_array($data['dokumen_files'])) {
            $dokumenData = [];
            foreach ($data['dokumen_files'] as $index => $dokumen) {
                if (isset($dokumen['file']) && $dokumen['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $media = $registration->addMedia($dokumen['file'])
                        ->usingName($dokumen['nomor'] ?? "Dokumen {$index}")
                        ->toMediaCollection('dokumen_files');

                    $dokumenData[] = [
                        'jenis_dokumen_id' => $dokumen['jenis_dokumen_id'] ?? null,
                        'nomor'            => $dokumen['nomor']            ?? null,
                        'file_url'         => $media->getUrl(),
                        'media_id'         => $media->id,
                    ];
                }
            }
            $data['dokumen'] = $dokumenData;
            unset($data['dokumen_files']);
        }

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
     * Approve registration - create atau update peserta record
     */
    public function approveRegistration(PesertaRegistration $registration, int $approvedBy): array
    {
        return DB::transaction(function () use ($registration, $approvedBy) {
            $data        = $registration->data_json;
            $pesertaType = $registration->peserta_type;
            $user        = $registration->user;

            $pesertaId = null;
            $roleId    = null;

            // Cek apakah user sudah punya peserta_id (flow baru)
            if ($user->peserta_id) {
                // User sudah punya peserta record, hanya update is_active menjadi 1
                $pesertaId = $user->peserta_id;
                
                // Update peserta record menjadi aktif
                switch ($pesertaType) {
                    case 'atlet':
                        \App\Models\Atlet::where('id', $pesertaId)->update(['is_active' => 1]);
                        $roleId = 35;
                        break;
                    case 'pelatih':
                        \App\Models\Pelatih::where('id', $pesertaId)->update(['is_active' => 1]);
                        $roleId = 36;
                        break;
                    case 'tenaga_pendukung':
                        \App\Models\TenagaPendukung::where('id', $pesertaId)->update(['is_active' => 1]);
                        $roleId = 37;
                        break;
                }
            } else {
                // User belum punya peserta record, create baru (flow lama)
                switch ($pesertaType) {
                    case 'atlet':
                        $pesertaId = $this->createAtlet($data, $user->id, $approvedBy, $registration);
                        $roleId    = 35; // Role ID Atlet
                        break;
                    case 'pelatih':
                        $pesertaId = $this->createPelatih($data, $user->id, $approvedBy, $registration);
                        $roleId    = 36; // Role ID Pelatih
                        break;
                    case 'tenaga_pendukung':
                        $pesertaId = $this->createTenagaPendukung($data, $user->id, $approvedBy, $registration);
                        $roleId    = 37; // Role ID Tenaga Pendukung
                        break;
                }
            }

            // Update user
            $user->update([
                'registration_status' => 'approved',
                'is_active'           => 1,
                'current_role_id'     => $roleId,
                'peserta_type'        => $pesertaType,
                'peserta_id'          => $pesertaId,
            ]);

            // Selaraskan relasi akun-peserta agar tab Akun mengenali statusnya
            $this->ensurePesertaUserLink($pesertaType, $pesertaId, $user);

            // Update registration
            $registration->update([
                'status' => 'approved',
            ]);

            Log::info('RegistrationRepository: Registration approved', [
                'registration_id' => $registration->id,
                'user_id'         => $user->id,
                'peserta_id'      => $pesertaId,
                'peserta_type'    => $pesertaType,
                'existing_peserta' => $user->peserta_id ? true : false,
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
    private function createAtlet(array $data, int $userId, int $createdBy, PesertaRegistration $registration): int
    {
        $step2Data = $data['step_2'] ?? [];

        // Handle profile photo dari media library registration
        $profileMedia = $registration->getFirstMedia('profile_photo');

        $atletData = array_merge($step2Data, [
            'users_id'   => $userId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'is_active'  => 1,
        ]);

        // Remove file_url from data karena akan diproses via media library
        unset($atletData['file_url'], $atletData['file']);

        $atlet = \App\Models\Atlet::create($atletData);

        // Handle profile photo upload - selalu gunakan path file dari media library
        if ($profileMedia && $profileMedia->getPath()) {
            $sourcePath = $profileMedia->getPath();
            if (file_exists($sourcePath)) {
                $atlet->addMedia($sourcePath)
                    ->usingName($step2Data['nama'] ?? 'Profile Photo')
                    ->toMediaCollection('images');
            }
        }

        // Handle kategori peserta
        if (isset($step2Data['kategori_pesertas']) && is_array($step2Data['kategori_pesertas'])) {
            $atlet->kategoriPesertas()->sync($step2Data['kategori_pesertas']);
        }

        // Handle sertifikat (step 3)
        if (isset($data['step_3']['sertifikat']) && is_array($data['step_3']['sertifikat'])) {
            $sertifikatMedia = $registration->getMedia('sertifikat_files');
            foreach ($data['step_3']['sertifikat'] as $index => $sertifikat) {
                $sertifikatRecord = \App\Models\AtletSertifikat::create([
                    'atlet_id'        => $atlet->id,
                    'nama_sertifikat' => $sertifikat['nama_sertifikat'] ?? null,
                    'penyelenggara'   => $sertifikat['penyelenggara']   ?? null,
                    'tanggal_terbit'  => $sertifikat['tanggal_terbit']  ?? null,
                    'created_by'      => $createdBy,
                    'updated_by'      => $createdBy,
                ]);

                // Copy file dari registration media ke sertifikat media
                $sourceMedia = null;
                if (isset($sertifikat['media_id'])) {
                    $sourceMedia = $sertifikatMedia->where('id', $sertifikat['media_id'])->first();
                }

                // Jika tidak ada media_id, coba ambil berdasarkan index
                if (!$sourceMedia && $sertifikatMedia->count() > $index) {
                    $sourceMedia = $sertifikatMedia->get($index);
                }

                if ($sourceMedia && $sourceMedia->getPath() && file_exists($sourceMedia->getPath())) {
                    $sertifikatRecord->addMedia($sourceMedia->getPath())
                        ->usingName($sertifikat['nama_sertifikat'] ?? "Sertifikat {$index}")
                        ->toMediaCollection('sertifikat_file');
                }
            }
        }

        // Handle prestasi (step 4)
        $prestasiData = [];
        if (isset($data['step_4']['prestasi']) && is_array($data['step_4']['prestasi'])) {
            $prestasiData = $data['step_4']['prestasi'];
        } elseif (isset($data['step_4']) && is_array($data['step_4']) && isset($data['step_4'][0])) {
            // Jika step_4 adalah array langsung
            $prestasiData = $data['step_4'];
        }

        foreach ($prestasiData as $prestasi) {
            \App\Models\AtletPrestasi::create([
                'atlet_id'   => $atlet->id,
                'nama_event' => $prestasi['nama_event'] ?? null,
                'tingkat_id' => $prestasi['tingkat_id'] ?? null,
                'tanggal'    => $prestasi['tanggal']    ?? null,
                'peringkat'  => $prestasi['peringkat']  ?? null,
                'keterangan' => $prestasi['keterangan'] ?? null,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ]);
        }

        // Handle dokumen (step 5)
        if (isset($data['step_5']['dokumen']) && is_array($data['step_5']['dokumen'])) {
            $dokumenMedia = $registration->getMedia('dokumen_files');
            foreach ($data['step_5']['dokumen'] as $index => $dokumen) {
                $dokumenRecord = \App\Models\AtletDokumen::create([
                    'atlet_id'         => $atlet->id,
                    'jenis_dokumen_id' => $dokumen['jenis_dokumen_id'] ?? null,
                    'nomor'            => $dokumen['nomor']            ?? null,
                    'created_by'       => $createdBy,
                    'updated_by'       => $createdBy,
                ]);

                // Copy file dari registration media ke dokumen media
                $sourceMedia = null;
                if (isset($dokumen['media_id'])) {
                    $sourceMedia = $dokumenMedia->where('id', $dokumen['media_id'])->first();
                }

                // Jika tidak ada media_id, coba ambil berdasarkan index
                if (!$sourceMedia && $dokumenMedia->count() > $index) {
                    $sourceMedia = $dokumenMedia->get($index);
                }

                if ($sourceMedia && $sourceMedia->getPath() && file_exists($sourceMedia->getPath())) {
                    $dokumenRecord->addMedia($sourceMedia->getPath())
                        ->usingName($dokumen['nomor'] ?? "Dokumen {$index}")
                        ->toMediaCollection('dokumen_file');
                }
            }
        }

        return $atlet->id;
    }

    /**
     * Create Pelatih dari registration data
     */
    private function createPelatih(array $data, int $userId, int $createdBy, PesertaRegistration $registration): int
    {
        $step2Data = $data['step_2'] ?? [];

        // Handle profile photo dari media library registration
        $profileMedia = $registration->getFirstMedia('profile_photo');

        $pelatihData = array_merge($step2Data, [
            'users_id'   => $userId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'is_active'  => 1,
        ]);

        // Remove file_url from data karena akan diproses via media library
        unset($pelatihData['file_url'], $pelatihData['file']);

        $pelatih = \App\Models\Pelatih::create($pelatihData);

        // Handle profile photo upload - selalu gunakan path file dari media library
        if ($profileMedia && $profileMedia->getPath()) {
            $sourcePath = $profileMedia->getPath();
            if (file_exists($sourcePath)) {
                $pelatih->addMedia($sourcePath)
                    ->usingName($step2Data['nama'] ?? 'Profile Photo')
                    ->toMediaCollection('images');
            }
        }

        // Handle kategori peserta
        if (isset($step2Data['kategori_pesertas']) && is_array($step2Data['kategori_pesertas'])) {
            $pelatih->kategoriPesertas()->sync($step2Data['kategori_pesertas']);
        }

        // Handle sertifikat (step 3)
        if (isset($data['step_3']['sertifikat']) && is_array($data['step_3']['sertifikat'])) {
            $sertifikatMedia = $registration->getMedia('sertifikat_files');
            foreach ($data['step_3']['sertifikat'] as $index => $sertifikat) {
                $sertifikatRecord = \App\Models\PelatihSertifikat::create([
                    'pelatih_id'      => $pelatih->id,
                    'nama_sertifikat' => $sertifikat['nama_sertifikat'] ?? null,
                    'penyelenggara'   => $sertifikat['penyelenggara']   ?? null,
                    'tanggal_terbit'  => $sertifikat['tanggal_terbit']  ?? null,
                    'created_by'      => $createdBy,
                    'updated_by'      => $createdBy,
                ]);

                // Copy file dari registration media ke sertifikat media
                $sourceMedia = null;
                if (isset($sertifikat['media_id'])) {
                    $sourceMedia = $sertifikatMedia->where('id', $sertifikat['media_id'])->first();
                }

                if (!$sourceMedia && $sertifikatMedia->count() > $index) {
                    $sourceMedia = $sertifikatMedia->get($index);
                }

                if ($sourceMedia && $sourceMedia->getPath() && file_exists($sourceMedia->getPath())) {
                    $sertifikatRecord->addMedia($sourceMedia->getPath())
                        ->usingName($sertifikat['nama_sertifikat'] ?? "Sertifikat {$index}")
                        ->toMediaCollection('sertifikat_file');
                }
            }
        }

        // Handle prestasi (step 4)
        $prestasiData = [];
        if (isset($data['step_4']['prestasi']) && is_array($data['step_4']['prestasi'])) {
            $prestasiData = $data['step_4']['prestasi'];
        } elseif (isset($data['step_4']) && is_array($data['step_4']) && isset($data['step_4'][0])) {
            $prestasiData = $data['step_4'];
        }

        foreach ($prestasiData as $prestasi) {
            \App\Models\PelatihPrestasi::create([
                'pelatih_id'                   => $pelatih->id,
                'nama_event'                   => $prestasi['nama_event']                   ?? null,
                'tingkat_id'                   => $prestasi['tingkat_id']                   ?? null,
                'tanggal'                      => $prestasi['tanggal']                      ?? null,
                'peringkat'                    => $prestasi['peringkat']                    ?? null,
                'keterangan'                   => $prestasi['keterangan']                   ?? null,
                'kategori_prestasi_pelatih_id' => $prestasi['kategori_prestasi_pelatih_id'] ?? null,
                'created_by'                   => $createdBy,
                'updated_by'                   => $createdBy,
            ]);
        }

        // Handle dokumen (step 5)
        if (isset($data['step_5']['dokumen']) && is_array($data['step_5']['dokumen'])) {
            $dokumenMedia = $registration->getMedia('dokumen_files');
            foreach ($data['step_5']['dokumen'] as $index => $dokumen) {
                $dokumenRecord = \App\Models\PelatihDokumen::create([
                    'pelatih_id'       => $pelatih->id,
                    'jenis_dokumen_id' => $dokumen['jenis_dokumen_id'] ?? null,
                    'nomor'            => $dokumen['nomor']            ?? null,
                    'created_by'       => $createdBy,
                    'updated_by'       => $createdBy,
                ]);

                // Copy file dari registration media ke dokumen media
                $sourceMedia = null;
                if (isset($dokumen['media_id'])) {
                    $sourceMedia = $dokumenMedia->where('id', $dokumen['media_id'])->first();
                }

                if (!$sourceMedia && $dokumenMedia->count() > $index) {
                    $sourceMedia = $dokumenMedia->get($index);
                }

                if ($sourceMedia && $sourceMedia->getPath() && file_exists($sourceMedia->getPath())) {
                    $dokumenRecord->addMedia($sourceMedia->getPath())
                        ->usingName($dokumen['nomor'] ?? "Dokumen {$index}")
                        ->toMediaCollection('dokumen_file');
                }
            }
        }

        return $pelatih->id;
    }

    /**
     * Create Tenaga Pendukung dari registration data
     */
    private function createTenagaPendukung(array $data, int $userId, int $createdBy, PesertaRegistration $registration): int
    {
        $step2Data = $data['step_2'] ?? [];

        // Handle profile photo dari media library registration
        $profileMedia = $registration->getFirstMedia('profile_photo');

        $tenagaPendukungData = array_merge($step2Data, [
            'users_id'   => $userId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'is_active'  => 1,
        ]);

        // Remove file_url from data karena akan diproses via media library
        unset($tenagaPendukungData['file_url'], $tenagaPendukungData['file']);

        $tenagaPendukung = \App\Models\TenagaPendukung::create($tenagaPendukungData);

        // Handle profile photo upload - selalu gunakan path file dari media library
        if ($profileMedia && $profileMedia->getPath()) {
            $sourcePath = $profileMedia->getPath();
            if (file_exists($sourcePath)) {
                $tenagaPendukung->addMedia($sourcePath)
                    ->usingName($step2Data['nama'] ?? 'Profile Photo')
                    ->toMediaCollection('images');
            }
        }

        // Handle kategori peserta
        if (isset($step2Data['kategori_pesertas']) && is_array($step2Data['kategori_pesertas'])) {
            $tenagaPendukung->kategoriPesertas()->sync($step2Data['kategori_pesertas']);
        }

        // Handle sertifikat (step 3)
        if (isset($data['step_3']['sertifikat']) && is_array($data['step_3']['sertifikat'])) {
            $sertifikatMedia = $registration->getMedia('sertifikat_files');
            foreach ($data['step_3']['sertifikat'] as $index => $sertifikat) {
                $sertifikatRecord = \App\Models\TenagaPendukungSertifikat::create([
                    'tenaga_pendukung_id' => $tenagaPendukung->id,
                    'nama_sertifikat'     => $sertifikat['nama_sertifikat'] ?? null,
                    'penyelenggara'       => $sertifikat['penyelenggara']   ?? null,
                    'tanggal_terbit'      => $sertifikat['tanggal_terbit']  ?? null,
                    'created_by'          => $createdBy,
                    'updated_by'          => $createdBy,
                ]);

                // Copy file dari registration media ke sertifikat media
                $sourceMedia = null;
                if (isset($sertifikat['media_id'])) {
                    $sourceMedia = $sertifikatMedia->where('id', $sertifikat['media_id'])->first();
                }

                if (!$sourceMedia && $sertifikatMedia->count() > $index) {
                    $sourceMedia = $sertifikatMedia->get($index);
                }

                if ($sourceMedia && $sourceMedia->getPath() && file_exists($sourceMedia->getPath())) {
                    $sertifikatRecord->addMedia($sourceMedia->getPath())
                        ->usingName($sertifikat['nama_sertifikat'] ?? "Sertifikat {$index}")
                        ->toMediaCollection('sertifikat_file');
                }
            }
        }

        // Handle prestasi (step 4)
        $prestasiData = [];
        if (isset($data['step_4']['prestasi']) && is_array($data['step_4']['prestasi'])) {
            $prestasiData = $data['step_4']['prestasi'];
        } elseif (isset($data['step_4']) && is_array($data['step_4']) && isset($data['step_4'][0])) {
            $prestasiData = $data['step_4'];
        }

        foreach ($prestasiData as $prestasi) {
            \App\Models\TenagaPendukungPrestasi::create([
                'tenaga_pendukung_id' => $tenagaPendukung->id,
                'nama_event'          => $prestasi['nama_event'] ?? null,
                'tingkat_id'          => $prestasi['tingkat_id'] ?? null,
                'tanggal'             => $prestasi['tanggal']    ?? null,
                'peringkat'           => $prestasi['peringkat']  ?? null,
                'keterangan'          => $prestasi['keterangan'] ?? null,
                'created_by'          => $createdBy,
                'updated_by'          => $createdBy,
            ]);
        }

        // Handle dokumen (step 5)
        if (isset($data['step_5']['dokumen']) && is_array($data['step_5']['dokumen'])) {
            $dokumenMedia = $registration->getMedia('dokumen_files');
            foreach ($data['step_5']['dokumen'] as $index => $dokumen) {
                $dokumenRecord = \App\Models\TenagaPendukungDokumen::create([
                    'tenaga_pendukung_id' => $tenagaPendukung->id,
                    'jenis_dokumen_id'    => $dokumen['jenis_dokumen_id'] ?? null,
                    'nomor'               => $dokumen['nomor']            ?? null,
                    'created_by'          => $createdBy,
                    'updated_by'          => $createdBy,
                ]);

                // Copy file dari registration media ke dokumen media
                $sourceMedia = null;
                if (isset($dokumen['media_id'])) {
                    $sourceMedia = $dokumenMedia->where('id', $dokumen['media_id'])->first();
                }

                if (!$sourceMedia && $dokumenMedia->count() > $index) {
                    $sourceMedia = $dokumenMedia->get($index);
                }

                if ($sourceMedia && $sourceMedia->getPath() && file_exists($sourceMedia->getPath())) {
                    $dokumenRecord->addMedia($sourceMedia->getPath())
                        ->usingName($dokumen['nomor'] ?? "Dokumen {$index}")
                        ->toMediaCollection('dokumen_file');
                }
            }
        }

        return $tenagaPendukung->id;
    }

    /**
     * Get registrations untuk admin (dengan filter)
     */
    public function getRegistrationsForAdmin(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Include status 'submitted' untuk user yang baru register dengan flow baru
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

    /**
     * Create draft peserta record dengan email auto-fill
     */
    public function createDraftPeserta(User $user, string $pesertaType): int
    {
        // Untuk Pelatih dan Tenaga Pendukung, nik harus unique dan tidak nullable
        // Jadi kita buat temporary nik yang unik (maksimal 30 karakter sesuai migration)
        $temporaryNik = null;
        if (in_array($pesertaType, ['pelatih', 'tenaga_pendukung'])) {
            // Gunakan kombinasi user_id dan timestamp untuk membuat nik temporary yang unik
            // Format: DRAFT-{user_id}-{timestamp} (dipotong jika lebih dari 30 karakter)
            $temporaryNik = 'DRAFT-' . $user->id . '-' . time();
            if (strlen($temporaryNik) > 30) {
                // Jika terlalu panjang, gunakan format yang lebih pendek
                $temporaryNik = 'D-' . $user->id . '-' . substr(time(), -8); // Ambil 8 digit terakhir timestamp
            }
        }
        
        $baseData = [
            'users_id' => $user->id,
            'email' => $user->email, // Auto-fill email
            'nama' => 'Pending', // Placeholder, akan diisi user di edit page
            'jenis_kelamin' => 'L', // Default value untuk enum (required field)
            'nik' => $temporaryNik, // Temporary nik untuk pelatih/tenaga pendukung, null untuk atlet
            'is_active' => 0, // Belum approved
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
        
        $pesertaId = match($pesertaType) {
            'atlet' => \App\Models\Atlet::create($baseData)->id,
            'pelatih' => \App\Models\Pelatih::create($baseData)->id,
            'tenaga_pendukung' => \App\Models\TenagaPendukung::create($baseData)->id,
            default => throw new \Exception('Invalid peserta type')
        };
        
        // Create atau update PesertaRegistration untuk muncul di halaman approval
        // Gunakan 'submitted' karena status enum tidak memiliki 'pending'
        $registration = PesertaRegistration::updateOrCreate(
            [
                'user_id' => $user->id,
                'peserta_type' => $pesertaType,
            ],
            [
                'peserta_type' => $pesertaType,
                'step_current' => 2, // Step 1 adalah pilih type, step 2 adalah data diri
                'status' => 'submitted', // Status submitted untuk muncul di approval page
                'data_json' => [
                    'step_1' => [
                        'peserta_type' => $pesertaType,
                    ],
                ],
            ]
        );

        Log::info('RegistrationRepository: PesertaRegistration created/updated', [
            'user_id' => $user->id,
            'peserta_id' => $pesertaId,
            'registration_id' => $registration->id,
            'status' => $registration->status,
            'peserta_type' => $pesertaType,
        ]);
        
        return $pesertaId;
    }

    /**
     * Sync data peserta ke PesertaRegistration untuk update di halaman approval
     */
    public function syncPesertaToRegistration(User $user, $pesertaModel): void
    {
        // Cari PesertaRegistration untuk user ini dengan status submitted atau rejected
        $registration = PesertaRegistration::where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'rejected'])
            ->first();

        if (!$registration) {
            return;
        }

        // Ambil data peserta untuk di-sync ke data_json
        $pesertaData = $pesertaModel->toArray();
        
        // Ambil kategori peserta jika ada
        $kategoriPesertas = [];
        if (method_exists($pesertaModel, 'kategoriPesertas')) {
            $kategoriPesertas = $pesertaModel->kategoriPesertas()->pluck('id')->toArray();
        }

        // Update data_json di PesertaRegistration
        $currentData = $registration->data_json ?? [];
        $currentData['step_2'] = array_merge($currentData['step_2'] ?? [], [
            'nama' => $pesertaData['nama'] ?? null,
            'nik' => $pesertaData['nik'] ?? null,
            'nisn' => $pesertaData['nisn'] ?? null,
            'jenis_kelamin' => $pesertaData['jenis_kelamin'] ?? null,
            'tempat_lahir' => $pesertaData['tempat_lahir'] ?? null,
            'tanggal_lahir' => $pesertaData['tanggal_lahir'] ?? null,
            'alamat' => $pesertaData['alamat'] ?? null,
            'kecamatan_id' => $pesertaData['kecamatan_id'] ?? null,
            'kelurahan_id' => $pesertaData['kelurahan_id'] ?? null,
            'no_hp' => $pesertaData['no_hp'] ?? null,
            'email' => $pesertaData['email'] ?? null,
            'agama' => $pesertaData['agama'] ?? null,
            'sekolah' => $pesertaData['sekolah'] ?? null,
            'kelas_sekolah' => $pesertaData['kelas_sekolah'] ?? null,
            'ukuran_baju' => $pesertaData['ukuran_baju'] ?? null,
            'ukuran_celana' => $pesertaData['ukuran_celana'] ?? null,
            'ukuran_sepatu' => $pesertaData['ukuran_sepatu'] ?? null,
            'tanggal_bergabung' => $pesertaData['tanggal_bergabung'] ?? null,
            'kategori_pesertas' => $kategoriPesertas,
        ]);

        // Untuk pelatih, tambahkan field khusus
        if ($registration->peserta_type === 'pelatih') {
            $currentData['step_2']['pekerjaan_selain_melatih'] = $pesertaData['pekerjaan_selain_melatih'] ?? null;
        }

        $registration->update([
            'data_json' => $currentData,
        ]);

        Log::info('RegistrationRepository: Synced peserta data to registration', [
            'user_id' => $user->id,
            'peserta_id' => $pesertaModel->id,
            'peserta_type' => $registration->peserta_type,
        ]);
    }

    /**
     * Pastikan kolom users_id pada tabel peserta terisi dengan user yang tepat
     */
    private function ensurePesertaUserLink(?string $pesertaType, ?int $pesertaId, User $user): void
    {
        if (!$pesertaType || !$pesertaId) {
            return;
        }

        $modelClass = match ($pesertaType) {
            'atlet'            => \App\Models\Atlet::class,
            'pelatih'          => \App\Models\Pelatih::class,
            'tenaga_pendukung' => \App\Models\TenagaPendukung::class,
            default            => null,
        };

        if (!$modelClass) {
            return;
        }

        $peserta = $modelClass::find($pesertaId);

        if (!$peserta) {
            Log::warning('RegistrationRepository: Peserta model not found for syncing users_id', [
                'peserta_type' => $pesertaType,
                'peserta_id'   => $pesertaId,
                'user_id'      => $user->id,
            ]);
            return;
        }

        if ((int) $peserta->users_id === (int) $user->id) {
            return;
        }

        $peserta->update(['users_id' => $user->id]);

        Log::info('RegistrationRepository: users_id synced on peserta record', [
            'peserta_type' => $pesertaType,
            'peserta_id'   => $pesertaId,
            'user_id'      => $user->id,
        ]);
    }
}
