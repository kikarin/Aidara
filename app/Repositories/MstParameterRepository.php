<?php

namespace App\Repositories;

use App\Http\Requests\MstParameterRequest;
use App\Models\MstParameter;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MstParameterRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(MstParameter $model)
    {
        $this->model   = $model;
        $this->request = MstParameterRequest::createFromBase(request());
        $this->with    = ['created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama', 'satuan', 'kategori', 'nilai_target', 'performa_arah');

        if (request('kategori')) {
            $query->where('kategori', request('kategori'));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%');
            });
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama', 'satuan', 'created_at', 'updated_at'];
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
                    'id'            => $item->id,
                    'nama'          => $item->nama,
                    'satuan'        => $item->satuan,
                    'kategori'      => $item->kategori ?? 'kesehatan',
                    'nilai_target'  => $item->nilai_target,
                    'performa_arah' => $item->performa_arah ?? 'max',
                ];
            });
            $data += [
                'parameters'    => $transformedData,
                'total'         => $transformedData->count(),
                'currentPage'   => 1,
                'perPage'       => -1,
                'search'        => request('search', ''),
                'sort'          => request('sort', ''),
                'order'         => request('order', 'asc'),
            ];

            return $data;
        }

        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();

        $transformedData = collect($items->items())->map(function ($item) {
            return [
                'id'            => $item->id,
                'nama'          => $item->nama,
                'satuan'        => $item->satuan,
                'kategori'      => $item->kategori ?? 'kesehatan',
                'nilai_target'  => $item->nilai_target,
                'performa_arah' => $item->performa_arah ?? 'max',
            ];
        });

        $data += [
            'parameters'    => $transformedData,
            'total'         => $items->total(),
            'currentPage'   => $items->currentPage(),
            'perPage'       => $items->perPage(),
            'search'        => request('search', ''),
            'sort'          => request('sort', ''),
            'order'         => request('order', 'asc'),
        ];

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

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function getDetailWithUserTrack($id)
    {
        return $this->model
            ->with(['created_by_user', 'updated_by_user'])
            ->where('id', $id)
            ->first();
    }

    public function handleShow($id)
    {
        $item = $this->getDetailWithUserTrack($id);

        if (! $item) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        $itemArray = $item->toArray();

        return Inertia::render('modules/data-master/parameters/Show', [
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
