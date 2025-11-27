<?php

namespace App\Repositories;

use App\Models\PemeriksaanPesertaParameter;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;

class PemeriksaanPesertaParameterRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PemeriksaanPesertaParameter $model)
    {
        $this->model = $model;
        $this->with  = [
            'pemeriksaan',
            'pemeriksaanPeserta.peserta',
            'pemeriksaanParameter.mstParameter',
            'created_by_user',
            'updated_by_user',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model->with($this->with);
        if (request('pemeriksaan_id')) {
            $query->where('pemeriksaan_peserta_parameter.pemeriksaan_id', request('pemeriksaan_id'));
        }
        if (request('pemeriksaan_peserta_id')) {
            $query->where('pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', request('pemeriksaan_peserta_id'));
        }
        $sortField = request('sort');
        $order     = request('order', 'asc');
        $search    = request('search');

        // Handle search kolom relasi
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('pemeriksaanParameter.mstParameter', function ($q2) use ($search) {
                    $q2->where('nama', 'like', "%$search%");
                });
                $q->orWhereHas('pemeriksaanPeserta.peserta', function ($q2) use ($search) {
                    $q2->where('nama', 'like', "%$search%");
                });
            });
        }

        // Handle sort kolom relasi dan kolom utama
        if ($sortField === 'parameter') {
            $query->leftJoin('pemeriksaan_parameter', 'pemeriksaan_peserta_parameter.pemeriksaan_parameter_id', '=', 'pemeriksaan_parameter.id')
                ->leftJoin('mst_parameter', 'pemeriksaan_parameter.mst_parameter_id', '=', 'mst_parameter.id')
                ->orderBy('mst_parameter.nama', $order)
                ->select('pemeriksaan_peserta_parameter.*');
        } elseif (in_array($sortField, ['nilai', 'trend'])) {
            $query->orderBy('pemeriksaan_peserta_parameter.'.$sortField, $order);
        } elseif ($sortField) {
            $query->orderBy('pemeriksaan_peserta_parameter.'.$sortField, $order);
        } else {
            $query->orderBy('pemeriksaan_peserta_parameter.id', $order);
        }

        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);
        if ($perPage === -1) {
            $all         = $query->get();
            $transformed = collect($all)->map(function ($item) {
                return [
                    'id'                       => $item->id,
                    'pemeriksaan_id'           => $item->pemeriksaan_id,
                    'pemeriksaan_peserta_id'   => $item->pemeriksaan_peserta_id,
                    'pemeriksaan_parameter_id' => $item->pemeriksaan_parameter_id,
                    'parameter'                => $item->pemeriksaanParameter?->mstParameter?->nama ?? '-',
                    'nilai'                    => $item->nilai,
                    'trend'                    => $item->trend,
                    'peserta'                  => $item->pemeriksaanPeserta?->peserta?->nama ?? '-',
                    'status'                   => $item->pemeriksaanPeserta && $item->pemeriksaanPeserta->status ? $item->pemeriksaanPeserta->status->nama : '-',
                    'created_at'               => $item->created_at,
                    'updated_at'               => $item->updated_at,
                    'created_by_user'          => $item->created_by_user?->name ?? '-',
                    'updated_by_user'          => $item->updated_by_user?->name ?? '-',
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
        $items       = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        $transformed = collect($items->items())->map(function ($item) {
            return [
                'id'                       => $item->id,
                'pemeriksaan_id'           => $item->pemeriksaan_id,
                'pemeriksaan_peserta_id'   => $item->pemeriksaan_peserta_id,
                'pemeriksaan_parameter_id' => $item->pemeriksaan_parameter_id,
                'parameter'                => $item->pemeriksaanParameter?->mstParameter?->nama ?? '-',
                'nilai'                    => $item->nilai,
                'trend'                    => $item->trend,
                'peserta'                  => $item->pemeriksaanPeserta?->peserta?->nama ?? '-',
                'status'                   => $item->pemeriksaanPeserta && $item->pemeriksaanPeserta->status ? $item->pemeriksaanPeserta->status->nama : '-',
                'created_at'               => $item->created_at,
                'updated_at'               => $item->updated_at,
                'created_by_user'          => $item->created_by_user?->name ?? '-',
                'updated_by_user'          => $item->updated_by_user?->name ?? '-',
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

    public function getByIdArray($id)
    {
        $item = $this->model->with($this->with)->findOrFail($id);

        return [
            'id'                       => $item->id,
            'pemeriksaan_id'           => $item->pemeriksaan_id,
            'pemeriksaan_peserta_id'   => $item->pemeriksaan_peserta_id,
            'pemeriksaan_parameter_id' => $item->pemeriksaan_parameter_id,
            'parameter'                => $item->pemeriksaanParameter?->mstParameter?->nama ?? '-',
            'nilai'                    => $item->nilai,
            'trend'                    => $item->trend,
            'peserta'                  => $item->pemeriksaanPeserta?->peserta?->nama ?? '-',
            'status'                   => $item->pemeriksaanPeserta && $item->pemeriksaanPeserta->status ? $item->pemeriksaanPeserta->status->nama : '-',
            'created_at'               => $item->created_at,
            'updated_at'               => $item->updated_at,
            'created_by_user'          => $item->created_by_user?->name ?? '-',
            'updated_by_user'          => $item->updated_by_user?->name ?? '-',
        ];
    }

    public function getDetailWithRelations($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }
}
