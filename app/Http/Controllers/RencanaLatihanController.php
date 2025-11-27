<?php

namespace App\Http\Controllers;

use App\Http\Requests\RencanaLatihanRequest;
use App\Models\ProgramLatihan;
use App\Repositories\RencanaLatihanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RencanaLatihanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, RencanaLatihanRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->route                          = 'rencana-latihan';
        $this->commonData['kode_first_menu']  = 'PROGRAM-LATIHAN';
        $this->commonData['kode_second_menu'] = 'RENCANA-LATIHAN';
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['nestedIndex']),
            new Middleware("can:$permission Add", only: ['nestedCreate', 'nestedStore']),
            new Middleware("can:$permission Detail", only: ['nestedShow']),
            new Middleware("can:$permission Edit", only: ['nestedEdit', 'nestedUpdate']),
            new Middleware("can:$permission Delete", only: ['nestedDestroy', 'destroy_selected']),
        ];
    }

    public function nestedIndex($program_id, Request $request)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $request->merge(['program_latihan_id' => $program_id]);
        $data               = $this->repository->customIndex($data);
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor?->nama,
            'cabor_kategori_nama' => $programLatihan->caborKategori?->nama,
            'cabor_kategori_id'   => $programLatihan->cabor_kategori_id,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
        ];

        return inertia('modules/rencana-latihan/Index', $data);
    }

    public function nestedCreate($program_id)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + ['item' => null];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor?->nama,
            'cabor_kategori_nama' => $programLatihan->caborKategori?->nama,
            'cabor_kategori_id'   => $programLatihan->cabor_kategori_id,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
        ];

        return inertia('modules/rencana-latihan/Create', $data);
    }

    public function nestedStore($program_id, RencanaLatihanRequest $request)
    {
        $request->merge(['program_latihan_id' => $program_id]);
        $data = $this->repository->validateRequest($request);
        $this->repository->createWithRelations($data);

        return redirect()->route('program-latihan.rencana-latihan.index', $program_id)->with('success', 'Rencana latihan berhasil ditambahkan!');
    }

    public function nestedShow($program_id, $rencana_id)
    {
        $item           = $this->repository->getDetailWithRelations($rencana_id);
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + ['item' => $item];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor?->nama,
            'cabor_kategori_nama' => $programLatihan->caborKategori?->nama,
            'cabor_kategori_id'   => $programLatihan->cabor_kategori_id,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
        ];

        return inertia('modules/rencana-latihan/Show', $data);
    }

    public function nestedEdit($program_id, $rencana_id)
    {
        $item           = $this->repository->getDetailWithRelations($rencana_id);
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + ['item' => $item];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor?->nama,
            'cabor_kategori_nama' => $programLatihan->caborKategori?->nama,
            'cabor_kategori_id'   => $programLatihan->cabor_kategori_id,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
        ];

        return inertia('modules/rencana-latihan/Edit', $data);
    }

    public function nestedUpdate($program_id, $rencana_id, RencanaLatihanRequest $request)
    {
        $request->merge(['program_latihan_id' => $program_id]);
        $data = $this->repository->validateRequest($request);
        $this->repository->updateWithRelations($rencana_id, $data);

        return redirect()->route('program-latihan.rencana-latihan.index', $program_id)->with('success', 'Rencana latihan berhasil diperbarui!');
    }

    public function nestedDestroy($program_id, $rencana_id)
    {
        $this->repository->delete($rencana_id);

        return redirect()->route('program-latihan.rencana-latihan.index', $program_id)->with('success', 'Rencana latihan berhasil dihapus!');
    }

    public function destroy_selected(Request $request, $program_id)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:rencana_latihan,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Rencana latihan berhasil dihapus!']);
    }

    public function apiIndex(Request $request)
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
}
