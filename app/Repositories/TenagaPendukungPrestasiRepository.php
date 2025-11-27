<?php

namespace App\Repositories;

use App\Models\TenagaPendukungPrestasi;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenagaPendukungPrestasiRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(TenagaPendukungPrestasi $model)
    {
        $this->model = $model;
        $this->with  = [
            'created_by_user',
            'updated_by_user',
            'tingkat',
        ];
    }

    public function create(array $data)
    {
        Log::info('TenagaPendukungPrestasiRepository: create', $data);
        $data  = $this->customDataCreateUpdate($data);
        $model = $this->model->create($data);

        return $model;
    }

    public function update($id, array $data)
    {
        Log::info('TenagaPendukungPrestasiRepository: update', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);
        if ($record) {
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            Log::info('TenagaPendukungPrestasiRepository: updated', $record->toArray());

            return $record;
        }
        Log::warning('TenagaPendukungPrestasiRepository: not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('TenagaPendukungPrestasiRepository: delete', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);
        if ($record) {
            $record->forceDelete();
            Log::info('TenagaPendukungPrestasiRepository: deleted', ['id' => $id]);

            return true;
        }
        Log::warning('TenagaPendukungPrestasiRepository: not found for delete', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        return $data;
    }

    public function getByTenagaPendukungId($tenagaPendukungId)
    {
        return $this->model->where('tenaga_pendukung_id', $tenagaPendukungId)->get();
    }

    public function getById($id)
    {
        return $this->model->with($this->with)->find($id);
    }

    public function apiIndex($tenagaPendukungId)
    {
        $query = $this->model->where('tenaga_pendukung_id', $tenagaPendukungId);
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_event', 'like', '%'.$search.'%')
                    ->orWhere('peringkat', 'like', '%'.$search.'%')
                    ->orWhere('keterangan', 'like', '%'.$search.'%')
                    ->orWhere('tanggal', 'like', '%'.$search.'%');
            });
        }
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama_event', 'tingkat_id', 'tanggal', 'peringkat', 'created_at', 'updated_at'];
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
            $all         = $query->with($this->with)->get();
            $transformed = collect($all)->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'nama_event' => $item->nama_event,
                    'tingkat'    => $item->tingkat ? ['id' => $item->tingkat->id, 'nama' => $item->tingkat->nama] : null,
                    'tanggal'    => $item->tanggal,
                    'peringkat'  => $item->peringkat,
                    'keterangan' => $item->keterangan,
                ];
            });

            return [
                'data' => $transformed,
                'meta' => [
                    'total'        => $transformed->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
        }
        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->with($this->with)->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformed     = collect($items->items())->map(function ($item) {
            return [
                'id'         => $item->id,
                'nama_event' => $item->nama_event,
                'tingkat'    => $item->tingkat ? ['id' => $item->tingkat->id, 'nama' => $item->tingkat->nama] : null,
                'tanggal'    => $item->tanggal,
                'peringkat'  => $item->peringkat,
                'keterangan' => $item->keterangan,
            ];
        });

        return [
            'data' => $transformed,
            'meta' => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];
    }

    public function handleCreate($tenagaPendukungId)
    {
        return Inertia::render('modules/tenaga-pendukung/prestasi/Create', [
            'tenagaPendukungId' => (int) $tenagaPendukungId,
        ]);
    }

    public function handleEdit($tenagaPendukungId, $id)
    {
        $prestasi = $this->getById($id);
        if (! $prestasi) {
            return redirect()->back()->with('error', 'Prestasi tidak ditemukan');
        }

        return Inertia::render('modules/tenaga-pendukung/prestasi/Edit', [
            'tenagaPendukungId' => (int) $tenagaPendukungId,
            'item'              => $prestasi,
        ]);
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->forceDelete();
    }
}
