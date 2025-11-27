<?php

namespace App\Repositories;

use App\Models\CaborKategoriAtlet;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaborKategoriAtletRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(CaborKategoriAtlet $model)
    {
        $this->model = $model;
        $this->with  = ['cabor', 'caborKategori', 'atlet', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model
            ->with(['cabor', 'caborKategori', 'atlet'])
            ->select(
                'cabor_kategori_atlet.id',
                'cabor_kategori_atlet.cabor_id',
                'cabor_kategori_atlet.cabor_kategori_id',
                'cabor_kategori_atlet.atlet_id',
                'cabor_kategori_atlet.posisi_atlet',
                'cabor_kategori_atlet.is_active',
                'cabor_kategori_atlet.created_at'
            );

        // Filter by cabor_kategori_id jika ada
        if (request('cabor_kategori_id')) {
            $query->where('cabor_kategori_atlet.cabor_kategori_id', request('cabor_kategori_id'));
        }
        // Filter by is_active jika ada
        if (request('is_active') !== null) {
            $query->where('cabor_kategori_atlet.is_active', request('is_active'));
        }

        // Search
        if (request('search')) {
            $search = request('search');
            $query->whereHas('atlet', function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%');
            });
        }

        // Sort
        if (request('sort')) {
            $order     = request('order', 'asc');
            $sortField = request('sort');

            if ($sortField === 'atlet_nama') {
                $query->join('atlets', 'cabor_kategori_atlet.atlet_id', '=', 'atlets.id')
                    ->orderBy('atlets.nama', $order);
            } else {
                $validColumns = ['id', 'cabor_id', 'cabor_kategori_id', 'atlet_id', 'created_at'];
                if (in_array($sortField, $validColumns)) {
                    $query->orderBy('cabor_kategori_atlet.'.$sortField, $order);
                } else {
                    $query->orderBy('cabor_kategori_atlet.id', 'desc');
                }
            }
        } else {
            $query->orderBy('cabor_kategori_atlet.id', 'desc');
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);

        if ($perPage === -1) {
            $allRecords         = $query->get();
            $transformedRecords = collect($allRecords)->map(function ($record) {
                return [
                    'id'                  => $record->id,
                    'cabor_id'            => $record->cabor_id,
                    'cabor_nama'          => $record->cabor->nama ?? '-',
                    'cabor_kategori_id'   => $record->cabor_kategori_id,
                    'cabor_kategori_nama' => $record->caborKategori->nama ?? '-',
                    'atlet_id'            => $record->atlet_id,
                    'atlet_nama'          => $record->atlet->nama ?? '-',
                    'atlet_nik'           => $record->atlet->nik  ?? '-',
                    'posisi_atlet'        => $record->posisi_atlet ?? '-',
                    'is_active'           => $record->is_active,
                    'is_active_badge'     => $record->is_active_badge,
                    'created_at'          => $record->created_at,
                ];
            });

            $data += [
                'records'     => $transformedRecords,
                'total'       => $transformedRecords->count(),
                'currentPage' => 1,
                'perPage'     => -1,
                'search'      => request('search', ''),
                'sort'        => request('sort', ''),
                'order'       => request('order', 'asc'),
            ];

            return $data;
        }

        $pageForPaginate = $page < 1 ? 1 : $page;
        $records         = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();

        $transformedRecords = collect($records->items())->map(function ($record) {
            return [
                'id'                => $record->id,
                'atlet_id'          => $record->atlet_id,
                'nama'              => $record->atlet->nama ?? '-',
                'atlet_nama'        => $record->atlet->nama ?? '-',
                'posisi_atlet'      => $record->posisi_atlet ?? '-',
                'posisi_atlet_nama' => $record->posisi_atlet ?? '-',
                'is_active'         => $record->is_active,
                'is_active_badge'   => $record->is_active_badge,
                'created_at'        => $record->created_at,
                'jenis_kelamin'     => $record->atlet->jenis_kelamin     ?? '-',
                'tempat_lahir'      => $record->atlet->tempat_lahir      ?? '-',
                'tanggal_lahir'     => $record->atlet->tanggal_lahir     ?? '-',
                'tanggal_bergabung' => $record->atlet->tanggal_bergabung ?? '-',
                'foto'              => $record->atlet->foto              ?? null,
            ];
        });

        $data += [
            'records'     => $transformedRecords,
            'total'       => $records->total(),
            'currentPage' => $records->currentPage(),
            'perPage'     => $records->perPage(),
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

    public function batchInsert($data)
    {
        Log::info('BatchInsert Data', $data);

        $userId     = Auth::id();
        $insertData = [];

        // Restore data yang sudah soft deleted
        if (! empty($data['atlet_ids'])) {
            foreach ($data['atlet_ids'] as $atletId) {
                $restore = $this->model->withTrashed()
                    ->where('cabor_id', $data['cabor_id'])
                    ->where('cabor_kategori_id', $data['cabor_kategori_id'])
                    ->where('atlet_id', $atletId)
                    ->whereNotNull('deleted_at')
                    ->first();
                if ($restore) {
                    $restore->restore();
                }
            }
        }

        foreach ($data['atlet_ids'] as $atletId) {
            // Cek apakah sudah ada (termasuk soft deleted)
            $existing = $this->model->withTrashed()
                ->where('cabor_id', $data['cabor_id'])
                ->where('cabor_kategori_id', $data['cabor_kategori_id'])
                ->where('atlet_id', $atletId)
                ->first();

            if ($existing) {
                // Jika soft deleted, restore
                if ($existing->trashed()) {
                    $existing->restore();
                }
                // Update status aktif/nonaktif
                $existing->is_active = (int) $data['is_active'];
                if (isset($data['posisi_atlet'])) {
                    $existing->posisi_atlet = $data['posisi_atlet'];
                }
                $existing->updated_by = $userId;
                $existing->updated_at = now();
                $existing->save();
            } else {
                // Insert baru
                $insertData[] = [
                    'cabor_id'          => $data['cabor_id'],
                    'cabor_kategori_id' => $data['cabor_kategori_id'],
                    'atlet_id'          => $atletId,
                    'posisi_atlet'      => $data['posisi_atlet'] ?? null,
                    'is_active'         => (int) $data['is_active'],
                    'created_by'        => $userId,
                    'updated_by'        => $userId,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }
        }
        if (! empty($insertData)) {
            DB::table('cabor_kategori_atlet')->insert($insertData);
        }

        try {
            DB::beginTransaction();
            // Insert batch dengan ignore untuk menghindari duplicate entry
            DB::table('cabor_kategori_atlet')->insertOrIgnore($insertData);
            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getByCaborKategoriId($caborKategoriId)
    {
        return $this->model
            ->with(['cabor', 'caborKategori', 'atlet'])
            ->where('cabor_kategori_id', $caborKategoriId)
            ->get();
    }

    public function deleteByCaborKategoriId($caborKategoriId, $atletIds = [])
    {
        $query = $this->model->where('cabor_kategori_id', $caborKategoriId);

        if (! empty($atletIds)) {
            $query->whereIn('atlet_id', $atletIds);
        }

        return $query->delete();
    }

    public function validateRequest($request)
    {
        $rules = [];

        if ($request->isMethod('patch') || $request->isMethod('put')) {
            // Untuk update, validasi posisi_atlet sebagai string
            $rules = [
                'posisi_atlet' => 'nullable|string|max:255',
            ];
        } else {
            // Untuk create/store, validasi semua field
            $rules = [
                'cabor_id'          => 'required|exists:cabor,id',
                'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
                'atlet_ids'         => 'required|array|min:1',
                'atlet_ids.*'       => 'required|exists:atlets,id',
                'is_active'         => 'nullable|in:0,1',
                'posisi_atlet'      => 'nullable|string|max:255',
            ];
        }

        $messages = [
            'cabor_id.required'          => 'Cabor harus dipilih.',
            'cabor_id.exists'            => 'Cabor yang dipilih tidak valid.',
            'cabor_kategori_id.required' => 'Kategori harus dipilih.',
            'cabor_kategori_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'atlet_ids.required'         => 'Atlet harus dipilih minimal 1.',
            'atlet_ids.array'            => 'Atlet harus berupa array.',
            'atlet_ids.min'              => 'Atlet harus dipilih minimal 1.',
            'atlet_ids.*.required'       => 'Atlet tidak boleh kosong.',
            'atlet_ids.*.exists'         => 'Atlet yang dipilih tidak valid.',
            'posisi_atlet.string'        => 'Posisi atlet harus berupa teks.',
            'posisi_atlet.max'           => 'Posisi atlet tidak boleh lebih dari 255 karakter.',
        ];

        return $request->validate($rules, $messages);
    }
}
