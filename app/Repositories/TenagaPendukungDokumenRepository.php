<?php

namespace App\Repositories;

use App\Models\TenagaPendukungDokumen;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenagaPendukungDokumenRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(TenagaPendukungDokumen $model)
    {
        $this->model = $model;
        $this->with  = [
            'media',
            'created_by_user',
            'updated_by_user',
            'jenis_dokumen',
        ];
    }

    public function create(array $data)
    {
        Log::info('TenagaPendukungDokumenRepository: create', $data);
        $file = $data['file'] ?? null;
        unset($data['file']);
        $data  = $this->customDataCreateUpdate($data);
        $model = $this->model->create($data);
        if ($file) {
            $model->addMedia($file)->usingName($data['nomor'] ?? 'Dokumen')->toMediaCollection('dokumen_file');
        }

        return $model;
    }

    public function update($id, array $data)
    {
        Log::info('TenagaPendukungDokumenRepository: update', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);
        if ($record) {
            $file = $data['file'] ?? null;
            unset($data['file']);
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            if ($file) {
                $record->clearMediaCollection('dokumen_file');
                $record->addMedia($file)->usingName($data['nomor'] ?? 'Dokumen')->toMediaCollection('dokumen_file');
            }
            Log::info('TenagaPendukungDokumenRepository: updated', $record->toArray());

            return $record;
        }
        Log::warning('TenagaPendukungDokumenRepository: not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('TenagaPendukungDokumenRepository: delete', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);
        if ($record) {
            $record->forceDelete();
            Log::info('TenagaPendukungDokumenRepository: deleted', ['id' => $id]);

            return true;
        }
        Log::warning('TenagaPendukungDokumenRepository: not found for delete', ['id' => $id]);

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
                $q->where('nomor', 'like', '%'.$search.'%')
                    ->orWhereHas('jenis_dokumen', function ($q) use ($search) {
                        $q->where('nama', 'like', '%'.$search.'%');
                    });
            });
        }
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nomor', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $order);
            } elseif ($sortField === 'jenis_dokumen.nama') {
                $query->join('mst_jenis_dokumen', 'tenaga_pendukung_dokumen.jenis_dokumen_id', '=', 'mst_jenis_dokumen.id')
                    ->orderBy('mst_jenis_dokumen.nama', $order)
                    ->select('tenaga_pendukung_dokumen.*');
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
                    'id'            => $item->id,
                    'jenis_dokumen' => $item->jenis_dokumen ? ['id' => $item->jenis_dokumen->id, 'nama' => $item->jenis_dokumen->nama] : null,
                    'nomor'         => $item->nomor,
                    'file_url'      => $item->file_url,
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
                'id'            => $item->id,
                'jenis_dokumen' => $item->jenis_dokumen ? ['id' => $item->jenis_dokumen->id, 'nama' => $item->jenis_dokumen->nama] : null,
                'nomor'         => $item->nomor,
                'file_url'      => $item->file_url,
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
        return Inertia::render('modules/tenaga-pendukung/dokumen/Create', [
            'pelatihId' => (int) $tenagaPendukungId,
        ]);
    }

    public function handleEdit($tenagaPendukungId, $id)
    {
        $dokumen = $this->getById($id);
        if (! $dokumen) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        return Inertia::render('modules/tenaga-pendukung/dokumen/Edit', [
            'pelatihId' => (int) $tenagaPendukungId,
            'item'      => $dokumen,
        ]);
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->forceDelete();
    }
}
