<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtletDokumenRequest;
use App\Repositories\AtletDokumenRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AtletDokumenController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(AtletDokumenRepository $repository, Request $request)
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
        $dokumen = $this->repository->getByAtletId($atletId);

        return response()->json($dokumen);
    }

    public function store(AtletDokumenRequest $request, $atlet_id)
    {
        $data  = $request->validated();
        $model = $this->repository->create($data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Dokumen berhasil ditambahkan!', 'dokumenId' => $model->id]);
        }

        return redirect()->route('atlet.dokumen.index', $atlet_id)
            ->with('success', 'Dokumen berhasil ditambahkan!');
    }

    public function update(AtletDokumenRequest $request, $atlet_id, $id)
    {
        $data  = $request->validated();
        $model = $this->repository->update($id, $data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Dokumen berhasil diperbarui!', 'dokumenId' => $model->id]);
        }

        return redirect()->route('atlet.dokumen.index', $atlet_id)
            ->with('success', 'Dokumen berhasil diperbarui!');
    }

    public function destroy($atlet_id, $id)
    {
        $this->repository->delete($id);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['message' => 'Dokumen berhasil dihapus!']);
        }

        return redirect()->route('atlet.dokumen.index', $atlet_id)
            ->with('success', 'Dokumen berhasil dihapus!');
    }

    public function apiIndex($atletId)
    {
        return response()->json($this->repository->apiIndex($atletId));
    }

    public function index($atlet_id)
    {
        return Inertia::render('modules/atlet/dokumen/Index', [
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
            'ids.*' => 'required|integer|exists:atlet_dokumen,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Dokumen terpilih berhasil dihapus!']);
    }

    public function show($atlet_id, $id)
    {
        $dokumen = $this->repository->getById($id);
        if (! $dokumen) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        return Inertia::render('modules/atlet/dokumen/Show', [
            'atletId' => (int) $atlet_id,
            'item'    => $dokumen,
        ]);
    }
}
