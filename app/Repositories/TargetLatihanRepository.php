<?php

namespace App\Repositories;

use App\Models\TargetLatihan;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;

class TargetLatihanRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(TargetLatihan $model)
    {
        $this->model = $model;
        $this->with  = ['programLatihan', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with($this->with);

        if (request('program_latihan_id')) {
            $query->where('program_latihan_id', request('program_latihan_id'));
        }
        if (request('jenis_target')) {
            $query->where('jenis_target', request('jenis_target'));
        }
        // Filter peruntukan hanya untuk target individu
        if (request('peruntukan') && request('jenis_target') === 'individu') {
            $query->where('peruntukan', request('peruntukan'));
        }
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi', 'like', "%$search%")
                    ->orWhere('satuan', 'like', "%$search%")
                    ->orWhere('nilai_target', 'like', "%$search%");
            });
        }
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'deskripsi', 'satuan', 'nilai_target', 'created_at', 'updated_at'];
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
                    'id'                   => $item->id,
                    'program_latihan_id'   => $item->program_latihan_id,
                    'program_latihan_nama' => $item->programLatihan?->nama_program,
                    'jenis_target'         => $item->jenis_target,
                    'peruntukan'           => $item->peruntukan,
                    'deskripsi'            => $item->deskripsi,
                    'satuan'               => $item->satuan,
                    'nilai_target'         => $item->nilai_target,
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
                'id'                   => $item->id,
                'program_latihan_id'   => $item->program_latihan_id,
                'program_latihan_nama' => $item->programLatihan?->nama_program,
                'jenis_target'         => $item->jenis_target,
                'peruntukan'           => $item->peruntukan,
                'deskripsi'            => $item->deskripsi,
                'satuan'               => $item->satuan,
                'nilai_target'         => $item->nilai_target,
                'performa_arah'        => $item->performa_arah ?? 'max',
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

    public function getDetailWithRelations($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }
}
