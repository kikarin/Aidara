<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkUpdatePesertaParameterRequest;
use App\Http\Requests\Api\StorePemeriksaanRequest;
use App\Http\Requests\Api\UpdatePemeriksaanRequest;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanParameter;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanPesertaParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PemeriksaanController extends Controller
{
    /**
     * Get list pemeriksaan
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat pemeriksaan.',
                ], 403);
            }
            
            // Query dasar - exclude soft deleted
            $query = Pemeriksaan::withoutGlobalScopes()
                ->whereNull('pemeriksaan.deleted_at')
                ->with([
                    'cabor',
                    'caborKategori',
                    'tenagaPendukung',
                ])
                ->withCount([
                    'pemeriksaanParameter as jumlah_parameter' => function ($q) {
                        $q->whereNull('pemeriksaan_parameter.deleted_at');
                    },
                    'pemeriksaanPeserta as jumlah_peserta' => function ($q) {
                        $q->whereNull('pemeriksaan_peserta.deleted_at');
                    },
                    'pemeriksaanPeserta as jumlah_atlet' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\Atlet')
                            ->whereNull('pemeriksaan_peserta.deleted_at');
                    },
                    'pemeriksaanPeserta as jumlah_pelatih' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\Pelatih')
                            ->whereNull('pemeriksaan_peserta.deleted_at');
                    },
                    'pemeriksaanPeserta as jumlah_tenaga_pendukung' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\TenagaPendukung')
                            ->whereNull('pemeriksaan_peserta.deleted_at');
                    },
                ]);
            
            // Apply role-based filtering
            $this->applyRoleBasedFiltering($query, $user);
            
            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_pemeriksaan', 'like', "%{$search}%")
                        ->orWhereHas('cabor', function ($caborQuery) use ($search) {
                            $caborQuery->where('nama', 'like', "%{$search}%");
                        })
                        ->orWhereHas('caborKategori', function ($kategoriQuery) use ($search) {
                            $kategoriQuery->where('nama', 'like', "%{$search}%");
                        })
                        ->orWhereHas('tenagaPendukung', function ($tenagaQuery) use ($search) {
                            $tenagaQuery->where('nama', 'like', "%{$search}%");
                        });
                });
            }
            
            // Filter by cabor_id
            if ($request->has('cabor_id') && $request->cabor_id && $request->cabor_id !== 'all') {
                $query->where('cabor_id', $request->cabor_id);
            }
            
            // Filter by cabor_kategori_id
            if ($request->has('cabor_kategori_id') && $request->cabor_kategori_id && $request->cabor_kategori_id !== 'all') {
                $query->where('cabor_kategori_id', $request->cabor_kategori_id);
            }
            
            // Filter by tanggal_pemeriksaan
            if ($request->has('tanggal_pemeriksaan') && $request->tanggal_pemeriksaan) {
                $query->whereDate('tanggal_pemeriksaan', $request->tanggal_pemeriksaan);
            }
            
            // Filter by status
            if ($request->has('status') && $request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Filter by date range
            if ($request->has('filter_start_date') && $request->has('filter_end_date')) {
                $query->whereBetween('tanggal_pemeriksaan', [
                    $request->filter_start_date,
                    $request->filter_end_date,
                ]);
            }
            
            // Sorting
            $sortField = $request->get('sort', 'id');
            $sortOrder = $request->get('order', 'desc');
            $validColumns = ['id', 'nama_pemeriksaan', 'tanggal_pemeriksaan', 'status', 'created_at', 'updated_at'];
            
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }
            
            // Pagination
            $perPage = (int) $request->get('per_page', 10);
            $page = (int) $request->get('page', 1);
            
            if ($perPage === -1) {
                $items = $query->get();
            } else {
                $items = $query->paginate($perPage, ['*'], 'page', $page);
            }
            
            // Format response
            $itemsArray = $items instanceof \Illuminate\Pagination\LengthAwarePaginator ? $items->items() : $items->all();
            
            $formattedData = collect($itemsArray)
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'cabor' => $item->cabor ? [
                            'id' => $item->cabor->id,
                            'nama' => $item->cabor->nama,
                        ] : null,
                        'cabor_kategori' => $item->caborKategori ? [
                            'id' => $item->caborKategori->id,
                            'nama' => $item->caborKategori->nama,
                        ] : null,
                        'tenaga_pendukung' => $item->tenagaPendukung ? [
                            'id' => $item->tenagaPendukung->id,
                            'nama' => $item->tenagaPendukung->nama,
                        ] : null,
                        'nama_pemeriksaan' => $item->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $item->tanggal_pemeriksaan,
                        'status' => $item->status,
                        'jumlah_parameter' => $item->jumlah_parameter ?? 0,
                        'jumlah_peserta' => $item->jumlah_peserta ?? 0,
                        'jumlah_atlet' => $item->jumlah_atlet ?? 0,
                        'jumlah_pelatih' => $item->jumlah_pelatih ?? 0,
                        'jumlah_tenaga_pendukung' => $item->jumlah_tenaga_pendukung ?? 0,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ];
                });
            
            $response = [
                'status' => 'success',
                'data' => $formattedData,
                'permissions' => $permissions,
            ];
            
            if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $response['meta'] = [
                    'total' => $items->total(),
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'last_page' => $items->lastPage(),
                ];
            } else {
                $response['meta'] = [
                    'total' => $formattedData->count(),
                    'current_page' => 1,
                    'per_page' => -1,
                    'last_page' => 1,
                ];
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Get Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil pemeriksaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Create pemeriksaan baru
     * Auto-insert peserta dari cabor kategori
     */
    public function store(StorePemeriksaanRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Add')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk membuat pemeriksaan.',
                ], 403);
            }
            
            // Check akses ke cabor kategori
            $hasAccess = $this->checkCaborKategoriAccess($request->cabor_id, $request->cabor_kategori_id, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke cabor kategori ini.',
                ], 403);
            }
            
            // Create pemeriksaan
            $pemeriksaan = Pemeriksaan::create([
                'cabor_id' => $request->cabor_id,
                'cabor_kategori_id' => $request->cabor_kategori_id,
                'tenaga_pendukung_id' => $request->tenaga_pendukung_id,
                'nama_pemeriksaan' => $request->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
                'status' => $request->status ?? 'belum',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            
            // 1. Simpan parameter pemeriksaan
            if (!empty($request->parameter_ids) && is_array($request->parameter_ids)) {
                foreach ($request->parameter_ids as $parameterId) {
                    PemeriksaanParameter::create([
                        'pemeriksaan_id' => $pemeriksaan->id,
                        'mst_parameter_id' => $parameterId,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }
            
            // 2. Auto-insert peserta dari cabor kategori (yang aktif)
            $caborKategoriId = $pemeriksaan->cabor_kategori_id;
            
            // Get Atlet aktif di kategori ini
            $atletIds = \App\Models\CaborKategoriAtlet::where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('atlet_id')
                ->unique()
                ->filter();
            
            foreach ($atletIds as $atletId) {
                PemeriksaanPeserta::create([
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'peserta_id' => $atletId,
                    'peserta_type' => 'App\\Models\\Atlet',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            
            // Get Pelatih aktif di kategori ini
            $pelatihIds = \App\Models\CaborKategoriPelatih::where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('pelatih_id')
                ->unique()
                ->filter();
            
            foreach ($pelatihIds as $pelatihId) {
                PemeriksaanPeserta::create([
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'peserta_id' => $pelatihId,
                    'peserta_type' => 'App\\Models\\Pelatih',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            
            // Get Tenaga Pendukung aktif di kategori ini
            $tenagaIds = \App\Models\CaborKategoriTenagaPendukung::where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('tenaga_pendukung_id')
                ->unique()
                ->filter();
            
            foreach ($tenagaIds as $tenagaId) {
                PemeriksaanPeserta::create([
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'peserta_id' => $tenagaId,
                    'peserta_type' => 'App\\Models\\TenagaPendukung',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            
            // Reload dengan relasi
            $pemeriksaan->load([
                'cabor',
                'caborKategori',
                'tenagaPendukung',
            ])
            ->loadCount([
                'pemeriksaanParameter as jumlah_parameter',
                'pemeriksaanPeserta as jumlah_peserta',
            ]);
            
            // Format response
            $formattedData = [
                'id' => $pemeriksaan->id,
                'cabor' => $pemeriksaan->cabor ? [
                    'id' => $pemeriksaan->cabor->id,
                    'nama' => $pemeriksaan->cabor->nama,
                ] : null,
                'cabor_kategori' => $pemeriksaan->caborKategori ? [
                    'id' => $pemeriksaan->caborKategori->id,
                    'nama' => $pemeriksaan->caborKategori->nama,
                ] : null,
                'tenaga_pendukung' => $pemeriksaan->tenagaPendukung ? [
                    'id' => $pemeriksaan->tenagaPendukung->id,
                    'nama' => $pemeriksaan->tenagaPendukung->nama,
                ] : null,
                'nama_pemeriksaan' => $pemeriksaan->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                'status' => $pemeriksaan->status,
                'jumlah_parameter' => $pemeriksaan->jumlah_parameter ?? 0,
                'jumlah_peserta' => $pemeriksaan->jumlah_peserta ?? 0,
                'created_at' => $pemeriksaan->created_at,
                'updated_at' => $pemeriksaan->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pemeriksaan berhasil dibuat. Peserta dari cabor kategori telah otomatis ditambahkan.',
                'data' => $formattedData,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Store Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat pemeriksaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Update pemeriksaan
     */
    public function update(UpdatePemeriksaanRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengupdate pemeriksaan.',
                ], 403);
            }
            
            $pemeriksaan = Pemeriksaan::findOrFail($id);
            
            // Check akses ke pemeriksaan ini
            $hasAccess = $this->checkPemeriksaanAccess($pemeriksaan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan ini.',
                ], 403);
            }
            
            // Update data
            $updateData = [
                'updated_by' => $user->id,
            ];
            
            if ($request->filled('cabor_id')) {
                $updateData['cabor_id'] = $request->cabor_id;
            }
            
            if ($request->filled('cabor_kategori_id')) {
                $updateData['cabor_kategori_id'] = $request->cabor_kategori_id;
            }
            
            if ($request->filled('tenaga_pendukung_id')) {
                $updateData['tenaga_pendukung_id'] = $request->tenaga_pendukung_id;
            }
            
            if ($request->filled('nama_pemeriksaan')) {
                $updateData['nama_pemeriksaan'] = $request->nama_pemeriksaan;
            }
            
            if ($request->filled('tanggal_pemeriksaan')) {
                $updateData['tanggal_pemeriksaan'] = $request->tanggal_pemeriksaan;
            }
            
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            
            $pemeriksaan->update($updateData);
            
            // Reload dengan relasi
            $pemeriksaan->load([
                'cabor',
                'caborKategori',
                'tenagaPendukung',
            ])
            ->loadCount([
                'pemeriksaanParameter as jumlah_parameter',
                'pemeriksaanPeserta as jumlah_peserta',
            ]);
            
            // Format response
            $formattedData = [
                'id' => $pemeriksaan->id,
                'cabor' => $pemeriksaan->cabor ? [
                    'id' => $pemeriksaan->cabor->id,
                    'nama' => $pemeriksaan->cabor->nama,
                ] : null,
                'cabor_kategori' => $pemeriksaan->caborKategori ? [
                    'id' => $pemeriksaan->caborKategori->id,
                    'nama' => $pemeriksaan->caborKategori->nama,
                ] : null,
                'tenaga_pendukung' => $pemeriksaan->tenagaPendukung ? [
                    'id' => $pemeriksaan->tenagaPendukung->id,
                    'nama' => $pemeriksaan->tenagaPendukung->nama,
                ] : null,
                'nama_pemeriksaan' => $pemeriksaan->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                'status' => $pemeriksaan->status,
                'jumlah_parameter' => $pemeriksaan->jumlah_parameter ?? 0,
                'jumlah_peserta' => $pemeriksaan->jumlah_peserta ?? 0,
                'created_at' => $pemeriksaan->created_at,
                'updated_at' => $pemeriksaan->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pemeriksaan berhasil diperbarui.',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Update Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui pemeriksaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Delete pemeriksaan
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Delete')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus pemeriksaan.',
                ], 403);
            }
            
            $pemeriksaan = Pemeriksaan::findOrFail($id);
            
            // Check akses ke pemeriksaan ini
            $hasAccess = $this->checkPemeriksaanAccess($pemeriksaan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan ini.',
                ], 403);
            }
            
            $pemeriksaan->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pemeriksaan berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus pemeriksaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Apply role-based filtering
     */
    private function applyRoleBasedFiltering($query, $user): void
    {
        $roleId = $user->current_role_id ?? null;
        
        // Jika bukan peserta (admin/superadmin), tidak perlu filter - bisa lihat semua
        if (!in_array($roleId, [35, 36, 37])) {
            return;
        }
        
        if ($roleId == 35) { // Atlet
            if ($user->atlet && $user->atlet->id) {
                $query->whereHas('caborKategori', function ($subQuery) use ($user) {
                    $subQuery->whereHas('caborKategoriAtlet', function ($subSubQuery) use ($user) {
                        $subSubQuery->where('atlet_id', $user->atlet->id)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_atlet.deleted_at');
                    });
                });
            }
        }
        
        if ($roleId == 36) { // Pelatih
            if ($user->pelatih && $user->pelatih->id) {
                $query->whereHas('caborKategori', function ($subQuery) use ($user) {
                    $subQuery->whereHas('caborKategoriPelatih', function ($subSubQuery) use ($user) {
                        $subSubQuery->where('pelatih_id', $user->pelatih->id)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_pelatih.deleted_at');
                    });
                });
            }
        }
        
        if ($roleId == 37) { // Tenaga Pendukung
            if ($user->tenagaPendukung && $user->tenagaPendukung->id) {
                $query->whereHas('caborKategori', function ($subQuery) use ($user) {
                    $subQuery->whereHas('caborKategoriTenagaPendukung', function ($subSubQuery) use ($user) {
                        $subSubQuery->where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                    });
                });
            }
        }
    }
    
    /**
     * Check apakah user punya akses ke cabor kategori
     */
    private function checkCaborKategoriAccess($caborId, $caborKategoriId, $user): bool
    {
        $roleId = $user->current_role_id ?? null;
        
        // Non-peserta (Superadmin, Admin) - bisa akses semua
        if (!in_array($roleId, [35, 36, 37])) {
            return true;
        }
        
        $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
        
        // Peserta - cek apakah punya akses ke cabor kategori
        if ($roleId == 35 && $user->atlet && $user->atlet->id) { // Atlet
            return \App\Models\CaborKategoriAtlet::where('atlet_id', $user->atlet->id)
                ->where('cabor_id', $caborId)
                ->where('cabor_kategori_id', $caborKategoriId)
                ->whereNull('deleted_at')
                ->exists();
        }
        
        if ($roleId == 36 && $user->pelatih && $user->pelatih->id) { // Pelatih
            return \App\Models\CaborKategoriPelatih::where('pelatih_id', $user->pelatih->id)
                ->where('cabor_id', $caborId)
                ->where('cabor_kategori_id', $caborKategoriId)
                ->whereNull('deleted_at')
                ->exists();
        }
        
        if ($roleId == 37 && $user->tenagaPendukung && $user->tenagaPendukung->id) { // Tenaga Pendukung
            return \App\Models\CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                ->where('cabor_id', $caborId)
                ->where('cabor_kategori_id', $caborKategoriId)
                ->whereNull('deleted_at')
                ->exists();
        }
        
        return false;
    }
    
    /**
     * Check apakah user punya akses ke pemeriksaan
     */
    private function checkPemeriksaanAccess(Pemeriksaan $pemeriksaan, $user): bool
    {
        return $this->checkCaborKategoriAccess(
            $pemeriksaan->cabor_id,
            $pemeriksaan->cabor_kategori_id,
            $user
        );
    }
    
    /**
     * Get detail pemeriksaan
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat pemeriksaan.',
                ], 403);
            }
            
            $pemeriksaan = Pemeriksaan::withoutGlobalScopes()
                ->with([
                    'cabor',
                    'caborKategori',
                    'tenagaPendukung',
                ])
                ->withCount([
                    'pemeriksaanParameter as jumlah_parameter',
                    'pemeriksaanPeserta as jumlah_peserta',
                    'pemeriksaanPeserta as jumlah_atlet' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\Atlet');
                    },
                    'pemeriksaanPeserta as jumlah_pelatih' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\Pelatih');
                    },
                    'pemeriksaanPeserta as jumlah_tenaga_pendukung' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\TenagaPendukung');
                    },
                ])
                ->findOrFail($id);
            
            // Check akses ke pemeriksaan ini
            $hasAccess = $this->checkPemeriksaanAccess($pemeriksaan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan ini.',
                ], 403);
            }
            
            // Get parameter pemeriksaan
            $parameters = PemeriksaanParameter::where('pemeriksaan_id', $id)
                ->with('mstParameter')
                ->orderBy('id')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nama' => $item->mstParameter->nama ?? '-',
                        'satuan' => $item->mstParameter->satuan ?? '-',
                    ];
                });
            
            // Get peserta dengan informasi lengkap
            $caborKategoriId = $pemeriksaan->cabor_kategori_id;
            $pesertaList = PemeriksaanPeserta::where('pemeriksaan_id', $id)
                ->with('peserta')
                ->get();
            
            $formattedPeserta = $pesertaList->map(function ($peserta) use ($caborKategoriId) {
                $pesertaModel = $peserta->peserta;
                
                if (!$pesertaModel) {
                    return null;
                }
                
                // Calculate age
                $usia = null;
                if ($pesertaModel->tanggal_lahir) {
                    $today = new \DateTime();
                    $birthDate = new \DateTime($pesertaModel->tanggal_lahir);
                    $usia = $today->diff($birthDate)->y;
                }
                
                // Get posisi/jenis berdasarkan peserta_type
                $posisi = null;
                $role = null;
                
                if ($peserta->peserta_type === 'App\\Models\\Atlet') {
                    $role = 'atlet';
                    // Get posisi dari cabor_kategori_atlet
                    $caborKategoriAtlet = \App\Models\CaborKategoriAtlet::where('atlet_id', $pesertaModel->id)
                        ->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('deleted_at')
                        ->first();
                    $posisi = $caborKategoriAtlet->posisi_atlet ?? null;
                } elseif ($peserta->peserta_type === 'App\\Models\\Pelatih') {
                    $role = 'pelatih';
                    // Get jenis pelatih dari cabor_kategori_pelatih
                    $caborKategoriPelatih = \App\Models\CaborKategoriPelatih::where('pelatih_id', $pesertaModel->id)
                        ->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('deleted_at')
                        ->first();
                    $posisi = $caborKategoriPelatih->jenis_pelatih ?? null;
                } elseif ($peserta->peserta_type === 'App\\Models\\TenagaPendukung') {
                    $role = 'tenaga-pendukung';
                    // Get jenis tenaga pendukung dari cabor_kategori_tenaga_pendukung
                    $caborKategoriTenaga = \App\Models\CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $pesertaModel->id)
                        ->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('deleted_at')
                        ->first();
                    $posisi = $caborKategoriTenaga->jenis_tenaga_pendukung ?? null;
                }
                
                return [
                    'id' => $peserta->id,
                    'nama' => $pesertaModel->nama ?? '-',
                    'jenis_kelamin' => $pesertaModel->jenis_kelamin ?? null,
                    'usia' => $usia,
                    'posisi' => $posisi,
                    'role' => $role,
                    'biodata_id' => $pesertaModel->id,
                ];
            })->filter(function ($item) {
                return $item !== null;
            })->values();
            
            // Format response
            $formattedData = [
                'id' => $pemeriksaan->id,
                'cabor' => $pemeriksaan->cabor ? [
                    'id' => $pemeriksaan->cabor->id,
                    'nama' => $pemeriksaan->cabor->nama,
                ] : null,
                'cabor_kategori' => $pemeriksaan->caborKategori ? [
                    'id' => $pemeriksaan->caborKategori->id,
                    'nama' => $pemeriksaan->caborKategori->nama,
                ] : null,
                'tenaga_pendukung' => $pemeriksaan->tenagaPendukung ? [
                    'id' => $pemeriksaan->tenagaPendukung->id,
                    'nama' => $pemeriksaan->tenagaPendukung->nama,
                ] : null,
                'nama_pemeriksaan' => $pemeriksaan->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                'status' => $pemeriksaan->status,
                'jumlah_parameter' => $pemeriksaan->jumlah_parameter ?? 0,
                'jumlah_peserta' => $pemeriksaan->jumlah_peserta ?? 0,
                'jumlah_atlet' => $pemeriksaan->jumlah_atlet ?? 0,
                'jumlah_pelatih' => $pemeriksaan->jumlah_pelatih ?? 0,
                'jumlah_tenaga_pendukung' => $pemeriksaan->jumlah_tenaga_pendukung ?? 0,
                'parameter' => $parameters,
                'peserta' => $formattedPeserta,
                'hasil_pemeriksaan' => [], // Optional, bisa dikembangkan nanti
                'created_at' => $pemeriksaan->created_at,
                'updated_at' => $pemeriksaan->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Detail Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil detail pemeriksaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get list peserta pemeriksaan dengan parameter mereka
     */
    public function getPesertaWithParameter(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat pemeriksaan.',
                ], 403);
            }
            
            $pemeriksaan = Pemeriksaan::findOrFail($id);
            
            // Check akses ke pemeriksaan ini
            $hasAccess = $this->checkPemeriksaanAccess($pemeriksaan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan ini.',
                ], 403);
            }
            
            // Get jenis peserta dari query (optional: atlet, pelatih, tenaga-pendukung)
            $jenisPeserta = $request->get('jenis_peserta', 'all'); // all, atlet, pelatih, tenaga-pendukung
            
            $pesertaTypeMap = [
                'atlet' => 'App\\Models\\Atlet',
                'pelatih' => 'App\\Models\\Pelatih',
                'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
            ];
            
            // Get parameter pemeriksaan
            $parameters = PemeriksaanParameter::where('pemeriksaan_id', $id)
                ->with('mstParameter')
                ->orderBy('id')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'parameter_id' => $item->mst_parameter_id,
                        'nama_parameter' => $item->mstParameter->nama ?? '-',
                        'satuan' => $item->mstParameter->satuan ?? '-',
                    ];
                });
            
            // Get peserta
            $query = PemeriksaanPeserta::where('pemeriksaan_id', $id)
                ->with([
                    'peserta',
                    'status',
                    'pemeriksaanPesertaParameter' => function ($q) {
                        $q->with('pemeriksaanParameter.mstParameter');
                    },
                ]);
            
            // Filter by jenis peserta jika bukan 'all'
            if ($jenisPeserta !== 'all' && isset($pesertaTypeMap[$jenisPeserta])) {
                $query->where('peserta_type', $pesertaTypeMap[$jenisPeserta]);
            }
            
            $pesertaList = $query->get();
            
            // Format response
            $formattedPeserta = $pesertaList->map(function ($peserta) use ($parameters) {
                $pesertaModel = $peserta->peserta;
                
                // Get parameter values - match dengan format yang diharapkan frontend
                $parameterValues = $parameters->map(function ($param) use ($peserta) {
                    $pesertaParam = $peserta->pemeriksaanPesertaParameter
                        ->where('pemeriksaan_parameter_id', $param['id'])
                        ->first();
                    
                    return [
                        'parameter_id' => $param['id'],
                        'mst_parameter_id' => $param['parameter_id'],
                        'nama_parameter' => $param['nama_parameter'],
                        'satuan' => $param['satuan'],
                        'nilai' => $pesertaParam ? ($pesertaParam->nilai ?? '') : '',
                        'trend' => $pesertaParam ? ($pesertaParam->trend ?? 'stabil') : 'stabil',
                    ];
                });
                
                // Calculate age
                $usia = null;
                if ($pesertaModel && $pesertaModel->tanggal_lahir) {
                    $today = new \DateTime();
                    $birthDate = new \DateTime($pesertaModel->tanggal_lahir);
                    $usia = $today->diff($birthDate)->y;
                }
                
                return [
                    'id' => $peserta->id,
                    'peserta_id' => $pesertaModel->id ?? null,
                    'peserta_type' => $peserta->peserta_type,
                    'peserta' => [
                        'id' => $pesertaModel->id ?? null,
                        'nama' => $pesertaModel->nama ?? '-',
                        'jenis_kelamin' => $pesertaModel->jenis_kelamin ?? null,
                        'tanggal_lahir' => $pesertaModel->tanggal_lahir ?? null,
                        'usia' => $usia,
                    ],
                    'status' => $peserta->status ? [
                        'id' => $peserta->status->id,
                        'nama' => $peserta->status->nama,
                    ] : null,
                    'ref_status_pemeriksaan_id' => $peserta->ref_status_pemeriksaan_id,
                    'catatan_umum' => $peserta->catatan_umum ?? '',
                    'pemeriksaanPesertaParameter' => $peserta->pemeriksaanPesertaParameter->map(function ($pp) {
                        return [
                            'id' => $pp->id,
                            'pemeriksaan_parameter_id' => $pp->pemeriksaan_parameter_id,
                            'nilai' => $pp->nilai,
                            'trend' => $pp->trend,
                        ];
                    }),
                    'parameters' => $parameterValues,
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedPeserta,
                'parameters' => $parameters,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Peserta With Parameter error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data peserta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Bulk update nilai parameter dan status pemeriksaan peserta
     */
    public function bulkUpdatePesertaParameter(BulkUpdatePesertaParameterRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengupdate pemeriksaan.',
                ], 403);
            }
            
            $pemeriksaan = Pemeriksaan::findOrFail($id);
            
            // Check akses ke pemeriksaan ini
            $hasAccess = $this->checkPemeriksaanAccess($pemeriksaan, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan ini.',
                ], 403);
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($request->data as $pesertaData) {
                    $peserta = PemeriksaanPeserta::where('pemeriksaan_id', $id)
                        ->findOrFail($pesertaData['peserta_id']);
                    
                    // Update status dan catatan
                    $updateData = [
                        'updated_by' => $user->id,
                    ];
                    
                    if (isset($pesertaData['status'])) {
                        $updateData['ref_status_pemeriksaan_id'] = $pesertaData['status'];
                    }
                    
                    if (isset($pesertaData['catatan'])) {
                        $updateData['catatan_umum'] = $pesertaData['catatan'];
                    }
                    
                    $peserta->update($updateData);
                    
                    // Update atau create parameter
                    foreach ($pesertaData['parameters'] as $param) {
                        PemeriksaanPesertaParameter::updateOrCreate(
                            [
                                'pemeriksaan_id' => $id,
                                'pemeriksaan_peserta_id' => $pesertaData['peserta_id'],
                                'pemeriksaan_parameter_id' => $param['parameter_id'],
                            ],
                            [
                                'nilai' => $param['nilai'] ?? null,
                                'trend' => $param['trend'] ?? 'stabil',
                                'created_by' => $user->id,
                                'updated_by' => $user->id,
                            ]
                        );
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil disimpan.',
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Bulk Update Peserta Parameter error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get statistik parameter per peserta
     * Endpoint: GET /api/v1/pemeriksaan/statistik/parameter/{parameter_id}
     */
    public function getStatistikParameter(Request $request, $parameterId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat statistik parameter.',
                ], 403);
            }
            
            // Get query parameters
            $caborId = $request->query('cabor_id');
            $caborKategoriId = $request->query('cabor_kategori_id');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            // Get pemeriksaan_parameter untuk mendapatkan info parameter
            $pemeriksaanParameter = PemeriksaanParameter::with('mstParameter')
                ->findOrFail($parameterId);
            
            if (!$pemeriksaanParameter->mstParameter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parameter tidak ditemukan.',
                ], 404);
            }
            
            // Ambil mst_parameter_id dari pemeriksaan_parameter yang dipilih
            $mstParameterId = $pemeriksaanParameter->mst_parameter_id;
            
            // Ambil semua pemeriksaan_parameter yang memiliki mst_parameter_id yang sama
            // Ini penting karena setiap pemeriksaan memiliki pemeriksaan_parameter sendiri
            // meskipun mst_parameter_id-nya sama
            $allPemeriksaanParameterIds = PemeriksaanParameter::withoutGlobalScopes()
                ->where('mst_parameter_id', $mstParameterId)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();
            
            if (empty($allPemeriksaanParameterIds)) {
                return response()->json([
                    'status' => 'success',
                    'data' => [],
                    'parameter' => [
                        'id' => $pemeriksaanParameter->id,
                        'nama' => $pemeriksaanParameter->mstParameter->nama ?? '-',
                        'satuan' => $pemeriksaanParameter->mstParameter->satuan ?? '-',
                    ],
                ]);
            }
            
            // Query dasar dari pemeriksaan_peserta_parameter
            // Ambil semua data yang memiliki pemeriksaan_parameter_id dari list di atas
            $query = PemeriksaanPesertaParameter::withoutGlobalScopes()
                ->whereIn('pemeriksaan_parameter_id', $allPemeriksaanParameterIds)
                ->whereNull('pemeriksaan_peserta_parameter.deleted_at')
                ->join('pemeriksaan_peserta', 'pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', '=', 'pemeriksaan_peserta.id')
                ->whereNull('pemeriksaan_peserta.deleted_at')
                ->join('pemeriksaan', 'pemeriksaan_peserta_parameter.pemeriksaan_id', '=', 'pemeriksaan.id')
                ->whereNull('pemeriksaan.deleted_at')
                ->select([
                    'pemeriksaan_peserta_parameter.*',
                    'pemeriksaan_peserta.peserta_id',
                    'pemeriksaan_peserta.peserta_type',
                    'pemeriksaan.tanggal_pemeriksaan',
                    'pemeriksaan.cabor_id',
                    'pemeriksaan.cabor_kategori_id',
                ]);
            
            // Apply filters
            if ($caborId) {
                $query->where('pemeriksaan.cabor_id', $caborId);
            }
            
            if ($caborKategoriId) {
                $query->where('pemeriksaan.cabor_kategori_id', $caborKategoriId);
            }
            
            if ($startDate) {
                $query->whereDate('pemeriksaan.tanggal_pemeriksaan', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->whereDate('pemeriksaan.tanggal_pemeriksaan', '<=', $endDate);
            }
            
            // Get all data
            $data = $query->orderBy('pemeriksaan.tanggal_pemeriksaan', 'asc')
                ->get();
            
            // Pre-load pemeriksaan dengan cabor dan kategori untuk menghindari N+1 query
            $pemeriksaanIds = $data->pluck('pemeriksaan_id')->unique();
            $pemeriksaanMap = Pemeriksaan::with(['cabor', 'caborKategori'])
                ->whereIn('id', $pemeriksaanIds)
                ->get()
                ->keyBy('id');
            
            // Pre-load peserta models untuk menghindari N+1 query
            $pesertaMap = [];
            foreach ($data->groupBy('peserta_type') as $pesertaType => $items) {
                $pesertaIds = $items->pluck('peserta_id')->unique();
                
                if ($pesertaType === 'App\\Models\\Atlet') {
                    $pesertaMap[$pesertaType] = \App\Models\Atlet::whereIn('id', $pesertaIds)
                        ->get()
                        ->keyBy('id');
                } elseif ($pesertaType === 'App\\Models\\Pelatih') {
                    $pesertaMap[$pesertaType] = \App\Models\Pelatih::whereIn('id', $pesertaIds)
                        ->get()
                        ->keyBy('id');
                } elseif ($pesertaType === 'App\\Models\\TenagaPendukung') {
                    $pesertaMap[$pesertaType] = \App\Models\TenagaPendukung::whereIn('id', $pesertaIds)
                        ->get()
                        ->keyBy('id');
                }
            }
            
            // Group by peserta
            $groupedByPeserta = [];
            
            foreach ($data as $item) {
                $pesertaKey = $item->peserta_type . '_' . $item->peserta_id;
                
                if (!isset($groupedByPeserta[$pesertaKey])) {
                    // Get peserta model from map
                    $pesertaModel = $pesertaMap[$item->peserta_type][$item->peserta_id] ?? null;
                    
                    if (!$pesertaModel) {
                        continue;
                    }
                    
                    $role = null;
                    $biodataId = $item->peserta_id;
                    
                    if ($item->peserta_type === 'App\\Models\\Atlet') {
                        $role = 'atlet';
                    } elseif ($item->peserta_type === 'App\\Models\\Pelatih') {
                        $role = 'pelatih';
                    } elseif ($item->peserta_type === 'App\\Models\\TenagaPendukung') {
                        $role = 'tenaga-pendukung';
                    }
                    
                    // Get cabor and kategori from pemeriksaan (ambil dari pemeriksaan pertama/terlama)
                    $pemeriksaan = $pemeriksaanMap[$item->pemeriksaan_id] ?? null;
                    
                    $groupedByPeserta[$pesertaKey] = [
                        'peserta_id' => $item->pemeriksaan_peserta_id,
                        'biodata_id' => $biodataId,
                        'nama' => $pesertaModel->nama ?? '-',
                        'cabor' => [
                            'id' => $pemeriksaan->cabor_id ?? null,
                            'nama' => $pemeriksaan->cabor->nama ?? '-',
                        ],
                        'cabor_kategori' => [
                            'id' => $pemeriksaan->cabor_kategori_id ?? null,
                            'nama' => $pemeriksaan->caborKategori->nama ?? '-',
                        ],
                        'role' => $role,
                        'history' => [],
                    ];
                }
                
                // Get pemeriksaan untuk tanggal
                $pemeriksaan = $pemeriksaanMap[$item->pemeriksaan_id] ?? null;
                $tanggalPemeriksaan = $pemeriksaan->tanggal_pemeriksaan ?? null;
                
                // Format tanggal
                $tanggalFormatted = null;
                if ($tanggalPemeriksaan) {
                    $tanggalFormatted = set_date($tanggalPemeriksaan, 'd M y');
                }
                
                // Get satuan dari mst_parameter
                $satuan = $pemeriksaanParameter->mstParameter->satuan ?? '-';
                
                // Add to history
                $groupedByPeserta[$pesertaKey]['history'][] = [
                    'pemeriksaan_id' => $item->pemeriksaan_id,
                    'tanggal_pemeriksaan' => $tanggalPemeriksaan ? date('Y-m-d', strtotime($tanggalPemeriksaan)) : null,
                    'tanggal_formatted' => $tanggalFormatted,
                    'nilai' => $item->nilai,
                    'trend' => $item->trend,
                    'satuan' => $satuan,
                ];
            }
            
            // Convert to array and sort history by date
            $result = [];
            foreach ($groupedByPeserta as $pesertaData) {
                // Sort history by tanggal_pemeriksaan (ascending - oldest first)
                usort($pesertaData['history'], function ($a, $b) {
                    if ($a['tanggal_pemeriksaan'] == $b['tanggal_pemeriksaan']) {
                        return 0;
                    }
                    return ($a['tanggal_pemeriksaan'] < $b['tanggal_pemeriksaan']) ? -1 : 1;
                });
                
                $result[] = $pesertaData;
            }
            
            // Sort result by nama peserta
            usort($result, function ($a, $b) {
                return strcmp($a['nama'], $b['nama']);
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $result,
                'parameter' => [
                    'id' => $pemeriksaanParameter->id,
                    'nama' => $pemeriksaanParameter->mstParameter->nama ?? '-',
                    'satuan' => $pemeriksaanParameter->mstParameter->satuan ?? '-',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Statistik Parameter error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'parameter_id' => $parameterId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik parameter.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

