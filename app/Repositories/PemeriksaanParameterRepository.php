<?php

namespace App\Repositories;

use App\Models\PemeriksaanParameter;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;

class PemeriksaanParameterRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PemeriksaanParameter $model)
    {
        $this->model = $model;
        $this->with  = ['pemeriksaan', 'mstParameter', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with($this->with);
        if (request('pemeriksaan_id')) {
            $query->where('pemeriksaan_id', request('pemeriksaan_id'));
        }

        // Filtering by kategori parameter
        $jenisPeserta = request('jenis_peserta');
        $query->whereHas('mstParameter', function ($q) use ($jenisPeserta) {
            // Selalu exclude kategori 'umum' dari pemetaan pemeriksaan
            $q->where('kategori', '!=', 'umum');
            if ($jenisPeserta && $jenisPeserta !== 'atlet') {
                // Untuk non-atlet, exclude juga kategori 'khusus'
                $q->where('kategori', '!=', 'khusus');
            }
        });

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('mstParameter', function ($q2) use ($search) {
                    $q2->where('nama', 'like', "%$search%")
                        ->orWhere('satuan', 'like', "%$search%");
                });
            });
        }
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'created_at', 'updated_at'];
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
                return [
                    'id'               => $item->id,
                    'pemeriksaan_id'   => $item->pemeriksaan_id,
                    'mst_parameter_id' => $item->mst_parameter_id,
                    'nama_parameter'   => $item->mstParameter?->nama,
                    'satuan'           => $item->mstParameter?->satuan,
                    'mst_parameter'    => $item->mstParameter,
                ];
            });
            $data += [
                'data'        => $transformed,
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
            return [
                'id'               => $item->id,
                'pemeriksaan_id'   => $item->pemeriksaan_id,
                'mst_parameter_id' => $item->mst_parameter_id,
                'nama_parameter'   => $item->mstParameter?->nama,
                'satuan'           => $item->mstParameter?->satuan,
                'mst_parameter'    => $item->mstParameter,
            ];
        });
        $data += [
            'data'        => $transformed,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => request('search', ''),
            'sort'        => request('sort', ''),
            'order'       => request('order', 'asc'),
        ];

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $data['item'] = $item;

        return $data;
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

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    public function getById($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }

    public function getDetailWithRelations($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }
}
