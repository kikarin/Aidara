<?php

namespace App\Repositories;

use App\Models\Pelatih;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;


class PelatihRepository
{
    use RepositoryTrait;

    protected $model;

    protected $pelatihSertifikatRepository;

    public function __construct(Pelatih $model, PelatihSertifikatRepository $pelatihSertifikatRepository)
    {
        $this->model                       = $model;
        $this->pelatihSertifikatRepository = $pelatihSertifikatRepository;
        $this->with                        = [
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
            'caborKategoriPelatih.cabor',
            'caborKategoriPelatih.caborKategori',
            'kategoriPesertas',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model->query();

        $auth = Auth::user();
        if ($auth && (int) $auth->current_role_id === 36) {
            $query->where('users_id', $auth->id);
        }

        // Filter untuk exclude pelatih yang sudah ada di kategori tertentu
        if (request('exclude_cabor_kategori_id')) {
            $excludeKategoriId = request('exclude_cabor_kategori_id');
            $query->whereNotExists(function ($subQuery) use ($excludeKategoriId) {
                $subQuery->select(DB::raw(1))
                    ->from('cabor_kategori_pelatih')
                    ->whereColumn('cabor_kategori_pelatih.pelatih_id', 'pelatihs.id')
                    ->where('cabor_kategori_pelatih.cabor_kategori_id', $excludeKategoriId)
                    ->whereNull('cabor_kategori_pelatih.deleted_at'); // hanya relasi aktif
            });

            // Filter berdasarkan kategori_peserta dari cabor_kategori jika ada
            $caborKategori = \App\Models\CaborKategori::find($excludeKategoriId);
            if ($caborKategori && $caborKategori->kategori_peserta_id) {
                $query->whereExists(function ($subQuery) use ($caborKategori) {
                    $subQuery->select(DB::raw(1))
                        ->from('pelatih_kategori_peserta')
                        ->whereColumn('pelatih_kategori_peserta.pelatih_id', 'pelatihs.id')
                        ->where('pelatih_kategori_peserta.mst_kategori_peserta_id', $caborKategori->kategori_peserta_id);
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
                $item->load(['caborKategoriPelatih.cabor', 'caborKategoriPelatih.caborKategori']);
                return $item->toArray();
            });
            $data += [
                'pelatihs'    => $transformed,
                'total'       => $transformed->count(),
                'currentPage' => 1,
                'perPage'     => -1,
                'search'      => request('search', ''),
                'sort'        => request('sort', ''),
                'order'       => request('order', 'asc'),
            ];

            return $data;
        }
        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformed     = collect($items->items())->map(function ($item) {
            // Pastikan relasi cabor dimuat sebelum konversi ke array
            $item->load(['caborKategoriPelatih.cabor', 'caborKategoriPelatih.caborKategori']);
            return $item->toArray();
        });
        $data += [
            'pelatihs'    => $transformed,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => request('search', ''),
            'sort'        => request('sort', ''),
            'order'       => request('order', 'asc'),
        ];

        return $data;
    }

    /**
     * Apply filters to the query
     */
    protected function applyFilters($query)
    {
        // Filter by cabor_id (hanya tampilkan pelatih yang sudah di cabor ini)
        if (request('cabor_id') && request('cabor_id') !== 'all') {
            $caborId = request('cabor_id');
            $query->whereExists(function ($sub) use ($caborId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_pelatih as ckp')
                    ->whereColumn('ckp.pelatih_id', 'pelatihs.id')
                    ->where('ckp.cabor_id', $caborId)
                    ->whereNull('ckp.deleted_at');
            });
        }

        // Exclude pelatih yang sudah ada di cabor tertentu
        if (request('exclude_cabor_id')) {
            $excludeCaborId = request('exclude_cabor_id');
            $query->whereNotExists(function ($sub) use ($excludeCaborId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_pelatih as ckp')
                    ->whereColumn('ckp.pelatih_id', 'pelatihs.id')
                    ->where('ckp.cabor_id', $excludeCaborId)
                    ->whereNull('ckp.deleted_at');
            });
        }

        // Filter by cabor_kategori_id
        if (request('cabor_kategori_id') && request('cabor_kategori_id') !== 'all') {
            $caborKategoriId = request('cabor_kategori_id');
            $query->whereExists(function ($sub) use ($caborKategoriId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_pelatih as ckp')
                    ->whereColumn('ckp.pelatih_id', 'pelatihs.id')
                    ->where('ckp.cabor_kategori_id', $caborKategoriId)
                    ->whereNull('ckp.deleted_at');
            });
        }

        // Filter by jenis kelamin
        if (request('jenis_kelamin') && request('jenis_kelamin') !== 'all') {
            $query->where('jenis_kelamin', request('jenis_kelamin'));
        }

        // Filter by status (is_active)
        if (request('status') && request('status') !== 'all') {
            $statusValue = request('status');
            Log::info('Filtering by status:', ['status' => $statusValue, 'type' => gettype($statusValue)]);

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
        // Tambahkan relasi untuk nanti kecamatan/kelurahan
        // Load kategori peserta yang sudah ada (multiple)
        if ($item && isset($item->id)) {
            $item->load(['kategoriPesertas', 'caborKategoriPelatih.cabor']);
            $kategoriPesertasIds = $item->kategoriPesertas->pluck('id')->toArray();
            $data['kategori_pesertas'] = $kategoriPesertasIds;
            
            // Load cabor data dari pivot (ambil yang pertama jika ada)
            $caborKategoriPelatih = $item->caborKategoriPelatih->first();
            
            // Convert item ke array dan tambahkan kategori_pesertas
            $itemArray = $item->toArray();
            $itemArray['kategori_pesertas'] = $kategoriPesertasIds;
            
            // Tambahkan cabor data
            if ($caborKategoriPelatih) {
                $itemArray['cabor_id'] = $caborKategoriPelatih->cabor_id;
                $itemArray['jenis_pelatih'] = $caborKategoriPelatih->jenis_pelatih;
                $itemArray['posisi_atlet'] = $caborKategoriPelatih->posisi_atlet;
            } else {
                $itemArray['cabor_id'] = null;
                $itemArray['jenis_pelatih'] = null;
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
        // Hapus dari data karena ini relasi many-to-many, bukan kolom di tabel pelatihs
        if (isset($data['kategori_pesertas'])) {
            $this->kategoriPesertasForCallback = $data['kategori_pesertas'];
            unset($data['kategori_pesertas']);
        } else {
            $this->kategoriPesertasForCallback = null;
        }

        // Simpan cabor_id, jenis_pelatih, dan posisi_atlet untuk digunakan di callbackAfterStoreOrUpdate
        $this->caborDataForCallback = [
            'cabor_id' => $data['cabor_id'] ?? null,
            'jenis_pelatih' => $data['jenis_pelatih'] ?? null,
            'posisi_atlet' => $data['posisi_atlet'] ?? null,
        ];
        unset($data['cabor_id']);
        unset($data['jenis_pelatih']);
        unset($data['posisi_atlet']);

        Log::info('PelatihRepository: customDataCreateUpdate', [
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
            // Ambil kategori_pesertas dari property yang disimpan di customDataCreateUpdate
            // atau dari request sebagai fallback
            $kategoriPesertas = $this->kategoriPesertasForCallback ?? request()->input('kategori_pesertas');
            
            Log::info('PelatihRepository: Starting callbackAfterStoreOrUpdate', [
                'method'            => $method,
                'has_file'          => isset($data['file']),
                'file_data'         => $data['file'] ? 'File exists' : 'No file',
                'is_delete_foto'    => @$data['is_delete_foto'],
                'kategori_pesertas' => $kategoriPesertas ?? 'not set',
                'kategori_pesertas_type' => $kategoriPesertas ? gettype($kategoriPesertas) : 'null',
                'kategori_pesertas_source' => $this->kategoriPesertasForCallback !== null ? 'property' : 'request',
            ]);

            // Handle file upload
            if (@$data['is_delete_foto'] == 1) {
                $model->clearMediaCollection('images');
                Log::info('PelatihRepository: Cleared media collection');
            }

            if (@$data['file']) {
                Log::info('PelatihRepository: Adding media file', [
                    'file_name' => $data['file']->getClientOriginalName(),
                    'file_size' => $data['file']->getSize(),
                    'model_id'  => $model->id,
                ]);

                $media = $model->addMedia($data['file'])
                    ->usingName($data['nama'])
                    ->toMediaCollection('images');

                Log::info('PelatihRepository: Media added successfully', [
                    'media_id'  => $media->id,
                    'file_name' => $media->file_name,
                    'disk'      => $media->disk,
                    'path'      => $media->getPath(),
                ]);
            }

            // Handle Multiple Kategori Peserta
            // Selalu sync, bahkan jika array kosong (untuk menghapus relasi yang ada)
            // Gunakan kategori_pesertas dari request karena sudah di-unset dari $data di customDataCreateUpdate
            $kategoriPesertasToSync = $kategoriPesertas ?? request()->input('kategori_pesertas');
            
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
                
                Log::info('PelatihRepository: Syncing KategoriPesertas', [
                    'pelatih_id' => $model->id,
                    'kategori_ids' => $kategoriIds,
                    'kategori_ids_type' => array_map('gettype', $kategoriIds),
                ]);
                
                // Sync dengan array kosong jika tidak ada kategori (untuk menghapus semua relasi)
                $model->kategoriPesertas()->sync($kategoriIds);
                // Refresh model untuk memastikan relasi ter-load
                $model->refresh();
                $model->load('kategoriPesertas');
                Log::info('PelatihRepository: Updated KategoriPesertas', ['pelatih_id' => $model->id, 'kategori_ids' => $kategoriIds]);
            } else {
                Log::warning('PelatihRepository: kategori_pesertas not set in data or request', ['data_keys' => array_keys($data)]);
            }

            // Sync data ke PesertaRegistration SETELAH sync kategori peserta
            // Ini penting agar kategori peserta yang baru ter-sync ke PesertaRegistration
            if ($method === 'update' && $model->users_id) {
                $user = User::find($model->users_id);
                if ($user) {
                    $registrationRepo = app(\App\Repositories\RegistrationRepository::class);
                    // Pastikan model sudah di-refresh dengan kategori peserta sebelum sync
                    $registrationRepo->syncPesertaToRegistration($user, $model);
                    Log::info('PelatihRepository: Synced data to PesertaRegistration after update', ['pelatih_id' => $model->id, 'user_id' => $user->id]);
                }
            }

            // Handle Cabor Assignment (langsung ke cabor tanpa kategori)
            $caborData = $this->caborDataForCallback ?? [
                'cabor_id' => request()->input('cabor_id'),
                'jenis_pelatih' => request()->input('jenis_pelatih'),
                'posisi_atlet' => request()->input('posisi_atlet'),
            ];
            
            if (!empty($caborData['cabor_id'])) {
                $caborId = $caborData['cabor_id'];
                $jenisPelatih = $caborData['jenis_pelatih'] ?? null;
                $posisiAtlet = $caborData['posisi_atlet'] ?? null;
                
                // Cek apakah sudah ada relasi ke cabor ini
                $existingRelation = \App\Models\CaborKategoriPelatih::where('cabor_id', $caborId)
                    ->where('pelatih_id', $model->id)
                    ->first();
                
                if ($existingRelation) {
                    // Update jenis dan posisi jika sudah ada
                    $existingRelation->update([
                        'jenis_pelatih' => $jenisPelatih,
                        'posisi_atlet' => $posisiAtlet,
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('PelatihRepository: Updated cabor assignment', [
                        'pelatih_id' => $model->id,
                        'cabor_id' => $caborId,
                        'jenis_pelatih' => $jenisPelatih,
                        'posisi_atlet' => $posisiAtlet,
                    ]);
                } else {
                    // Buat relasi baru tanpa kategori (cabor_kategori_id = null)
                    \App\Models\CaborKategoriPelatih::create([
                        'cabor_id' => $caborId,
                        'cabor_kategori_id' => null, // Langsung ke cabor tanpa kategori
                        'pelatih_id' => $model->id,
                        'jenis_pelatih' => $jenisPelatih,
                        'posisi_atlet' => $posisiAtlet,
                        'is_active' => 1,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('PelatihRepository: Created new cabor assignment', [
                        'pelatih_id' => $model->id,
                        'cabor_id' => $caborId,
                        'jenis_pelatih' => $jenisPelatih,
                        'posisi_atlet' => $posisiAtlet,
                    ]);
                }
            }

            Log::info('PelatihRepository: callbackAfterStoreOrUpdate completed successfully');

            return $model;
        } catch (\Exception $e) {
            Log::error('PelatihRepository: Error during file upload or other save operations', [
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

        $pelatih = $this->model->with($with)->findOrFail($id);

        $pelatih->load(['caborKategoriPelatih.cabor', 'caborKategoriPelatih.caborKategori']);

        return $pelatih;
    }

    /**
     * Handle Pelatih Akun creation/update
     */
    public function handlePelatihAkun($pelatih, $data)
    {
        $userId   = Auth::check() ? Auth::id() : null;
        $userData = [
            'name'            => $pelatih->nama,
            'email'           => $data['akun_email'],
            'no_hp'           => $pelatih->no_hp,
            'is_active'       => 1,
            'current_role_id' => 36,
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
                $role = Role::find(36); // Role Pelatih
                if ($role && !$user->hasRole($role)) {
                    $user->assignRole($role);
                }

                Log::info('PelatihRepository: Updated existing user for pelatih', [
                    'pelatih_id' => $pelatih->id,
                    'user_id'    => $user->id,
                ]);
            }
        } else {
            // Create new user
            $user = User::create($userData);

            // Assign role Pelatih using Spatie Permission
            $role = Role::find(36); // Role Pelatih
            if ($role) {
                $user->assignRole($role);
            }

            // Also create users_role record for compatibility
            $user->users_role()->create([
                'users_id'   => $user->id,
                'role_id'    => 36,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $pelatih->update(['users_id' => $user->id]);

            Log::info('PelatihRepository: Created new user for pelatih', [
                'pelatih_id' => $pelatih->id,
                'user_id'    => $user->id,
            ]);
        }
    }

    /**
     * Get jumlah karakteristik pelatih
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
            $pelatihIds = collect($getData)->pluck('id')->filter()->values()->all();
            if (!empty($pelatihIds)) {
                $rows = DB::table('cabor_kategori_pelatih as ckp')
                    ->join('cabor as c', 'ckp.cabor_id', '=', 'c.id')
                    ->whereNull('ckp.deleted_at')
                    ->whereIn('ckp.pelatih_id', $pelatihIds)
                    ->select('c.id', 'c.nama', DB::raw('COUNT(DISTINCT ckp.pelatih_id) as jumlah'))
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
