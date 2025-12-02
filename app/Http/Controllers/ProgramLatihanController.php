<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramLatihanRequest;
use App\Repositories\ProgramLatihanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ProgramLatihanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, ProgramLatihanRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = ProgramLatihanRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'program-latihan';
        $this->commonData['kode_first_menu']  = 'PROGRAM-LATIHAN';
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

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['data'],
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

        return inertia('modules/program-latihan/Index', $data);
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

        return inertia('modules/program-latihan/Create', $data);
    }

    public function store(ProgramLatihanRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('program-latihan.index')->with('success', 'Program latihan berhasil ditambahkan!');
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

        return inertia('modules/program-latihan/Edit', $data);
    }

    public function update(ProgramLatihanRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('program-latihan.index')->with('success', 'Program latihan berhasil diperbarui!');
    }

    public function show($id)
    {
        $item = $this->repository->getDetailWithRelations($id);

        return Inertia::render('modules/program-latihan/Show', [
            'item' => $item,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('program-latihan.index')->with('success', 'Program latihan berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:program_latihan,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Program latihan terpilih berhasil dihapus!']);
    }
}
