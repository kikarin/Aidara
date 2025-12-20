<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePemeriksaanKhususRequest;
use App\Http\Requests\Api\UpdatePemeriksaanKhususRequest;
use App\Http\Requests\Api\SavePemeriksaanKhususSetupRequest;
use App\Http\Requests\Api\SavePemeriksaanKhususTemplateRequest;
use App\Http\Requests\Api\SavePemeriksaanKhususHasilTesRequest;
use App\Models\PemeriksaanKhusus;
use App\Models\PemeriksaanKhususPeserta;
use App\Models\MstTemplatePemeriksaanKhususAspek;
use App\Repositories\PemeriksaanKhususRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PemeriksaanKhususController extends Controller
{
    /**
     * Get list pemeriksaan khusus
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat pemeriksaan khusus.',
                ], 403);
            }
            
            // Query dasar - exclude soft deleted
            $query = PemeriksaanKhusus::withoutGlobalScopes()
                ->whereNull('pemeriksaan_khusus.deleted_at')
                ->with([
                    'cabor',
                    'caborKategori',
                ])
                ->withCount([
                    'pemeriksaanKhususPeserta as jumlah_peserta' => function ($q) {
                        $q->whereNull('pemeriksaan_khusus_peserta.deleted_at');
                    },
                    'pemeriksaanKhususPeserta as jumlah_atlet' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\Atlet')
                            ->whereNull('pemeriksaan_khusus_peserta.deleted_at');
                    },
                    'pemeriksaanKhususPeserta as jumlah_pelatih' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\Pelatih')
                            ->whereNull('pemeriksaan_khusus_peserta.deleted_at');
                    },
                    'pemeriksaanKhususPeserta as jumlah_tenaga_pendukung' => function ($q) {
                        $q->where('peserta_type', 'App\\Models\\TenagaPendukung')
                            ->whereNull('pemeriksaan_khusus_peserta.deleted_at');
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
            
            // Filter by jenis peserta
            if ($request->has('jenis_peserta') && $request->jenis_peserta && $request->jenis_peserta !== 'all') {
                $pesertaType = null;
                if ($request->jenis_peserta === 'atlet') {
                    $pesertaType = 'App\\Models\\Atlet';
                } elseif ($request->jenis_peserta === 'pelatih') {
                    $pesertaType = 'App\\Models\\Pelatih';
                } elseif ($request->jenis_peserta === 'tenaga-pendukung') {
                    $pesertaType = 'App\\Models\\TenagaPendukung';
                }
                
                if ($pesertaType) {
                    $query->whereHas('pemeriksaanKhususPeserta', function ($q) use ($pesertaType) {
                        $q->where('peserta_type', $pesertaType)
                            ->whereNull('pemeriksaan_khusus_peserta.deleted_at');
                    });
                }
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
                        'nama_pemeriksaan' => $item->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $item->tanggal_pemeriksaan,
                        'status' => $item->status,
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
            Log::error('Get Pemeriksaan Khusus error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil pemeriksaan khusus.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Create pemeriksaan khusus baru
     * Auto-insert peserta dari cabor kategori
     */
    public function store(StorePemeriksaanKhususRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Add')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk membuat pemeriksaan khusus.',
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
            
            DB::beginTransaction();
            
            try {
                // Create pemeriksaan khusus
                $pemeriksaanKhusus = PemeriksaanKhusus::create([
                    'cabor_id' => $request->cabor_id,
                    'cabor_kategori_id' => $request->cabor_kategori_id,
                    'nama_pemeriksaan' => $request->nama_pemeriksaan,
                    'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
                    'status' => $request->status ?? 'belum',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                
                // Auto-insert peserta dari cabor kategori (yang aktif)
                $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id;
                
                // Get Atlet aktif di kategori ini
                $atletIds = \App\Models\CaborKategoriAtlet::where('cabor_kategori_id', $caborKategoriId)
                    ->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->pluck('atlet_id')
                    ->unique()
                    ->filter();
                
                foreach ($atletIds as $atletId) {
                    PemeriksaanKhususPeserta::create([
                        'pemeriksaan_khusus_id' => $pemeriksaanKhusus->id,
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
                    PemeriksaanKhususPeserta::create([
                        'pemeriksaan_khusus_id' => $pemeriksaanKhusus->id,
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
                    PemeriksaanKhususPeserta::create([
                        'pemeriksaan_khusus_id' => $pemeriksaanKhusus->id,
                        'peserta_id' => $tenagaId,
                        'peserta_type' => 'App\\Models\\TenagaPendukung',
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
                
                DB::commit();
                
                // Reload dengan relasi
                $pemeriksaanKhusus->load([
                    'cabor',
                    'caborKategori',
                ])
                ->loadCount([
                    'pemeriksaanKhususPeserta as jumlah_peserta',
                ]);
                
                // Format response
                $formattedData = [
                    'id' => $pemeriksaanKhusus->id,
                    'cabor' => $pemeriksaanKhusus->cabor ? [
                        'id' => $pemeriksaanKhusus->cabor->id,
                        'nama' => $pemeriksaanKhusus->cabor->nama,
                    ] : null,
                    'cabor_kategori' => $pemeriksaanKhusus->caborKategori ? [
                        'id' => $pemeriksaanKhusus->caborKategori->id,
                        'nama' => $pemeriksaanKhusus->caborKategori->nama,
                    ] : null,
                    'nama_pemeriksaan' => $pemeriksaanKhusus->nama_pemeriksaan,
                    'tanggal_pemeriksaan' => $pemeriksaanKhusus->tanggal_pemeriksaan,
                    'status' => $pemeriksaanKhusus->status,
                    'jumlah_peserta' => $pemeriksaanKhusus->jumlah_peserta ?? 0,
                    'created_at' => $pemeriksaanKhusus->created_at,
                    'updated_at' => $pemeriksaanKhusus->updated_at,
                ];
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pemeriksaan khusus berhasil dibuat. Peserta dari cabor kategori telah otomatis ditambahkan.',
                    'data' => $formattedData,
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Store Pemeriksaan Khusus error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat pemeriksaan khusus.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Update pemeriksaan khusus
     */
    public function update(UpdatePemeriksaanKhususRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengupdate pemeriksaan khusus.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Check akses ke cabor kategori baru (jika diubah)
            if ($request->has('cabor_id') || $request->has('cabor_kategori_id')) {
                $caborId = $request->cabor_id ?? $pemeriksaanKhusus->cabor_id;
                $caborKategoriId = $request->cabor_kategori_id ?? $pemeriksaanKhusus->cabor_kategori_id;
                
                $hasAccess = $this->checkCaborKategoriAccess($caborId, $caborKategoriId, $user);
                if (!$hasAccess) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Anda tidak memiliki akses ke cabor kategori ini.',
                    ], 403);
                }
            }
            
            // Update data
            $updateData = [
                'updated_by' => $user->id,
            ];
            
            if ($request->has('cabor_id')) {
                $updateData['cabor_id'] = $request->cabor_id;
            }
            
            if ($request->has('cabor_kategori_id')) {
                $updateData['cabor_kategori_id'] = $request->cabor_kategori_id;
            }
            
            if ($request->has('nama_pemeriksaan')) {
                $updateData['nama_pemeriksaan'] = $request->nama_pemeriksaan;
            }
            
            if ($request->has('tanggal_pemeriksaan')) {
                $updateData['tanggal_pemeriksaan'] = $request->tanggal_pemeriksaan;
            }
            
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            
            $pemeriksaanKhusus->update($updateData);
            
            // Reload dengan relasi
            $pemeriksaanKhusus->load([
                'cabor',
                'caborKategori',
            ])
            ->loadCount([
                'pemeriksaanKhususPeserta as jumlah_peserta',
            ]);
            
            // Format response
            $formattedData = [
                'id' => $pemeriksaanKhusus->id,
                'cabor' => $pemeriksaanKhusus->cabor ? [
                    'id' => $pemeriksaanKhusus->cabor->id,
                    'nama' => $pemeriksaanKhusus->cabor->nama,
                ] : null,
                'cabor_kategori' => $pemeriksaanKhusus->caborKategori ? [
                    'id' => $pemeriksaanKhusus->caborKategori->id,
                    'nama' => $pemeriksaanKhusus->caborKategori->nama,
                ] : null,
                'nama_pemeriksaan' => $pemeriksaanKhusus->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $pemeriksaanKhusus->tanggal_pemeriksaan,
                'status' => $pemeriksaanKhusus->status,
                'jumlah_peserta' => $pemeriksaanKhusus->jumlah_peserta ?? 0,
                'created_at' => $pemeriksaanKhusus->created_at,
                'updated_at' => $pemeriksaanKhusus->updated_at,
            ];
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pemeriksaan khusus berhasil diperbarui.',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Update Pemeriksaan Khusus error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui pemeriksaan khusus.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Delete pemeriksaan khusus (soft delete)
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Delete')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus pemeriksaan khusus.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Soft delete
            $pemeriksaanKhusus->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pemeriksaan khusus berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Pemeriksaan Khusus error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus pemeriksaan khusus.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Apply role-based filtering
     * Logic sama dengan repository: hanya tampilkan pemeriksaan khusus dari cabor kategori dimana user aktif (is_active = 1)
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
     * Check akses ke cabor kategori
     */
    private function checkCaborKategoriAccess($caborId, $caborKategoriId, $user): bool
    {
        // Superadmin dan admin bisa akses semua
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Peserta (atlet, pelatih, tenaga pendukung) hanya bisa akses cabor kategori mereka
        $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
        
        if ($user->atlet) {
            $hasAccess = \App\Models\CaborKategoriAtlet::where('atlet_id', $user->atlet->id)
                ->where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->exists();
            
            if ($hasAccess) {
                return true;
            }
        }
        
        if ($user->pelatih) {
            $hasAccess = \App\Models\CaborKategoriPelatih::where('pelatih_id', $user->pelatih->id)
                ->where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->exists();
            
            if ($hasAccess) {
                return true;
            }
        }
        
        if ($user->tenagaPendukung) {
            $hasAccess = \App\Models\CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                ->where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->exists();
            
            if ($hasAccess) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check akses ke pemeriksaan khusus
     */
    private function checkPemeriksaanKhususAccess(PemeriksaanKhusus $pemeriksaanKhusus, $user): bool
    {
        // Superadmin dan admin bisa akses semua
        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Check akses ke cabor kategori pemeriksaan khusus ini
        return $this->checkCaborKategoriAccess(
            $pemeriksaanKhusus->cabor_id,
            $pemeriksaanKhusus->cabor_kategori_id,
            $user
        );
    }
    
    /**
     * Get setup pemeriksaan khusus (aspek dan item tes)
     */
    public function getSetup(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat setup pemeriksaan khusus.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::with([
                'aspek' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                },
                'aspek.itemTes' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                },
            ])
            ->findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Format response
            $aspek = $pemeriksaanKhusus->aspek
                ->filter(fn($a) => $a->deleted_at === null)
                ->unique('id')
                ->map(function ($aspek) {
                    return [
                        'id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'urutan' => $aspek->urutan,
                        'mst_template_aspek_id' => $aspek->mst_template_aspek_id,
                        'item_tes' => $aspek->itemTes
                            ->filter(fn($it) => $it->deleted_at === null)
                            ->unique('id')
                            ->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'nama' => $item->nama,
                                    'satuan' => $item->satuan,
                                    'target_laki_laki' => $item->target_laki_laki,
                                    'target_perempuan' => $item->target_perempuan,
                                    'performa_arah' => $item->performa_arah,
                                    'urutan' => $item->urutan,
                                    'mst_template_item_tes_id' => $item->mst_template_item_tes_id,
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'pemeriksaan_khusus_id' => $pemeriksaanKhusus->id,
                    'cabor_id' => $pemeriksaanKhusus->cabor_id,
                    'aspek' => $aspek,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Setup Pemeriksaan Khusus error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil setup pemeriksaan khusus.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get setup dengan target sesuai jenis kelamin peserta
     * Digunakan untuk menampilkan form input hasil tes per peserta
     */
    public function getSetupForPeserta(Request $request, $id, $pesertaId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat setup.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::with([
                'aspek' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                },
                'aspek.itemTes' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                },
            ])
            ->findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Get peserta untuk cek jenis kelamin
            $peserta = PemeriksaanKhususPeserta::with('peserta')
                ->where('pemeriksaan_khusus_id', $id)
                ->findOrFail($pesertaId);
            
            $jenisKelamin = $peserta->peserta->jenis_kelamin ?? null;
            $isLakiLaki = ($jenisKelamin === 'L' || $jenisKelamin === 'Laki-laki');
            
            // Get hasil tes yang sudah ada (jika ada)
            $hasilTesMap = \App\Models\PemeriksaanKhususPesertaItemTes::where('pemeriksaan_khusus_peserta_id', $pesertaId)
                ->get()
                ->keyBy('pemeriksaan_khusus_item_tes_id');
            
            // Format response dengan target sesuai jenis kelamin
            $aspek = $pemeriksaanKhusus->aspek
                ->filter(fn($a) => $a->deleted_at === null)
                ->unique('id')
                ->map(function ($aspek) use ($isLakiLaki, $hasilTesMap) {
                    return [
                        'id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'urutan' => $aspek->urutan,
                        'item_tes' => $aspek->itemTes
                            ->filter(fn($it) => $it->deleted_at === null)
                            ->unique('id')
                            ->map(function ($item) use ($isLakiLaki, $hasilTesMap) {
                                $target = $isLakiLaki ? $item->target_laki_laki : $item->target_perempuan;
                                $hasilTes = $hasilTesMap->get($item->id);
                                
                                return [
                                    'id' => $item->id,
                                    'nama' => $item->nama,
                                    'satuan' => $item->satuan,
                                    'target' => $target, // Target sesuai jenis kelamin
                                    'target_laki_laki' => $item->target_laki_laki,
                                    'target_perempuan' => $item->target_perempuan,
                                    'performa_arah' => $item->performa_arah,
                                    'urutan' => $item->urutan,
                                    // Hasil tes yang sudah ada (jika ada)
                                    'nilai' => $hasilTes ? $hasilTes->nilai : null,
                                    'persentase_performa' => $hasilTes && $hasilTes->persentase_performa ? (float) $hasilTes->persentase_performa : null,
                                    'persentase_riil' => $hasilTes && $hasilTes->persentase_riil ? (float) $hasilTes->persentase_riil : null,
                                    'predikat' => $hasilTes ? $hasilTes->predikat : null,
                                    'predikat_label' => $hasilTes && $hasilTes->predikat ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($hasilTes->predikat) : null,
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'pemeriksaan_khusus_id' => $pemeriksaanKhusus->id,
                    'peserta_id' => $pesertaId,
                    'peserta' => [
                        'id' => $peserta->peserta_id,
                        'nama' => $peserta->peserta->nama ?? '-',
                        'jenis_kelamin' => $jenisKelamin,
                    ],
                    'aspek' => $aspek,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Setup For Peserta error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'peserta_id' => $pesertaId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil setup.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get template berdasarkan cabor_id
     */
    public function getTemplate(Request $request, $caborId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat template.',
                ], 403);
            }
            
            $template = MstTemplatePemeriksaanKhususAspek::withoutGlobalScopes()
                ->where('cabor_id', $caborId)
                ->whereNull('deleted_at')
                ->with(['itemTes' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                }])
                ->orderBy('urutan')
                ->get();
            
            $hasTemplate = $template->count() > 0;
            
            $templateData = null;
            if ($hasTemplate) {
                $templateData = $template->map(function ($aspek) {
                    return [
                        'id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'urutan' => $aspek->urutan,
                        'item_tes' => $aspek->itemTes
                            ->filter(fn($it) => $it->deleted_at === null)
                            ->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'nama' => $item->nama,
                                    'satuan' => $item->satuan,
                                    'target_laki_laki' => $item->target_laki_laki,
                                    'target_perempuan' => $item->target_perempuan,
                                    'performa_arah' => $item->performa_arah,
                                    'urutan' => $item->urutan,
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();
            }
            
            return response()->json([
                'status' => 'success',
                'has_template' => $hasTemplate,
                'data' => $templateData,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Template error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'cabor_id' => $caborId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil template.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Save setup pemeriksaan khusus (manual atau dari template)
     */
    public function saveSetup(SavePemeriksaanKhususSetupRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Setup')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk setup pemeriksaan khusus.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($request->pemeriksaan_khusus_id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Use repository to save
            $repository = new PemeriksaanKhususRepository(new PemeriksaanKhusus());
            $repository->saveAspekItemTes(
                $request->pemeriksaan_khusus_id,
                $request->aspek
            );
            
            return response()->json([
                'status' => 'success',
                'message' => 'Setup pemeriksaan khusus berhasil disimpan.',
            ]);
        } catch (\Exception $e) {
            Log::error('Save Setup Pemeriksaan Khusus error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $request->pemeriksaan_khusus_id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan setup pemeriksaan khusus.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Save template untuk cabor tertentu
     */
    public function saveTemplate(SavePemeriksaanKhususTemplateRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Setup')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menyimpan template.',
                ], 403);
            }
            
            // Use repository to save template
            $repository = new PemeriksaanKhususRepository(new PemeriksaanKhusus());
            $repository->saveAsTemplate(
                $request->cabor_id,
                $request->aspek
            );
            
            return response()->json([
                'status' => 'success',
                'message' => 'Template berhasil disimpan untuk cabor ini.',
            ]);
        } catch (\Exception $e) {
            Log::error('Save Template error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'cabor_id' => $request->cabor_id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan template.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Clone template ke pemeriksaan khusus
     */
    public function cloneFromTemplate(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Setup')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk clone template.',
                ], 403);
            }
            
            $request->validate([
                'pemeriksaan_khusus_id' => 'required|exists:pemeriksaan_khusus,id',
                'cabor_id' => 'required|exists:cabor,id',
            ]);
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($request->pemeriksaan_khusus_id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Use repository to clone template
            $repository = new PemeriksaanKhususRepository(new PemeriksaanKhusus());
            $repository->cloneFromTemplate(
                $request->pemeriksaan_khusus_id,
                $request->cabor_id
            );
            
            return response()->json([
                'status' => 'success',
                'message' => 'Template berhasil di-clone ke pemeriksaan khusus.',
            ]);
        } catch (\Exception $e) {
            Log::error('Clone Template error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $request->pemeriksaan_khusus_id ?? null,
                'cabor_id' => $request->cabor_id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat clone template.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get peserta untuk input hasil tes
     */
    public function getPesertaForInputHasilTes(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Input Hasil Tes')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk input hasil tes.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Get jenis peserta dari query parameter (optional)
            $jenisPeserta = $request->query('jenis_peserta', 'atlet'); // default atlet
            
            $pesertaTypeMap = [
                'atlet' => 'App\\Models\\Atlet',
                'pelatih' => 'App\\Models\\Pelatih',
                'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
            ];
            
            $pesertaType = $pesertaTypeMap[$jenisPeserta] ?? 'App\\Models\\Atlet';
            
            $pesertaList = PemeriksaanKhususPeserta::with(['peserta'])
                ->where('pemeriksaan_khusus_id', $id)
                ->where('peserta_type', $pesertaType)
                ->whereNull('deleted_at')
                ->get();
            
            // Format data untuk frontend
            $formattedPeserta = [];
            
            $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id;
            
            foreach ($pesertaList as $peserta) {
                $pesertaData = [
                    'id' => $peserta->id, // pemeriksaan_khusus_peserta id
                    'peserta_id' => $peserta->peserta_id, // id peserta asli (atlet/pelatih/tenaga pendukung)
                    'nama' => $peserta->peserta->nama ?? '-',
                    'jenis_kelamin' => $peserta->peserta->jenis_kelamin ?? null,
                    'tanggal_lahir' => $peserta->peserta->tanggal_lahir ?? null,
                ];
                
                // Hitung usia jika ada tanggal_lahir
                if ($pesertaData['tanggal_lahir']) {
                    $pesertaData['usia'] = \Carbon\Carbon::parse($pesertaData['tanggal_lahir'])->age;
                } else {
                    $pesertaData['usia'] = null;
                }
                
                // Tambahkan posisi/jenis berdasarkan tipe peserta
                if ($jenisPeserta === 'atlet' && $caborKategoriId) {
                    $caborKategoriAtlet = \App\Models\CaborKategoriAtlet::where('cabor_kategori_id', $caborKategoriId)
                        ->where('atlet_id', $peserta->peserta_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $pesertaData['posisi_atlet'] = $caborKategoriAtlet ? ($caborKategoriAtlet->posisi_atlet ?? '-') : '-';
                } elseif ($jenisPeserta === 'pelatih' && $caborKategoriId) {
                    $caborKategoriPelatih = \App\Models\CaborKategoriPelatih::where('cabor_kategori_id', $caborKategoriId)
                        ->where('pelatih_id', $peserta->peserta_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $pesertaData['jenis_pelatih'] = $caborKategoriPelatih ? ($caborKategoriPelatih->jenis_pelatih ?? '-') : '-';
                } elseif ($jenisPeserta === 'tenaga-pendukung' && $caborKategoriId) {
                    $caborKategoriTenagaPendukung = \App\Models\CaborKategoriTenagaPendukung::where('cabor_kategori_id', $caborKategoriId)
                        ->where('tenaga_pendukung_id', $peserta->peserta_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $pesertaData['jenis_tenaga_pendukung'] = $caborKategoriTenagaPendukung ? ($caborKategoriTenagaPendukung->jenis_tenaga_pendukung ?? '-') : '-';
                }
                
                $formattedPeserta[] = $pesertaData;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedPeserta,
                'jenis_peserta' => $jenisPeserta,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Peserta For Input Hasil Tes error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil peserta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Save hasil tes pemeriksaan khusus
     * Auto-calculate persentase, predikat, nilai aspek, dan nilai keseluruhan
     */
    public function saveHasilTes(SavePemeriksaanKhususHasilTesRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Input Hasil Tes')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk input hasil tes.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($request->pemeriksaan_khusus_id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Use repository to save hasil tes
            $repository = new PemeriksaanKhususRepository(new PemeriksaanKhusus());
            
            // Process catatan per peserta jika ada
            foreach ($request->data as $pesertaData) {
                $pesertaId = $pesertaData['peserta_id'];
                
                // Update catatan peserta jika ada (jika field catatan ada di tabel)
                if (isset($pesertaData['catatan']) && !empty($pesertaData['catatan'])) {
                    $peserta = PemeriksaanKhususPeserta::find($pesertaId);
                    if ($peserta) {
                        // Catatan bisa disimpan di field catatan_umum atau catatan jika ada
                        // Untuk sekarang, kita skip karena tidak ada field di tabel
                        // Tapi struktur sudah siap jika nanti ditambahkan field catatan
                    }
                }
            }
            
            // Save hasil tes (repository akan handle calculation)
            $repository->saveHasilTes(
                $request->pemeriksaan_khusus_id,
                $request->data
            );
            
            return response()->json([
                'status' => 'success',
                'message' => 'Hasil tes berhasil disimpan. Persentase, predikat, nilai aspek, dan nilai keseluruhan telah dihitung otomatis.',
            ]);
        } catch (\Exception $e) {
            Log::error('Save Hasil Tes error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $request->pemeriksaan_khusus_id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan hasil tes.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get hasil tes pemeriksaan khusus
     */
    public function getHasilTes(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat hasil tes.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Get jenis peserta dari query parameter (optional, default: atlet)
            $jenisPeserta = $request->query('jenis_peserta', 'atlet');
            
            $pesertaTypeMap = [
                'atlet' => 'App\\Models\\Atlet',
                'pelatih' => 'App\\Models\\Pelatih',
                'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
            ];
            
            $pesertaType = $pesertaTypeMap[$jenisPeserta] ?? 'App\\Models\\Atlet';
            
            // Get peserta dengan hasil tes
            $pesertaList = PemeriksaanKhususPeserta::with([
                'peserta',
                'pemeriksaanKhususPesertaItemTes.itemTes',
                'hasilAspek',
                'hasilKeseluruhan',
            ])
                ->where('pemeriksaan_khusus_id', $id)
                ->where('peserta_type', $pesertaType)
                ->whereNull('deleted_at')
                ->get();
            
            // Format data untuk response
            $data = [];
            foreach ($pesertaList as $peserta) {
                $pesertaData = [
                    'peserta_id' => $peserta->id, // pemeriksaan_khusus_peserta id
                    'peserta' => [
                        'id' => $peserta->peserta_id,
                        'nama' => $peserta->peserta->nama ?? '-',
                        'jenis_kelamin' => $peserta->peserta->jenis_kelamin ?? null,
                    ],
                    'item_tes' => [],
                    'aspek' => [],
                    'nilai_keseluruhan' => null,
                    'predikat_keseluruhan' => null,
                ];
                
                // Get hasil tes per item
                foreach ($peserta->pemeriksaanKhususPesertaItemTes as $hasilTes) {
                    $pesertaData['item_tes'][] = [
                        'item_tes_id' => $hasilTes->pemeriksaan_khusus_item_tes_id,
                        'nilai' => $hasilTes->nilai,
                        'persentase_performa' => $hasilTes->persentase_performa ? (float) $hasilTes->persentase_performa : null,
                        'persentase_riil' => $hasilTes->persentase_riil ? (float) $hasilTes->persentase_riil : null,
                        'predikat' => $hasilTes->predikat,
                        'predikat_label' => $hasilTes->predikat ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($hasilTes->predikat) : null,
                    ];
                }
                
                // Get hasil aspek
                foreach ($peserta->hasilAspek as $hasilAspek) {
                    $pesertaData['aspek'][] = [
                        'aspek_id' => $hasilAspek->pemeriksaan_khusus_aspek_id,
                        'nilai_performa' => $hasilAspek->nilai_performa ? (float) $hasilAspek->nilai_performa : null,
                        'predikat' => $hasilAspek->predikat,
                        'predikat_label' => $hasilAspek->predikat ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($hasilAspek->predikat) : null,
                    ];
                }
                
                // Get nilai keseluruhan
                if ($peserta->hasilKeseluruhan) {
                    $pesertaData['nilai_keseluruhan'] = $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        ? (float) $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        : null;
                    $pesertaData['predikat_keseluruhan'] = $peserta->hasilKeseluruhan->predikat;
                    $pesertaData['predikat_keseluruhan_label'] = $peserta->hasilKeseluruhan->predikat 
                        ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($peserta->hasilKeseluruhan->predikat) 
                        : null;
                }
                
                $data[] = $pesertaData;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $data,
                'jenis_peserta' => $jenisPeserta,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Hasil Tes error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil hasil tes.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get list peserta untuk visualisasi (dropdown)
     */
    public function getPesertaVisualisasi(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat visualisasi data.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::findOrFail($id);
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Get hanya atlet yang sudah punya hasil tes (pelatih dan tenaga pendukung tidak dinilai)
            $pesertaList = PemeriksaanKhususPeserta::with(['peserta', 'hasilKeseluruhan'])
                ->where('pemeriksaan_khusus_id', $id)
                ->where('peserta_type', 'App\\Models\\Atlet')
                ->whereNull('deleted_at')
                ->whereHas('hasilKeseluruhan')
                ->get();
            
            $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id;
            $caborNama = $pemeriksaanKhusus->cabor->nama ?? '-';
            
            $formattedPeserta = [];
            foreach ($pesertaList as $peserta) {
                // Get posisi
                $posisi = '-';
                if ($caborKategoriId) {
                    try {
                        $posisi = $this->getAtletPosisi($peserta->peserta_id, $caborKategoriId);
                    } catch (\Exception $e) {
                        Log::warning('Error getting posisi for peserta in visualisasi', [
                            'peserta_id' => $peserta->peserta_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Get umur
                $umur = '-';
                if ($peserta->peserta && isset($peserta->peserta->tanggal_lahir)) {
                    $umur = $this->calculateAge($peserta->peserta->tanggal_lahir);
                }
                
                // Get nilai keseluruhan
                $nilaiKeseluruhan = null;
                $predikatKeseluruhan = null;
                if ($peserta->hasilKeseluruhan) {
                    $nilaiKeseluruhan = $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        ? (float) $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        : null;
                    $predikatKeseluruhan = $peserta->hasilKeseluruhan->predikat;
                }
                
                $formattedPeserta[] = [
                    'peserta_id' => $peserta->id, // pemeriksaan_khusus_peserta id
                    'peserta' => [
                        'id' => $peserta->peserta_id,
                        'nama' => $peserta->peserta->nama ?? '-',
                        'jenis_kelamin' => $peserta->peserta->jenis_kelamin ?? null,
                        'posisi' => $posisi,
                        'umur' => $umur,
                        'cabor' => $caborNama,
                    ],
                    'nilai_keseluruhan' => $nilaiKeseluruhan,
                    'predikat_keseluruhan' => $predikatKeseluruhan,
                    'predikat_keseluruhan_label' => $predikatKeseluruhan 
                        ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($predikatKeseluruhan) 
                        : null,
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedPeserta,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Peserta Visualisasi error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil list peserta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get visualisasi data untuk peserta tertentu
     */
    public function getVisualisasiPeserta(Request $request, $id, $pesertaId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Pemeriksaan Khusus Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat visualisasi data.',
                ], 403);
            }
            
            $pemeriksaanKhusus = PemeriksaanKhusus::with('cabor')->find($id);
            
            if (!$pemeriksaanKhusus) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemeriksaan khusus tidak ditemukan.',
                ], 404);
            }
            
            // Check akses ke pemeriksaan khusus ini
            $hasAccess = $this->checkPemeriksaanKhususAccess($pemeriksaanKhusus, $user);
            if (!$hasAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke pemeriksaan khusus ini.',
                ], 403);
            }
            
            // Load aspek dengan urutan
            $aspekList = \App\Models\PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $id)
                ->whereNull('deleted_at')
                ->orderBy('urutan')
                ->get();
            
            // Load semua item tes dengan aspek untuk mapping
            $itemTesList = \App\Models\PemeriksaanKhususItemTes::with('aspek')
                ->whereHas('aspek', function ($q) use ($id) {
                    $q->where('pemeriksaan_khusus_id', $id)->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->orderBy('pemeriksaan_khusus_aspek_id')
                ->orderBy('urutan')
                ->get();
            
            // Get peserta dengan hasil aspek, item tes, dan keseluruhan
            // $pesertaId bisa berupa pemeriksaan_khusus_peserta.id atau peserta_id (ID dari tabel atlet/pelatih/tenaga pendukung)
            $peserta = PemeriksaanKhususPeserta::with([
                'peserta',
                'hasilAspek.aspek',
                'hasilKeseluruhan',
                'pemeriksaanKhususPesertaItemTes.itemTes.aspek',
            ])
                ->where('pemeriksaan_khusus_id', $id)
                ->whereNull('deleted_at')
                ->where(function ($query) use ($pesertaId) {
                    // Coba cari berdasarkan pemeriksaan_khusus_peserta.id dulu
                    $query->where('id', $pesertaId)
                        // Jika tidak ditemukan, coba cari berdasarkan peserta_id
                        ->orWhere('peserta_id', $pesertaId);
                })
                ->first();
            
            if (!$peserta) {
                // Log untuk debugging
                Log::warning('Peserta tidak ditemukan untuk visualisasi', [
                    'pemeriksaan_khusus_id' => $id,
                    'peserta_id_param' => $pesertaId,
                    'user_id' => $user->id ?? null,
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Peserta tidak ditemukan dalam pemeriksaan khusus ini.',
                ], 404);
            }
            
            // Validasi bahwa peserta adalah atlet (hanya atlet yang dinilai)
            if ($peserta->peserta_type !== 'App\\Models\\Atlet') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Visualisasi data hanya tersedia untuk atlet.',
                ], 400);
            }
            
            // Validasi bahwa peserta memiliki relasi peserta
            if (!$peserta->peserta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data peserta tidak ditemukan.',
                ], 404);
            }
            
            // Get jenis kelamin untuk menentukan target
            $jenisKelamin = $peserta->peserta->jenis_kelamin ?? null;
            $isLakiLaki = ($jenisKelamin === 'L' || $jenisKelamin === 'Laki-laki');
            
            // Get informasi lengkap peserta (posisi, umur, cabor)
            $posisi = '-';
            $umur = '-';
            $caborNama = '-';
            $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id ?? null;
            
            // Load cabor relation jika belum ter-load
            if (!$pemeriksaanKhusus->relationLoaded('cabor')) {
                $pemeriksaanKhusus->load('cabor');
            }
            
            if ($pemeriksaanKhusus->cabor) {
                $caborNama = $pemeriksaanKhusus->cabor->nama ?? '-';
            }
            
            if ($caborKategoriId && $peserta->peserta_id) {
                try {
                    $posisi = $this->getAtletPosisi($peserta->peserta_id, $caborKategoriId);
                    if ($peserta->peserta && isset($peserta->peserta->tanggal_lahir) && $peserta->peserta->tanggal_lahir) {
                        $umur = $this->calculateAge($peserta->peserta->tanggal_lahir);
                    }
                } catch (\Exception $e) {
                    Log::warning('Error getting posisi/umur for peserta in visualisasi', [
                        'peserta_id' => $peserta->peserta_id,
                        'cabor_kategori_id' => $caborKategoriId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $pesertaData = [
                'peserta_id' => $peserta->id,
                'peserta' => [
                    'id' => $peserta->peserta_id,
                    'nama' => $peserta->peserta->nama ?? '-',
                    'jenis_kelamin' => $jenisKelamin,
                    'posisi' => $posisi,
                    'umur' => $umur,
                    'cabor' => $caborNama,
                ],
                'aspek' => [],
                'item_tes' => [],
                'nilai_keseluruhan' => null,
                'predikat_keseluruhan' => null,
                'predikat_keseluruhan_label' => null,
            ];
            
            // Map hasil aspek berdasarkan urutan aspek
            foreach ($aspekList as $aspek) {
                try {
                    $hasilAspek = null;
                    if ($peserta->relationLoaded('hasilAspek') && $peserta->hasilAspek) {
                        $hasilAspek = $peserta->hasilAspek->firstWhere('pemeriksaan_khusus_aspek_id', $aspek->id);
                    }
                    
                    $pesertaData['aspek'][] = [
                        'aspek_id' => $aspek->id ?? null,
                        'nama' => $aspek->nama ?? '-',
                        'urutan' => $aspek->urutan ?? 0,
                        'nilai_performa' => $hasilAspek && $hasilAspek->nilai_performa !== null ? (float) $hasilAspek->nilai_performa : null,
                        'predikat' => $hasilAspek ? ($hasilAspek->predikat ?? null) : null,
                        'predikat_label' => ($hasilAspek && $hasilAspek->predikat) 
                            ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($hasilAspek->predikat) 
                            : null,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error mapping aspek in visualisasi', [
                        'aspek_id' => $aspek->id ?? null,
                        'peserta_id' => $pesertaId,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
            
            // Map hasil item tes berdasarkan aspek
            foreach ($aspekList as $aspek) {
                try {
                    $itemTesInAspek = $itemTesList->where('pemeriksaan_khusus_aspek_id', $aspek->id);
                    
                    foreach ($itemTesInAspek as $itemTes) {
                        try {
                            $hasilItemTes = null;
                            if ($peserta->relationLoaded('pemeriksaanKhususPesertaItemTes') && $peserta->pemeriksaanKhususPesertaItemTes) {
                                $hasilItemTes = $peserta->pemeriksaanKhususPesertaItemTes->firstWhere('pemeriksaan_khusus_item_tes_id', $itemTes->id);
                            }
                            
                            // Tentukan target berdasarkan jenis kelamin
                            $target = $isLakiLaki 
                                ? ($itemTes->target_laki_laki ?? null) 
                                : ($itemTes->target_perempuan ?? null);
                            
                            $pesertaData['item_tes'][] = [
                                'item_tes_id' => $itemTes->id ?? null,
                                'aspek_id' => $aspek->id ?? null,
                                'aspek_nama' => $aspek->nama ?? '-',
                                'nama' => $itemTes->nama ?? '-',
                                'satuan' => $itemTes->satuan ?? '-',
                                'target' => $target,
                                'target_laki_laki' => $itemTes->target_laki_laki ?? null,
                                'target_perempuan' => $itemTes->target_perempuan ?? null,
                                'performa_arah' => $itemTes->performa_arah ?? null,
                                'urutan' => $itemTes->urutan ?? 0,
                                'nilai' => $hasilItemTes ? ($hasilItemTes->nilai ?? null) : null,
                                'persentase_performa' => ($hasilItemTes && $hasilItemTes->persentase_performa !== null) 
                                    ? (float) $hasilItemTes->persentase_performa 
                                    : null,
                                'persentase_riil' => ($hasilItemTes && $hasilItemTes->persentase_riil !== null) 
                                    ? (float) $hasilItemTes->persentase_riil 
                                    : null,
                                'predikat' => $hasilItemTes ? ($hasilItemTes->predikat ?? null) : null,
                                'predikat_label' => ($hasilItemTes && $hasilItemTes->predikat) 
                                    ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($hasilItemTes->predikat) 
                                    : null,
                            ];
                        } catch (\Exception $e) {
                            Log::warning('Error mapping item tes in visualisasi', [
                                'item_tes_id' => $itemTes->id ?? null,
                                'peserta_id' => $pesertaId,
                                'error' => $e->getMessage(),
                            ]);
                            continue;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Error processing aspek item tes in visualisasi', [
                        'aspek_id' => $aspek->id ?? null,
                        'peserta_id' => $pesertaId,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
            
            // Get nilai keseluruhan
            try {
                if ($peserta->relationLoaded('hasilKeseluruhan') && $peserta->hasilKeseluruhan) {
                    $pesertaData['nilai_keseluruhan'] = ($peserta->hasilKeseluruhan->nilai_keseluruhan !== null) 
                        ? (float) $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        : null;
                    $pesertaData['predikat_keseluruhan'] = $peserta->hasilKeseluruhan->predikat ?? null;
                    $pesertaData['predikat_keseluruhan_label'] = $peserta->hasilKeseluruhan->predikat 
                        ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($peserta->hasilKeseluruhan->predikat) 
                        : null;
                }
            } catch (\Exception $e) {
                Log::warning('Error getting nilai keseluruhan in visualisasi', [
                    'peserta_id' => $pesertaId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Format aspek list dengan null safety
            $aspekListFormatted = $aspekList->map(function ($a) {
                return [
                    'id' => $a->id ?? null,
                    'nama' => $a->nama ?? '-',
                    'urutan' => $a->urutan ?? 0,
                ];
            })->toArray();
            
            return response()->json([
                'status' => 'success',
                'data' => $pesertaData,
                'aspek_list' => $aspekListFormatted,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pemeriksaan khusus atau peserta tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Get Visualisasi Peserta error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'pemeriksaan_khusus_id' => $id,
                'peserta_id' => $pesertaId,
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil visualisasi data.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Helper function untuk menghitung umur
     */
    private function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }
        
        try {
            $tanggalLahir = new \Carbon\Carbon($tanggalLahir);
            $today = \Carbon\Carbon::today();
            return (int) $tanggalLahir->diffInYears($today);
        } catch (\Exception $e) {
            return '-';
        }
    }
    
    /**
     * Helper function untuk mendapatkan posisi atlet
     */
    private function getAtletPosisi($atletId, $caborKategoriId)
    {
        if (!$caborKategoriId || !$atletId) {
            return '-';
        }
        
        try {
            $posisi = DB::table('cabor_kategori_atlet')
                ->where('cabor_kategori_atlet.atlet_id', $atletId)
                ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                ->whereNull('cabor_kategori_atlet.deleted_at')
                ->value('cabor_kategori_atlet.posisi_atlet');
            
            return $posisi ?? '-';
        } catch (\Exception $e) {
            Log::warning('Error in getAtletPosisi', [
                'atlet_id' => $atletId,
                'cabor_kategori_id' => $caborKategoriId,
                'error' => $e->getMessage(),
            ]);
            
            return '-';
        }
    }
}

