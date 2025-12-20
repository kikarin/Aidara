<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDokumenRequest;
use App\Http\Requests\Api\StorePrestasiRequest;
use App\Http\Requests\Api\StoreSertifikatRequest;
use App\Http\Requests\Api\UpdateBiodataRequest;
use App\Models\Atlet;
use App\Models\AtletDokumen;
use App\Models\AtletPrestasi;
use App\Models\AtletSertifikat;
use App\Models\Pelatih;
use App\Models\PelatihDokumen;
use App\Models\PelatihPrestasi;
use App\Models\PelatihSertifikat;
use App\Models\TenagaPendukung;
use App\Models\TenagaPendukungDokumen;
use App\Models\TenagaPendukungPrestasi;
use App\Models\TenagaPendukungSertifikat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Get biodata sesuai role user yang login
     */
    public function getBiodata(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Show")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat biodata.',
                ], 403);
            }

            // Get peserta data berdasarkan role
            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            // Format response berdasarkan role
            $biodata = $this->formatBiodataResponse($peserta, $user->peserta_type);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'role' => $user->peserta_type,
                    'biodata' => $biodata,
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Biodata error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil biodata.',
            ], 500);
        }
    }

    /**
     * Update biodata sesuai role user yang login
     */
    public function updateBiodata(UpdateBiodataRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Edit")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengedit biodata.',
                ], 403);
            }

            // Get peserta data
            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            // Check ownership
            if ($peserta->users_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengedit data ini.',
                ], 403);
            }

            $data = $request->validated();
            
            // Handle file upload
            if ($request->hasFile('file')) {
                $data['file'] = $request->file('file');
            }

            // Handle delete foto
            if ($request->has('is_delete_foto') && $request->is_delete_foto) {
                $data['is_delete_foto'] = true;
            }

            // Update berdasarkan role dengan field spesifik
            $this->updatePesertaBiodata($peserta, $data, $user->peserta_type);

            // Reload peserta dengan relasi yang diperlukan
            $peserta->refresh();
            
            // Reload dengan relasi sesuai role
            switch ($user->peserta_type) {
                case 'atlet':
                    $peserta->load([
                        'kecamatan', 
                        'kelurahan', 
                        'kategoriAtlet', 
                        'posisiAtlet',
                        'caborKategoriAtlet' => function($query) {
                            $query->whereNull('deleted_at')
                                ->with(['cabor', 'caborKategori']);
                        },
                        'kategoriPesertas',
                    ]);
                    break;
                case 'pelatih':
                    $peserta->load([
                        'kecamatan', 
                        'kelurahan', 
                        'jenisPelatih',
                        'caborKategoriPelatih' => function($query) {
                            $query->whereNull('deleted_at')
                                ->with(['cabor', 'caborKategori']);
                        },
                        'kategoriPesertas',
                    ]);
                    break;
                case 'tenaga_pendukung':
                    $peserta->load([
                        'kecamatan', 
                        'kelurahan',
                        'caborKategoriTenagaPendukung' => function($query) {
                            $query->whereNull('deleted_at')
                                ->with(['cabor', 'caborKategori']);
                        },
                        'kategoriPesertas',
                    ]);
                    break;
            }
            
            $biodata = $this->formatBiodataResponse($peserta, $user->peserta_type);

            Log::info('Biodata updated', [
                'user_id' => $user->id,
                'peserta_type' => $user->peserta_type,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Biodata berhasil diperbarui.',
                'data' => [
                    'role' => $user->peserta_type,
                    'biodata' => $biodata,
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Update Biodata error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
                'request_data' => $request->except(['file', 'password']),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui biodata.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get list sertifikat
     */
    public function getSertifikat(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Sertifikat Show")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat sertifikat.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            $sertifikat = $peserta->sertifikat()->with('created_by_user', 'updated_by_user')->get();
            
            $formattedSertifikat = $sertifikat->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_sertifikat' => $item->nama_sertifikat,
                    'penyelenggara' => $item->penyelenggara,
                    'tanggal_terbit' => $item->tanggal_terbit,
                    'file_url' => $item->file_url ?? null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'sertifikat' => $formattedSertifikat,
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Sertifikat error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil sertifikat.',
            ], 500);
        }
    }

    /**
     * Store sertifikat
     */
    public function storeSertifikat(StoreSertifikatRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Sertifikat Add")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menambah sertifikat.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            $data = $request->validated();
            $file = $request->file('file');
            
            // Create sertifikat berdasarkan role
            $sertifikat = $this->createSertifikat($peserta, $data, $file, $user->peserta_type);

            Log::info('Sertifikat created', [
                'user_id' => $user->id,
                'peserta_type' => $user->peserta_type,
                'sertifikat_id' => $sertifikat->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Sertifikat berhasil ditambahkan.',
                'data' => [
                    'sertifikat' => [
                        'id' => $sertifikat->id,
                        'nama_sertifikat' => $sertifikat->nama_sertifikat,
                        'penyelenggara' => $sertifikat->penyelenggara,
                        'tanggal_terbit' => $sertifikat->tanggal_terbit,
                        'file_url' => $sertifikat->file_url ?? null,
                    ],
                    'permissions' => $permissions,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Store Sertifikat error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambah sertifikat.',
            ], 500);
        }
    }

    /**
     * Delete sertifikat
     */
    public function deleteSertifikat(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Sertifikat Delete")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus sertifikat.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            // Get sertifikat dan check ownership
            $sertifikat = $this->getSertifikatById($id, $user->peserta_type);
            
            if (!$sertifikat) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sertifikat tidak ditemukan.',
                ], 404);
            }

            // Check ownership
            if (!$this->checkSertifikatOwnership($sertifikat, $peserta, $user->peserta_type)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus sertifikat ini.',
                ], 403);
            }

            $sertifikat->forceDelete();

            Log::info('Sertifikat deleted', [
                'user_id' => $user->id,
                'sertifikat_id' => $id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Sertifikat berhasil dihapus.',
                'data' => [
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Sertifikat error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus sertifikat.',
            ], 500);
        }
    }

    /**
     * Get list prestasi
     */
    public function getPrestasi(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Prestasi Show")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat prestasi.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            $prestasi = $peserta->prestasi()->with([
                'tingkat', 
                'kategoriPeserta',
                'created_by_user', 
                'updated_by_user'
            ])->get();
            
            $formattedPrestasi = $prestasi->map(function ($item) use ($user) {
                $data = [
                    'id' => $item->id,
                    'nama_event' => $item->nama_event,
                    'tingkat' => $item->tingkat ? [
                        'id' => $item->tingkat->id,
                        'nama' => $item->tingkat->nama,
                    ] : null,
                    'tanggal' => $item->tanggal,
                    'peringkat' => $item->peringkat, // Legacy field, masih support
                    'juara' => $item->juara ?? null, // Field baru
                    'medali' => $item->medali ?? null, // Field baru
                    'jenis_prestasi' => $item->jenis_prestasi ?? 'individu', // Field baru
                    'kategori_peserta' => $item->kategoriPeserta ? [
                        'id' => $item->kategoriPeserta->id,
                        'nama' => $item->kategoriPeserta->nama,
                    ] : null, // Field baru
                    'keterangan' => $item->keterangan,
                    'bonus' => $item->bonus ?? 0,
                    'prestasi_group_id' => $item->prestasi_group_id ?? null, // Untuk beregu
                ];

                // Tambahkan field khusus untuk Pelatih
                if ($user->peserta_type === 'pelatih' && method_exists($item, 'kategoriPrestasiPelatih')) {
                    $data['kategori_prestasi_pelatih'] = $item->kategoriPrestasiPelatih ? [
                        'id' => $item->kategoriPrestasiPelatih->id,
                        'nama' => $item->kategoriPrestasiPelatih->nama,
                    ] : null;
                    $data['kategori_atlet'] = $item->kategoriAtlet ? [
                        'id' => $item->kategoriAtlet->id,
                        'nama' => $item->kategoriAtlet->nama,
                    ] : null;
                }

                // Untuk beregu, load anggota jika ada
                if ($item->jenis_prestasi === 'ganda/mixed/beregu/double' && $item->prestasi_group_id) {
                    // Load anggota beregu (hanya untuk atlet)
                    if ($user->peserta_type === 'atlet' && method_exists($item, 'anggotaBeregu')) {
                        $item->load('anggotaBeregu.atlet');
                        $data['anggota_beregu'] = $item->anggotaBeregu->map(function ($anggota) {
                            return [
                                'id' => $anggota->atlet->id ?? null,
                                'nama' => $anggota->atlet->nama ?? null,
                            ];
                        })->filter(function ($anggota) {
                            return $anggota['id'] !== null;
                        })->values();
                    }
                }

                return $data;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'prestasi' => $formattedPrestasi,
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Prestasi error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil prestasi.',
            ], 500);
        }
    }

    /**
     * Store prestasi
     */
    public function storePrestasi(StorePrestasiRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Prestasi Add")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menambah prestasi.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            $data = $request->validated();
            
            // Validasi anggota_beregu jika ada
            if (isset($data['anggota_beregu']) && is_array($data['anggota_beregu'])) {
                // Validasi bahwa semua ID anggota valid berdasarkan role
                $anggotaBeregu = $data['anggota_beregu'];
                $validIds = [];
                
                switch ($user->peserta_type) {
                    case 'atlet':
                        $validIds = Atlet::whereIn('id', $anggotaBeregu)
                            ->whereNull('deleted_at')
                            ->pluck('id')
                            ->toArray();
                        break;
                    case 'pelatih':
                        $validIds = Pelatih::whereIn('id', $anggotaBeregu)
                            ->whereNull('deleted_at')
                            ->pluck('id')
                            ->toArray();
                        break;
                }
                
                // Filter hanya ID yang valid
                $data['anggota_beregu'] = array_intersect($anggotaBeregu, $validIds);
            }
            
            // Create prestasi berdasarkan role
            $prestasi = $this->createPrestasi($peserta, $data, $user->peserta_type);

            Log::info('Prestasi created', [
                'user_id' => $user->id,
                'peserta_type' => $user->peserta_type,
                'prestasi_id' => $prestasi->id,
            ]);

            // Reload dengan relasi yang diperlukan
            $prestasi->load('tingkat', 'kategoriPeserta');
            
            $formattedPrestasi = [
                'id' => $prestasi->id,
                'nama_event' => $prestasi->nama_event,
                'tingkat' => $prestasi->tingkat ? [
                    'id' => $prestasi->tingkat->id,
                    'nama' => $prestasi->tingkat->nama,
                ] : null,
                'tanggal' => $prestasi->tanggal,
                'peringkat' => $prestasi->peringkat, // Legacy field
                'juara' => $prestasi->juara ?? null, // Field baru
                'medali' => $prestasi->medali ?? null, // Field baru
                'jenis_prestasi' => $prestasi->jenis_prestasi ?? 'individu', // Field baru
                'kategori_peserta' => $prestasi->kategoriPeserta ? [
                    'id' => $prestasi->kategoriPeserta->id,
                    'nama' => $prestasi->kategoriPeserta->nama,
                ] : null, // Field baru
                'keterangan' => $prestasi->keterangan,
                'bonus' => $prestasi->bonus ?? 0,
                'prestasi_group_id' => $prestasi->prestasi_group_id ?? null, // Untuk beregu
            ];

            // Tambahkan field khusus untuk Pelatih
            if ($user->peserta_type === 'pelatih' && method_exists($prestasi, 'kategoriPrestasiPelatih')) {
                $prestasi->load('kategoriPrestasiPelatih', 'kategoriAtlet');
                $formattedPrestasi['kategori_prestasi_pelatih'] = $prestasi->kategoriPrestasiPelatih ? [
                    'id' => $prestasi->kategoriPrestasiPelatih->id,
                    'nama' => $prestasi->kategoriPrestasiPelatih->nama,
                ] : null;
                $formattedPrestasi['kategori_atlet'] = $prestasi->kategoriAtlet ? [
                    'id' => $prestasi->kategoriAtlet->id,
                    'nama' => $prestasi->kategoriAtlet->nama,
                ] : null;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Prestasi berhasil ditambahkan.',
                'data' => [
                    'prestasi' => $formattedPrestasi,
                    'permissions' => $permissions,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Store Prestasi error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambah prestasi.',
            ], 500);
        }
    }

    /**
     * Delete prestasi
     */
    public function deletePrestasi(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Prestasi Delete")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus prestasi.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            // Get prestasi dan check ownership
            $prestasi = $this->getPrestasiById($id, $user->peserta_type);
            
            if (!$prestasi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prestasi tidak ditemukan.',
                ], 404);
            }

            // Check ownership
            if (!$this->checkPrestasiOwnership($prestasi, $peserta, $user->peserta_type)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus prestasi ini.',
                ], 403);
            }

            $prestasi->forceDelete();

            Log::info('Prestasi deleted', [
                'user_id' => $user->id,
                'prestasi_id' => $id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Prestasi berhasil dihapus.',
                'data' => [
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Prestasi error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus prestasi.',
            ], 500);
        }
    }

    /**
     * Get list dokumen
     */
    public function getDokumen(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Dokumen Show")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat dokumen.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            $dokumen = $peserta->dokumen()->with('jenis_dokumen', 'created_by_user', 'updated_by_user')->get();
            
            $formattedDokumen = $dokumen->map(function ($item) {
                return [
                    'id' => $item->id,
                    'jenis_dokumen' => $item->jenis_dokumen ? [
                        'id' => $item->jenis_dokumen->id,
                        'nama' => $item->jenis_dokumen->nama,
                    ] : null,
                    'nomor' => $item->nomor,
                    'file_url' => $item->file_url ?? null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'dokumen' => $formattedDokumen,
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Dokumen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil dokumen.',
            ], 500);
        }
    }

    /**
     * Store dokumen
     */
    public function storeDokumen(StoreDokumenRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Dokumen Add")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menambah dokumen.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            $data = $request->validated();
            $file = $request->file('file');
            
            // Create dokumen berdasarkan role
            $dokumen = $this->createDokumen($peserta, $data, $file, $user->peserta_type);

            Log::info('Dokumen created', [
                'user_id' => $user->id,
                'peserta_type' => $user->peserta_type,
                'dokumen_id' => $dokumen->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen berhasil ditambahkan.',
                'data' => [
                    'dokumen' => [
                        'id' => $dokumen->id,
                        'jenis_dokumen' => $dokumen->jenis_dokumen ? [
                            'id' => $dokumen->jenis_dokumen->id,
                            'nama' => $dokumen->jenis_dokumen->nama,
                        ] : null,
                        'nomor' => $dokumen->nomor,
                        'file_url' => $dokumen->file_url ?? null,
                    ],
                    'permissions' => $permissions,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Store Dokumen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambah dokumen.',
            ], 500);
        }
    }

    /**
     * Delete dokumen
     */
    public function deleteDokumen(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            $roleName = $this->getRoleName($user);
            if (!Gate::allows("{$roleName} Dokumen Delete")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus dokumen.',
                ], 403);
            }

            $peserta = $this->getPesertaData($user);
            
            if (!$peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }

            // Get dokumen dan check ownership
            $dokumen = $this->getDokumenById($id, $user->peserta_type);
            
            if (!$dokumen) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dokumen tidak ditemukan.',
                ], 404);
            }

            // Check ownership
            if (!$this->checkDokumenOwnership($dokumen, $peserta, $user->peserta_type)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus dokumen ini.',
                ], 403);
            }

            $dokumen->forceDelete();

            Log::info('Dokumen deleted', [
                'user_id' => $user->id,
                'dokumen_id' => $id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen berhasil dihapus.',
                'data' => [
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Dokumen error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus dokumen.',
            ], 500);
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Get role name untuk permission check
     */
    private function getRoleName($user): string
    {
        $roleMap = [
            'atlet' => 'Atlet',
            'pelatih' => 'Pelatih',
            'tenaga_pendukung' => 'Tenaga Pendukung',
        ];

        return $roleMap[$user->peserta_type] ?? 'Atlet';
    }

    /**
     * Get peserta data berdasarkan user
     */
    private function getPesertaData($user)
    {
        switch ($user->peserta_type) {
            case 'atlet':
                return Atlet::where('users_id', $user->id)
                    ->with([
                        'kecamatan', 
                        'kelurahan', 
                        'kategoriAtlet', 
                        'posisiAtlet',
                        'caborKategoriAtlet' => function($query) {
                            $query->whereNull('deleted_at')
                                ->with(['cabor', 'caborKategori']);
                        },
                        'kategoriPesertas',
                    ])
                    ->first();
            case 'pelatih':
                return Pelatih::where('users_id', $user->id)
                    ->with([
                        'kecamatan', 
                        'kelurahan', 
                        'jenisPelatih',
                        'caborKategoriPelatih' => function($query) {
                            $query->whereNull('deleted_at')
                                ->with(['cabor', 'caborKategori']);
                        },
                        'kategoriPesertas',
                    ])
                    ->first();
            case 'tenaga_pendukung':
                return TenagaPendukung::where('users_id', $user->id)
                    ->with([
                        'kecamatan', 
                        'kelurahan',
                        'caborKategoriTenagaPendukung' => function($query) {
                            $query->whereNull('deleted_at')
                                ->with(['cabor', 'caborKategori']);
                        },
                        'kategoriPesertas',
                    ])
                    ->first();
            default:
                return null;
        }
    }

    /**
     * Format biodata response berdasarkan role
     */
    private function formatBiodataResponse($peserta, $pesertaType): array
    {
        $baseData = [
            'id' => $peserta->id,
            'nik' => $peserta->nik,
            'nama' => $peserta->nama,
            'jenis_kelamin' => $peserta->jenis_kelamin,
            'tempat_lahir' => $peserta->tempat_lahir,
            'tanggal_lahir' => $peserta->tanggal_lahir,
            'tanggal_bergabung' => $peserta->tanggal_bergabung,
            'alamat' => $peserta->alamat,
            'kecamatan' => $peserta->kecamatan ? [
                'id' => $peserta->kecamatan->id,
                'nama' => $peserta->kecamatan->nama,
            ] : null,
            'kelurahan' => $peserta->kelurahan ? [
                'id' => $peserta->kelurahan->id,
                'nama' => $peserta->kelurahan->nama,
            ] : null,
            'no_hp' => $peserta->no_hp,
            'email' => $peserta->email,
            'foto' => $peserta->foto,
            'foto_thumbnail' => $peserta->foto_thumbnail ?? null,
        ];

        // Field khusus untuk Atlet
        if ($pesertaType === 'atlet') {
            $baseData['nisn'] = $peserta->nisn ?? null;
            $baseData['agama'] = $peserta->agama ?? null;
            $baseData['sekolah'] = $peserta->sekolah ?? null;
            $baseData['kelas_sekolah'] = $peserta->kelas_sekolah ?? null;
            $baseData['ukuran_baju'] = $peserta->ukuran_baju ?? null;
            $baseData['ukuran_celana'] = $peserta->ukuran_celana ?? null;
            $baseData['ukuran_sepatu'] = $peserta->ukuran_sepatu ?? null;
            $baseData['disabilitas'] = $peserta->disabilitas ?? null;
            $baseData['klasifikasi'] = $peserta->klasifikasi ?? null;
            $baseData['iq'] = $peserta->iq ?? null;
            $baseData['kategori_atlet'] = $peserta->kategoriAtlet ? [
                'id' => $peserta->kategoriAtlet->id,
                'nama' => $peserta->kategoriAtlet->nama,
            ] : null;
            $baseData['posisi_atlet'] = $peserta->posisiAtlet ? [
                'id' => $peserta->posisiAtlet->id,
                'nama' => $peserta->posisiAtlet->nama,
            ] : null;
            
            // Cabor data (many-to-many) - format simple: hanya nama cabor unique
            $caborNames = $peserta->caborKategoriAtlet
                ->map(function ($item) {
                    return $item->cabor->nama ?? null;
                })
                ->filter(function ($nama) {
                    return $nama !== null;
                })
                ->unique()
                ->values()
                ->toArray();
            
            $baseData['cabor'] = $caborNames; // Array of strings: ["Renang", "TENIS MEJA TUNET"]
            
            // Kategori Peserta (many-to-many)
            $baseData['kategori_peserta'] = $peserta->kategoriPesertas->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                ];
            });
        }

        // Field khusus untuk Pelatih
        if ($pesertaType === 'pelatih') {
            $baseData['pekerjaan_selain_melatih'] = $peserta->pekerjaan_selain_melatih ?? null;
            $baseData['jenis_pelatih'] = $peserta->jenisPelatih ? [
                'id' => $peserta->jenisPelatih->id,
                'nama' => $peserta->jenisPelatih->nama,
            ] : null;
            
            // Cabor data (many-to-many) - format simple: hanya nama cabor unique
            $caborNames = $peserta->caborKategoriPelatih
                ->map(function ($item) {
                    return $item->cabor->nama ?? null;
                })
                ->filter(function ($nama) {
                    return $nama !== null;
                })
                ->unique()
                ->values()
                ->toArray();
            
            $baseData['cabor'] = $caborNames; // Array of strings: ["Renang", "TENIS MEJA TUNET"]
            
            // Kategori Peserta (many-to-many)
            $baseData['kategori_peserta'] = $peserta->kategoriPesertas->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                ];
            });
        }
        
        // Field khusus untuk Tenaga Pendukung
        if ($pesertaType === 'tenaga_pendukung') {
            // Cabor data (many-to-many) - format simple: hanya nama cabor unique
            $caborNames = $peserta->caborKategoriTenagaPendukung
                ->map(function ($item) {
                    return $item->cabor->nama ?? null;
                })
                ->filter(function ($nama) {
                    return $nama !== null;
                })
                ->unique()
                ->values()
                ->toArray();
            
            $baseData['cabor'] = $caborNames; // Array of strings: ["Renang", "TENIS MEJA TUNET"]
            
            // Kategori Peserta (many-to-many)
            $baseData['kategori_peserta'] = $peserta->kategoriPesertas->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                ];
            });
        }

        return $baseData;
    }

    /**
     * Update biodata peserta
     * Support partial update: hanya update field yang dikirim
     */
    private function updatePesertaBiodata($peserta, $data, $pesertaType): void
    {
        $updateData = [
            'updated_by' => auth()->id(),
        ];

        // Field yang required berdasarkan role (tidak boleh null di database)
        $requiredFields = [];
        if ($pesertaType === 'pelatih' || $pesertaType === 'tenaga_pendukung') {
            $requiredFields = ['nik', 'nama', 'jenis_kelamin'];
        } elseif ($pesertaType === 'atlet') {
            $requiredFields = ['nama', 'jenis_kelamin']; // nik nullable untuk atlet
        }

        // Hanya update field yang dikirim (partial update)
        $commonFields = [
            'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
            'tanggal_bergabung', 'alamat', 'kecamatan_id', 'kelurahan_id',
            'no_hp', 'email',
        ];

        foreach ($commonFields as $field) {
            if (array_key_exists($field, $data)) {
                // Skip field required jika nilainya null (untuk mencegah error NOT NULL constraint)
                if (in_array($field, $requiredFields) && $data[$field] === null) {
                    continue; // Jangan update field required ke null
                }
                $updateData[$field] = $data[$field];
            }
        }

        // Field khusus untuk Atlet
        if ($pesertaType === 'atlet') {
            $atletSpecificFields = [
                'nisn', 'agama', 'sekolah', 'kelas_sekolah',
                'ukuran_baju', 'ukuran_celana', 'ukuran_sepatu',
                'disabilitas', 'klasifikasi', 'iq',
            ];
            
            foreach ($atletSpecificFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        // Field khusus untuk Pelatih
        if ($pesertaType === 'pelatih') {
            if (isset($data['pekerjaan_selain_melatih'])) {
                $updateData['pekerjaan_selain_melatih'] = $data['pekerjaan_selain_melatih'];
            }
        }

        // Handle file upload
        if (isset($data['file'])) {
            $peserta->clearMediaCollection('images');
            $nama = $updateData['nama'] ?? $peserta->nama ?? 'Foto';
            $peserta->addMedia($data['file'])
                ->usingName($nama)
                ->toMediaCollection('images');
        }

        // Handle delete foto
        if (isset($data['is_delete_foto']) && $data['is_delete_foto']) {
            $peserta->clearMediaCollection('images');
        }

        // Update data (hanya jika ada field yang di-update)
        if (count($updateData) > 1) { // Lebih dari 1 karena ada 'updated_by'
            $peserta->update($updateData);
        }
    }

    /**
     * Create sertifikat berdasarkan role
     */
    private function createSertifikat($peserta, $data, $file, $pesertaType)
    {
        $sertifikatData = [
            'nama_sertifikat' => $data['nama_sertifikat'],
            'penyelenggara' => $data['penyelenggara'] ?? null,
            'tanggal_terbit' => $data['tanggal_terbit'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        switch ($pesertaType) {
            case 'atlet':
                $sertifikatData['atlet_id'] = $peserta->id;
                $sertifikat = AtletSertifikat::create($sertifikatData);
                break;
            case 'pelatih':
                $sertifikatData['pelatih_id'] = $peserta->id;
                $sertifikat = PelatihSertifikat::create($sertifikatData);
                break;
            case 'tenaga_pendukung':
                $sertifikatData['tenaga_pendukung_id'] = $peserta->id;
                $sertifikat = TenagaPendukungSertifikat::create($sertifikatData);
                break;
            default:
                throw new \Exception('Invalid peserta type');
        }

        if ($file) {
            $sertifikat->addMedia($file)
                ->usingName($data['nama_sertifikat'])
                ->toMediaCollection('sertifikat_file');
        }

        return $sertifikat;
    }

    /**
     * Get sertifikat by ID
     */
    private function getSertifikatById($id, $pesertaType)
    {
        switch ($pesertaType) {
            case 'atlet':
                return AtletSertifikat::withTrashed()->find($id);
            case 'pelatih':
                return PelatihSertifikat::withTrashed()->find($id);
            case 'tenaga_pendukung':
                return TenagaPendukungSertifikat::withTrashed()->find($id);
            default:
                return null;
        }
    }

    /**
     * Check sertifikat ownership
     */
    private function checkSertifikatOwnership($sertifikat, $peserta, $pesertaType): bool
    {
        switch ($pesertaType) {
            case 'atlet':
                return $sertifikat->atlet_id === $peserta->id;
            case 'pelatih':
                return $sertifikat->pelatih_id === $peserta->id;
            case 'tenaga_pendukung':
                return $sertifikat->tenaga_pendukung_id === $peserta->id;
            default:
                return false;
        }
    }

    /**
     * Create prestasi berdasarkan role
     */
    private function createPrestasi($peserta, $data, $pesertaType)
    {
        $prestasiData = [
            'nama_event' => $data['nama_event'],
            'tingkat_id' => $data['tingkat_id'] ?? null,
            'tanggal' => $data['tanggal'] ?? null,
            'peringkat' => $data['peringkat'] ?? null, // Legacy field
            'juara' => $data['juara'] ?? null, // Field baru
            'medali' => $data['medali'] ?? null, // Field baru
            'jenis_prestasi' => $data['jenis_prestasi'] ?? 'individu', // Field baru
            'kategori_peserta_id' => $data['kategori_peserta_id'] ?? null, // Field baru
            'keterangan' => $data['keterangan'] ?? null,
            'bonus' => $data['bonus'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        $prestasi = null;
        $anggotaBeregu = $data['anggota_beregu'] ?? [];

        switch ($pesertaType) {
            case 'atlet':
                $prestasiData['atlet_id'] = $peserta->id;
                $prestasi = AtletPrestasi::create($prestasiData);
                
                // Handle beregu jika jenis_prestasi = ganda/mixed/beregu/double
                if ($prestasiData['jenis_prestasi'] === 'ganda/mixed/beregu/double' && !empty($anggotaBeregu)) {
                    // Set prestasi_group_id ke id prestasi utama
                    $prestasi->prestasi_group_id = $prestasi->id;
                    $prestasi->save();
                    
                    // Create prestasi untuk setiap anggota beregu
                    foreach ($anggotaBeregu as $atletId) {
                        // Skip jika atlet_id sama dengan peserta (sudah dibuat sebagai prestasi utama)
                        if ($atletId == $peserta->id) {
                            continue;
                        }
                        
                        // Create prestasi untuk anggota beregu
                        $anggotaPrestasiData = $prestasiData;
                        $anggotaPrestasiData['atlet_id'] = $atletId;
                        $anggotaPrestasiData['prestasi_group_id'] = $prestasi->id;
                        $anggotaPrestasiData['created_by'] = auth()->id();
                        $anggotaPrestasiData['updated_by'] = auth()->id();
                        
                        AtletPrestasi::create($anggotaPrestasiData);
                    }
                }
                
                return $prestasi;
                
            case 'pelatih':
                $prestasiData['pelatih_id'] = $peserta->id;
                $prestasiData['kategori_prestasi_pelatih_id'] = $data['kategori_prestasi_pelatih_id'] ?? null;
                $prestasiData['kategori_atlet_id'] = $data['kategori_atlet_id'] ?? null;
                $prestasi = PelatihPrestasi::create($prestasiData);
                
                // Handle beregu untuk pelatih (jika diperlukan)
                if ($prestasiData['jenis_prestasi'] === 'ganda/mixed/beregu/double' && !empty($anggotaBeregu)) {
                    $prestasi->prestasi_group_id = $prestasi->id;
                    $prestasi->save();
                    
                    // Create prestasi untuk setiap anggota beregu (pelatih lain)
                    foreach ($anggotaBeregu as $pelatihId) {
                        if ($pelatihId == $peserta->id) {
                            continue;
                        }
                        
                        $anggotaPrestasiData = $prestasiData;
                        $anggotaPrestasiData['pelatih_id'] = $pelatihId;
                        $anggotaPrestasiData['prestasi_group_id'] = $prestasi->id;
                        $anggotaPrestasiData['created_by'] = auth()->id();
                        $anggotaPrestasiData['updated_by'] = auth()->id();
                        
                        PelatihPrestasi::create($anggotaPrestasiData);
                    }
                }
                
                return $prestasi;
                
            case 'tenaga_pendukung':
                $prestasiData['tenaga_pendukung_id'] = $peserta->id;
                return TenagaPendukungPrestasi::create($prestasiData);
                
            default:
                throw new \Exception('Invalid peserta type');
        }
    }

    /**
     * Get prestasi by ID
     */
    private function getPrestasiById($id, $pesertaType)
    {
        switch ($pesertaType) {
            case 'atlet':
                return AtletPrestasi::withTrashed()->find($id);
            case 'pelatih':
                return PelatihPrestasi::withTrashed()->find($id);
            case 'tenaga_pendukung':
                return TenagaPendukungPrestasi::withTrashed()->find($id);
            default:
                return null;
        }
    }

    /**
     * Check prestasi ownership
     */
    private function checkPrestasiOwnership($prestasi, $peserta, $pesertaType): bool
    {
        switch ($pesertaType) {
            case 'atlet':
                return $prestasi->atlet_id === $peserta->id;
            case 'pelatih':
                return $prestasi->pelatih_id === $peserta->id;
            case 'tenaga_pendukung':
                return $prestasi->tenaga_pendukung_id === $peserta->id;
            default:
                return false;
        }
    }

    /**
     * Create dokumen berdasarkan role
     */
    private function createDokumen($peserta, $data, $file, $pesertaType)
    {
        $dokumenData = [
            'jenis_dokumen_id' => $data['jenis_dokumen_id'] ?? null,
            'nomor' => $data['nomor'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        switch ($pesertaType) {
            case 'atlet':
                $dokumenData['atlet_id'] = $peserta->id;
                $dokumen = AtletDokumen::create($dokumenData);
                break;
            case 'pelatih':
                $dokumenData['pelatih_id'] = $peserta->id;
                $dokumen = PelatihDokumen::create($dokumenData);
                break;
            case 'tenaga_pendukung':
                $dokumenData['tenaga_pendukung_id'] = $peserta->id;
                $dokumen = TenagaPendukungDokumen::create($dokumenData);
                break;
            default:
                throw new \Exception('Invalid peserta type');
        }

        if ($file) {
            $dokumen->addMedia($file)
                ->usingName($data['nomor'] ?? 'Dokumen')
                ->toMediaCollection('dokumen_file');
        }

        return $dokumen;
    }

    /**
     * Get dokumen by ID
     */
    private function getDokumenById($id, $pesertaType)
    {
        switch ($pesertaType) {
            case 'atlet':
                return AtletDokumen::withTrashed()->find($id);
            case 'pelatih':
                return PelatihDokumen::withTrashed()->find($id);
            case 'tenaga_pendukung':
                return TenagaPendukungDokumen::withTrashed()->find($id);
            default:
                return null;
        }
    }

    /**
     * Check dokumen ownership
     */
    private function checkDokumenOwnership($dokumen, $peserta, $pesertaType): bool
    {
        switch ($pesertaType) {
            case 'atlet':
                return $dokumen->atlet_id === $peserta->id;
            case 'pelatih':
                return $dokumen->pelatih_id === $peserta->id;
            case 'tenaga_pendukung':
                return $dokumen->tenaga_pendukung_id === $peserta->id;
            default:
                return false;
        }
    }
}

