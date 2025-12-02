<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenagaPendukungDokumenRequest;
use App\Repositories\TenagaPendukungDokumenRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class TenagaPendukungDokumenController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(TenagaPendukungDokumenRepository $repository, Request $request)
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
            new Middleware("can:$permission Show", only: ['index', 'getByTenagaPendukungId']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['getByTenagaPendukungId', 'show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function getByTenagaPendukungId($tenagaPendukungId)
    {
        $dokumen = $this->repository->getByTenagaPendukungId($tenagaPendukungId);

        return response()->json($dokumen);
    }

    public function store(TenagaPendukungDokumenRequest $request, $tenaga_pendukung_id)
    {
        $data  = $request->validated();
        $model = $this->repository->create($data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Dokumen berhasil ditambahkan!', 'dokumenId' => $model->id]);
        }

        return redirect()->route('tenaga-pendukung.dokumen.index', $tenaga_pendukung_id)
            ->with('success', 'Dokumen berhasil ditambahkan!');
    }

    public function update(TenagaPendukungDokumenRequest $request, $tenaga_pendukung_id, $id)
    {
        $data  = $request->validated();
        $model = $this->repository->update($id, $data);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Dokumen berhasil diperbarui!', 'dokumenId' => $model->id]);
        }

        return redirect()->route('tenaga-pendukung.dokumen.index', $tenaga_pendukung_id)
            ->with('success', 'Dokumen berhasil diperbarui!');
    }

    public function destroy($tenaga_pendukung_id, $id)
    {
        $this->repository->delete($id);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['message' => 'Dokumen berhasil dihapus!']);
        }

        return redirect()->route('tenaga-pendukung.dokumen.index', $tenaga_pendukung_id)
            ->with('success', 'Dokumen berhasil dihapus!');
    }

    public function apiIndex($tenagaPendukungId)
    {
        return response()->json($this->repository->apiIndex($tenagaPendukungId));
    }

    public function index($tenaga_pendukung_id)
    {
        return Inertia::render('modules/tenaga-pendukung/dokumen/Index', [
            'tenagaPendukungId' => (int) $tenaga_pendukung_id,
        ]);
    }

    public function create($tenaga_pendukung_id)
    {
        return Inertia::render('modules/tenaga-pendukung/dokumen/Create', [
            'tenagaPendukungId' => (int) $tenaga_pendukung_id,
        ]);
    }

    public function edit($tenaga_pendukung_id, $id)
    {
        $dokumen = $this->repository->getById($id);
        if (! $dokumen) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        return Inertia::render('modules/tenaga-pendukung/dokumen/Edit', [
            'tenagaPendukungId' => (int) $tenaga_pendukung_id,
            'item'              => $dokumen,
        ]);
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenaga_pendukung_dokumen,id',
        ]);

        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Dokumen terpilih berhasil dihapus!']);
    }

    public function show($tenaga_pendukung_id, $id)
    {
        $dokumen = $this->repository->getById($id);
        if (! $dokumen) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        return Inertia::render('modules/tenaga-pendukung/dokumen/Show', [
            'tenagaPendukungId' => (int) $tenaga_pendukung_id,
            'item'              => $dokumen,
        ]);
    }
}
