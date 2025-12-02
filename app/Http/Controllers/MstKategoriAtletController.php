<?php

namespace App\Http\Controllers;

use App\Http\Requests\MstKategoriAtletRequest;
use App\Repositories\MstKategoriAtletRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class MstKategoriAtletController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $request;

    private $repository;

    public function __construct(Request $request, MstKategoriAtletRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = MstKategoriAtletRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'kategori-atlet'; // Sesuaikan dengan nama route resource
        $this->commonData['kode_first_menu']  = 'DATA-MASTER';
        $this->commonData['kode_second_menu'] = 'DATA-MASTER-KATEGORI-ATLET';
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
            'data' => $data['kategori_atlets'],
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

        return inertia('modules/data-master/kategori-atlet/Index', $data);
    }

    public function store(MstKategoriAtletRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('kategori-atlet.index')->with('success', 'Data kategori atlet berhasil ditambahkan!');
    }

    public function update(MstKategoriAtletRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('kategori-atlet.index')->with('success', 'Data kategori atlet berhasil diperbarui!');
    }

    public function show($id)
    {
        $item      = $this->repository->getById($id);
        $itemArray = $item->toArray();

        return Inertia::render('modules/data-master/kategori-atlet/Show', [
            'item' => $itemArray,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('kategori-atlet.index')->with('success', 'Data kategori atlet berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:mst_kategori_atlet,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data kategori atlet berhasil dihapus!']);
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

        return inertia('modules/data-master/kategori-atlet/Create', $data);
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

        return inertia('modules/data-master/kategori-atlet/Edit', $data);
    }
}
