<?php

namespace App\Repositories;

use App\Models\PelatihSertifikat;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PelatihSertifikatRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PelatihSertifikat $model)
    {
        $this->model = $model;
        $this->with  = [
            'media',
            'created_by_user',
            'updated_by_user',
        ];
    }

    public function create(array $data)
    {
        Log::info('PelatihSertifikatRepository: create', $data);
        $file = $data['file'] ?? null;
        unset($data['file']);
        $data  = $this->customDataCreateUpdate($data);
        $model = $this->model->create($data);
        if ($file) {
            $model->addMedia($file)->usingName($data['nama_sertifikat'] ?? 'Sertifikat')->toMediaCollection('sertifikat_file');
        }

        return $model;
    }

    public function update($id, array $data)
    {
        Log::info('PelatihSertifikatRepository: update', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);
        if ($record) {
            $file = $data['file'] ?? null;
            unset($data['file']);
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            if ($file) {
                $record->clearMediaCollection('sertifikat_file');
                $record->addMedia($file)->usingName($data['nama_sertifikat'] ?? 'Sertifikat')->toMediaCollection('sertifikat_file');
            }
            Log::info('PelatihSertifikatRepository: updated', $record->toArray());

            return $record;
        }
        Log::warning('PelatihSertifikatRepository: not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('PelatihSertifikatRepository: delete', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);
        if ($record) {
            $record->forceDelete();
            Log::info('PelatihSertifikatRepository: deleted', ['id' => $id]);

            return true;
        }
        Log::warning('PelatihSertifikatRepository: not found for delete', ['id' => $id]);

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

    public function getByPelatihId($pelatihId)
    {
        return $this->model->where('pelatih_id', $pelatihId)->get();
    }

    public function getById($id)
    {
        return $this->model->with($this->with)->find($id);
    }

    public function apiIndex($pelatihId)
    {
        $query = $this->model->where('pelatih_id', $pelatihId);

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_sertifikat', 'like', '%'.$search.'%')
                    ->orWhere('penyelenggara', 'like', '%'.$search.'%')
                    ->orWhere('tanggal_terbit', 'like', '%'.$search.'%');
            });
        }
        // Sort
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama_sertifikat', 'penyelenggara', 'tanggal_terbit', 'created_at', 'updated_at'];
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
                    'id'              => $item->id,
                    'nama_sertifikat' => $item->nama_sertifikat,
                    'penyelenggara'   => $item->penyelenggara,
                    'tanggal_terbit'  => $item->tanggal_terbit,
                    'file_url'        => $item->file_url,
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
        $items           = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformed     = collect($items->items())->map(function ($item) {
            return [
                'id'              => $item->id,
                'nama_sertifikat' => $item->nama_sertifikat,
                'penyelenggara'   => $item->penyelenggara,
                'tanggal_terbit'  => $item->tanggal_terbit,
                'file_url'        => $item->file_url,
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

    public function handleCreate($pelatihId)
    {
        return Inertia::render('modules/pelatih/sertifikat/Create', [
            'pelatihId' => (int) $pelatihId,
        ]);
    }

    public function handleEdit($pelatihId, $id)
    {
        $sertifikat = $this->getById($id);
        if (! $sertifikat) {
            return redirect()->back()->with('error', 'Sertifikat tidak ditemukan');
        }

        return Inertia::render('modules/pelatih/sertifikat/Edit', [
            'pelatihId' => (int) $pelatihId,
            'item'      => $sertifikat,
        ]);
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->forceDelete();
    }
}
