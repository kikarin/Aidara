<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\PesertaRegistration;
use App\Repositories\RegistrationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationApprovalController extends Controller
{
    protected $repository;

    public function __construct(RegistrationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Index page untuk list pengajuan registrasi
     */
    public function index(Request $request): Response
    {
        $filters = [
            'status'       => $request->get('status'),
            'peserta_type' => $request->get('peserta_type'),
            'date_from'    => $request->get('date_from'),
            'date_to'      => $request->get('date_to'),
            'search'       => $request->get('search'),
            'per_page'     => $request->get('per_page', 15),
        ];

        $registrations = $this->repository->getRegistrationsForAdmin($filters);

        return Inertia::render('modules/registration-approval/Index', [
            'registrations' => $registrations,
            'filters'       => $filters,
        ]);
    }

    /**
     * API endpoint untuk datatable
     */
    public function apiIndex(Request $request)
    {
        $filters = [
            'status'       => $request->get('status'),
            'peserta_type' => $request->get('peserta_type'),
            'date_from'    => $request->get('date_from'),
            'date_to'      => $request->get('date_to'),
            'search'       => $request->get('search'),
            'per_page'     => $request->get('per_page', 15),
        ];

        $registrations = $this->repository->getRegistrationsForAdmin($filters);

        $data = $registrations->map(function ($registration) {
            return [
                'id'                   => $registration->id,
                'user_id'              => $registration->user_id,
                'user_name'            => $registration->user->name  ?? '-',
                'user_email'           => $registration->user->email ?? '-',
                'peserta_type'         => $registration->peserta_type,
                'peserta_type_label'   => ucfirst(str_replace('_', ' ', $registration->peserta_type)),
                'status'               => $registration->status,
                'status_label'         => $this->getStatusLabel($registration->status),
                'step_current'         => $registration->step_current,
                'rejected_reason'      => $registration->rejected_reason ?? $registration->user->registration_rejected_reason ?? null,
                'created_at'           => $registration->created_at->format('Y-m-d H:i:s'),
                'created_at_formatted' => $registration->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'        => $registrations->total(),
                'current_page' => $registrations->currentPage(),
                'per_page'     => $registrations->perPage(),
                'last_page'    => $registrations->lastPage(),
                'search'       => $filters['search'] ?? '',
                'sort'         => '',
                'order'        => 'asc',
            ],
        ]);
    }

    /**
     * Show detail pengajuan
     */
    public function show($id): Response
    {
        $registration = PesertaRegistration::with(['user'])->findOrFail($id);

        $data = $registration->data_json ?? [];

        // Load additional data untuk preview
        $additionalData = [];

        // Cek apakah user sudah punya peserta_id (flow baru - data ada di model peserta)
        $user = $registration->user;
        $pesertaModel = null;
        
        if ($user && $user->peserta_id && $user->peserta_type) {
            // Load data dari model peserta (flow baru)
            switch ($user->peserta_type) {
                case 'atlet':
                    $pesertaModel = \App\Models\Atlet::with(['kategoriPesertas', 'sertifikat.media', 'prestasi.tingkat', 'dokumen.jenis_dokumen', 'media'])->find($user->peserta_id);
                    break;
                case 'pelatih':
                    $pesertaModel = \App\Models\Pelatih::with(['kategoriPesertas', 'sertifikat.media', 'prestasi.tingkat', 'dokumen.jenis_dokumen', 'media'])->find($user->peserta_id);
                    break;
                case 'tenaga_pendukung':
                    $pesertaModel = \App\Models\TenagaPendukung::with(['kategoriPesertas', 'sertifikat.media', 'prestasi.tingkat', 'dokumen.jenis_dokumen', 'media'])->find($user->peserta_id);
                    break;
            }
        }

        if (isset($data['step_2']) || $pesertaModel) {
            $step2 = $data['step_2'] ?? [];
            
            // Jika ada model peserta, gunakan data dari model peserta
            if ($pesertaModel) {
                $step2 = array_merge($step2, $pesertaModel->toArray());
                
                // Load kategori peserta
                if ($pesertaModel->kategoriPesertas) {
                    $additionalData['kategori_pesertas'] = $pesertaModel->kategoriPesertas;
                    $step2['kategori_pesertas'] = $pesertaModel->kategoriPesertas->pluck('id')->toArray();
                }
                
                // Load foto dari model peserta
                $profileMedia = $pesertaModel->getFirstMedia('images');
                if ($profileMedia) {
                    $step2['file_url'] = $profileMedia->getUrl();
                }
                
                // Load sertifikat dari model peserta
                if ($pesertaModel->sertifikat && $pesertaModel->sertifikat->count() > 0) {
                    $sertifikatData = $pesertaModel->sertifikat->map(function ($sertifikat) {
                        // Gunakan file_url dari model (sudah di-append) atau ambil dari media
                        $fileUrl = $sertifikat->file_url ?? null;
                        if (!$fileUrl) {
                            $media = $sertifikat->getFirstMedia('sertifikat_file');
                            $fileUrl = $media ? $media->getUrl() : null;
                        }
                        
                        return [
                            'nama_sertifikat' => $sertifikat->nama_sertifikat,
                            'penyelenggara' => $sertifikat->penyelenggara,
                            'tanggal_terbit' => $sertifikat->tanggal_terbit,
                            'file_url' => $fileUrl,
                            'file' => $fileUrl, // Tambahkan juga di 'file' untuk kompatibilitas frontend
                        ];
                    })->toArray();
                    $data['step_3'] = ['sertifikat' => $sertifikatData];
                }
                
                // Load prestasi dari model peserta
                if ($pesertaModel->prestasi && $pesertaModel->prestasi->count() > 0) {
                    $prestasiData = $pesertaModel->prestasi->map(function ($prestasi) {
                        return [
                            'nama_event' => $prestasi->nama_event,
                            'tingkat_id' => $prestasi->tingkat_id,
                            'tingkat_label' => $prestasi->tingkat->nama ?? null,
                            'tanggal' => $prestasi->tanggal,
                            'peringkat' => $prestasi->peringkat,
                            'keterangan' => $prestasi->keterangan,
                        ];
                    })->toArray();
                    $data['step_4'] = ['prestasi' => $prestasiData];
                }
                
                // Load dokumen dari model peserta
                if ($pesertaModel->dokumen && $pesertaModel->dokumen->count() > 0) {
                    $dokumenData = $pesertaModel->dokumen->map(function ($dokumen) {
                        // Gunakan file_url dari model (sudah di-append) atau ambil dari media
                        $fileUrl = $dokumen->file_url ?? null;
                        if (!$fileUrl) {
                            $media = $dokumen->getFirstMedia('dokumen_file');
                            $fileUrl = $media ? $media->getUrl() : null;
                        }
                        
                        return [
                            'jenis_dokumen_id' => $dokumen->jenis_dokumen_id,
                            'jenis_dokumen_label' => $dokumen->jenis_dokumen->nama ?? null,
                            'nomor' => $dokumen->nomor,
                            'file_url' => $fileUrl,
                            'file' => $fileUrl, // Tambahkan juga di 'file' untuk kompatibilitas frontend
                        ];
                    })->toArray();
                    $data['step_5'] = ['dokumen' => $dokumenData];
                }
            }

            // Load kecamatan, kelurahan jika ada
            if (isset($step2['kecamatan_id'])) {
                $additionalData['kecamatan'] = \App\Models\MstKecamatan::find($step2['kecamatan_id']);
            }
            if (isset($step2['kelurahan_id'])) {
                $additionalData['kelurahan'] = \App\Models\MstDesa::find($step2['kelurahan_id']);
            }

            // Load kategori peserta jika belum di-load dari model
            if (!isset($additionalData['kategori_pesertas']) && isset($step2['kategori_pesertas']) && is_array($step2['kategori_pesertas'])) {
                $additionalData['kategori_pesertas'] = \App\Models\MstKategoriPeserta::whereIn('id', $step2['kategori_pesertas'])->get();
            }

            // Load profile photo dari PesertaRegistration media library jika belum ada dari model
            if (!isset($step2['file_url'])) {
                $profileMedia = $registration->getFirstMedia('profile_photo');
                if ($profileMedia) {
                    $step2['file_url'] = $profileMedia->getUrl();
                }
            }
            
            $data['step_2'] = $step2;
        }

        // Load sertifikat files dari PesertaRegistration media library (jika belum di-load dari model peserta)
        if (!isset($data['step_3']) || !isset($data['step_3']['sertifikat'])) {
            $originalData = $registration->data_json ?? [];
            if (isset($originalData['step_3']['sertifikat']) && is_array($originalData['step_3']['sertifikat'])) {
                $sertifikatMedia = $registration->getMedia('sertifikat_files');
                foreach ($originalData['step_3']['sertifikat'] as $index => &$sertifikat) {
                    if (isset($sertifikat['media_id'])) {
                        $media = $sertifikatMedia->where('id', $sertifikat['media_id'])->first();
                        if ($media) {
                            $sertifikat['file_url'] = $media->getUrl();
                        }
                    }
                }
                $data['step_3'] = ['sertifikat' => $originalData['step_3']['sertifikat']];
            }
        }

        // Load dokumen files dari PesertaRegistration media library (jika belum di-load dari model peserta)
        if (!isset($data['step_5']) || !isset($data['step_5']['dokumen'])) {
            $originalData = $registration->data_json ?? [];
            if (isset($originalData['step_5']['dokumen']) && is_array($originalData['step_5']['dokumen'])) {
                $dokumenMedia = $registration->getMedia('dokumen_files');
                
                // Collect semua jenis_dokumen_id untuk load sekaligus
                $jenisDokumenIds = [];
                foreach ($originalData['step_5']['dokumen'] as $dokumen) {
                    if (isset($dokumen['jenis_dokumen_id'])) {
                        $jenisDokumenIds[] = $dokumen['jenis_dokumen_id'];
                    }
                }
                
                // Load semua jenis dokumen sekaligus
                if (!empty($jenisDokumenIds)) {
                    $jenisDokumenCollection = \App\Models\MstJenisDokumen::whereIn('id', array_unique($jenisDokumenIds))->get();
                    // Convert ke array dengan key ID untuk mudah diakses di frontend
                    $additionalData['jenis_dokumen'] = $jenisDokumenCollection->keyBy('id')->map(function ($item) {
                        return ['id' => $item->id, 'nama' => $item->nama];
                    })->toArray();
                }
                
                foreach ($originalData['step_5']['dokumen'] as $index => &$dokumen) {
                    if (isset($dokumen['media_id'])) {
                        $media = $dokumenMedia->where('id', $dokumen['media_id'])->first();
                        if ($media) {
                            $dokumen['file_url'] = $media->getUrl();
                        }
                    }
                }
                $data['step_5'] = ['dokumen' => $originalData['step_5']['dokumen']];
            }
        } else if (isset($data['step_5']['dokumen']) && is_array($data['step_5']['dokumen'])) {
            // Load jenis dokumen untuk data yang sudah di-load dari model peserta
            $jenisDokumenIds = [];
            foreach ($data['step_5']['dokumen'] as $dokumen) {
                if (isset($dokumen['jenis_dokumen_id'])) {
                    $jenisDokumenIds[] = $dokumen['jenis_dokumen_id'];
                }
            }
            
            if (!empty($jenisDokumenIds) && !isset($additionalData['jenis_dokumen'])) {
                $jenisDokumenCollection = \App\Models\MstJenisDokumen::whereIn('id', array_unique($jenisDokumenIds))->get();
                $additionalData['jenis_dokumen'] = $jenisDokumenCollection->keyBy('id')->map(function ($item) {
                    return ['id' => $item->id, 'nama' => $item->nama];
                })->toArray();
            }
        }

        // Load tingkat prestasi jika ada (jika belum di-load dari model peserta)
        if (!isset($data['step_4']) || !isset($data['step_4']['prestasi'])) {
            $originalData = $registration->data_json ?? [];
            if (isset($originalData['step_4']['prestasi']) && is_array($originalData['step_4']['prestasi'])) {
                $data['step_4'] = ['prestasi' => $originalData['step_4']['prestasi']];
            }
        }
        
        // Load tingkat prestasi untuk mapping label
        if (isset($data['step_4']['prestasi']) && is_array($data['step_4']['prestasi'])) {
            $tingkatIds = [];
            foreach ($data['step_4']['prestasi'] as $prestasi) {
                if (isset($prestasi['tingkat_id'])) {
                    $tingkatIds[] = $prestasi['tingkat_id'];
                }
            }
            
            // Load semua tingkat sekaligus jika belum di-load
            if (!empty($tingkatIds) && !isset($additionalData['tingkat'])) {
                $tingkatCollection = \App\Models\MstTingkat::whereIn('id', array_unique($tingkatIds))->get();
                // Convert ke array dengan key ID untuk mudah diakses di frontend
                $additionalData['tingkat'] = $tingkatCollection->keyBy('id')->map(function ($item) {
                    return ['id' => $item->id, 'nama' => $item->nama];
                })->toArray();
            }
        }

        return Inertia::render('modules/registration-approval/Show', [
            'registration'     => $registration,
            'registrationData' => $data,
            'additionalData'   => $additionalData,
        ]);
    }

    /**
     * Approve registration
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'ids'   => 'nullable|array', // Untuk bulk approve
            'ids.*' => 'exists:peserta_registrations,id',
        ]);

        try {
            $approvedBy = Auth::id();
            $ids        = $request->input('ids', [$id]);

            $approved = [];
            $errors   = [];

            foreach ($ids as $registrationId) {
                try {
                    $registration = PesertaRegistration::with('user')->findOrFail($registrationId);

                    // Include status 'submitted' untuk user dengan flow baru
                    if (!in_array($registration->status, ['submitted', 'rejected'], true)) {
                        $errors[] = "Pengajuan #{$registrationId} tidak dapat disetujui (status: {$registration->status})";
                        continue;
                    }

                    if ($registration->status === 'rejected') {
                        $registration->update([
                            'status'          => 'submitted',
                            'rejected_reason' => null,
                        ]);

                        $registration->user?->update([
                            'registration_status'          => 'pending',
                            'registration_rejected_reason' => null,
                        ]);
                    }

                    $result     = $this->repository->approveRegistration($registration->fresh(), $approvedBy);
                    $approved[] = $registrationId;

                    Log::info('RegistrationApprovalController: Registration approved', [
                        'registration_id' => $registrationId,
                        'approved_by'     => $approvedBy,
                    ]);
                } catch (\Exception $e) {
                    Log::error('RegistrationApprovalController: Error approving registration', [
                        'registration_id' => $registrationId,
                        'error'           => $e->getMessage(),
                    ]);
                    $errors[] = "Gagal menyetujui pengajuan #{$registrationId}: " . $e->getMessage();
                }
            }

            if (count($approved) > 0) {
                $message = count($approved) > 1
                    ? count($approved) . ' pengajuan berhasil disetujui'
                    : 'Pengajuan berhasil disetujui';

                if (count($errors) > 0) {
                    $message .= '. ' . implode('. ', $errors);
                }

                // Return JSON response for API calls
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                    ]);
                }

                return redirect()->route('registration-approval.index')
                    ->with('success', $message);
            } else {
                $errorMessage = implode('. ', $errors);

                // Return JSON response for API calls
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                    ], 400);
                }

                return redirect()->back()
                    ->withErrors(['error' => $errorMessage]);
            }
        } catch (\Exception $e) {
            Log::error('RegistrationApprovalController: Error in approve', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyetujui pengajuan.']);
        }
    }

    /**
     * Reject registration
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:1000',
            'ids'             => 'nullable|array', // Untuk bulk reject
            'ids.*'           => 'exists:peserta_registrations,id',
        ]);

        try {
            $rejectedBy = Auth::id();
            $reason     = $request->input('rejected_reason');
            $ids        = $request->input('ids', [$id]);

            $rejected = [];
            $errors   = [];

            foreach ($ids as $registrationId) {
                try {
                    $registration = PesertaRegistration::with('user')->findOrFail($registrationId);

                    if ($registration->status !== 'submitted') {
                        $errors[] = "Pengajuan #{$registrationId} tidak dapat ditolak (status: {$registration->status})";
                        continue;
                    }

                    $this->repository->rejectRegistration($registration, $reason, $rejectedBy);
                    $rejected[] = $registrationId;

                    Log::info('RegistrationApprovalController: Registration rejected', [
                        'registration_id' => $registrationId,
                        'rejected_by'     => $rejectedBy,
                        'reason'          => $reason,
                    ]);
                } catch (\Exception $e) {
                    Log::error('RegistrationApprovalController: Error rejecting registration', [
                        'registration_id' => $registrationId,
                        'error'           => $e->getMessage(),
                    ]);
                    $errors[] = "Gagal menolak pengajuan #{$registrationId}: " . $e->getMessage();
                }
            }

            if (count($rejected) > 0) {
                $message = count($rejected) > 1
                    ? count($rejected) . ' pengajuan berhasil ditolak'
                    : 'Pengajuan berhasil ditolak';

                if (count($errors) > 0) {
                    $message .= '. ' . implode('. ', $errors);
                }

                // Return JSON response for API calls
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                    ]);
                }

                return redirect()->route('registration-approval.index')
                    ->with('success', $message);
            } else {
                $errorMessage = implode('. ', $errors);

                // Return JSON response for API calls
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                    ], 400);
                }

                return redirect()->back()
                    ->withErrors(['error' => $errorMessage]);
            }
        } catch (\Exception $e) {
            Log::error('RegistrationApprovalController: Error in reject', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menolak pengajuan.']);
        }
    }

    /**
     * Delete single registration
     */
    public function destroy($id)
    {
        try {
            $registration = PesertaRegistration::with('user')->findOrFail($id);

            // Hanya bisa delete jika status bukan 'approved'
            if ($registration->status === 'approved') {
                return redirect()->back()
                    ->withErrors(['error' => 'Tidak dapat menghapus pengajuan yang sudah disetujui.']);
            }

            // Delete media files jika ada
            $registration->clearMediaCollection('profile_photo');
            $registration->clearMediaCollection('sertifikat_files');
            $registration->clearMediaCollection('dokumen_files');

            // Delete registration
            $registration->delete();

            Log::info('RegistrationApprovalController: Registration deleted', [
                'registration_id' => $id,
                'deleted_by'      => Auth::id(),
            ]);

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengajuan berhasil dihapus',
                ]);
            }

            return redirect()->route('registration-approval.index')
                ->with('success', 'Pengajuan berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('RegistrationApprovalController: Error deleting registration', [
                'registration_id' => $id,
                'error'           => $e->getMessage(),
            ]);

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus pengajuan: ' . $e->getMessage(),
                ], 400);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menghapus pengajuan.']);
        }
    }

    /**
     * Delete multiple registrations
     */
    public function destroySelected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:peserta_registrations,id',
        ]);

        try {
            $ids = $request->input('ids');
            $deleted = [];
            $errors = [];

            foreach ($ids as $id) {
                try {
                    $registration = PesertaRegistration::with('user')->findOrFail($id);

                    // Hanya bisa delete jika status bukan 'approved'
                    if ($registration->status === 'approved') {
                        $errors[] = "Pengajuan #{$id} tidak dapat dihapus karena sudah disetujui";
                        continue;
                    }

                    // Delete media files jika ada
                    $registration->clearMediaCollection('profile_photo');
                    $registration->clearMediaCollection('sertifikat_files');
                    $registration->clearMediaCollection('dokumen_files');

                    // Delete registration
                    $registration->delete();
                    $deleted[] = $id;

                    Log::info('RegistrationApprovalController: Registration deleted', [
                        'registration_id' => $id,
                        'deleted_by'      => Auth::id(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('RegistrationApprovalController: Error deleting registration', [
                        'registration_id' => $id,
                        'error'           => $e->getMessage(),
                    ]);
                    $errors[] = "Gagal menghapus pengajuan #{$id}: " . $e->getMessage();
                }
            }

            if (count($deleted) > 0) {
                $message = count($deleted) > 1
                    ? count($deleted) . ' pengajuan berhasil dihapus'
                    : 'Pengajuan berhasil dihapus';

                if (count($errors) > 0) {
                    $message .= '. ' . implode('. ', $errors);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                $errorMessage = implode('. ', $errors);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('RegistrationApprovalController: Error in destroySelected', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus pengajuan.',
            ], 500);
        }
    }

    /**
     * Get status label
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'draft'     => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'pending'   => 'Menunggu Persetujuan', 
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            default     => ucfirst($status),
        };
    }
}
