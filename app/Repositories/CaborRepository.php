<?php

namespace App\Repositories;

use App\Http\Requests\CaborRequest;
use App\Models\Cabor;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CaborRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(Cabor $model)
    {
        $this->model   = $model;
        $this->request = CaborRequest::createFromBase(request());
        $this->with    = ['created_by_user', 'updated_by_user', 'kategori'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama', 'deskripsi');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$search.'%');
            });
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama', 'deskripsi', 'created_at', 'updated_at'];
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
                // Hitung jumlah peserta unik per cabor
                $jumlahAtlet = DB::table('cabor_kategori_atlet')
                    ->where('cabor_id', $item->id)
                    ->distinct('atlet_id')
                    ->count('atlet_id');

                $jumlahPelatih = DB::table('cabor_kategori_pelatih')
                    ->where('cabor_id', $item->id)
                    ->distinct('pelatih_id')
                    ->count('pelatih_id');

                $jumlahTenagaPendukung = DB::table('cabor_kategori_tenaga_pendukung')
                    ->where('cabor_id', $item->id)
                    ->distinct('tenaga_pendukung_id')
                    ->count('tenaga_pendukung_id');

                return [
                    'id'                      => $item->id,
                    'nama'                    => $item->nama,
                    'deskripsi'               => $item->deskripsi,
                    'jumlah_atlet'            => $jumlahAtlet,
                    'jumlah_pelatih'          => $jumlahPelatih,
                    'jumlah_tenaga_pendukung' => $jumlahTenagaPendukung,
                ];
            });
            $data += [
                'cabors'      => $transformedData,
                'total'       => $transformedData->count(),
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

        $transformedData = collect($items->items())->map(function ($item) {
            // Hitung jumlah peserta unik per cabor
            $jumlahAtlet = DB::table('cabor_kategori_atlet')
                ->where('cabor_id', $item->id)
                ->distinct('atlet_id')
                ->count('atlet_id');

            $jumlahPelatih = DB::table('cabor_kategori_pelatih')
                ->where('cabor_id', $item->id)
                ->distinct('pelatih_id')
                ->count('pelatih_id');

            $jumlahTenagaPendukung = DB::table('cabor_kategori_tenaga_pendukung')
                ->where('cabor_id', $item->id)
                ->distinct('tenaga_pendukung_id')
                ->count('tenaga_pendukung_id');

            return [
                'id'                      => $item->id,
                'nama'                    => $item->nama,
                'deskripsi'               => $item->deskripsi,
                'jumlah_atlet'            => $jumlahAtlet,
                'jumlah_pelatih'          => $jumlahPelatih,
                'jumlah_tenaga_pendukung' => $jumlahTenagaPendukung,
            ];
        });

        $data += [
            'cabors'      => $transformedData,
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

    public function getDetailWithUserTrack($id)
    {
        return $this->model
            ->with(['created_by_user', 'updated_by_user', 'kategori'])
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

        return Inertia::render('modules/cabor/Cabor/Show', [
            'item' => $itemArray,
        ]);
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    public function getPesertaByCabor($caborId, $tipe)
    {
        switch ($tipe) {
            case 'atlet':
                return DB::table('cabor_kategori_atlet as cka')
                    ->join('atlets as a', 'cka.atlet_id', '=', 'a.id')
                    ->where('cka.cabor_id', $caborId)
                    ->select('a.id', 'a.nama', 'a.foto', 'a.jenis_kelamin', 'a.tanggal_lahir')
                    ->distinct()
                    ->get()
                    ->map(function ($item) {
                        // Hitung usia
                        $usia = null;
                        if ($item->tanggal_lahir) {
                            $usia = Carbon::parse($item->tanggal_lahir)->age;
                        }

                        return [
                            'id'            => $item->id,
                            'nama'          => $item->nama,
                            'foto'          => $item->foto,
                            'jenis_kelamin' => $item->jenis_kelamin,
                            'usia'          => $usia,
                        ];
                    });

            case 'pelatih':
                return DB::table('cabor_kategori_pelatih as ckp')
                    ->join('pelatihs as p', 'ckp.pelatih_id', '=', 'p.id')
                    ->where('ckp.cabor_id', $caborId)
                    ->select('p.id', 'p.nama', 'p.foto', 'p.jenis_kelamin', 'p.tanggal_lahir')
                    ->distinct()
                    ->get()
                    ->map(function ($item) {
                        // Hitung usia
                        $usia = null;
                        if ($item->tanggal_lahir) {
                            $usia = Carbon::parse($item->tanggal_lahir)->age;
                        }

                        return [
                            'id'            => $item->id,
                            'nama'          => $item->nama,
                            'foto'          => $item->foto,
                            'jenis_kelamin' => $item->jenis_kelamin,
                            'usia'          => $usia,
                        ];
                    });

            case 'tenaga_pendukung':
                return DB::table('cabor_kategori_tenaga_pendukung as cktp')
                    ->join('tenaga_pendukungs as tp', 'cktp.tenaga_pendukung_id', '=', 'tp.id')
                    ->where('cktp.cabor_id', $caborId)
                    ->select('tp.id', 'tp.nama', 'tp.foto', 'tp.jenis_kelamin', 'tp.tanggal_lahir')
                    ->distinct()
                    ->get()
                    ->map(function ($item) {
                        // Hitung usia
                        $usia = null;
                        if ($item->tanggal_lahir) {
                            $usia = Carbon::parse($item->tanggal_lahir)->age;
                        }

                        return [
                            'id'            => $item->id,
                            'nama'          => $item->nama,
                            'foto'          => $item->foto,
                            'jenis_kelamin' => $item->jenis_kelamin,
                            'usia'          => $usia,
                        ];
                    });

            default:
                return collect();
        }
    }
}
