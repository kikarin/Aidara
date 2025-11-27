<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemeriksaanParameterRequest;
use App\Models\MstParameter;
use App\Models\Pemeriksaan;
use App\Repositories\PemeriksaanParameterRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PemeriksaanParameterController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, PemeriksaanParameterRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = PemeriksaanParameterRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'pemeriksaan-parameter';
        $this->commonData['kode_first_menu']  = 'PEMERIKSAAN';
        $this->commonData['kode_second_menu'] = 'PEMERIKSAAN-PARAMETER';
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index', 'nestedIndex']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function index(Pemeriksaan $pemeriksaan)
    {
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + ['pemeriksaan' => $pemeriksaan];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        request()->merge(['pemeriksaan_id' => $pemeriksaan->id]);
        $data = $this->repository->customIndex($data);

        return inertia('modules/pemeriksaan-parameter/Index', $data);
    }

    public function create(Pemeriksaan $pemeriksaan)
    {
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        // Exclude kategori 'umum' dari opsi parameter pemeriksaan
        $mstParameters = MstParameter::whereNull('deleted_at')
            ->where('kategori', '!=', 'umum')
            ->get(['id', 'nama', 'satuan']);
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + ['item' => null, 'pemeriksaan' => $pemeriksaan, 'mstParameters' => $mstParameters];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/pemeriksaan-parameter/Create', $data);
    }

    public function store(PemeriksaanParameterRequest $request, Pemeriksaan $pemeriksaan)
    {
        $data                   = $this->repository->validateRequest($request);
        $data['pemeriksaan_id'] = $pemeriksaan->id;
        $this->repository->create($data);

        return redirect()->route('pemeriksaan.parameter.index', $pemeriksaan->id)->with('success', 'Parameter pemeriksaan berhasil ditambahkan!');
    }

    public function show(Pemeriksaan $pemeriksaan, $id)
    {
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        $item = $this->repository->getById($id);

        return Inertia::render('modules/pemeriksaan-parameter/Show', ['item' => $item, 'pemeriksaan' => $pemeriksaan]);
    }

    public function edit(Pemeriksaan $pemeriksaan, $id)
    {
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        // Exclude kategori 'umum' dari opsi parameter pemeriksaan
        $mstParameters = MstParameter::whereNull('deleted_at')
            ->where('kategori', '!=', 'umum')
            ->get(['id', 'nama', 'satuan']);
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + ['item' => $item, 'pemeriksaan' => $pemeriksaan, 'mstParameters' => $mstParameters];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/pemeriksaan-parameter/Edit', $data);
    }

    public function update(PemeriksaanParameterRequest $request, Pemeriksaan $pemeriksaan, $id)
    {
        $data                   = $this->repository->validateRequest($request);
        $data['pemeriksaan_id'] = $pemeriksaan->id;
        $this->repository->update($id, $data);

        return redirect()->route('pemeriksaan.parameter.index', $pemeriksaan->id)->with('success', 'Parameter pemeriksaan berhasil diperbarui!');
    }

    public function destroy(Pemeriksaan $pemeriksaan, $id)
    {
        $this->repository->delete($id);

        return redirect()->route('pemeriksaan.parameter.index', $pemeriksaan->id)->with('success', 'Parameter pemeriksaan berhasil dihapus!');
    }

    public function destroy_selected(Request $request, Pemeriksaan $pemeriksaan)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:pemeriksaan_parameter,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Parameter pemeriksaan berhasil dihapus!']);
    }

    public function apiIndex(Pemeriksaan $pemeriksaan)
    {
        request()->merge(['pemeriksaan_id' => $pemeriksaan->id]);
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['data'],
            'meta' => [
                'total'        => $data['total'],
                'current_page' => $data['currentPage'],
                'per_page'     => $data['perPage'],
                'search'       => $data['search'],
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ]);
    }
}
