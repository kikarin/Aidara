<?php

namespace App\Http\Controllers;

use App\Http\Requests\PelatihSertifikatRequest;
use App\Repositories\PelatihSertifikatRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PelatihSertifikatController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(PelatihSertifikatRepository $repository, Request $request)
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
            new Middleware("can:$permission Show", only: ['index', 'getByPelatihId']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['getByPelatihId', 'show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function getByPelatihId($pelatihId)
    {
        $sertifikat = $this->repository->getByPelatihId($pelatihId);

        return response()->json($sertifikat);
    }

    public function store(PelatihSertifikatRequest $request, $pelatih_id)
    {
        $data  = $request->validated();
        $model = $this->repository->create($data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Sertifikat berhasil ditambahkan!', 'sertifikatId' => $model->id]);
        }

        return redirect()->route('pelatih.sertifikat.index', $pelatih_id)
            ->with('success', 'Sertifikat berhasil ditambahkan!');
    }

    public function update(PelatihSertifikatRequest $request, $pelatih_id, $id)
    {
        $data  = $request->validated();
        $model = $this->repository->update($id, $data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Sertifikat berhasil diperbarui!', 'sertifikatId' => $model->id]);
        }

        return redirect()->route('pelatih.sertifikat.index', $pelatih_id)
            ->with('success', 'Sertifikat berhasil diperbarui!');
    }

    public function destroy($pelatih_id, $id)
    {
        $this->repository->delete($id);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['message' => 'Sertifikat berhasil dihapus!']);
        }

        return redirect()->route('pelatih.sertifikat.index', $pelatih_id)
            ->with('success', 'Sertifikat berhasil dihapus!');
    }

    public function apiIndex($pelatihId)
    {
        return response()->json($this->repository->apiIndex($pelatihId));
    }

    public function index($pelatih_id)
    {
        return Inertia::render('modules/pelatih/sertifikat/Index', [
            'pelatihId' => (int) $pelatih_id,
        ]);
    }

    public function create($pelatih_id)
    {
        return $this->repository->handleCreate($pelatih_id);
    }

    public function edit($pelatih_id, $id)
    {
        return $this->repository->handleEdit($pelatih_id, $id);
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:pelatih_sertifikat,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Sertifikat terpilih berhasil dihapus!']);
    }

    public function show($pelatih_id, $id)
    {
        $sertifikat = $this->repository->getById($id);
        if (! $sertifikat) {
            return redirect()->back()->with('error', 'Sertifikat tidak ditemukan');
        }

        return Inertia::render('modules/pelatih/sertifikat/Show', [
            'pelatihId' => (int) $pelatih_id,
            'item'      => $sertifikat,
        ]);
    }
}
