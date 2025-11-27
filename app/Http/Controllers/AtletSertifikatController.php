<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtletSertifikatRequest;
use App\Repositories\AtletSertifikatRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AtletSertifikatController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(AtletSertifikatRepository $repository, Request $request)
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
        $sertifikat = $this->repository->getByAtletId($atletId);

        return response()->json($sertifikat);
    }

    public function store(AtletSertifikatRequest $request, $atlet_id)
    {
        $data  = $request->validated();
        $model = $this->repository->create($data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Sertifikat berhasil ditambahkan!', 'sertifikatId' => $model->id]);
        }

        return redirect()->route('atlet.sertifikat.index', $atlet_id)
            ->with('success', 'Sertifikat berhasil ditambahkan!');
    }

    public function update(AtletSertifikatRequest $request, $atlet_id, $id)
    {
        $data  = $request->validated();
        $model = $this->repository->update($id, $data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Sertifikat berhasil diperbarui!', 'sertifikatId' => $model->id]);
        }

        return redirect()->route('atlet.sertifikat.index', $atlet_id)
            ->with('success', 'Sertifikat berhasil diperbarui!');
    }

    public function destroy($atlet_id, $id)
    {
        $this->repository->delete($id);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['message' => 'Sertifikat berhasil dihapus!']);
        }

        return redirect()->route('atlet.sertifikat.index', $atlet_id)
            ->with('success', 'Sertifikat berhasil dihapus!');
    }

    public function apiIndex($atletId)
    {
        return response()->json($this->repository->apiIndex($atletId));
    }

    public function index($atlet_id)
    {
        // Render Inertia page untuk Sertifikat Index
        return Inertia::render('modules/atlet/sertifikat/Index', [
            'atletId' => (int) $atlet_id,
        ]);
    }

    public function create($atlet_id)
    {
        // Panggil metode handleCreate dari repository
        return $this->repository->handleCreate($atlet_id);
    }

    public function edit($atlet_id, $id)
    {
        // Panggil metode handleEdit dari repository
        return $this->repository->handleEdit($atlet_id, $id);
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:atlet_sertifikat,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Sertifikat terpilih berhasil dihapus!']);
    }

    public function show($atlet_id, $id)
    {
        $sertifikat = $this->repository->getById($id);
        if (! $sertifikat) {
            return redirect()->back()->with('error', 'Sertifikat tidak ditemukan');
        }

        return Inertia::render('modules/atlet/sertifikat/Show', [
            'atletId' => (int) $atlet_id,
            'item'    => $sertifikat,
        ]);
    }
}
