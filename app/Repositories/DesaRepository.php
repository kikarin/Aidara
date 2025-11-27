<?php

namespace App\Repositories;

use App\Models\MstDesa;
use App\Traits\RepositoryTrait;

class DesaRepository
{
    use RepositoryTrait;

    protected $model;

    protected $kecamatanRepository;

    public function __construct(MstDesa $model, KecamatanRepository $kecamatanRepository)
    {
        $this->model               = $model;
        $this->orderByColumnsArray = ['id_kecamatan' => 'asc', 'nama' => 'asc'];
        $this->with                = ['kecamatan'];

        $this->kecamatanRepository = $kecamatanRepository;
    }

    public function customCreateEdit($data, $item = null)
    {
        $data += [
            'listKecamatan' => $this->kecamatanRepository->getAll()->pluck('nama', 'id')->toArray(),
        ];

        return $data;
    }

    public function getByIdKecamatan($id_kecamatan)
    {
        $record = $this->model::where('id_kecamatan', $id_kecamatan)->get();

        return $record;
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama', 'id_kecamatan');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%');
            });
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama', 'id_kecamatan', 'created_at', 'updated_at'];
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
                    'id'           => $item->id,
                    'nama'         => $item->nama,
                    'id_kecamatan' => $item->id_kecamatan,
                ];
            });
            $data += [
                'desas'       => $transformedData,
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
                'id'           => $item->id,
                'nama'         => $item->nama,
                'id_kecamatan' => $item->id_kecamatan,
            ];
        });

        $data += [
            'desas'       => $transformedData,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => request('search', ''),
            'sort'        => request('sort', ''),
            'order'       => request('order', 'asc'),
        ];

        return $data;
    }
}
