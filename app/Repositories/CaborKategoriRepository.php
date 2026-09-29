<?php

namespace App\Repositories;

use App\Http\Requests\CaborKategoriRequest;
use App\Models\Atlet;
use App\Models\CaborKategori;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CaborKategoriRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(CaborKategori $model)
    {
        $this->model   = $model;
        $this->request = CaborKategoriRequest::createFromBase(request());
        $this->with    = ['created_by_user', 'updated_by_user', 'cabor'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with(['cabor', 'kategoriPeserta'])->select('id', 'cabor_id', 'nama', 'deskripsi', 'jenis_kelamin', 'kategori_peserta_id');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$search.'%');
            });
        }

        // Apply filters
        $this->applyFilters($query);

        // Role-based filtering
        $auth = Auth::user();
        if ($auth && $auth->current_role_id == 35) { // Atlet
            if ($auth->atlet && $auth->atlet->id) {
                $query->whereHas('caborKategoriAtlet', function ($sub_query) use ($auth) {
                    $sub_query->where('atlet_id', $auth->atlet->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                });
            }
        } elseif ($auth && $auth->current_role_id == 36) { // Pelatih
            if ($auth->pelatih && $auth->pelatih->id) {
                $query->whereHas('caborKategoriPelatih', function ($sub_query) use ($auth) {
                    $sub_query->where('pelatih_id', $auth->pelatih->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                });
            }
        } elseif ($auth && $auth->current_role_id == 37) { // Tenaga Pendukung
            if ($auth->tenagaPendukung && $auth->tenagaPendukung->id) {
                $query->whereHas('tenagaPendukung', function ($sub_query) use ($auth) {
                    $sub_query->where('tenaga_pendukung_id', $auth->tenagaPendukung->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                });
            }
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'cabor_id', 'nama', 'deskripsi', 'created_at', 'updated_at'];
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
            $allData         = $query->get();
            $transformedData = $allData->map(function ($item) {
                return [
                    'id'                      => $item->id,
                    'cabor_id'                => $item->cabor_id,
                    'cabor_nama'              => $item->cabor?->nama,
                    'nama'                    => $item->nama,
                    'jenis_kelamin'           => $item->jenis_kelamin,
                    'kategori_peserta_id'     => $item->kategori_peserta_id,
                    'kategori_peserta_nama'   => $item->kategoriPeserta?->nama ?? '-',
                    'jenis'                   => $item->jenis,
                    'deskripsi'               => $item->deskripsi,
                    'jumlah_atlet'            => $item->jumlah_atlet,
                    'jumlah_pelatih'          => $item->jumlah_pelatih,
                    'jumlah_tenaga_pendukung' => CaborKategoriTenagaPendukung::where('cabor_kategori_id', $item->id)->count(),
                ];
            });
            $data += [
                'kategori'    => $transformedData,
                'total'       => $transformedData->count(),
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

        $transformedData = collect($items->items())->map(function ($item) {
            return [
                'id'                      => $item->id,
                'cabor_id'                => $item->cabor_id,
                'cabor_nama'              => $item->cabor?->nama,
                'nama'                    => $item->nama,
                'jenis_kelamin'           => $item->jenis_kelamin,
                'kategori_peserta_id'     => $item->kategori_peserta_id,
                'kategori_peserta_nama'   => $item->kategoriPeserta?->nama ?? '-',
                'deskripsi'               => $item->deskripsi,
                'jumlah_atlet'            => $item->jumlah_atlet,
                'jumlah_pelatih'          => $item->jumlah_pelatih,
                'jumlah_tenaga_pendukung' => CaborKategoriTenagaPendukung::where('cabor_kategori_id', $item->id)->count(),
            ];
        });

        $data += [
            'kategori'    => $transformedData,
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
        // Filter by cabor_id
        if (request('cabor_id') && request('cabor_id') !== 'all') {
            $query->where('cabor_id', request('cabor_id'));
        }

        // Filter by cabor_kategori_id (nama kategori)
        if (request('cabor_kategori_id') && request('cabor_kategori_id') !== 'all') {
            $query->where('id', request('cabor_kategori_id'));
        }

        // Filter by date range
        if (request('filter_start_date') && request('filter_end_date')) {
            $query->whereBetween('created_at', [
                request('filter_start_date') . ' 00:00:00',
                request('filter_end_date') . ' 23:59:59',
            ]);
        }
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::id();

        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        return $data;
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function getDetailWithUserTrack($id)
    {
        return $this->model
            ->with(['created_by_user', 'updated_by_user', 'cabor'])
            ->where('id', $id)
            ->first();
    }

    public function handleShow($id)
    {
        $item = $this->getDetailWithUserTrack($id);

        if (! $item) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        $itemArray                  = $item->toArray();
        $itemArray['cabor_nama']    = $item->cabor?->nama ?? '-';
        $itemArray['jenis_kelamin'] = $item->jenis_kelamin;

        return Inertia::render('modules/cabor-kategori/Show', [
            'item' => $itemArray,
        ]);
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    /**
     * Callback setelah store atau update untuk auto-tambah peserta dari cabor
     */
    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        $userId = Auth::id();

        // Hanya proses untuk create (store), bukan update
        if ($method === 'store') {
            $caborId = $model->cabor_id;
            $caborKategoriId = $model->id;
            $jenisKelamin = $model->jenis_kelamin;

            // Get semua atlet dari cabor (dari tabel pivot dengan cabor_id)
            $atletQuery = DB::table('cabor_kategori_atlet')
                ->where('cabor_id', $caborId)
                ->whereNull('deleted_at')
                ->select('atlet_id', 'posisi_atlet')
                ->distinct();

            // Filter berdasarkan jenis kelamin kategori jika L atau P
            if ($jenisKelamin === 'L' || $jenisKelamin === 'P') {
                $atletIds = $atletQuery->pluck('atlet_id')->unique();
                $atletIds = Atlet::whereIn('id', $atletIds)
                    ->where('jenis_kelamin', $jenisKelamin)
                    ->pluck('id')
                    ->toArray();
            } else {
                // Campuran: ambil semua
                $atletIds = $atletQuery->pluck('atlet_id')->unique()->toArray();
            }

            // Insert atlet ke kategori baru
            // Unique constraint adalah cabor_kategori_id + atlet_id, jadi atlet bisa punya beberapa kategori dalam satu cabor
            foreach ($atletIds as $atletId) {
                // Cek apakah sudah ada record dengan cabor_kategori_id + atlet_id (unique constraint)
                $existing = CaborKategoriAtlet::where('cabor_kategori_id', $caborKategoriId)
                    ->where('atlet_id', $atletId)
                    ->whereNull('deleted_at')
                    ->first();

                if ($existing) {
                    // Jika sudah ada, skip (tidak perlu update karena sudah di kategori ini)
                    continue;
                } else {
                    // Buat record baru untuk kategori ini
                    // Ambil posisi_atlet dari record yang sudah ada di cabor (jika ada)
                    $existingCaborAtlet = DB::table('cabor_kategori_atlet')
                        ->where('cabor_id', $caborId)
                        ->where('atlet_id', $atletId)
                        ->whereNull('deleted_at')
                        ->first();

                    CaborKategoriAtlet::create([
                        'cabor_id' => $caborId,
                        'cabor_kategori_id' => $caborKategoriId,
                        'atlet_id' => $atletId,
                        'posisi_atlet' => $existingCaborAtlet->posisi_atlet ?? null,
                        'is_active' => 1,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }

            // Get semua pelatih dari cabor (tidak peduli gender)
            $pelatihIds = DB::table('cabor_kategori_pelatih')
                ->where('cabor_id', $caborId)
                ->whereNull('deleted_at')
                ->pluck('pelatih_id')
                ->unique()
                ->toArray();

            // Insert pelatih ke kategori baru
            foreach ($pelatihIds as $pelatihId) {
                // Cek apakah sudah ada
                $existing = CaborKategoriPelatih::where('cabor_kategori_id', $caborKategoriId)
                    ->where('pelatih_id', $pelatihId)
                    ->first();

                if (!$existing) {
                    // Ambil jenis_pelatih dari record yang sudah ada di cabor (jika ada)
                    $existingCaborPelatih = DB::table('cabor_kategori_pelatih')
                        ->where('cabor_id', $caborId)
                        ->where('pelatih_id', $pelatihId)
                        ->whereNull('deleted_at')
                        ->first();

                    CaborKategoriPelatih::create([
                        'cabor_id' => $caborId,
                        'cabor_kategori_id' => $caborKategoriId,
                        'pelatih_id' => $pelatihId,
                        'jenis_pelatih' => $existingCaborPelatih->jenis_pelatih ?? null,
                        'is_active' => 1,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }

            // Get semua tenaga pendukung dari cabor (tidak peduli gender)
            $tenagaPendukungIds = DB::table('cabor_kategori_tenaga_pendukung')
                ->where('cabor_id', $caborId)
                ->whereNull('deleted_at')
                ->pluck('tenaga_pendukung_id')
                ->unique()
                ->toArray();

            // Insert tenaga pendukung ke kategori baru
            foreach ($tenagaPendukungIds as $tenagaPendukungId) {
                // Cek apakah sudah ada
                $existing = CaborKategoriTenagaPendukung::where('cabor_kategori_id', $caborKategoriId)
                    ->where('tenaga_pendukung_id', $tenagaPendukungId)
                    ->first();

                if (!$existing) {
                    // Ambil jenis_tenaga_pendukung dari record yang sudah ada di cabor (jika ada)
                    $existingCaborTenaga = DB::table('cabor_kategori_tenaga_pendukung')
                        ->where('cabor_id', $caborId)
                        ->where('tenaga_pendukung_id', $tenagaPendukungId)
                        ->whereNull('deleted_at')
                        ->first();

                    CaborKategoriTenagaPendukung::create([
                        'cabor_id' => $caborId,
                        'cabor_kategori_id' => $caborKategoriId,
                        'tenaga_pendukung_id' => $tenagaPendukungId,
                        'jenis_tenaga_pendukung' => $existingCaborTenaga->jenis_tenaga_pendukung ?? null,
                        'is_active' => 1,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }
        }

        return $model;
    }
}
