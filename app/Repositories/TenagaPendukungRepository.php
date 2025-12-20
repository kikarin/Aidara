<?php

namespace App\Repositories;

use App\Models\TenagaPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use App\Models\CaborKategori;
use App\Models\CaborKategoriTenagaPendukung;

class TenagaPendukungRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(TenagaPendukung $model)
    {
        $this->model = $model;
        $this->with  = [
            'media',
            'created_by_user',
            'updated_by_user',
            'user',
            'sertifikat',
            'sertifikat.media',
            'sertifikat.created_by_user',
            'sertifikat.updated_by_user',
            'prestasi',
            'prestasi.created_by_user',
            'prestasi.updated_by_user',
            'kesehatan',
            'kesehatan.created_by_user',
            'kesehatan.updated_by_user',
            'dokumen',
            'dokumen.created_by_user',
            'dokumen.updated_by_user',
            'dokumen.jenis_dokumen',
            'caborKategoriTenagaPendukung.cabor',
            'caborKategoriTenagaPendukung.caborKategori',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model->query();

        $auth = Auth::user();
        if ($auth && (int) $auth->current_role_id === 37) {
            $query->where('users_id', $auth->id);
        }

        // Filter untuk exclude tenaga pendukung yang sudah ada di kategori tertentu
        if (request('exclude_cabor_kategori_id')) {
            $excludeKategoriId = request('exclude_cabor_kategori_id');
            $query->whereNotExists(function ($subQuery) use ($excludeKategoriId) {
                $subQuery->select(DB::raw(1))
                    ->from('cabor_kategori_tenaga_pendukung')
                    ->whereColumn('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', 'tenaga_pendukungs.id')
                    ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $excludeKategoriId)
                    ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at'); // hanya relasi aktif
            });

            // Filter berdasarkan kategori_peserta dari cabor_kategori jika ada
            $caborKategori = CaborKategori::find($excludeKategoriId);
            if ($caborKategori && $caborKategori->kategori_peserta_id) {
                $query->whereExists(function ($subQuery) use ($caborKategori) {
                    $subQuery->select(DB::raw(1))
                        ->from('tenaga_pendukung_kategori_peserta')
                        ->whereColumn('tenaga_pendukung_kategori_peserta.tenaga_pendukung_id', 'tenaga_pendukungs.id')
                        ->where('tenaga_pendukung_kategori_peserta.mst_kategori_peserta_id', $caborKategori->kategori_peserta_id);
                });
            }
            
        }

        // Apply filters
        $this->applyFilters($query);

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nik', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('no_hp', 'like', '%'.$search.'%')
                    ->orWhere('jenis_kelamin', 'like', '%'.$search.'%')
                    ->orWhere('tempat_lahir', 'like', '%'.$search.'%')
                    ->orWhere('alamat', 'like', '%'.$search.'%');
            });
        }
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'no_hp', 'email', 'is_active', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $order);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }
        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);
        if ($perPage === -1) {
            $all         = $query->get();
            $transformed = collect($all)->map(function ($item) {
                // Pastikan relasi cabor dimuat sebelum konversi ke array
                $item->load(['caborKategoriTenagaPendukung.cabor', 'caborKategoriTenagaPendukung.caborKategori']);
                return $item->toArray();
            });
            $data += [
                'tenaga_pendukungs' => $transformed,
                'total'             => $transformed->count(),
                'currentPage'       => 1,
                'perPage'           => -1,
                'search'            => request('search', ''),
                'sort'              => request('sort', ''),
                'order'             => request('order', 'asc'),
            ];

            return $data;
        }
        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformed     = collect($items->items())->map(function ($item) {
            // Pastikan relasi cabor dimuat sebelum konversi ke array
            $item->load(['caborKategoriTenagaPendukung.cabor', 'caborKategoriTenagaPendukung.caborKategori']);
            return $item->toArray();
        });
        $data += [
            'tenaga_pendukungs' => $transformed,
            'total'             => $items->total(),
            'currentPage'       => $items->currentPage(),
            'perPage'           => $items->perPage(),
            'search'            => request('search', ''),
            'sort'              => request('sort', ''),
            'order'             => request('order', 'asc'),
        ];

        return $data;
    }

    /**
     * Apply filters to the query
     */
    protected function applyFilters($query)
    {
        // Filter by cabor_id (hanya tampilkan tenaga pendukung yang sudah di cabor ini)
        if (request('cabor_id') && request('cabor_id') !== 'all') {
            $caborId = request('cabor_id');
            $query->whereExists(function ($sub) use ($caborId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_tenaga_pendukung as cktp')
                    ->whereColumn('cktp.tenaga_pendukung_id', 'tenaga_pendukungs.id')
                    ->where('cktp.cabor_id', $caborId)
                    ->whereNull('cktp.deleted_at');
            });
        }

        // Exclude tenaga pendukung yang sudah ada di cabor tertentu
        if (request('exclude_cabor_id')) {
            $excludeCaborId = request('exclude_cabor_id');
            $query->whereNotExists(function ($sub) use ($excludeCaborId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_tenaga_pendukung as cktp')
                    ->whereColumn('cktp.tenaga_pendukung_id', 'tenaga_pendukungs.id')
                    ->where('cktp.cabor_id', $excludeCaborId)
                    ->whereNull('cktp.deleted_at');
            });
        }

        // Filter by cabor_kategori_id
        if (request('cabor_kategori_id') && request('cabor_kategori_id') !== 'all') {
            $caborKategoriId = request('cabor_kategori_id');
            $query->whereExists(function ($sub) use ($caborKategoriId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_tenaga_pendukung as cktp')
                    ->whereColumn('cktp.tenaga_pendukung_id', 'tenaga_pendukungs.id')
                    ->where('cktp.cabor_kategori_id', $caborKategoriId)
                    ->whereNull('cktp.deleted_at');
            });
        }

        // Filter by jenis kelamin
        if (request('jenis_kelamin') && request('jenis_kelamin') !== 'all') {
            $query->where('jenis_kelamin', request('jenis_kelamin'));
        }

        // Filter by status (is_active)
        if (request('status') && request('status') !== 'all') {
            $statusValue = request('status');
            \Log::info('Filtering by status:', ['status' => $statusValue, 'type' => gettype($statusValue)]);

            // Convert string to boolean/integer for database query
            if ($statusValue === '1' || $statusValue === 1 || $statusValue === true) {
                $query->where('is_active', 1);
            } elseif ($statusValue === '0' || $statusValue === 0 || $statusValue === false) {
                $query->where('is_active', 0);
            }
        }

        // Filter by kategori usia
        if (request('kategori_usia') && request('kategori_usia') !== 'all') {
            $this->applyKategoriUsiaFilter($query, request('kategori_usia'));
        }

        // Filter by lama bergabung
        if (request('lama_bergabung') && request('lama_bergabung') !== 'all') {
            $this->applyLamaBergabungFilter($query, request('lama_bergabung'));
        }

        // Filter by kategori_peserta_id (support both kategori_atlet_id for backward compatibility)
        $kategoriPesertaId = request('kategori_peserta_id') ?: request('kategori_atlet_id');
        if ($kategoriPesertaId && $kategoriPesertaId !== 'all') {
            $query->whereHas('kategoriPesertas', function ($q) use ($kategoriPesertaId) {
                $q->where('mst_kategori_peserta.id', $kategoriPesertaId);
            });
        }

        // Filter by date range
        if (request('filter_start_date') && request('filter_end_date')) {
            $query->whereBetween('created_at', [
                request('filter_start_date') . ' 00:00:00',
                request('filter_end_date') . ' 23:59:59',
            ]);
        }
    }

    /**
     * Apply kategori usia filter
     */
    protected function applyKategoriUsiaFilter($query, $kategori)
    {
        $today = now();

        switch ($kategori) {
            case 'dewasa_muda':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(25))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(18));
                break;
            case 'dewasa':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(35))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(26));
                break;
            case 'dewasa_tua':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(45))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(36));
                break;
            case 'senior':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(55))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(46));
                break;
            case 'veteran':
                $query->where('tanggal_lahir', '<', $today->copy()->subYears(56));
                break;
        }
    }

    /**
     * Apply lama bergabung filter
     */
    protected function applyLamaBergabungFilter($query, $kategori)
    {
        $today = now();

        switch ($kategori) {
            case 'baru':
                $query->where('tanggal_bergabung', '>=', $today->copy()->subYears(2));
                break;
            case 'sedang':
                $query->where('tanggal_bergabung', '>=', $today->copy()->subYears(5))
                      ->where('tanggal_bergabung', '<', $today->copy()->subYears(2));
                break;
            case 'lama':
                $query->where('tanggal_bergabung', '>=', $today->copy()->subYears(10))
                      ->where('tanggal_bergabung', '<', $today->copy()->subYears(5));
                break;
            case 'sangat_lama':
                $query->where('tanggal_bergabung', '<', $today->copy()->subYears(10));
                break;
        }
    }

    public function customCreateEdit($data, $item = null)
    {
        // Load kategori peserta yang sudah ada (multiple)
        if ($item && isset($item->id)) {
            $item->load(['kategoriPesertas', 'caborKategoriTenagaPendukung.cabor', 'caborKategoriTenagaPendukung.caborKategori']);
            $kategoriPesertasIds = $item->kategoriPesertas->pluck('id')->toArray();
            $data['kategori_pesertas'] = $kategoriPesertasIds;
            
            // Load semua cabor data dari pivot (yang langsung ke cabor, tanpa kategori)
            $caborKategoriTenagaPendukungList = $item->caborKategoriTenagaPendukung->where('cabor_kategori_id', null);
            
            // Convert item ke array dan tambahkan kategori_pesertas
            $itemArray = $item->toArray();
            $itemArray['kategori_pesertas'] = $kategoriPesertasIds;
            
            // Tambahkan cabor_kategori_tenaga_pendukung untuk form bisa akses
            $itemArray['cabor_kategori_tenaga_pendukung'] = $item->caborKategoriTenagaPendukung->toArray();
            
            // Ambil semua cabor_id yang unik (yang langsung ke cabor, tanpa kategori)
            $caborIds = $caborKategoriTenagaPendukungList->pluck('cabor_id')->unique()->values()->toArray();
            $itemArray['cabor_ids'] = $caborIds;
            
            // Tambahkan cabor data (untuk backward compatibility)
            $firstCaborKategoriTenagaPendukung = $caborKategoriTenagaPendukungList->first();
            if ($firstCaborKategoriTenagaPendukung) {
                $itemArray['cabor_id'] = $firstCaborKategoriTenagaPendukung->cabor_id;
                $itemArray['jenis_tenaga_pendukung'] = $firstCaborKategoriTenagaPendukung->jenis_tenaga_pendukung;
                $itemArray['posisi_atlet'] = $firstCaborKategoriTenagaPendukung->posisi_atlet;
            } else {
                $itemArray['cabor_id'] = null;
                $itemArray['jenis_tenaga_pendukung'] = null;
                $itemArray['posisi_atlet'] = null;
            }
            
            $data['item'] = $itemArray;
        } else {
            $data['item'] = $item;
            $data['kategori_pesertas'] = [];
        }

        return $data;
    }

    // Property untuk menyimpan kategori_pesertas sebelum di-unset
    private $kategoriPesertasForCallback = null;
    
    // Property untuk menyimpan cabor data sebelum di-unset
    private $caborDataForCallback = null;

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        // Jika user masih pending, set is_active ke 0 jika tidak ada di request
        if ($record && $userId) {
            $user = User::find($userId);
            if ($user && $user->registration_status === 'pending') {
                // Jika is_active tidak ada di request, set ke 0 (nonaktif)
                if (!isset($data['is_active'])) {
                    $data['is_active'] = 0;
                } else {
                    // Jika ada di request, tetap set ke 0 untuk user pending
                    $data['is_active'] = 0;
                }
            }
        }

        // Simpan kategori_pesertas sebelum di-unset untuk digunakan di callbackAfterStoreOrUpdate
        // Hapus dari data karena ini relasi many-to-many, bukan kolom di tabel tenaga_pendukungs
        if (isset($data['kategori_pesertas'])) {
            $this->kategoriPesertasForCallback = $data['kategori_pesertas'];
            unset($data['kategori_pesertas']);
        } else {
            $this->kategoriPesertasForCallback = null;
        }

        // Simpan cabor_ids (array), jenis_tenaga_pendukung, dan posisi_atlet untuk digunakan di callbackAfterStoreOrUpdate
        // Support backward compatibility dengan cabor_id (single)
        // Cek dari $data terlebih dahulu, lalu dari request sebagai fallback
        $caborIds = [];
        if (isset($data['cabor_ids']) && is_array($data['cabor_ids'])) {
            $caborIds = array_filter($data['cabor_ids']); // Remove null/empty values
        } elseif (request()->has('cabor_ids') && is_array(request()->input('cabor_ids'))) {
            // Fallback: ambil dari request langsung
            $caborIds = array_filter(request()->input('cabor_ids', []));
        } elseif (isset($data['cabor_id']) && $data['cabor_id']) {
            // Backward compatibility: jika masih pakai cabor_id (single)
            $caborIds = [$data['cabor_id']];
        } elseif (request()->has('cabor_id') && request()->input('cabor_id')) {
            // Fallback: ambil dari request langsung
            $caborIds = [request()->input('cabor_id')];
        }
        
        Log::info('TenagaPendukungRepository: customDataCreateUpdate - cabor_ids', [
            'cabor_ids_from_data' => $data['cabor_ids'] ?? 'not set',
            'cabor_ids_from_request' => request()->input('cabor_ids', 'not set'),
            'cabor_ids_final' => $caborIds,
            'method' => is_null($record) ? 'create' : 'update',
        ]);
        
        $this->caborDataForCallback = [
            'cabor_ids' => $caborIds,
            'jenis_tenaga_pendukung' => $data['jenis_tenaga_pendukung'] ?? request()->input('jenis_tenaga_pendukung'),
            'posisi_atlet' => $data['posisi_atlet'] ?? request()->input('posisi_atlet'),
        ];
        unset($data['cabor_id']);
        unset($data['cabor_ids']);
        unset($data['jenis_tenaga_pendukung']);
        unset($data['posisi_atlet']);

        Log::info('TenagaPendukungRepository: customDataCreateUpdate', [
            'data'   => $data,
            'method' => is_null($record) ? 'create' : 'update',
            'kategori_pesertas_saved' => $this->kategoriPesertasForCallback !== null,
        ]);

        return $data;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        // Note: Tidak perlu DB::beginTransaction() karena sudah dalam transaction dari RepositoryTrait
        try {
            // Sync data ke PesertaRegistration jika user masih pending
            if ($method === 'update' && $model->users_id) {
                $user = User::find($model->users_id);
                if ($user && $user->registration_status === 'pending') {
                    $registrationRepo = app(\App\Repositories\RegistrationRepository::class);
                    $registrationRepo->syncPesertaToRegistration($user, $model);
                }
            }

            Log::info('TenagaPendukungRepository: Starting file upload process', [
                'method'            => $method,
                'has_file'          => isset($data['file']),
                'file_data'         => $data['file'] ? 'File exists' : 'No file',
                'is_delete_foto'    => @$data['is_delete_foto'],
                'kategori_pesertas' => $data['kategori_pesertas'] ?? 'not set',
            ]);
            if (@$data['is_delete_foto'] == 1) {
                $model->clearMediaCollection('images');
                Log::info('TenagaPendukungRepository: Cleared media collection');
            }
            if (@$data['file']) {
                Log::info('TenagaPendukungRepository: Adding media file', [
                    'file_name' => $data['file']->getClientOriginalName(),
                    'file_size' => $data['file']->getSize(),
                    'model_id'  => $model->id,
                ]);
                $media = $model->addMedia($data['file'])
                    ->usingName($data['nama'])
                    ->toMediaCollection('images');
                Log::info('TenagaPendukungRepository: Media added successfully', [
                    'media_id'  => $media->id,
                    'file_name' => $media->file_name,
                    'disk'      => $media->disk,
                    'path'      => $media->getPath(),
                ]);
            }

            // Handle Multiple Kategori Peserta
            // Selalu sync, bahkan jika array kosong (untuk menghapus relasi yang ada)
            // Gunakan kategori_pesertas dari property yang disimpan karena sudah di-unset dari $data di customDataCreateUpdate
            $kategoriPesertasToSync = $this->kategoriPesertasForCallback ?? request()->input('kategori_pesertas');
            
            if ($kategoriPesertasToSync !== null) {
                // Filter out empty values dan convert ke integer
                $kategoriIds = [];
                if (is_array($kategoriPesertasToSync)) {
                    $kategoriIds = array_filter($kategoriPesertasToSync, function ($id) {
                        return !empty($id) && $id !== null;
                    });
                    // Convert semua ID ke integer untuk memastikan tipe data benar
                    $kategoriIds = array_map('intval', $kategoriIds);
                    // Remove duplicates dan re-index array
                    $kategoriIds = array_values(array_unique($kategoriIds));
                }
                
                Log::info('TenagaPendukungRepository: Syncing KategoriPesertas', [
                    'tenaga_pendukung_id' => $model->id,
                    'kategori_ids' => $kategoriIds,
                    'kategori_ids_type' => array_map('gettype', $kategoriIds),
                ]);
                
                // Sync dengan array kosong jika tidak ada kategori (untuk menghapus semua relasi)
                $model->kategoriPesertas()->sync($kategoriIds);
                // Refresh model untuk memastikan relasi ter-load
                $model->refresh();
                $model->load('kategoriPesertas');
                Log::info('TenagaPendukungRepository: Updated KategoriPesertas', ['tenaga_pendukung_id' => $model->id, 'kategori_ids' => $kategoriIds]);
            } else {
                Log::warning('TenagaPendukungRepository: kategori_pesertas not set in data or request', ['data_keys' => array_keys($data)]);
            }

            // Handle Cabor Assignment (langsung ke cabor tanpa kategori)
            // Support multiple cabor_ids (array) dengan backward compatibility untuk cabor_id (single)
            $caborData = $this->caborDataForCallback ?? [
                'cabor_ids' => request()->input('cabor_ids', []),
                'cabor_id' => request()->input('cabor_id'), // Backward compatibility
                'jenis_tenaga_pendukung' => request()->input('jenis_tenaga_pendukung'),
                'posisi_atlet' => request()->input('posisi_atlet'),
            ];
            
            // Get cabor_ids array (support backward compatibility)
            $caborIds = [];
            if (!empty($caborData['cabor_ids']) && is_array($caborData['cabor_ids'])) {
                $caborIds = array_filter($caborData['cabor_ids']); // Remove null/empty values
            } elseif (!empty($caborData['cabor_id'])) {
                // Backward compatibility: jika masih pakai cabor_id (single)
                $caborIds = [$caborData['cabor_id']];
            }
            
            // Selalu hapus relasi cabor yang tidak ada di array baru (soft delete)
            // Ini penting untuk handle kasus ketika cabor_ids kosong atau berubah
            $existingCaborIds = CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $model->id)
                ->whereNull('cabor_kategori_id') // Hanya yang langsung ke cabor (tanpa kategori)
                ->pluck('cabor_id')
                ->toArray();
            
            $caborIdsToDelete = array_diff($existingCaborIds, $caborIds);
            if (!empty($caborIdsToDelete)) {
                CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $model->id)
                    ->whereNull('cabor_kategori_id')
                    ->whereIn('cabor_id', $caborIdsToDelete)
                    ->delete();
                Log::info('TenagaPendukungRepository: Deleted cabor assignments', [
                    'tenaga_pendukung_id' => $model->id,
                    'deleted_cabor_ids' => $caborIdsToDelete,
                ]);
            }
            
            // Jika ada cabor_ids, create atau update relasi
            if (!empty($caborIds)) {
                $jenisTenagaPendukung = $caborData['jenis_tenaga_pendukung'] ?? null;
                $posisiAtlet = $caborData['posisi_atlet'] ?? null;
                
                // Create atau update relasi untuk setiap cabor_id
                foreach ($caborIds as $caborId) {
                    if (empty($caborId)) continue;
                
                // Cek apakah sudah ada relasi ke cabor ini
                $existingRelation = CaborKategoriTenagaPendukung::where('cabor_id', $caborId)
                    ->where('tenaga_pendukung_id', $model->id)
                        ->whereNull('cabor_kategori_id') // Hanya yang langsung ke cabor
                    ->first();
                
                if ($existingRelation) {
                    // Update jenis dan posisi jika sudah ada
                    $existingRelation->update([
                        'jenis_tenaga_pendukung' => $jenisTenagaPendukung,
                        'posisi_atlet' => $posisiAtlet,
                            'is_active' => 1,
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('TenagaPendukungRepository: Updated cabor assignment', [
                        'tenaga_pendukung_id' => $model->id,
                        'cabor_id' => $caborId,
                        'jenis_tenaga_pendukung' => $jenisTenagaPendukung,
                        'posisi_atlet' => $posisiAtlet,
                    ]);
                } else {
                    // Buat relasi baru tanpa kategori (cabor_kategori_id = null)
                    CaborKategoriTenagaPendukung::create([
                        'cabor_id' => $caborId,
                        'cabor_kategori_id' => null, // Langsung ke cabor tanpa kategori
                        'tenaga_pendukung_id' => $model->id,
                        'jenis_tenaga_pendukung' => $jenisTenagaPendukung,
                        'posisi_atlet' => $posisiAtlet,
                        'is_active' => 1,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('TenagaPendukungRepository: Created new cabor assignment', [
                        'tenaga_pendukung_id' => $model->id,
                        'cabor_id' => $caborId,
                        'jenis_tenaga_pendukung' => $jenisTenagaPendukung,
                        'posisi_atlet' => $posisiAtlet,
                    ]);
                    }
                }
            }

            Log::info('TenagaPendukungRepository: callbackAfterStoreOrUpdate completed successfully');

            return $model;
        } catch (\Exception $e) {
            Log::error('TenagaPendukungRepository: Error during file upload or other save operations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    public function getDetailWithRelations($id)
    {
        $with = array_merge($this->with, ['kecamatan', 'kelurahan', 'kategoriPesertas']);

        $tenagaPendukung = $this->model->with($with)->findOrFail($id);

        // Pastikan relasi cabor dimuat dengan benar
        $tenagaPendukung->load(['caborKategoriTenagaPendukung.cabor', 'caborKategoriTenagaPendukung.caborKategori']);

        return $tenagaPendukung;
    }

    /**
     * Handle Tenaga Pendukung Akun creation/update
     */
    public function handleTenagaPendukungAkun($tenagaPendukung, $data)
    {
        $userId   = Auth::check() ? Auth::id() : null;
        $userData = [
            'name'            => $tenagaPendukung->nama,
            'email'           => $data['akun_email'],
            'no_hp'           => $tenagaPendukung->no_hp,
            'is_active'       => 1,
            'current_role_id' => 37,
            'created_by'      => $userId,
            'updated_by'      => $userId,
        ];

        // Jika ada password, hash password
        if (isset($data['akun_password']) && $data['akun_password']) {
            $userData['password'] = bcrypt($data['akun_password']);
        }

        // Jika sudah ada users_id, update user
        if (isset($data['users_id']) && $data['users_id']) {
            $user = User::find($data['users_id']);
            if ($user) {
                $user->update($userData);

                // Ensure role is assigned using Spatie Permission
                $role = Role::find(37); // Role Tenaga Pendukung
                if ($role && !$user->hasRole($role)) {
                    $user->assignRole($role);
                }

                Log::info('TenagaPendukungRepository: Updated existing user for tenaga pendukung', [
                    'tenaga_pendukung_id' => $tenagaPendukung->id,
                    'user_id'             => $user->id,
                ]);
            }
        } else {
            // Create new user
            $user = User::create($userData);

            // Assign role Tenaga Pendukung using Spatie Permission
            $role = Role::find(37); // Role Tenaga Pendukung
            if ($role) {
                $user->assignRole($role);
            }

            // Also create users_role record for compatibility
            $user->users_role()->create([
                'users_id'   => $user->id,
                'role_id'    => 37,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $tenagaPendukung->update(['users_id' => $user->id]);

            Log::info('TenagaPendukungRepository: Created new user for tenaga pendukung', [
                'tenaga_pendukung_id' => $tenagaPendukung->id,
                'user_id'             => $user->id,
            ]);
        }
    }

    /**
     * Get jumlah karakteristik tenaga pendukung
     */
    public function jumlah_karakteristik($data = [])
    {
        $tanggal_awal  = $data['tanggal_awal']  ?? null;
        $tanggal_akhir = $data['tanggal_akhir'] ?? null;

        // Ambil semua data yang akan direkap
        $this->with = [];
        $getData    = $this->getAll([
            'filter_start_date' => $tanggal_awal,
            'filter_end_date'   => $tanggal_akhir,
        ]);
        $totalData = count($getData); // total keseluruhan

        $result = [];

        // Jenis Kelamin
        $listIndikator         = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
        $listIndikator['NULL'] = '-';

        $indikatorData = [];
        foreach ($listIndikator as $key => $value) {
            $jumlah = collect($getData)->filter(function ($item) use ($key) {
                $key_value = $item->jenis_kelamin ?? null;

                if ($key === 'NULL') {
                    return is_null($key_value);
                }

                return $key_value == $key;
            })->count();
            $persentase = $totalData > 0 ? round(($jumlah / $totalData) * 100, 2) : 0;

            $indikatorData[] = [
                'nama_indikator' => $value,
                'jumlah'         => $jumlah,
                'persentase'     => $persentase,
            ];
        }

        $result[] = [
            'key'  => 'jenis_kelamin',
            'name' => 'Jenis Kelamin',
            'data' => $indikatorData,
        ];

        // Status Aktif
        $listIndikator         = [1 => 'Aktif', 0 => 'Nonaktif'];
        $listIndikator['NULL'] = '-';

        $indikatorData = [];
        foreach ($listIndikator as $key => $value) {
            $jumlah = collect($getData)->filter(function ($item) use ($key) {
                $key_value = $item->is_active ?? null;

                if ($key === 'NULL') {
                    return is_null($key_value);
                }

                return $key_value == $key;
            })->count();
            $persentase = $totalData > 0 ? round(($jumlah / $totalData) * 100, 2) : 0;

            $indikatorData[] = [
                'nama_indikator' => $value,
                'jumlah'         => $jumlah,
                'persentase'     => $persentase,
            ];
        }

        $result[] = [
            'key'  => 'status_aktif',
            'name' => 'Status Aktif',
            'data' => $indikatorData,
        ];

        // Usia (dibagi berdasarkan range)
        $usiaRanges = [
            'dewasa_muda' => ['min' => 18, 'max' => 25, 'label' => 'Dewasa Muda (18-25 tahun)'],
            'dewasa'      => ['min' => 26, 'max' => 35, 'label' => 'Dewasa (26-35 tahun)'],
            'dewasa_tua'  => ['min' => 36, 'max' => 45, 'label' => 'Dewasa Tua (36-45 tahun)'],
            'senior'      => ['min' => 46, 'max' => 55, 'label' => 'Senior (46-55 tahun)'],
            'veteran'     => ['min' => 56, 'max' => 100, 'label' => 'Veteran (56+ tahun)'],
        ];

        $indikatorData = [];
        foreach ($usiaRanges as $key => $range) {
            $jumlah = collect($getData)->filter(function ($item) use ($range) {
                if (!$item->tanggal_lahir) {
                    return false;
                }

                $usia = date_diff(date_create($item->tanggal_lahir), date_create('today'))->y;
                return $usia >= $range['min'] && $usia <= $range['max'];
            })->count();

            $persentase = $totalData > 0 ? round(($jumlah / $totalData) * 100, 2) : 0;

            $indikatorData[] = [
                'nama_indikator' => $range['label'],
                'jumlah'         => $jumlah,
                'persentase'     => $persentase,
            ];
        }

        // Tambahkan kategori "Tidak ada data tanggal lahir"
        $jumlahNoTanggalLahir = collect($getData)->filter(function ($item) {
            return !$item->tanggal_lahir;
        })->count();

        if ($jumlahNoTanggalLahir > 0) {
            $persentase      = $totalData > 0 ? round(($jumlahNoTanggalLahir / $totalData) * 100, 2) : 0;
            $indikatorData[] = [
                'nama_indikator' => 'Tidak ada data tanggal lahir',
                'jumlah'         => $jumlahNoTanggalLahir,
                'persentase'     => $persentase,
            ];
        }

        $result[] = [
            'key'  => 'usia',
            'name' => 'Kategori Usia',
            'data' => $indikatorData,
        ];

        // Lama Bergabung
        $lamaBergabungRanges = [
            'baru'        => ['min' => 0, 'max' => 2, 'label' => 'Baru bergabung (< 2 tahun)'],
            'sedang'      => ['min' => 2, 'max' => 5, 'label' => 'Sedang (2-5 tahun)'],
            'lama'        => ['min' => 5, 'max' => 10, 'label' => 'Lama (5-10 tahun)'],
            'sangat_lama' => ['min' => 10, 'max' => 100, 'label' => 'Sangat lama (10+ tahun)'],
        ];

        $indikatorData = [];
        foreach ($lamaBergabungRanges as $key => $range) {
            $jumlah = collect($getData)->filter(function ($item) use ($range) {
                if (!$item->tanggal_bergabung) {
                    return false;
                }

                $lamaBergabung = date_diff(date_create($item->tanggal_bergabung), date_create('today'))->y;
                return $lamaBergabung >= $range['min'] && $lamaBergabung <= $range['max'];
            })->count();

            $persentase = $totalData > 0 ? round(($jumlah / $totalData) * 100, 2) : 0;

            $indikatorData[] = [
                'nama_indikator' => $range['label'],
                'jumlah'         => $jumlah,
                'persentase'     => $persentase,
            ];
        }

        // Tambahkan kategori "Tidak ada data tanggal bergabung"
        $jumlahNoTanggalBergabung = collect($getData)->filter(function ($item) {
            return !$item->tanggal_bergabung;
        })->count();

        if ($jumlahNoTanggalBergabung > 0) {
            $persentase      = $totalData > 0 ? round(($jumlahNoTanggalBergabung / $totalData) * 100, 2) : 0;
            $indikatorData[] = [
                'nama_indikator' => 'Tidak ada data tanggal bergabung',
                'jumlah'         => $jumlahNoTanggalBergabung,
                'persentase'     => $persentase,
            ];
        }

        $result[] = [
            'key'  => 'lama_bergabung',
            'name' => 'Lama Bergabung',
            'data' => $indikatorData,
        ];

        // Cabor (agregasi berdasarkan relasi cabor kategori -> cabor)
        try {
            $tpIds = collect($getData)->pluck('id')->filter()->values()->all();
            if (!empty($tpIds)) {
                $rows = DB::table('cabor_kategori_tenaga_pendukung as cktp')
                    ->join('cabor as c', 'cktp.cabor_id', '=', 'c.id')
                    ->whereNull('cktp.deleted_at')
                    ->whereIn('cktp.tenaga_pendukung_id', $tpIds)
                    ->select('c.id', 'c.nama', DB::raw('COUNT(DISTINCT cktp.tenaga_pendukung_id) as jumlah'))
                    ->groupBy('c.id', 'c.nama')
                    ->orderBy('c.nama')
                    ->get();

                $indikatorData = [];
                foreach ($rows as $row) {
                    $jumlah          = (int) $row->jumlah;
                    $persentase      = $totalData > 0 ? round(($jumlah / $totalData) * 100, 2) : 0;
                    $indikatorData[] = [
                        'nama_indikator' => $row->nama ?? '-',
                        'jumlah'         => $jumlah,
                        'persentase'     => $persentase,
                    ];
                }

                $result[] = [
                    'key'  => 'cabor',
                    'name' => 'Cabor',
                    'data' => $indikatorData,
                ];
            } else {
                $result[] = [
                    'key'  => 'cabor',
                    'name' => 'Cabor',
                    'data' => [],
                ];
            }
        } catch (\Exception $e) {
            $result[] = [
                'key'  => 'cabor',
                'name' => 'Cabor',
                'data' => [],
            ];
        }

        return $result;
    }
}
