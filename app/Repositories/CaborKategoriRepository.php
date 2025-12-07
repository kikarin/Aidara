<?php

namespace App\Repositories;

use App\Http\Requests\CaborKategoriRequest;
use App\Models\CaborKategori;
use App\Models\CaborKategoriTenagaPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
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
}
