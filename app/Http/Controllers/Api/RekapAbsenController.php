<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRekapAbsenRequest;
use App\Http\Requests\Api\UpdateRekapAbsenRequest;
use App\Models\ProgramLatihan;
use App\Models\RekapAbsenProgramLatihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RekapAbsenController extends Controller
{
    /**
     * Get list rekap absen berdasarkan program_latihan_id
     */
    public function index(Request $request, $programId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat rekap absen.',
                ], 403);
            }
            
            // Cek program latihan
            $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($programId);
            
            // Apply role-based filtering - cek apakah user punya akses ke program ini
            $hasAccess = $this->checkProgramAccess($programLatihan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }
            
            // Ambil semua rekap absen
            $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $programId)
                ->orderBy('tanggal', 'desc')
                ->get();
            
            // Format response
            $formattedData = $rekapAbsen->map(function ($rekap) {
                return [
                    'id' => $rekap->id,
                    'tanggal' => $rekap->tanggal,
                    'jenis_latihan' => $rekap->jenis_latihan,
                    'keterangan' => $rekap->keterangan,
                    'foto_absen' => $rekap->getMedia('foto_absen')->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                            'name' => $media->name,
                        ];
                    })->toArray(),
                    'file_nilai' => $rekap->getMedia('file_nilai')->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                            'name' => $media->name,
                        ];
                    })->toArray(),
                    'created_at' => $rekap->created_at,
                    'updated_at' => $rekap->updated_at,
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedData,
                'program_latihan' => [
                    'id' => $programLatihan->id,
                    'nama_program' => $programLatihan->nama_program,
                    'cabor' => $programLatihan->cabor ? [
                        'id' => $programLatihan->cabor->id,
                        'nama' => $programLatihan->cabor->nama,
                    ] : null,
                    'cabor_kategori' => $programLatihan->caborKategori ? [
                        'id' => $programLatihan->caborKategori->id,
                        'nama' => $programLatihan->caborKategori->nama,
                    ] : null,
                ],
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Rekap Absen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_id' => $programId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil rekap absen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get rekap absen untuk tanggal hari ini
     */
    public function getToday(Request $request, $programId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat rekap absen.',
                ], 403);
            }
            
            // Cek program latihan
            $programLatihan = ProgramLatihan::findOrFail($programId);
            
            // Apply role-based filtering
            $hasAccess = $this->checkProgramAccess($programLatihan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }
            
            $today = now()->format('Y-m-d');
            
            // Cari rekap absen untuk hari ini
            $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $programId)
                ->where('tanggal', $today)
                ->first();
            
            if (!$rekapAbsen) {
                return response()->json([
                    'status' => 'success',
                    'data' => null,
                    'message' => 'Belum ada rekap absen untuk hari ini.',
                ]);
            }
            
            // Format response
            $formattedData = [
                'id' => $rekapAbsen->id,
                'tanggal' => $rekapAbsen->tanggal,
                'jenis_latihan' => $rekapAbsen->jenis_latihan,
                'keterangan' => $rekapAbsen->keterangan,
                'foto_absen' => $rekapAbsen->getMedia('foto_absen')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray(),
                'file_nilai' => $rekapAbsen->getMedia('file_nilai')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray(),
                'created_at' => $rekapAbsen->created_at,
                'updated_at' => $rekapAbsen->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Rekap Absen Today error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_id' => $programId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil rekap absen hari ini.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Create rekap absen (hanya untuk tanggal hari ini)
     */
    public function store(StoreRekapAbsenRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk membuat rekap absen.',
                ], 403);
            }
            
            $programLatihan = ProgramLatihan::findOrFail($request->program_latihan_id);
            
            // Apply role-based filtering
            $hasAccess = $this->checkProgramAccess($programLatihan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }
            
            // Validasi: Hanya bisa input untuk tanggal hari ini (sudah di validation request)
            $today = now()->format('Y-m-d');
            if ($request->tanggal !== $today) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya dapat input rekap absen untuk tanggal hari ini.',
                ], 422);
            }
            
            // Cek apakah tanggal dalam range periode
            if ($request->tanggal < $programLatihan->periode_mulai || $request->tanggal > $programLatihan->periode_selesai) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tanggal harus dalam periode program latihan.',
                ], 422);
            }
            
            // Cari atau buat rekap absen
            $rekapAbsen = RekapAbsenProgramLatihan::firstOrCreate(
                [
                    'program_latihan_id' => $request->program_latihan_id,
                    'tanggal' => $request->tanggal,
                ],
                [
                    'jenis_latihan' => $request->jenis_latihan,
                    'keterangan' => $request->keterangan,
                    'created_by' => $user->id,
                ]
            );
            
            // Update jika sudah ada
            if (!$rekapAbsen->wasRecentlyCreated) {
                $rekapAbsen->update([
                    'jenis_latihan' => $request->jenis_latihan,
                    'keterangan' => $request->keterangan,
                    'updated_by' => $user->id,
                ]);
            }
            
            // Upload foto absen (multiple)
            if ($request->hasFile('foto_absen')) {
                foreach ($request->file('foto_absen') as $foto) {
                    $rekapAbsen->addMedia($foto)
                        ->usingName('Foto Absen ' . $request->tanggal)
                        ->toMediaCollection('foto_absen');
                }
            }
            
            // Upload file nilai (multiple)
            if ($request->hasFile('file_nilai')) {
                foreach ($request->file('file_nilai') as $file) {
                    $rekapAbsen->addMedia($file)
                        ->usingName('File Nilai ' . $request->tanggal)
                        ->toMediaCollection('file_nilai');
                }
            }
            
            // Reload dengan media
            $rekapAbsen->load('media');
            
            // Format response
            $formattedData = [
                'id' => $rekapAbsen->id,
                'tanggal' => $rekapAbsen->tanggal,
                'jenis_latihan' => $rekapAbsen->jenis_latihan,
                'keterangan' => $rekapAbsen->keterangan,
                'foto_absen' => $rekapAbsen->getMedia('foto_absen')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray(),
                'file_nilai' => $rekapAbsen->getMedia('file_nilai')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray(),
                'created_at' => $rekapAbsen->created_at,
                'updated_at' => $rekapAbsen->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Rekap absen berhasil disimpan.',
                'data' => $formattedData,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Store Rekap Absen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_id' => $request->program_latihan_id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan rekap absen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Update rekap absen (hanya untuk tanggal hari ini)
     */
    public function update(UpdateRekapAbsenRequest $request, $programId, $rekapId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengupdate rekap absen.',
                ], 403);
            }
            
            $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $programId)
                ->findOrFail($rekapId);
            
            // Apply role-based filtering
            $programLatihan = $rekapAbsen->programLatihan;
            $hasAccess = $this->checkProgramAccess($programLatihan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }
            
            // Validasi: Hanya bisa update untuk tanggal hari ini
            $today = now()->format('Y-m-d');
            if ($rekapAbsen->tanggal !== $today) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya dapat mengupdate rekap absen untuk tanggal hari ini.',
                ], 422);
            }
            
            // Handle file upload SEBELUM validated() untuk memastikan file tidak hilang
            // Sama seperti di ProfileController
            $allFiles = $request->allFiles();
            $fotoFiles = [];
            $fileNilaiFiles = [];
            
            // Deteksi foto_absen dengan berbagai cara
            // Handle multiple files dengan key foto_absen[] atau foto_absen
            // Prioritas: cek allFiles dulu karena lebih reliable untuk PUT dengan multipart/form-data
            if (!empty($allFiles)) {
                // Iterate semua file dan cari yang sesuai dengan foto_absen
                foreach ($allFiles as $key => $file) {
                    $keyLower = strtolower($key);
                    // Handle key seperti "foto_absen", "foto_absen[]", atau key yang mengandung "foto" dan "absen"
                    if (str_contains($keyLower, 'foto') && str_contains($keyLower, 'absen')) {
                        if (is_array($file)) {
                            // Jika array, tambahkan semua file
                            foreach ($file as $f) {
                                if ($f instanceof \Illuminate\Http\UploadedFile) {
                                    $fotoFiles[] = $f;
                                }
                            }
                        } elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                            $fotoFiles[] = $file;
                        }
                    }
                }
            }
            
            // Fallback: cek dengan hasFile dan file() method
            if (empty($fotoFiles) && $request->hasFile('foto_absen')) {
                $files = $request->file('foto_absen');
                $fotoFiles = is_array($files) ? array_values($files) : [$files];
            } elseif (empty($fotoFiles) && isset($allFiles['foto_absen'])) {
                $files = $allFiles['foto_absen'];
                $fotoFiles = is_array($files) ? array_values($files) : [$files];
            }
            
            // Deteksi file_nilai dengan berbagai cara
            if ($request->hasFile('file_nilai')) {
                $files = $request->file('file_nilai');
                $fileNilaiFiles = is_array($files) ? array_values($files) : [$files];
            } elseif (isset($allFiles['file_nilai'])) {
                $files = $allFiles['file_nilai'];
                $fileNilaiFiles = is_array($files) ? array_values($files) : [$files];
            } elseif (!empty($allFiles)) {
                // Fallback: cari semua file yang mungkin file_nilai
                foreach ($allFiles as $key => $file) {
                    if (is_array($file)) {
                        foreach ($file as $f) {
                            if ($f && (str_contains(strtolower($key), 'nilai') || str_contains(strtolower($key), 'file'))) {
                                // Pastikan tidak duplikat dengan foto_absen
                                if (!in_array($f, $fotoFiles, true)) {
                                    $fileNilaiFiles[] = $f;
                                }
                            }
                        }
                    } elseif (str_contains(strtolower($key), 'nilai') || str_contains(strtolower($key), 'file')) {
                        // Pastikan tidak duplikat dengan foto_absen
                        if (!in_array($file, $fotoFiles, true)) {
                            $fileNilaiFiles[] = $file;
                        }
                    }
                }
            }
            
            // Filter hanya file yang valid (instance of UploadedFile)
            $fotoFiles = array_filter($fotoFiles, function($file) {
                return $file instanceof \Illuminate\Http\UploadedFile;
            });
            
            $fileNilaiFiles = array_filter($fileNilaiFiles, function($file) {
                return $file instanceof \Illuminate\Http\UploadedFile;
            });
            
            // Urutan operasi: DELETE dulu, baru ADD
            // 1. Delete media yang diminta (jika ada)
            $deletedMediaIds = $request->input('deleted_media_ids', []);
            if (!empty($deletedMediaIds) && is_array($deletedMediaIds)) {
                foreach ($deletedMediaIds as $mediaId) {
                    if ($mediaId) {
                        // Cari di semua collection (foto_absen dan file_nilai)
                        $media = $rekapAbsen->getMedia('foto_absen')->find($mediaId);
                        if (!$media) {
                            $media = $rekapAbsen->getMedia('file_nilai')->find($mediaId);
                        }
                        if ($media) {
                            $media->delete();
                        }
                    }
                }
                // Refresh setelah delete untuk memastikan media terhapus dari cache
                $rekapAbsen->refresh();
            }
            
            // 2. Update data (hanya field yang ada di request)
            $updateData = [
                'updated_by' => $user->id,
            ];
            
            // Handle jenis_latihan - hanya update jika ada di request
            if ($request->filled('jenis_latihan')) {
                $updateData['jenis_latihan'] = $request->jenis_latihan;
            }
            
            if ($request->has('keterangan')) {
                $updateData['keterangan'] = $request->keterangan;
            }
            
            // Hanya update jika ada data yang diubah
            if (count($updateData) > 1) {
                $rekapAbsen->update($updateData);
            }
            
            // 3. Upload foto absen baru (multiple) - SETELAH delete
            if (!empty($fotoFiles)) {
                foreach ($fotoFiles as $foto) {
                    if ($foto) {
                        $rekapAbsen->addMedia($foto)
                            ->usingName('Foto Absen ' . $rekapAbsen->tanggal)
                            ->toMediaCollection('foto_absen');
                    }
                }
                $rekapAbsen->touch(); // Update timestamps to trigger media save
            }
            
            // 4. Upload file nilai baru (multiple) - SETELAH delete
            if (!empty($fileNilaiFiles)) {
                foreach ($fileNilaiFiles as $file) {
                    if ($file) {
                        $rekapAbsen->addMedia($file)
                            ->usingName('File Nilai ' . $rekapAbsen->tanggal)
                            ->toMediaCollection('file_nilai');
                    }
                }
                $rekapAbsen->touch(); // Update timestamps to trigger media save
            }
            
            // Force reload media collection - sama seperti di ProfileController
            // Pastikan media ter-reload dengan fresh data (termasuk yang baru di-upload)
            $rekapAbsen->refresh();
            $rekapAbsen->unsetRelation('media');
            $rekapAbsen->load('media');
            
            // Reload ulang dari database untuk memastikan semua media ter-include (termasuk yang baru)
            $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $programId)
                ->findOrFail($rekapId);
            $rekapAbsen->load('media');
            
            // Format response - pastikan mengembalikan SEMUA media (existing yang tidak dihapus + baru)
            $formattedData = [
                'id' => $rekapAbsen->id,
                'tanggal' => $rekapAbsen->tanggal,
                'jenis_latihan' => $rekapAbsen->jenis_latihan,
                'keterangan' => $rekapAbsen->keterangan,
                'foto_absen' => $rekapAbsen->getMedia('foto_absen')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray(),
                'file_nilai' => $rekapAbsen->getMedia('file_nilai')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray(),
                'created_at' => $rekapAbsen->created_at,
                'updated_at' => $rekapAbsen->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Rekap absen berhasil diperbarui.',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Update Rekap Absen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_id' => $programId,
                'rekap_id' => $rekapId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui rekap absen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Delete media dari rekap absen
     */
    public function deleteMedia(Request $request, $programId, $rekapId, $mediaId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus media.',
                ], 403);
            }
            
            $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $programId)
                ->findOrFail($rekapId);
            
            // Apply role-based filtering
            $programLatihan = $rekapAbsen->programLatihan;
            $hasAccess = $this->checkProgramAccess($programLatihan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }
            
            // Validasi: Hanya bisa delete media untuk tanggal hari ini
            $today = now()->format('Y-m-d');
            if ($rekapAbsen->tanggal !== $today) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya dapat menghapus media untuk rekap absen tanggal hari ini.',
                ], 422);
            }
            
            $media = $rekapAbsen->getMedia()->find($mediaId);
            if (!$media) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Media tidak ditemukan.',
                ], 404);
            }
            
            $media->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Media berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Media Rekap Absen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_id' => $programId,
                'rekap_id' => $rekapId,
                'media_id' => $mediaId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus media.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Check apakah user punya akses ke program latihan
     */
    private function checkProgramAccess(ProgramLatihan $programLatihan, $user): bool
    {
        $roleId = $user->current_role_id ?? null;
        
        // Non-peserta (Superadmin, Admin) - bisa akses semua
        if (!in_array($roleId, [35, 36, 37])) {
            return true;
        }
        
        // Load relasi user
        $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
        
        // Peserta (Atlet, Pelatih, Tenaga Pendukung) - cek apakah punya akses ke cabor kategori program
        if ($roleId == 35 && $user->atlet && $user->atlet->id) { // Atlet
            return \App\Models\CaborKategoriAtlet::where('atlet_id', $user->atlet->id)
                ->where('cabor_id', $programLatihan->cabor_id)
                ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                ->whereNull('deleted_at')
                ->exists();
        }
        
        if ($roleId == 36 && $user->pelatih && $user->pelatih->id) { // Pelatih
            return \App\Models\CaborKategoriPelatih::where('pelatih_id', $user->pelatih->id)
                ->where('cabor_id', $programLatihan->cabor_id)
                ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                ->whereNull('deleted_at')
                ->exists();
        }
        
        if ($roleId == 37 && $user->tenagaPendukung && $user->tenagaPendukung->id) { // Tenaga Pendukung
            return \App\Models\CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                ->where('cabor_id', $programLatihan->cabor_id)
                ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                ->whereNull('deleted_at')
                ->exists();
        }
        
        return false;
    }
}

