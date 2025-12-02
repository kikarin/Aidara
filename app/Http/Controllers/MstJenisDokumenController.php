<?php

namespace App\Http\Controllers;

use App\Http\Requests\MstJenisDokumenRequest;
use App\Repositories\MstJenisDokumenRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class MstJenisDokumenController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $request;

    private $repository;

    public function __construct(Request $request, MstJenisDokumenRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = MstJenisDokumenRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'jenis-dokumen'; // Sesuaikan dengan nama route resource
        $this->commonData['kode_first_menu']  = 'DATA-MASTER';
        $this->commonData['kode_second_menu'] = 'DATA-MASTER-JENIS-DOKUMEN';
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
            'data' => $data['jenisDokumens'],
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

        return inertia('modules/data-master/jenis-dokumen/Index', $data);
    }

    public function store(MstJenisDokumenRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('jenis-dokumen.index')->with('success', 'Data jenis dokumen berhasil ditambahkan!');
    }

    public function update(MstJenisDokumenRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('jenis-dokumen.index')->with('success', 'Data jenis dokumen berhasil diperbarui!');
    }

    public function show($id)
    {
        $item      = $this->repository->getById($id);
        $itemArray = $item->toArray();

        return Inertia::render('modules/data-master/jenis-dokumen/Show', [
            'item' => $itemArray,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('jenis-dokumen.index')->with('success', 'Data jenis dokumen berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:mst_jenis_dokumen,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data jenis dokumen berhasil dihapus!']);
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

        return inertia('modules/data-master/jenis-dokumen/Create', $data);
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

        return inertia('modules/data-master/jenis-dokumen/Edit', $data);
    }
}
