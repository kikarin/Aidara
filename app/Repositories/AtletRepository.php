<?php

namespace App\Repositories;

use App\Models\Atlet;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use App\Models\MstParameter;

class AtletRepository
{
    use RepositoryTrait;

    protected $model;

    protected $atletOrangTuaRepository;

    protected $atletParameterUmumRepository;

    public function __construct(Atlet $model, AtletOrangTuaRepository $atletOrangTuaRepository, AtletParameterUmumRepository $atletParameterUmumRepository)
    {
        $this->model                        = $model;
        $this->atletOrangTuaRepository      = $atletOrangTuaRepository;
        $this->atletParameterUmumRepository = $atletParameterUmumRepository;
        $this->with                         = [
            'media',
            'created_by_user',
            'updated_by_user',
            'user',
            'atletOrangTua.created_by_user',
            'atletOrangTua.updated_by_user',
            'sertifikat',
            'sertifikat.media',
            'sertifikat.created_by_user',
            'sertifikat.updated_by_user',
            'prestasi',
            'prestasi.created_by_user',
            'prestasi.updated_by_user',
            'dokumen',
            'dokumen.created_by_user',
            'dokumen.updated_by_user',
            'kesehatan',
            'kesehatan.created_by_user',
            'kesehatan.updated_by_user',
            'caborKategoriAtlet.cabor',
            'caborKategoriAtlet.cabor.kategoriPeserta',
            'caborKategoriAtlet.caborKategori',
            'kategoriAtlet',
            'kategoriPesertas',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model->query();

        $auth = Auth::user();
        if ($auth && (int) $auth->current_role_id === 35) {
            // Atlet hanya melihat data mereka sendiri
            $query->where('users_id', $auth->id);
        } elseif ($auth && (int) $auth->current_role_id === 36) {
            // Pelatih hanya melihat atlet yang memiliki kategori peserta yang sama
            if ($auth->pelatih && $auth->pelatih->id) {
                // Ambil kategori peserta dari pelatih
                $pelatihKategoriPesertaIds = DB::table('pelatih_kategori_peserta')
                    ->where('pelatih_id', $auth->pelatih->id)
                    ->whereNull('deleted_at')
                    ->pluck('mst_kategori_peserta_id')
                    ->toArray();
                
                if (!empty($pelatihKategoriPesertaIds)) {
                    // Filter atlet yang memiliki kategori peserta yang sama dengan pelatih
                    $query->whereExists(function ($subQuery) use ($pelatihKategoriPesertaIds) {
                        $subQuery->select(DB::raw(1))
                            ->from('atlet_kategori_peserta')
                            ->whereColumn('atlet_kategori_peserta.atlet_id', 'atlets.id')
                            ->whereIn('atlet_kategori_peserta.mst_kategori_peserta_id', $pelatihKategoriPesertaIds)
                            ->whereNull('atlet_kategori_peserta.deleted_at'); // Filter soft deleted
                    });
                } else {
                    // Jika pelatih tidak punya kategori peserta, tidak tampilkan atlet apapun
                    $query->whereRaw('1 = 0');
                }
            }
        } elseif ($auth && (int) $auth->current_role_id === 37) {
            // Tenaga Pendukung hanya melihat atlet yang memiliki kategori peserta yang sama
            if ($auth->tenagaPendukung && $auth->tenagaPendukung->id) {
                // Ambil kategori peserta dari tenaga pendukung
                $tenagaPendukungKategoriPesertaIds = DB::table('tenaga_pendukung_kategori_peserta')
                    ->where('tenaga_pendukung_id', $auth->tenagaPendukung->id)
                    ->whereNull('deleted_at')
                    ->pluck('mst_kategori_peserta_id')
                    ->toArray();
                
                if (!empty($tenagaPendukungKategoriPesertaIds)) {
                    // Filter atlet yang memiliki kategori peserta yang sama dengan tenaga pendukung
                    $query->whereExists(function ($subQuery) use ($tenagaPendukungKategoriPesertaIds) {
                        $subQuery->select(DB::raw(1))
                            ->from('atlet_kategori_peserta')
                            ->whereColumn('atlet_kategori_peserta.atlet_id', 'atlets.id')
                            ->whereIn('atlet_kategori_peserta.mst_kategori_peserta_id', $tenagaPendukungKategoriPesertaIds)
                            ->whereNull('atlet_kategori_peserta.deleted_at'); // Filter soft deleted
                    });
                } else {
                    // Jika tenaga pendukung tidak punya kategori peserta, tidak tampilkan atlet apapun
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // Filter untuk exclude atlet yang sudah ada di kategori tertentu
        if (request('exclude_cabor_kategori_id')) {
            $excludeKategoriId = request('exclude_cabor_kategori_id');
            $query->whereNotExists(function ($subQuery) use ($excludeKategoriId) {
                $subQuery->select(DB::raw(1))
                    ->from('cabor_kategori_atlet')
                    ->whereColumn('cabor_kategori_atlet.atlet_id', 'atlets.id')
                    ->where('cabor_kategori_atlet.cabor_kategori_id', $excludeKategoriId)
                    ->whereNull('cabor_kategori_atlet.deleted_at'); // hanya relasi aktif
            });

            // Filter berdasarkan kategori_peserta dari cabor_kategori jika ada
            $caborKategori = \App\Models\CaborKategori::find($excludeKategoriId);
            if ($caborKategori && $caborKategori->kategori_peserta_id) {
                // Filter atlet yang memiliki kategori_peserta yang sama
                $query->whereExists(function ($subQuery) use ($caborKategori) {
                    $subQuery->select(DB::raw(1))
                        ->from('atlet_kategori_peserta')
                        ->whereColumn('atlet_kategori_peserta.atlet_id', 'atlets.id')
                        ->where('atlet_kategori_peserta.mst_kategori_peserta_id', $caborKategori->kategori_peserta_id);
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
                // relasi caborKategoriAtlet dan kategoriPesertas dimuat
                $item->load(['caborKategoriAtlet.cabor.kategoriPeserta', 'kategoriPesertas']);
                $itemArray = $item->toArray();
                if (!isset($itemArray['cabor_kategori_atlet'])) {
                    $itemArray['cabor_kategori_atlet'] = $item->caborKategoriAtlet->map(function ($cabor) {
                        $caborData = $cabor->cabor ? $cabor->cabor->toArray() : null;
                        // Include kategori_peserta dari cabor
                        if ($caborData && $cabor->cabor && $cabor->cabor->kategoriPeserta) {
                            $caborData['kategori_peserta'] = [
                                'id' => $cabor->cabor->kategoriPeserta->id,
                                'nama' => $cabor->cabor->kategoriPeserta->nama,
                            ];
                        }
                        return [
                            'id'    => $cabor->id,
                            'cabor' => $caborData,
                        ];
                    })->toArray();
                }
                // Ensure kategori_pesertas ter-include
                if (!isset($itemArray['kategori_pesertas'])) {
                    $itemArray['kategori_pesertas'] = $item->kategoriPesertas->map(function ($kategori) {
                        return [
                            'id'   => $kategori->id,
                            'nama' => $kategori->nama,
                        ];
                    })->toArray();
                }
                return $itemArray;
            });
            $data += [
                'atlets'      => $transformed,
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
            // relasi caborKategoriAtlet dan kategoriPesertas dimuat
            $item->load(['caborKategoriAtlet.cabor.kategoriPeserta', 'kategoriPesertas']);
            $itemArray = $item->toArray();
            if (!isset($itemArray['cabor_kategori_atlet'])) {
                $itemArray['cabor_kategori_atlet'] = $item->caborKategoriAtlet->map(function ($cabor) {
                    $caborData = $cabor->cabor ? $cabor->cabor->toArray() : null;
                    // Include kategori_peserta dari cabor
                    if ($caborData && $cabor->cabor && $cabor->cabor->kategoriPeserta) {
                        $caborData['kategori_peserta'] = [
                            'id' => $cabor->cabor->kategoriPeserta->id,
                            'nama' => $cabor->cabor->kategoriPeserta->nama,
                        ];
                    }
                    return [
                        'id'    => $cabor->id,
                        'cabor' => $caborData,
                    ];
                })->toArray();
            }
            // Ensure kategori_pesertas ter-include
            if (!isset($itemArray['kategori_pesertas'])) {
                $itemArray['kategori_pesertas'] = $item->kategoriPesertas->map(function ($kategori) {
                    return [
                        'id'   => $kategori->id,
                        'nama' => $kategori->nama,
                    ];
                })->toArray();
            }
            return $itemArray;
        });
        $data += [
            'atlets'      => $transformed,
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
        // Filter by cabor_id (hanya tampilkan atlet yang sudah di cabor ini)
        if (request('cabor_id') && request('cabor_id') !== 'all') {
            $caborId = request('cabor_id');
            $query->whereExists(function ($sub) use ($caborId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_atlet as cka')
                    ->whereColumn('cka.atlet_id', 'atlets.id')
                    ->where('cka.cabor_id', $caborId)
                    ->whereNull('cka.deleted_at');
            });
        }

        // Exclude atlet yang sudah ada di cabor tertentu
        if (request('exclude_cabor_id')) {
            $excludeCaborId = request('exclude_cabor_id');
            $query->whereNotExists(function ($sub) use ($excludeCaborId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_atlet as cka')
                    ->whereColumn('cka.atlet_id', 'atlets.id')
                    ->where('cka.cabor_id', $excludeCaborId)
                    ->whereNull('cka.deleted_at');
            });
        }

        // Filter by cabor_kategori_id
        if (request('cabor_kategori_id') && request('cabor_kategori_id') !== 'all') {
            $caborKategoriId = request('cabor_kategori_id');
            $query->whereExists(function ($sub) use ($caborKategoriId) {
                $sub->select(DB::raw(1))
                    ->from('cabor_kategori_atlet as cka')
                    ->whereColumn('cka.atlet_id', 'atlets.id')
                    ->where('cka.cabor_kategori_id', $caborKategoriId)
                    ->whereNull('cka.deleted_at');
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
            case 'anak':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(12));
                break;
            case 'remaja':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(17))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(13));
                break;
            case 'dewasa_muda':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(25))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(18));
                break;
            case 'dewasa':
                $query->where('tanggal_lahir', '>=', $today->copy()->subYears(35))
                      ->where('tanggal_lahir', '<', $today->copy()->subYears(26));
                break;
            case 'dewasa_tua':
                $query->where('tanggal_lahir', '<', $today->copy()->subYears(36));
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
                $query->where('tanggal_bergabung', '>=', $today->copy()->subYear());
                break;
            case 'sedang':
                $query->where('tanggal_bergabung', '>=', $today->copy()->subYears(3))
                      ->where('tanggal_bergabung', '<', $today->copy()->subYear());
                break;
            case 'lama':
                $query->where('tanggal_bergabung', '>=', $today->copy()->subYears(5))
                      ->where('tanggal_bergabung', '<', $today->copy()->subYears(3));
                break;
            case 'sangat_lama':
                $query->where('tanggal_bergabung', '<', $today->copy()->subYears(5));
                break;
        }
    }

    public function customCreateEdit($data, $item = null)
    {
        // Tambahkan relasi untuk nanti kecamatan/kelurahan
        $data['item'] = $item;

        // Load parameter umum untuk form
        $parameterUmum = MstParameter::where('kategori', 'umum')
            ->whereNull('deleted_at')
            ->select('id', 'nama', 'satuan', 'nilai_target', 'performa_arah')
            ->orderBy('nama')
            ->get();

        $data['parameter_umum_master'] = $parameterUmum;

        // Jika edit, load nilai parameter umum yang sudah ada
        if ($item && isset($item->id)) {
            $parameterUmumValues           = $this->atletParameterUmumRepository->getByAtletId($item->id);
            $data['parameter_umum_values'] = $parameterUmumValues->mapWithKeys(function ($param) {
                return [$param->mst_parameter_id => $param->nilai];
            })->toArray();

            // Load kategori peserta yang sudah ada (multiple)
            $item->load('kategoriPesertas');
            $kategoriPesertasIds = $item->kategoriPesertas->pluck('id')->toArray();
            $data['kategori_pesertas'] = $kategoriPesertasIds;
            // Backward compatibility
            $data['kategori_atlets'] = $kategoriPesertasIds;
            
            // Load cabor data dari pivot (ambil yang pertama jika ada)
            $item->load('caborKategoriAtlet.cabor');
            $caborKategoriAtlet = $item->caborKategoriAtlet->first();
            
            // Convert item ke array dan tambahkan kategori_pesertas
            $itemArray = $item->toArray();
            $itemArray['kategori_pesertas'] = $kategoriPesertasIds;
            $itemArray['kategori_atlets'] = $kategoriPesertasIds; // Backward compatibility
            
            // Tambahkan cabor data
            if ($caborKategoriAtlet) {
                $itemArray['cabor_id'] = $caborKategoriAtlet->cabor_id;
                $itemArray['posisi_atlet'] = $caborKategoriAtlet->posisi_atlet;
            } else {
                $itemArray['cabor_id'] = null;
                $itemArray['posisi_atlet'] = null;
            }
            
            $data['item'] = $itemArray;
        } else {
            $data['parameter_umum_values'] = [];
            $data['kategori_pesertas']     = [];
            $data['kategori_atlets']       = [];
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
        // Hapus dari data karena ini relasi many-to-many, bukan kolom di tabel atlets
        if (isset($data['kategori_pesertas'])) {
            $this->kategoriPesertasForCallback = $data['kategori_pesertas'];
            unset($data['kategori_pesertas']);
        } else {
            $this->kategoriPesertasForCallback = null;
        }
        
        // Juga handle kategori_atlets untuk backward compatibility
        if (isset($data['kategori_atlets'])) {
            if ($this->kategoriPesertasForCallback === null) {
                $this->kategoriPesertasForCallback = $data['kategori_atlets'];
            }
            unset($data['kategori_atlets']);
        }

        // Simpan cabor_id dan posisi_atlet untuk digunakan di callbackAfterStoreOrUpdate
        $this->caborDataForCallback = [
            'cabor_id' => $data['cabor_id'] ?? null,
            'posisi_atlet' => $data['posisi_atlet'] ?? null,
        ];
        unset($data['cabor_id']);
        unset($data['posisi_atlet']);

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'kecamatan_id', 'kelurahan_id', 'kategori_atlet_id', 
            'tanggal_bergabung', 'tanggal_lahir',
            'nik', 'nisn', 'tempat_lahir', 'agama', 'alamat',
            'sekolah', 'kelas_sekolah', 'ukuran_baju', 'ukuran_celana', 'ukuran_sepatu',
            'no_hp', 'email'
        ];
        
        foreach ($nullableFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        Log::info('AtletRepository: customDataCreateUpdate', [
            'data'   => $data,
            'method' => is_null($record) ? 'create' : 'update',
        ]);

        return $data;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        try {
            DB::beginTransaction();

            // Sync data ke PesertaRegistration jika user masih pending
            if ($method === 'update' && $model->users_id) {
                $user = User::find($model->users_id);
                if ($user && $user->registration_status === 'pending') {
                    $registrationRepo = app(\App\Repositories\RegistrationRepository::class);
                    $registrationRepo->syncPesertaToRegistration($user, $model);
                }
            }

            Log::info('AtletRepository: Starting file upload process', [
                'method'         => $method,
                'has_file'       => isset($data['file']),
                'file_type'      => isset($data['file']) ? gettype($data['file']) : 'N/A',
                'is_uploaded_file' => isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile,
                'is_delete_foto' => @$data['is_delete_foto'],
            ]);

            // Handle file upload
            if (@$data['is_delete_foto'] == 1) {
                $model->clearMediaCollection('images');
                Log::info('AtletRepository: Cleared media collection');
            }

            // Validasi file sebelum memproses - pastikan adalah UploadedFile instance yang valid
            if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
                // Pastikan file valid dan ter-upload dengan benar
                if ($data['file']->isValid()) {
                    try {
                        Log::info('AtletRepository: Adding media file', [
                            'file_name' => $data['file']->getClientOriginalName(),
                            'file_size' => $data['file']->getSize(),
                            'mime_type' => $data['file']->getMimeType(),
                            'model_id'  => $model->id,
                        ]);

                        $media = $model->addMedia($data['file'])
                            ->usingName($data['nama'] ?? 'Foto Atlet')
                            ->toMediaCollection('images');

                        Log::info('AtletRepository: Media added successfully', [
                            'media_id'  => $media->id,
                            'file_name' => $media->file_name,
                            'disk'      => $media->disk,
                            'path'      => $media->getPath(),
                        ]);
                    } catch (\Exception $e) {
                        Log::error('AtletRepository: Error adding media', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        throw $e;
                    }
                } else {
                    Log::warning('AtletRepository: File upload is not valid', [
                        'error' => $data['file']->getError(),
                        'error_message' => $data['file']->getErrorMessage(),
                    ]);
                    // Jangan throw error, hanya log warning karena mungkin file tidak ter-upload dengan benar
                }
            } elseif (isset($data['file'])) {
                // File ada tapi bukan UploadedFile instance (mungkin string path yang tidak valid)
                Log::warning('AtletRepository: File is not a valid UploadedFile instance', [
                    'file_type' => gettype($data['file']),
                    'file_value' => is_string($data['file']) ? $data['file'] : 'Not a string',
                ]);
                // Jangan proses file jika bukan UploadedFile instance untuk menghindari error
            }

            // Handle AtletOrangTua data
            $atletOrangTuaData   = [];
            $atletOrangTuaFields = [
                'nama_ibu_kandung', 'tempat_lahir_ibu', 'tanggal_lahir_ibu', 'alamat_ibu', 'no_hp_ibu', 'pekerjaan_ibu',
                'nama_ayah_kandung', 'tempat_lahir_ayah', 'tanggal_lahir_ayah', 'alamat_ayah', 'no_hp_ayah', 'pekerjaan_ayah',
                'nama_wali', 'tempat_lahir_wali', 'tanggal_lahir_wali', 'alamat_wali', 'no_hp_wali', 'pekerjaan_wali',
            ];

            foreach ($atletOrangTuaFields as $field) {
                if (isset($data[$field])) {
                    $atletOrangTuaData[$field] = $data[$field];
                }
            }

            if (! empty($atletOrangTuaData)) {
                $atletOrangTuaData['atlet_id'] = $model->id;

                if (isset($data['atlet_orang_tua_id']) && ! is_null($data['atlet_orang_tua_id'])) {
                    $this->atletOrangTuaRepository->update($data['atlet_orang_tua_id'], $atletOrangTuaData);
                    Log::info('AtletRepository: Updated AtletOrangTua', ['id' => $data['atlet_orang_tua_id']]);
                } else {
                    $this->atletOrangTuaRepository->create($atletOrangTuaData);
                    Log::info('AtletRepository: Created new AtletOrangTua for atlet_id', ['atlet_id' => $model->id]);
                }
            }

            // Handle Atlet Parameter Umum data
            if (isset($data['parameter_umum']) && is_array($data['parameter_umum'])) {
                $this->atletParameterUmumRepository->upsertByAtletId($model->id, $data['parameter_umum']);
                Log::info('AtletRepository: Updated AtletParameterUmum', ['atlet_id' => $model->id]);
            }

            // Handle Multiple Kategori Peserta
            // Gunakan kategori_pesertas dari property yang disimpan karena sudah di-unset dari $data di customDataCreateUpdate
            $kategoriPesertasToSync = $this->kategoriPesertasForCallback ?? request()->input('kategori_pesertas') ?? request()->input('kategori_atlets');
            
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
                
                Log::info('AtletRepository: Syncing KategoriPesertas', [
                    'atlet_id' => $model->id,
                    'kategori_ids' => $kategoriIds,
                    'kategori_ids_type' => array_map('gettype', $kategoriIds),
                ]);
                
                // Sync dengan array kosong jika tidak ada kategori (untuk menghapus semua relasi)
                $model->kategoriPesertas()->sync($kategoriIds);
                // Refresh model untuk memastikan relasi ter-load
                $model->refresh();
                $model->load('kategoriPesertas');
                Log::info('AtletRepository: Updated KategoriPesertas', ['atlet_id' => $model->id, 'kategori_ids' => $kategoriIds]);
            } else {
                Log::warning('AtletRepository: kategori_pesertas not set in data or request', ['data_keys' => array_keys($data)]);
            }

            // Handle Atlet Akun data
            if (isset($data['akun_email']) && $data['akun_email']) {
                $this->handleAtletAkun($model, $data);
            }

            // Handle Cabor Assignment (langsung ke cabor tanpa kategori)
            $caborData = $this->caborDataForCallback ?? [
                'cabor_id' => request()->input('cabor_id'),
                'posisi_atlet' => request()->input('posisi_atlet'),
            ];
            
            if (!empty($caborData['cabor_id'])) {
                $caborId = $caborData['cabor_id'];
                $posisiAtlet = $caborData['posisi_atlet'] ?? null;
                
                // Cek apakah sudah ada relasi ke cabor ini
                $existingRelation = \App\Models\CaborKategoriAtlet::where('cabor_id', $caborId)
                    ->where('atlet_id', $model->id)
                    ->first();
                
                if ($existingRelation) {
                    // Update posisi jika sudah ada
                    $existingRelation->update([
                        'posisi_atlet' => $posisiAtlet,
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('AtletRepository: Updated cabor assignment', [
                        'atlet_id' => $model->id,
                        'cabor_id' => $caborId,
                        'posisi_atlet' => $posisiAtlet,
                    ]);
                } else {
                    // Buat relasi baru tanpa kategori (cabor_kategori_id = null)
                    \App\Models\CaborKategoriAtlet::create([
                        'cabor_id' => $caborId,
                        'cabor_kategori_id' => null, // Langsung ke cabor tanpa kategori
                        'atlet_id' => $model->id,
                        'posisi_atlet' => $posisiAtlet,
                        'is_active' => 1,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('AtletRepository: Created new cabor assignment', [
                        'atlet_id' => $model->id,
                        'cabor_id' => $caborId,
                        'posisi_atlet' => $posisiAtlet,
                    ]);
                }
            }

            DB::commit();
            Log::info('AtletRepository: Transaction committed successfully');

            return $model;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('AtletRepository: Error during file upload or AtletOrangTua save', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Atlet Akun creation/update
     */
    public function handleAtletAkun($atlet, $data)
    {
        $userId   = Auth::check() ? Auth::id() : null;
        $userData = [
            'name'            => $atlet->nama,
            'email'           => $data['akun_email'],
            'no_hp'           => $atlet->no_hp,
            'is_active'       => 1,
            'current_role_id' => 35, // Set current_role_id ke Role Atlet
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
                $role = Role::find(35); // Role Atlet
                if ($role && !$user->hasRole($role)) {
                    $user->assignRole($role);
                }

                Log::info('AtletRepository: Updated existing user for atlet', [
                    'atlet_id' => $atlet->id,
                    'user_id'  => $user->id,
                ]);
            }
        } else {
            // Create new user
            $user = User::create($userData);

            // Assign role Atlet using Spatie Permission
            $role = Role::find(35); // Role Atlet
            if ($role) {
                $user->assignRole($role);
            }

            // Also create users_role record for compatibility
            $user->users_role()->create([
                'users_id'   => $user->id,
                'role_id'    => 35, // Role Atlet
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Update atlet dengan users_id
            $atlet->update(['users_id' => $user->id]);

            Log::info('AtletRepository: Created new user for atlet', [
                'atlet_id' => $atlet->id,
                'user_id'  => $user->id,
            ]);
        }
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        // override pesan global (fallback)
        $messages = array_merge([
            'nik.max'      => 'NIK tidak boleh lebih dari 16 karakter.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.unique'   => 'NIK sudah terdaftar.',
        ], $messages);

        return $request->validate($rules, $messages);
    }

    public function getDetailWithRelations($id)
    {
        $with = array_merge($this->with, ['kecamatan', 'kelurahan', 'kategoriAtlet', 'kategoriPesertas']);

        return $this->model->with($with)->findOrFail($id);
    }

    /**
     * Get jumlah karakteristik atlet
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
            'anak'        => ['min' => 0, 'max' => 12, 'label' => 'Anak-anak (0-12 tahun)'],
            'remaja'      => ['min' => 13, 'max' => 17, 'label' => 'Remaja (13-17 tahun)'],
            'dewasa_muda' => ['min' => 18, 'max' => 25, 'label' => 'Dewasa Muda (18-25 tahun)'],
            'dewasa'      => ['min' => 26, 'max' => 35, 'label' => 'Dewasa (26-35 tahun)'],
            'dewasa_tua'  => ['min' => 36, 'max' => 100, 'label' => 'Dewasa Tua (36+ tahun)'],
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
            'baru'        => ['min' => 0, 'max' => 1, 'label' => 'Baru bergabung (< 1 tahun)'],
            'sedang'      => ['min' => 1, 'max' => 3, 'label' => 'Sedang (1-3 tahun)'],
            'lama'        => ['min' => 3, 'max' => 5, 'label' => 'Lama (3-5 tahun)'],
            'sangat_lama' => ['min' => 5, 'max' => 100, 'label' => 'Sangat lama (5+ tahun)'],
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
            $atletIds = collect($getData)->pluck('id')->filter()->values()->all();
            if (!empty($atletIds)) {
                $rows = DB::table('cabor_kategori_atlet as cka')
                    ->join('cabor as c', 'cka.cabor_id', '=', 'c.id')
                    ->whereNull('cka.deleted_at')
                    ->whereIn('cka.atlet_id', $atletIds)
                    ->select('c.id', 'c.nama', DB::raw('COUNT(DISTINCT cka.atlet_id) as jumlah'))
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
            // Jika terjadi error agregasi, tetap kembalikan blok kosong agar UI tidak rusak
            $result[] = [
                'key'  => 'cabor',
                'name' => 'Cabor',
                'data' => [],
            ];
        }

        return $result;
    }

    public function upsertParameterUmum(int $atletId, array $parameterData): void
    {
        $this->atletParameterUmumRepository->upsertByAtletId($atletId, $parameterData);
    }
}
