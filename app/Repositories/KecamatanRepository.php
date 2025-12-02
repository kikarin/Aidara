<?php

namespace App\Repositories;

use App\Models\MstKecamatan;
use App\Traits\RepositoryTrait;

class KecamatanRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(MstKecamatan $model)
    {
        $this->model = $model;
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%');
            });
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama', 'created_at', 'updated_at'];
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
                    'id'   => $item->id,
                    'nama' => $item->nama,
                ];
            });
            $data += [
                'kecamatans'  => $transformedData,
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
                'id'   => $item->id,
                'nama' => $item->nama,
            ];
        });

        $data += [
            'kecamatans'  => $transformedData,
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
