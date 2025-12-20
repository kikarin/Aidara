<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProgramLatihanRequest;
use App\Http\Requests\Api\UpdateProgramLatihanRequest;
use App\Models\ProgramLatihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ProgramLatihanController extends Controller
{
    /**
     * Get list program latihan
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Load relasi user dulu untuk memastikan tersedia
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Program Latihan Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat program latihan.',
                ], 403);
            }

            // Query dasar - untuk mobile API, lebih sederhana
            // Pastikan tidak ada filter yang menghilangkan data
            // Gunakan withoutGlobalScopes() untuk memastikan tidak ada scope yang menghilangkan data
            // Tapi tetap exclude soft deleted records
            $query = ProgramLatihan::withoutGlobalScopes()
                ->whereNull('program_latihan.deleted_at');
            
            // Apply role-based filtering (hanya untuk peserta, admin/superadmin bisa lihat semua)
            // Untuk mobile API - lebih fleksibel, tidak terlalu ketat seperti web
            // HARUS dilakukan SEBELUM search untuk memastikan query yang benar
            $this->applyRoleBasedFiltering($query, $user);

            // Search - dilakukan setelah role-based filtering
            if ($request->has('search') && !empty(trim($request->search))) {
                $search = trim($request->search);
                
                // Semua kondisi search dalam satu closure untuk memastikan query yang benar
                $query->where(function ($q) use ($search) {
                    // Search di field utama program latihan
                    $q->where('program_latihan.nama_program', 'like', "%{$search}%")
                        ->orWhere('program_latihan.keterangan', 'like', "%{$search}%")
                        ->orWhere('program_latihan.tahap', 'like', "%{$search}%")
                        // Search di relasi cabor
                        ->orWhereHas('cabor', function ($caborQuery) use ($search) {
                            $caborQuery->where('cabor.nama', 'like', "%{$search}%")
                                ->whereNull('cabor.deleted_at');
                        })
                        // Search di relasi caborKategori
                        ->orWhereHas('caborKategori', function ($kategoriQuery) use ($search) {
                            $kategoriQuery->where('cabor_kategori.nama', 'like', "%{$search}%")
                                ->whereNull('cabor_kategori.deleted_at');
                        });
                });
            }
            
            // Load relasi - dilakukan setelah semua filter dan search
            // Untuk mobile API, load relasi tanpa filter soft deletes
            $query->with([
                'cabor' => function($q) {
                    $q->whereNull('cabor.deleted_at');
                },
                'caborKategori' => function($q) {
                    $q->whereNull('cabor_kategori.deleted_at');
                }
            ]);

            // Filter by cabor_id
            if ($request->has('cabor_id') && $request->cabor_id && $request->cabor_id !== 'all') {
                $query->where('cabor_id', $request->cabor_id);
            }

            // Filter by cabor_kategori_id
            if ($request->has('cabor_kategori_id') && $request->cabor_kategori_id && $request->cabor_kategori_id !== 'all') {
                $query->where('cabor_kategori_id', $request->cabor_kategori_id);
            }

            // Filter by date range
            if ($request->has('filter_start_date') && $request->has('filter_end_date')) {
                $query->whereBetween('created_at', [
                    $request->filter_start_date . ' 00:00:00',
                    $request->filter_end_date . ' 23:59:59',
                ]);
            }

            // Sorting
            $sortField = $request->get('sort', 'id');
            $sortOrder = $request->get('order', 'desc');
            $validColumns = ['id', 'nama_program', 'periode_mulai', 'periode_selesai', 'created_at', 'updated_at'];
            
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }

            // Pagination
            $perPage = (int) $request->get('per_page', 10);
            $page = (int) $request->get('page', 1);
            
            // Jika ada search dengan whereHas, perlu distinct untuk menghindari duplicate
            if ($request->has('search') && !empty(trim($request->search))) {
                $query->distinct();
            }
            
            // Pagination - langsung execute
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
                        'nama_program' => $item->nama_program,
                        'cabor_kategori' => $item->caborKategori ? [
                            'id' => $item->caborKategori->id,
                            'nama' => $item->caborKategori->nama,
                        ] : null,
                        'periode_mulai' => $item->periode_mulai,
                        'periode_selesai' => $item->periode_selesai,
                        'periode_hitung' => $item->periode_hitung,
                        'tahap' => $item->tahap,
                        'keterangan' => $item->keterangan,
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
            Log::error('Get Program Latihan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil program latihan.',
            ], 500);
        }
    }

    /**
     * Store program latihan
     */
    public function store(StoreProgramLatihanRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Program Latihan Add')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menambah program latihan.',
                ], 403);
            }

            $data = $request->validated();

            // Create program latihan
            $programLatihan = ProgramLatihan::create([
                'cabor_id' => $data['cabor_id'],
                'nama_program' => $data['nama_program'],
                'cabor_kategori_id' => $data['cabor_kategori_id'],
                'periode_mulai' => $data['periode_mulai'],
                'periode_selesai' => $data['periode_selesai'],
                'tahap' => $data['tahap'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Reload with relations
            $programLatihan->load(['cabor', 'caborKategori']);

            return response()->json([
                'status' => 'success',
                'message' => 'Program latihan berhasil ditambahkan.',
                'data' => [
                    'program_latihan' => [
                        'id' => $programLatihan->id,
                        'cabor' => $programLatihan->cabor ? [
                            'id' => $programLatihan->cabor->id,
                            'nama' => $programLatihan->cabor->nama,
                        ] : null,
                        'nama_program' => $programLatihan->nama_program,
                        'cabor_kategori' => $programLatihan->caborKategori ? [
                            'id' => $programLatihan->caborKategori->id,
                            'nama' => $programLatihan->caborKategori->nama,
                        ] : null,
                        'periode_mulai' => $programLatihan->periode_mulai,
                        'periode_selesai' => $programLatihan->periode_selesai,
                        'periode_hitung' => $programLatihan->periode_hitung,
                        'tahap' => $programLatihan->tahap,
                        'keterangan' => $programLatihan->keterangan,
                    ],
                    'permissions' => $permissions,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Store Program Latihan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambah program latihan.',
            ], 500);
        }
    }

    /**
     * Update program latihan
     */
    public function update(UpdateProgramLatihanRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Program Latihan Edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengedit program latihan.',
                ], 403);
            }

            $programLatihan = ProgramLatihan::find($id);

            if (!$programLatihan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Program latihan tidak ditemukan.',
                ], 404);
            }

            $data = $request->validated();

            // Update program latihan (support partial update)
            $updateData = [];
            if (isset($data['cabor_id'])) {
                $updateData['cabor_id'] = $data['cabor_id'];
            }
            if (isset($data['nama_program'])) {
                $updateData['nama_program'] = $data['nama_program'];
            }
            if (isset($data['cabor_kategori_id'])) {
                $updateData['cabor_kategori_id'] = $data['cabor_kategori_id'];
            }
            if (isset($data['periode_mulai'])) {
                $updateData['periode_mulai'] = $data['periode_mulai'];
            }
            if (isset($data['periode_selesai'])) {
                $updateData['periode_selesai'] = $data['periode_selesai'];
            }
            if (isset($data['tahap'])) {
                $updateData['tahap'] = $data['tahap'];
            }
            if (isset($data['keterangan'])) {
                $updateData['keterangan'] = $data['keterangan'];
            }
            $updateData['updated_by'] = $user->id;

            $programLatihan->update($updateData);

            // Reload with relations
            $programLatihan->refresh();
            $programLatihan->load(['cabor', 'caborKategori']);

            return response()->json([
                'status' => 'success',
                'message' => 'Program latihan berhasil diperbarui.',
                'data' => [
                    'program_latihan' => [
                        'id' => $programLatihan->id,
                        'cabor' => $programLatihan->cabor ? [
                            'id' => $programLatihan->cabor->id,
                            'nama' => $programLatihan->cabor->nama,
                        ] : null,
                        'nama_program' => $programLatihan->nama_program,
                        'cabor_kategori' => $programLatihan->caborKategori ? [
                            'id' => $programLatihan->caborKategori->id,
                            'nama' => $programLatihan->caborKategori->nama,
                        ] : null,
                        'periode_mulai' => $programLatihan->periode_mulai,
                        'periode_selesai' => $programLatihan->periode_selesai,
                        'periode_hitung' => $programLatihan->periode_hitung,
                        'tahap' => $programLatihan->tahap,
                        'keterangan' => $programLatihan->keterangan,
                    ],
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Update Program Latihan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_latihan_id' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui program latihan.',
            ], 500);
        }
    }

    /**
     * Delete program latihan
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Get permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Check permission
            if (!Gate::allows('Program Latihan Delete')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus program latihan.',
                ], 403);
            }

            $programLatihan = ProgramLatihan::find($id);

            if (!$programLatihan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Program latihan tidak ditemukan.',
                ], 404);
            }

            $programLatihan->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Program latihan berhasil dihapus.',
                'data' => [
                    'permissions' => $permissions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Program Latihan error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'program_latihan_id' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus program latihan.',
            ], 500);
        }
    }

    /**
     * Apply role-based filtering
     * Untuk mobile API - lebih sederhana dan fleksibel
     * Hanya filter jika user adalah peserta DAN memiliki relasi yang valid
     * Jika relasi tidak ada, tidak di-filter (bisa lihat semua untuk mobile)
     */
    private function applyRoleBasedFiltering($query, $user): void
    {
        $roleId = $user->current_role_id ?? null;

        // Jika bukan peserta (admin/superadmin), tidak perlu filter - bisa lihat semua
        if (!in_array($roleId, [35, 36, 37])) {
            return;
        }

        // Load relasi user dulu dengan fresh
        $user->load(['atlet', 'pelatih', 'tenagaPendukung']);

        if ($roleId == 35) { // Atlet
            // Hanya filter jika atlet relasi ada dan valid
            if ($user->atlet && $user->atlet->id) {
                $query->whereHas('caborKategori', function ($subQuery) use ($user) {
                    $subQuery->whereHas('caborKategoriAtlet', function ($subSubQuery) use ($user) {
                        $subSubQuery->where('atlet_id', $user->atlet->id)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_atlet.deleted_at');
                    });
                });
            }
            // Jika tidak ada relasi atlet, tidak di-filter (untuk mobile lebih fleksibel)
        }

        if ($roleId == 36) { // Pelatih
            // Hanya filter jika pelatih relasi ada dan valid
            if ($user->pelatih && $user->pelatih->id) {
                $query->whereHas('caborKategori', function ($subQuery) use ($user) {
                    $subQuery->whereHas('caborKategoriPelatih', function ($subSubQuery) use ($user) {
                        $subSubQuery->where('pelatih_id', $user->pelatih->id)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_pelatih.deleted_at');
                    });
                });
            }
            // Jika tidak ada relasi pelatih, tidak di-filter (untuk mobile lebih fleksibel)
        }

        if ($roleId == 37) { // Tenaga Pendukung
            // Hanya filter jika tenaga pendukung relasi ada dan valid
            if ($user->tenagaPendukung && $user->tenagaPendukung->id) {
                $query->whereHas('caborKategori', function ($subQuery) use ($user) {
                    $subQuery->whereHas('caborKategoriTenagaPendukung', function ($subSubQuery) use ($user) {
                        $subSubQuery->where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                    });
                });
            }
            // Jika tidak ada relasi tenaga pendukung, tidak di-filter (untuk mobile lebih fleksibel)
        }
    }

    /**
     * Get list cabor untuk filter
     * Peserta hanya melihat cabor yang mereka miliki
     */
    public function getCaborList(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            $roleId = $user->current_role_id ?? null;
            
            // Non-peserta (Superadmin, Admin) - lihat semua cabor
            if (!in_array($roleId, [35, 36, 37])) {
                $cabors = \App\Models\Cabor::select('id', 'nama')
                    ->orderBy('nama')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'nama' => $item->nama,
                        ];
                    });
            } else {
                // Peserta (Atlet, Pelatih, Tenaga Pendukung) - hanya cabor mereka
                $caborIds = collect();
                
                if ($roleId == 35 && $user->atlet && $user->atlet->id) { // Atlet
                    $caborIds = \App\Models\CaborKategoriAtlet::where('cabor_kategori_atlet.atlet_id', $user->atlet->id)
                        ->where('is_active', 1)
                        ->whereNull('cabor_kategori_atlet.deleted_at')
                        ->join('cabor_kategori', 'cabor_kategori_atlet.cabor_kategori_id', '=', 'cabor_kategori.id')
                        ->whereNull('cabor_kategori.deleted_at')
                        ->pluck('cabor_kategori.cabor_id')
                        ->unique();
                }
                
                if ($roleId == 36 && $user->pelatih && $user->pelatih->id) { // Pelatih
                    // Ambil langsung cabor_id dari tabel cabor_kategori_pelatih (ada kolom cabor_id langsung)
                    $caborIds = \App\Models\CaborKategoriPelatih::where('cabor_kategori_pelatih.pelatih_id', $user->pelatih->id)
                        ->where('is_active', 1)
                        ->whereNull('cabor_kategori_pelatih.deleted_at')
                        ->pluck('cabor_kategori_pelatih.cabor_id')
                        ->filter(function ($id) {
                            return $id !== null;
                        })
                        ->unique();
                }
                
                if ($roleId == 37 && $user->tenagaPendukung && $user->tenagaPendukung->id) { // Tenaga Pendukung
                    // Ambil langsung cabor_id dari tabel cabor_kategori_tenaga_pendukung (ada kolom cabor_id langsung)
                    $caborIds = \App\Models\CaborKategoriTenagaPendukung::where('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', $user->tenagaPendukung->id)
                        ->where('is_active', 1)
                        ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
                        ->pluck('cabor_kategori_tenaga_pendukung.cabor_id')
                        ->filter(function ($id) {
                            return $id !== null;
                        })
                        ->unique();
                }
                
                $cabors = \App\Models\Cabor::whereIn('id', $caborIds)
                    ->select('id', 'nama')
                    ->orderBy('nama')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'nama' => $item->nama,
                        ];
                    });
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $cabors,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Cabor List error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil daftar cabor.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get list kategori berdasarkan cabor_id
     * Peserta hanya melihat kategori yang mereka miliki
     */
    public function getKategoriByCabor(Request $request, $caborId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            $roleId = $user->current_role_id ?? null;
            
            // Non-peserta (Superadmin, Admin) - lihat semua kategori dari cabor tersebut
            if (!in_array($roleId, [35, 36, 37])) {
                $kategoris = \App\Models\CaborKategori::where('cabor_id', $caborId)
                    ->select('id', 'nama')
                    ->orderBy('nama')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'nama' => $item->nama,
                        ];
                    });
            } else {
                // Peserta (Atlet, Pelatih, Tenaga Pendukung) - hanya kategori mereka
                $kategoriIds = collect();
                
                if ($roleId == 35 && $user->atlet && $user->atlet->id) { // Atlet
                    $kategoriIds = \App\Models\CaborKategoriAtlet::where('atlet_id', $user->atlet->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at')
                        ->whereHas('caborKategori', function ($q) use ($caborId) {
                            $q->where('cabor_id', $caborId)
                                ->whereNull('deleted_at');
                        })
                        ->pluck('cabor_kategori_id')
                        ->unique();
                }
                
                if ($roleId == 36 && $user->pelatih && $user->pelatih->id) { // Pelatih
                    $kategoriIds = \App\Models\CaborKategoriPelatih::where('pelatih_id', $user->pelatih->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at')
                        ->whereHas('caborKategori', function ($q) use ($caborId) {
                            $q->where('cabor_id', $caborId)
                                ->whereNull('deleted_at');
                        })
                        ->pluck('cabor_kategori_id')
                        ->unique();
                }
                
                if ($roleId == 37 && $user->tenagaPendukung && $user->tenagaPendukung->id) { // Tenaga Pendukung
                    $kategoriIds = \App\Models\CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at')
                        ->whereHas('caborKategori', function ($q) use ($caborId) {
                            $q->where('cabor_id', $caborId)
                                ->whereNull('deleted_at');
                        })
                        ->pluck('cabor_kategori_id')
                        ->unique();
                }
                
                $kategoris = \App\Models\CaborKategori::whereIn('id', $kategoriIds)
                    ->where('cabor_id', $caborId)
                    ->select('id', 'nama')
                    ->orderBy('nama')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'nama' => $item->nama,
                        ];
                    });
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $kategoris,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Kategori By Cabor error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'cabor_id' => $caborId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil daftar kategori.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

}

