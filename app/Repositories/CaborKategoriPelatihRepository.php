<?php

namespace App\Repositories;

use App\Models\CaborKategoriPelatih;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaborKategoriPelatihRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(CaborKategoriPelatih $model)
    {
        $this->model = $model;
        $this->with  = ['cabor', 'caborKategori', 'pelatih', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model
            ->with(['cabor', 'caborKategori', 'pelatih'])
            ->select('cabor_kategori_pelatih.id', 'cabor_kategori_pelatih.cabor_id', 'cabor_kategori_pelatih.cabor_kategori_id', 'cabor_kategori_pelatih.pelatih_id', 'cabor_kategori_pelatih.jenis_pelatih', 'cabor_kategori_pelatih.is_active', 'cabor_kategori_pelatih.created_at');

        // Filter by cabor_kategori_id jika ada
        if (request('cabor_kategori_id')) {
            $query->where('cabor_kategori_pelatih.cabor_kategori_id', request('cabor_kategori_id'));
        }
        // Filter by pelatih_id jika ada
        if (request('pelatih_id')) {
            $query->where('cabor_kategori_pelatih.pelatih_id', request('pelatih_id'));
        }
        // Filter by is_active jika ada
        if (request('is_active') !== null) {
            $query->where('cabor_kategori_pelatih.is_active', request('is_active'));
        }

        // Search
        if (request('search')) {
            $search = request('search');
            $query->whereHas('pelatih', function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%');
            });
        }

        // Sort
        if (request('sort')) {
            $order     = request('order', 'asc');
            $sortField = request('sort');

            if ($sortField === 'pelatih_nama') {
                $query->join('pelatihs', 'cabor_kategori_pelatih.pelatih_id', '=', 'pelatihs.id')
                    ->orderBy('pelatihs.nama', $order);
            } else {
                $validColumns = ['id', 'cabor_id', 'cabor_kategori_id', 'pelatih_id', 'jenis_pelatih', 'created_at'];
                if (in_array($sortField, $validColumns)) {
                    $query->orderBy('cabor_kategori_pelatih.'.$sortField, $order);
                } else {
                    $query->orderBy('cabor_kategori_pelatih.id', 'desc');
                }
            }
        } else {
            $query->orderBy('cabor_kategori_pelatih.id', 'desc');
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
                    'pelatih_id'          => $record->pelatih_id,
                    'pelatih_nama'        => $record->pelatih->nama ?? '-',
                    'pelatih_nik'         => $record->pelatih->nik  ?? '-',
                    'jenis_pelatih'       => $record->jenis_pelatih ?? '-',
                    'jenis_pelatih_nama'  => $record->jenis_pelatih ?? '-',
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
                'id'                 => $record->id,
                'pelatih_id'         => $record->pelatih_id,
                'nama'               => $record->pelatih->nama ?? '-',
                'pelatih_nama'       => $record->pelatih->nama ?? '-',
                'jenis_pelatih'      => $record->jenis_pelatih ?? '-',
                'jenis_pelatih_nama' => $record->jenis_pelatih ?? '-',
                'is_active'          => $record->is_active,
                'is_active_badge'    => $record->is_active_badge,
                'created_at'         => $record->created_at,
                'jenis_kelamin'      => $record->pelatih->jenis_kelamin     ?? '-',
                'tempat_lahir'       => $record->pelatih->tempat_lahir      ?? '-',
                'tanggal_lahir'      => $record->pelatih->tanggal_lahir     ?? '-',
                'tanggal_bergabung'  => $record->pelatih->tanggal_bergabung ?? '-',
                'foto'               => $record->pelatih->foto              ?? null,
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
        $userId     = Auth::id();
        $insertData = [];

        foreach ($data['pelatih_ids'] as $pelatihId) {
            // Cek apakah sudah ada (termasuk soft deleted) - sesuai unique constraint
            $existing = $this->model->withTrashed()
                ->where('cabor_kategori_id', $data['cabor_kategori_id'])
                ->where('pelatih_id', $pelatihId)
                ->first();

            if ($existing) {
                // Jika soft deleted, restore
                if ($existing->trashed()) {
                    $existing->restore();
                }
                // Update status aktif/nonaktif dan jenis pelatih
                $existing->is_active     = (int) $data['is_active'];
                $existing->jenis_pelatih = $data['jenis_pelatih'] ?? null;
                $existing->cabor_id      = $data['cabor_id'];
                $existing->updated_by    = $userId;
                $existing->updated_at    = now();
                $existing->save();

                Log::info('Updated existing pelatih', [
                    'pelatih_id'    => $pelatihId,
                    'is_active'     => $data['is_active'],
                    'jenis_pelatih' => $data['jenis_pelatih'] ?? null,
                ]);
            } else {
                // Insert baru
                $insertData[] = [
                    'cabor_id'          => $data['cabor_id'],
                    'cabor_kategori_id' => $data['cabor_kategori_id'],
                    'pelatih_id'        => $pelatihId,
                    'jenis_pelatih'    => $data['jenis_pelatih'] ?? null,
                    'is_active'         => (int) $data['is_active'],
                    'created_by'        => $userId,
                    'updated_by'        => $userId,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                Log::info('Will insert new pelatih', [
                    'pelatih_id'    => $pelatihId,
                    'is_active'     => $data['is_active'],
                    'jenis_pelatih' => $data['jenis_pelatih'] ?? null,
                ]);
            }
        }

        Log::info('Total data to insert', ['count' => count($insertData)]);

        try {
            DB::beginTransaction();
            if (! empty($insertData)) {
                DB::table('cabor_kategori_pelatih')->insert($insertData);
            }
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
            ->with(['cabor', 'caborKategori', 'pelatih'])
            ->where('cabor_kategori_id', $caborKategoriId)
            ->get();
    }

    public function deleteByCaborKategoriId($caborKategoriId, $pelatihIds = [])
    {
        $query = $this->model->where('cabor_kategori_id', $caborKategoriId);

        if (! empty($pelatihIds)) {
            $query->whereIn('pelatih_id', $pelatihIds);
        }

        return $query->delete();
    }

    public function validateRequest($request)
    {
        $rules = [];

        if ($request->isMethod('patch') || $request->isMethod('put')) {
            // Untuk update, validasi jenis_pelatih sebagai string
            $rules = [
                'jenis_pelatih' => 'nullable|string|max:255',
            ];
        } else {
            // Untuk create/store, validasi semua field
            $rules = [
                'cabor_id'          => 'required|exists:cabor,id',
                'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
                'pelatih_ids'       => 'required|array|min:1',
                'pelatih_ids.*'     => 'required|exists:pelatihs,id',
                'jenis_pelatih'     => 'nullable|string|max:255',
            ];
        }

        $messages = [
            'cabor_id.required'          => 'Cabor harus dipilih.',
            'cabor_id.exists'            => 'Cabor yang dipilih tidak valid.',
            'cabor_kategori_id.required' => 'Kategori harus dipilih.',
            'cabor_kategori_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'pelatih_ids.required'       => 'Pelatih harus dipilih minimal 1.',
            'pelatih_ids.array'          => 'Pelatih harus berupa array.',
            'pelatih_ids.min'            => 'Pelatih harus dipilih minimal 1.',
            'pelatih_ids.*.required'     => 'Pelatih tidak boleh kosong.',
            'pelatih_ids.*.exists'       => 'Pelatih yang dipilih tidak valid.',
            'jenis_pelatih.string'       => 'Jenis pelatih harus berupa teks.',
            'jenis_pelatih.max'          => 'Jenis pelatih tidak boleh lebih dari 255 karakter.',
        ];

        return $request->validate($rules, $messages);
    }
}
