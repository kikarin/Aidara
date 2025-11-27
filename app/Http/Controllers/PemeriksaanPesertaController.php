<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemeriksaanPesertaRequest;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanPeserta;
use App\Models\RefStatusPemeriksaan;
use App\Models\TenagaPendukung;
use App\Repositories\PemeriksaanPesertaRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PemeriksaanPesertaController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    public function __construct(PemeriksaanPesertaRepository $repository)
    {
        $this->repository = $repository;
        $this->initialize();
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

    public function index(Pemeriksaan $pemeriksaan)
    {
        // Load semua relasi yang dibutuhkan
        $pemeriksaan->load([
            'cabor',
            'caborKategori',
            'tenagaPendukung',
        ]);

        $data = [
            'peserta_type' => request('jenis_peserta', 'atlet'),
        ];

        Log::info('Index method called with jenis_peserta: '.request('jenis_peserta', 'atlet'));

        $items = $this->repository->customIndex($data);

        Log::info('Items count: '.count($items['data']));
        if (count($items['data']) > 0) {
            Log::info('First item: '.json_encode($items['data'][0]));
        }

        return Inertia::render('modules/pemeriksaan-peserta/Index', [
            'items'         => $items,
            'pemeriksaan'   => $pemeriksaan,
            'jenis_peserta' => request('jenis_peserta', 'atlet'),
        ]);
    }

    public function create(Pemeriksaan $pemeriksaan, Request $request)
    {
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        $ref_status_pemeriksaan = RefStatusPemeriksaan::all();

        return Inertia::render('modules/pemeriksaan-peserta/Create', [
            'pemeriksaan'            => $pemeriksaan,
            'ref_status_pemeriksaan' => $ref_status_pemeriksaan,
        ]);

        return inertia('modules/pemeriksaan-peserta/Create', [
            'pemeriksaan'            => $pemeriksaan,
            'atlets'                 => $atlets,
            'pelatihs'               => $pelatihs,
            'tenagaPendukung'        => $tenagaPendukung,
            'ref_status_pemeriksaan' => $ref_status_pemeriksaan,
            'jenis_peserta'          => $request->query('jenis_peserta', 'atlet'),
        ]);
    }

    public function store(PemeriksaanPesertaRequest $request, Pemeriksaan $pemeriksaan)
    {
        $data = $request->validated();

        $this->repository->createMultiple($pemeriksaan, $data);

        return redirect()->route('pemeriksaan.peserta.index', $pemeriksaan->id)->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function show(Pemeriksaan $pemeriksaan, $peserta_id)
    {
        $item = $this->repository->getDetailWithRelations($peserta_id);
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);

        return inertia('modules/pemeriksaan-peserta/Show', [
            'pemeriksaan' => $pemeriksaan,
            'item'        => $item,
        ]);
    }

    public function edit(Pemeriksaan $pemeriksaan, $peserta_id)
    {
        $item = $this->repository->getById($peserta_id);
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        $atlets                 = Atlet::where('is_active', true)->get(['id', 'nama']);
        $pelatihs               = Pelatih::where('is_active', true)->get(['id', 'nama']);
        $tenagaPendukung        = TenagaPendukung::where('is_active', true)->get(['id', 'nama']);
        $ref_status_pemeriksaan = RefStatusPemeriksaan::all();

        return inertia('modules/pemeriksaan-peserta/Edit', [
            'pemeriksaan'            => $pemeriksaan,
            'item'                   => $item,
            'atlets'                 => $atlets,
            'pelatihs'               => $pelatihs,
            'tenagaPendukung'        => $tenagaPendukung,
            'ref_status_pemeriksaan' => $ref_status_pemeriksaan,
        ]);
    }

    public function update(Request $request, $pemeriksaan, $peserta)
    {
        $item                            = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaan)->where('id', $peserta)->firstOrFail();
        $item->ref_status_pemeriksaan_id = $request->input('ref_status_pemeriksaan_id');
        $item->catatan_umum              = $request->input('catatan_umum');
        $item->save();
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Peserta berhasil diperbarui']);
        }

        return redirect()->route('pemeriksaan.peserta.index', $pemeriksaan)->with('success', 'Peserta berhasil diperbarui!');
    }

    public function destroy($pemeriksaan, $peserta)
    {
        $item = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaan)->where('id', $peserta)->firstOrFail();
        $item->delete();
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Peserta berhasil dihapus']);
        }

        return redirect()->route('pemeriksaan.peserta.index', $pemeriksaan)->with('success', 'Peserta berhasil dihapus!');
    }

    public function apiIndex(Pemeriksaan $pemeriksaan, $jenis_peserta = null)
    {
        request()->merge([
            'pemeriksaan_id' => $pemeriksaan->id,
            'jenis_peserta'  => $jenis_peserta,
        ]);
        $data = $this->repository->customIndex([]);

        return response()->json($data);
    }
}
