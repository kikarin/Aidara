<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtletPrestasiRequest;
use App\Repositories\AtletPrestasiRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AtletPrestasiController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(AtletPrestasiRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->commonData['kode_first_menu']  = $this->kode_menu;
        $this->commonData['kode_second_menu'] = null;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index', 'getByAtletId']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['getByAtletId', 'show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function getByAtletId($atletId)
    {
        $prestasi = $this->repository->getByAtletId($atletId);

        return response()->json($prestasi);
    }

    public function store(AtletPrestasiRequest $request, $atlet_id)
    {
        $data  = $request->validated();
        $model = $this->repository->create($data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Prestasi berhasil ditambahkan!', 'prestasiId' => $model->id]);
        }

        return redirect()->route('atlet.prestasi.index', $atlet_id)
            ->with('success', 'Prestasi berhasil ditambahkan!');
    }

    public function update(AtletPrestasiRequest $request, $atlet_id, $id)
    {
        $data  = $request->validated();
        $model = $this->repository->update($id, $data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Prestasi berhasil diperbarui!', 'prestasiId' => $model->id]);
        }

        return redirect()->route('atlet.prestasi.index', $atlet_id)
            ->with('success', 'Prestasi berhasil diperbarui!');
    }

    public function destroy($atlet_id, $id)
    {
        $this->repository->delete($id);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['message' => 'Prestasi berhasil dihapus!']);
        }

        return redirect()->route('atlet.prestasi.index', $atlet_id)
            ->with('success', 'Prestasi berhasil dihapus!');
    }

    public function apiIndex($atletId)
    {
        return response()->json($this->repository->apiIndex($atletId));
    }

    public function index($atlet_id)
    {
        return Inertia::render('modules/atlet/prestasi/Index', [
            'atletId' => (int) $atlet_id,
        ]);
    }

    public function create($atlet_id)
    {
        return $this->repository->handleCreate($atlet_id);
    }

    public function edit($atlet_id, $id)
    {
        return $this->repository->handleEdit($atlet_id, $id);
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:atlet_prestasi,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Prestasi terpilih berhasil dihapus!']);
    }

    public function show($atlet_id, $id)
    {
        $prestasi = $this->repository->getById($id);
        if (! $prestasi) {
            return redirect()->back()->with('error', 'Prestasi tidak ditemukan');
        }

        return Inertia::render('modules/atlet/prestasi/Show', [
            'atletId' => (int) $atlet_id,
            'item'    => $prestasi,
        ]);
    }
}
