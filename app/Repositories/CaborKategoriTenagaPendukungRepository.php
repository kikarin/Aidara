<?php

namespace App\Repositories;

use App\Models\CaborKategoriTenagaPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaborKategoriTenagaPendukungRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(CaborKategoriTenagaPendukung $model)
    {
        $this->model = $model;
        $this->with  = [
            'cabor',
            'caborKategori',
            'tenagaPendukung',
            'created_by_user',
            'updated_by_user',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model
            ->with(['cabor', 'caborKategori', 'tenagaPendukung'])
            ->select('cabor_kategori_tenaga_pendukung.id', 'cabor_kategori_tenaga_pendukung.cabor_id', 'cabor_kategori_tenaga_pendukung.cabor_kategori_id', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.is_active', 'cabor_kategori_tenaga_pendukung.created_at');

        if (request('cabor_kategori_id')) {
            $query->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', request('cabor_kategori_id'));
        }
        if (request('tenaga_pendukung_id')) {
            $query->where('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', request('tenaga_pendukung_id'));
        }
        if (request('is_active') !== null) {
            $query->where('cabor_kategori_tenaga_pendukung.is_active', request('is_active'));
        }
        if (request('search')) {
            $search = request('search');
            $query->whereHas('tenagaPendukung', function ($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                    ->orWhere('nik', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('no_hp', 'like', "%$search%");
            });
        }
        if (request('sort')) {
            $order     = request('order', 'asc');
            $sortField = request('sort');
            if ($sortField === 'tenaga_pendukung_nama') {
                $query->join('tenaga_pendukungs', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', '=', 'tenaga_pendukungs.id')
                    ->orderBy('tenaga_pendukungs.nama', $order);
            } else {
                $validColumns = ['id', 'cabor_id', 'cabor_kategori_id', 'tenaga_pendukung_id', 'jenis_tenaga_pendukung', 'created_at'];
                if (in_array($sortField, $validColumns)) {
                    $query->orderBy('cabor_kategori_tenaga_pendukung.'.$sortField, $order);
                } else {
                    $query->orderBy('cabor_kategori_tenaga_pendukung.id', 'desc');
                }
            }
        } else {
            $query->orderBy('cabor_kategori_tenaga_pendukung.id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);
        if ($perPage === -1) {
            $allRecords         = $query->get();
            $transformedRecords = collect($allRecords)->map(function ($record) {
                return [
                    'id'                          => $record->id,
                    'cabor_id'                    => $record->cabor_id,
                    'cabor_nama'                  => $record->cabor->nama ?? '-',
                    'cabor_kategori_id'           => $record->cabor_kategori_id,
                    'cabor_kategori_nama'         => $record->caborKategori->nama ?? '-',
                    'tenaga_pendukung_id'         => $record->tenaga_pendukung_id,
                    'tenaga_pendukung_nama'       => $record->tenagaPendukung->nama ?? '-',
                    'tenaga_pendukung_nik'        => $record->tenagaPendukung->nik  ?? '-',
                    'jenis_tenaga_pendukung'      => $record->jenis_tenaga_pendukung ?? '-',
                    'jenis_tenaga_pendukung_nama' => $record->jenis_tenaga_pendukung ?? '-',
                    'is_active'                   => $record->is_active,
                    'is_active_badge'             => $record->is_active_badge,
                    'created_at'                  => $record->created_at,
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
        $pageForPaginate    = $page < 1 ? 1 : $page;
        $records            = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformedRecords = collect($records->items())->map(function ($record) {
            return [
                'id'                          => $record->id,
                'tenaga_pendukung_id'         => $record->tenaga_pendukung_id,
                'nama'                        => $record->tenagaPendukung->nama ?? '-',
                'tenaga_pendukung_nama'       => $record->tenagaPendukung->nama ?? '-',
                'jenis_tenaga_pendukung'      => $record->jenis_tenaga_pendukung ?? '-',
                'jenis_tenaga_pendukung_nama' => $record->jenis_tenaga_pendukung ?? '-',
                'is_active'                   => $record->is_active,
                'is_active_badge'             => $record->is_active_badge,
                'created_at'                  => $record->created_at,
                'jenis_kelamin'               => $record->tenagaPendukung->jenis_kelamin     ?? '-',
                'tempat_lahir'                => $record->tenagaPendukung->tempat_lahir      ?? '-',
                'tanggal_lahir'               => $record->tenagaPendukung->tanggal_lahir     ?? '-',
                'tanggal_bergabung'           => $record->tenagaPendukung->tanggal_bergabung ?? '-',
                'foto'                        => $record->tenagaPendukung->foto              ?? null,
                'no_hp'                       => $record->tenagaPendukung->no_hp             ?? '-',
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
        foreach ($data['tenaga_pendukung_ids'] as $tpId) {
            $existing = $this->model->withTrashed()
                ->where('cabor_kategori_id', $data['cabor_kategori_id'])
                ->where('tenaga_pendukung_id', $tpId)
                ->first();
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->is_active            = (int) $data['is_active'];
                $existing->jenis_tenaga_pendukung = $data['jenis_tenaga_pendukung'] ?? null;
                $existing->cabor_id             = $data['cabor_id'];
                $existing->updated_by           = $userId;
                $existing->updated_at           = now();
                $existing->save();
                Log::info('Updated existing tenaga pendukung', [
                    'tenaga_pendukung_id'    => $tpId,
                    'is_active'              => $data['is_active'],
                    'jenis_tenaga_pendukung' => $data['jenis_tenaga_pendukung'] ?? null,
                ]);
            } else {
                $insertData[] = [
                    'cabor_id'                => $data['cabor_id'],
                    'cabor_kategori_id'      => $data['cabor_kategori_id'],
                    'tenaga_pendukung_id'    => $tpId,
                    'jenis_tenaga_pendukung' => $data['jenis_tenaga_pendukung'] ?? null,
                    'is_active'              => (int) $data['is_active'],
                    'created_by'             => $userId,
                    'updated_by'             => $userId,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
                Log::info('Will insert new tenaga pendukung', [
                    'tenaga_pendukung_id'    => $tpId,
                    'is_active'              => $data['is_active'],
                    'jenis_tenaga_pendukung' => $data['jenis_tenaga_pendukung'] ?? null,
                ]);
            }
        }
        Log::info('Total data to insert', ['count' => count($insertData)]);
        try {
            DB::beginTransaction();
            if (! empty($insertData)) {
                DB::table('cabor_kategori_tenaga_pendukung')->insert($insertData);
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
            ->with(['cabor', 'caborKategori', 'tenagaPendukung'])
            ->where('cabor_kategori_id', $caborKategoriId)
            ->get();
    }

    public function deleteByCaborKategoriId($caborKategoriId, $tenagaPendukungIds = [])
    {
        $query = $this->model->where('cabor_kategori_id', $caborKategoriId);
        if (! empty($tenagaPendukungIds)) {
            $query->whereIn('tenaga_pendukung_id', $tenagaPendukungIds);
        }

        return $query->delete();
    }

    public function validateRequest($request)
    {
        $rules = [];

        if ($request->isMethod('patch') || $request->isMethod('put')) {
            // Untuk update, validasi jenis_tenaga_pendukung sebagai string
            $rules = [
                'jenis_tenaga_pendukung' => 'nullable|string|max:255',
            ];
        } else {
            // Untuk create/store, validasi semua field
            $rules = [
                'cabor_id'                  => 'required|exists:cabor,id',
                'cabor_kategori_id'         => 'required|exists:cabor_kategori,id',
                'tenaga_pendukung_ids'      => 'required|array|min:1',
                'tenaga_pendukung_ids.*'    => 'required|exists:tenaga_pendukungs,id',
                'jenis_tenaga_pendukung'    => 'nullable|string|max:255',
            ];
        }

        $messages = [
            'cabor_id.required'                  => 'Cabor harus dipilih.',
            'cabor_id.exists'                    => 'Cabor yang dipilih tidak valid.',
            'cabor_kategori_id.required'         => 'Kategori harus dipilih.',
            'cabor_kategori_id.exists'           => 'Kategori yang dipilih tidak valid.',
            'tenaga_pendukung_ids.required'      => 'Tenaga Pendukung harus dipilih minimal 1.',
            'tenaga_pendukung_ids.array'         => 'Tenaga Pendukung harus berupa array.',
            'tenaga_pendukung_ids.min'           => 'Tenaga Pendukung harus dipilih minimal 1.',
            'tenaga_pendukung_ids.*.required'    => 'Tenaga Pendukung tidak boleh kosong.',
            'tenaga_pendukung_ids.*.exists'      => 'Tenaga Pendukung yang dipilih tidak valid.',
            'jenis_tenaga_pendukung.string'      => 'Jenis tenaga pendukung harus berupa teks.',
            'jenis_tenaga_pendukung.max'         => 'Jenis tenaga pendukung tidak boleh lebih dari 255 karakter.',
        ];

        return $request->validate($rules, $messages);
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->forceDelete();
    }
}
