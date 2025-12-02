<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaborKategoriRequest;
use App\Models\CaborKategori;
use App\Repositories\CaborKategoriRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CaborKategoriController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, CaborKategoriRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = CaborKategoriRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'cabor-kategori';
        $this->commonData['kode_first_menu']  = 'CABOR';
        $this->commonData['kode_second_menu'] = 'KATEGORI';
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['kategori'],
            'meta' => [
                'total'        => $data['total'],
                'current_page' => $data['currentPage'],
                'per_page'     => $data['perPage'],
                'search'       => $data['search'],
                'sort'         => $data['sort'],
                'order'        => $data['order'],
            ],
        ]);
    }

    public function index()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);

        return inertia('modules/cabor-kategori/Index', $data);
    }

    public function store(CaborKategoriRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('cabor-kategori.index')->with('success', 'Data kategori berhasil ditambahkan!');
    }

    public function update(CaborKategoriRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('cabor-kategori.index')->with('success', 'Data kategori berhasil diperbarui!');
    }

    public function show($id)
    {
        return $this->repository->handleShow($id);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('cabor-kategori.index')->with('success', 'Data kategori berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:cabor_kategori,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data kategori berhasil dihapus!']);
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);
        if (! is_array($data)) {
            return $data;
        }

        return inertia('modules/cabor-kategori/Create', $data);
    }

    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);
        if (! is_array($data)) {
            return $data;
        }

        return inertia('modules/cabor-kategori/Edit', $data);
    }

    public function list()
    {
        $data = CaborKategori::select('id', 'nama')->orderBy('nama')->get();

        return response()->json($data);
    }

    public function listByCabor($cabor_id)
    {
        $data = CaborKategori::where('cabor_id', $cabor_id)
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        return response()->json($data);
    }
}
