<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemeriksaanRequest;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Repositories\PemeriksaanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * API untuk mendapatkan semua peserta (Atlet, Pelatih, Tenaga Pendukung) berdasarkan cabor kategori
     * Digunakan untuk auto-load peserta saat create pemeriksaan
     */
    public function apiPesertaByCaborKategori($cabor_kategori_id)
    {
        try {
            // Get Atlet aktif di kategori ini
            $atlet = CaborKategoriAtlet::with(['atlet'])
                ->where('cabor_kategori_atlet.cabor_kategori_id', $cabor_kategori_id)
                ->where('cabor_kategori_atlet.is_active', 1)
                ->whereNull('cabor_kategori_atlet.deleted_at')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'     => $item->atlet->id ?? null,
                        'nama'   => $item->atlet->nama ?? '-',
                        'posisi' => $item->posisi_atlet ?? '-',
                    ];
                })
                ->filter(fn ($item) => $item['id'] !== null)
                ->values();

            // Get Pelatih aktif di kategori ini
            $pelatih = CaborKategoriPelatih::with(['pelatih'])
                ->where('cabor_kategori_pelatih.cabor_kategori_id', $cabor_kategori_id)
                ->where('cabor_kategori_pelatih.is_active', 1)
                ->whereNull('cabor_kategori_pelatih.deleted_at')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'    => $item->pelatih->id ?? null,
                        'nama'  => $item->pelatih->nama ?? '-',
                        'jenis' => $item->jenis_pelatih ?? '-',
                    ];
                })
                ->filter(fn ($item) => $item['id'] !== null)
                ->values();

            // Get Tenaga Pendukung aktif di kategori ini
            $tenagaPendukung = CaborKategoriTenagaPendukung::with(['tenagaPendukung'])
                ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $cabor_kategori_id)
                ->where('cabor_kategori_tenaga_pendukung.is_active', 1)
                ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'    => $item->tenagaPendukung->id ?? null,
                        'nama'  => $item->tenagaPendukung->nama ?? '-',
                        'jenis' => $item->jenis_tenaga_pendukung ?? '-',
                    ];
                })
                ->filter(fn ($item) => $item['id'] !== null)
                ->values();

            return response()->json([
                'atlet'           => $atlet,
                'pelatih'         => $pelatih,
                'tenagaPendukung' => $tenagaPendukung,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in apiPesertaByCaborKategori: ' . $e->getMessage(), [
                'cabor_kategori_id' => $cabor_kategori_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data peserta',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
