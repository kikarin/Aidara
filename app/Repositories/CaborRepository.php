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
        $this->with    = ['created_by_user', 'updated_by_user', 'kategori', 'kategoriPeserta'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama', 'deskripsi', 'kategori_peserta_id', 'icon')->with('kategoriPeserta');

        // Role-based filtering
        $auth = Auth::user();
        if ($auth && $auth->current_role_id == 35) { // Atlet
            if ($auth->atlet && $auth->atlet->id) {
                $query->whereHas('caborAtlet', function ($sub_query) use ($auth) {
                    $sub_query->where('atlet_id', $auth->atlet->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                });
            }
        } elseif ($auth && $auth->current_role_id == 36) { // Pelatih
            if ($auth->pelatih && $auth->pelatih->id) {
                $query->whereHas('caborPelatih', function ($sub_query) use ($auth) {
                    $sub_query->where('pelatih_id', $auth->pelatih->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                });
            }
        } elseif ($auth && $auth->current_role_id == 37) { // Tenaga Pendukung
            if ($auth->tenagaPendukung && $auth->tenagaPendukung->id) {
                $query->whereHas('caborTenagaPendukung', function ($sub_query) use ($auth) {
                    $sub_query->where('tenaga_pendukung_id', $auth->tenagaPendukung->id)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at');
                });
            }
        }

        // Filter by kategori_peserta_id (Jenis)
        if (request('kategori_peserta_id')) {
            $kategoriPesertaId = request('kategori_peserta_id');
            if ($kategoriPesertaId !== 'all') {
                $query->where('kategori_peserta_id', $kategoriPesertaId);
            }
        }

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
                    ->whereNull('deleted_at') // Filter soft deleted
                    ->selectRaw('COUNT(DISTINCT atlet_id) as count')
                    ->value('count') ?? 0;

                $jumlahPelatih = DB::table('cabor_kategori_pelatih')
                    ->where('cabor_id', $item->id)
                    ->whereNull('deleted_at') // Filter soft deleted
                    ->selectRaw('COUNT(DISTINCT pelatih_id) as count')
                    ->value('count') ?? 0;

                $jumlahTenagaPendukung = DB::table('cabor_kategori_tenaga_pendukung')
                    ->where('cabor_id', $item->id)
                    ->whereNull('deleted_at') // Filter soft deleted
                    ->selectRaw('COUNT(DISTINCT tenaga_pendukung_id) as count')
                    ->value('count') ?? 0;

                return [
                    'id'                      => $item->id,
                    'nama'                    => $item->nama,
                    'deskripsi'               => $item->deskripsi,
                    'icon'                    => $item->icon,
                    'kategori_peserta'        => $item->kategoriPeserta ? [
                        'id' => $item->kategoriPeserta->id,
                        'nama' => $item->kategoriPeserta->nama,
                    ] : null,
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
                ->whereNull('deleted_at') // Filter soft deleted
                ->selectRaw('COUNT(DISTINCT atlet_id) as count')
                ->value('count') ?? 0;

            $jumlahPelatih = DB::table('cabor_kategori_pelatih')
                ->where('cabor_id', $item->id)
                ->whereNull('deleted_at') // Filter soft deleted
                ->selectRaw('COUNT(DISTINCT pelatih_id) as count')
                ->value('count') ?? 0;

            $jumlahTenagaPendukung = DB::table('cabor_kategori_tenaga_pendukung')
                ->where('cabor_id', $item->id)
                ->whereNull('deleted_at') // Filter soft deleted
                ->selectRaw('COUNT(DISTINCT tenaga_pendukung_id) as count')
                ->value('count') ?? 0;

            return [
                'id'                      => $item->id,
                'nama'                    => $item->nama,
                'deskripsi'               => $item->deskripsi,
                'kategori_peserta'        => $item->kategoriPeserta ? [
                    'id' => $item->kategoriPeserta->id,
                    'nama' => $item->kategoriPeserta->nama,
                ] : null,
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
            ->with(['created_by_user', 'updated_by_user', 'kategori', 'kategoriPeserta'])
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
                    ->whereNull('cka.deleted_at') // Filter soft deleted
                    ->select('a.id', 'a.nama', 'a.foto', 'a.jenis_kelamin', 'a.tanggal_lahir', DB::raw('COALESCE(cka.posisi_atlet, "-") as posisi_atlet'))
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
                            'posisi_atlet'  => $item->posisi_atlet ?? '-',
                        ];
                    });

            case 'pelatih':
                return DB::table('cabor_kategori_pelatih as ckp')
                    ->join('pelatihs as p', 'ckp.pelatih_id', '=', 'p.id')
                    ->where('ckp.cabor_id', $caborId)
                    ->whereNull('ckp.deleted_at') // Filter soft deleted
                    ->select('p.id', 'p.nama', 'p.foto', 'p.jenis_kelamin', 'p.tanggal_lahir', DB::raw('COALESCE(ckp.jenis_pelatih, "-") as jenis_pelatih'))
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
                            'jenis_pelatih' => $item->jenis_pelatih ?? '-',
                        ];
                    });

            case 'tenaga_pendukung':
                return DB::table('cabor_kategori_tenaga_pendukung as cktp')
                    ->join('tenaga_pendukungs as tp', 'cktp.tenaga_pendukung_id', '=', 'tp.id')
                    ->where('cktp.cabor_id', $caborId)
                    ->whereNull('cktp.deleted_at') // Filter soft deleted
                    ->select('tp.id', 'tp.nama', 'tp.foto', 'tp.jenis_kelamin', 'tp.tanggal_lahir', DB::raw('COALESCE(cktp.jenis_tenaga_pendukung, "-") as jenis_tenaga_pendukung'))
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
                            'jenis_tenaga_pendukung' => $item->jenis_tenaga_pendukung ?? '-',
                        ];
                    });

            default:
                return collect();
        }
    }
}
