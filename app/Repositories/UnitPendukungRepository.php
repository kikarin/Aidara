<?php

namespace App\Repositories;

use App\Http\Requests\UnitPendukungRequest;
use App\Models\UnitPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UnitPendukungRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(UnitPendukung $model)
    {
        $this->model   = $model;
        $this->request = UnitPendukungRequest::createFromBase(request());
        $this->with    = ['created_by_user', 'updated_by_user', 'jenisUnitPendukung'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama', 'jenis_unit_pendukung_id', 'deskripsi')
            ->with('jenisUnitPendukung:id,nama');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                  ->orWhere('deskripsi', 'like', '%'.$search.'%')
                  ->orWhereHas('jenisUnitPendukung', function ($subQ) use ($search) {
                      $subQ->where('nama', 'like', '%'.$search.'%');
                  });
            });
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama', 'jenis_unit_pendukung_id', 'deskripsi', 'created_at', 'updated_at'];
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
                    'nama'                    => $item->nama,
                    'jenis_unit_pendukung_id' => $item->jenis_unit_pendukung_id,
                    'jenis_unit_pendukung'    => $item->jenisUnitPendukung ? [
                        'id'   => $item->jenisUnitPendukung->id,
                        'nama' => $item->jenisUnitPendukung->nama,
                    ] : null,
                    'deskripsi' => $item->deskripsi,
                ];
            });
            $data += [
                'unitPendukungs' => $transformedData,
                'total'          => $transformedData->count(),
                'currentPage'    => 1,
                'perPage'        => -1,
                'search'         => request('search', ''),
                'sort'           => request('sort', ''),
                'order'          => request('order', 'asc'),
            ];

            return $data;
        }

        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();

        $transformedData = collect($items->items())->map(function ($item) {
            return [
                'id'                      => $item->id,
                'nama'                    => $item->nama,
                'jenis_unit_pendukung_id' => $item->jenis_unit_pendukung_id,
                'jenis_unit_pendukung'    => $item->jenisUnitPendukung ? [
                    'id'   => $item->jenisUnitPendukung->id,
                    'nama' => $item->jenisUnitPendukung->nama,
                ] : null,
                'deskripsi' => $item->deskripsi,
            ];
        });

        $data += [
            'unitPendukungs' => $transformedData,
            'total'          => $items->total(),
            'currentPage'    => $items->currentPage(),
            'perPage'        => $items->perPage(),
            'search'         => request('search', ''),
            'sort'           => request('sort', ''),
            'order'          => request('order', 'asc'),
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
            ->with(['created_by_user', 'updated_by_user', 'jenisUnitPendukung'])
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

        return Inertia::render('modules/unit-pendukung/Show', [
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
