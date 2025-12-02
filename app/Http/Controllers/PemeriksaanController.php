<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemeriksaanRequest;
use App\Repositories\PemeriksaanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PemeriksaanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, PemeriksaanRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = PemeriksaanRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'pemeriksaan';
        $this->commonData['kode_first_menu']  = 'PEMERIKSAAN';
        $this->commonData['kode_second_menu'] = null;
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

    public function index()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);

        return inertia('modules/pemeriksaan/Index', $data);
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + ['item' => null];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/pemeriksaan/Create', $data);
    }

    public function store(PemeriksaanRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('pemeriksaan.index')->with('success', 'Pemeriksaan berhasil ditambahkan!');
    }

    public function show($id)
    {
        $item      = $this->repository->getById($id);
        $itemArray = $item->toArray();

        return Inertia::render('modules/pemeriksaan/Show', ['item' => $itemArray]);
    }

    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + ['item' => $item];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/pemeriksaan/Edit', $data);
    }

    public function update(PemeriksaanRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('pemeriksaan.index')->with('success', 'Pemeriksaan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('pemeriksaan.index')->with('success', 'Pemeriksaan berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:pemeriksaan,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data pemeriksaan berhasil dihapus!']);
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['pemeriksaan'],
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
