<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaborKategoriAtletRequest;
use App\Models\CaborKategori;
use App\Models\CaborKategoriAtlet;
use App\Models\PemeriksaanPeserta;
use App\Repositories\CaborKategoriAtletRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class CaborKategoriAtletController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, CaborKategoriAtletRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->route                          = 'cabor-kategori-atlet';
        $this->commonData['kode_first_menu']  = 'CABOR';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index', 'atletByKategori', 'createMultiple', 'getByAtletId']),
            new Middleware("can:$permission Add", only: ['create', 'store', 'storeMultiple']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function index()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'titlePage' => 'Cabor Kategori Atlet',
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        // Tambahkan customIndex() call untuk permission checking yang konsisten
        $data = $this->repository->customIndex($data);

        return inertia('modules/cabor-kategori-atlet/Index', $data);
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'titlePage' => 'Tambah Cabor Kategori Atlet',
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/cabor-kategori-atlet/Create', $data);
    }

    public function store(CaborKategoriAtletRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->batchInsert($data);

        return redirect()->route('cabor-kategori-atlet.index')->with('success', 'Atlet berhasil ditambahkan ke kategori!');
    }

    public function show($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'titlePage' => 'Detail Cabor Kategori Atlet',
            'item'      => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);

        return inertia('modules/cabor-kategori-atlet/Show', $data);
    }

    public function edit($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'titlePage' => 'Edit Cabor Kategori Atlet',
            'item'      => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/cabor-kategori-atlet/Edit', $data);
    }

    public function update(CaborKategoriAtletRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        $item       = $this->repository->getById($id);
        $kategoriId = $item->cabor_kategori_id ?? null;
        if ($kategoriId) {
            return redirect()->route('cabor-kategori-atlet.atlet-by-kategori', $kategoriId)
                ->with('success', 'Posisi atlet berhasil diperbarui!');
        }
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Data berhasil dihapus']);
        }

        return redirect()->route('cabor-kategori-atlet.index')->with('success', 'Cabor Kategori Atlet berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Pilih data yang akan dihapus!');
        }
        $this->repository->delete_selected($ids);

        return redirect()->route('cabor-kategori-atlet.index')->with('success', 'Data berhasil dihapus!');
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['records'],
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

    // Method untuk halaman daftar atlet per kategori
    public function atletByKategori($caborKategoriId)
    {
        $this->repository->customProperty(__FUNCTION__, ['cabor_kategori_id' => $caborKategoriId]);
        $caborKategori = app(CaborKategori::class)->with('cabor')->find($caborKategoriId);

        if (! $caborKategori) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan!');
        }

        $data = $this->commonData + [
            'titlePage'     => 'Daftar Atlet - '.$caborKategori->nama,
            'caborKategori' => $caborKategori,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/cabor-kategori-atlet/AtletByKategori', $data);
    }

    // Method untuk halaman tambah multiple atlet
    public function createMultiple($caborKategoriId)
    {
        $this->repository->customProperty(__FUNCTION__, ['cabor_kategori_id' => $caborKategoriId]);
        $caborKategori = app(CaborKategori::class)->with('cabor')->find($caborKategoriId);

        if (! $caborKategori) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan!');
        }

        $data = $this->commonData + [
            'titlePage'     => 'Tambah Multiple Atlet - '.$caborKategori->nama,
            'caborKategori' => $caborKategori,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/cabor-kategori-atlet/CreateMultiple', $data);
    }

    // Method untuk store multiple atlet
    public function storeMultiple(Request $request, $caborKategoriId)
    {
        try {
            Log::info('storeMultiple called', [
                'caborKategoriId' => $caborKategoriId,
                'request_data'    => $request->all(),
            ]);

            $caborKategori = app(CaborKategori::class)->find($caborKategoriId);
            if (! $caborKategori) {
                return redirect()->back()->with('error', 'Kategori tidak ditemukan!');
            }

            // Merge ke request sebelum validasi
            $request->merge([
                'cabor_kategori_id' => $caborKategoriId,
                'cabor_id'          => $caborKategori->cabor_id,
                'posisi_atlet_id'   => $request->input('posisi_atlet_id'),
            ]);

            $validatedData = $this->repository->validateRequest($request);

            // Pastikan posisi_atlet_id tetap diteruskan ke repository
            if ($request->has('posisi_atlet_id')) {
                $validatedData['posisi_atlet_id'] = $request->input('posisi_atlet_id');
            }

            Log::info('Validated data', $validatedData);

            $this->repository->batchInsert($validatedData);

            Log::info('Batch insert successful');

            return redirect()->route('cabor-kategori-atlet.atlet-by-kategori', $caborKategoriId)
                ->with('success', 'Atlet berhasil ditambahkan ke kategori!');
        } catch (\Exception $e) {
            Log::error('Error in storeMultiple', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Gagal menambahkan atlet: '.$e->getMessage());
        }
    }

    // Endpoint khusus untuk pemeriksaan peserta: hanya tampilkan atlet yang belum jadi peserta di pemeriksaan ini
    public function apiAvailableForPemeriksaan(Request $request)
    {
        $caborKategoriId = $request->input('cabor_kategori_id');
        $pemeriksaanId   = $request->input('pemeriksaan_id');

        // Ambil semua atlet_id yang sudah jadi peserta di pemeriksaan ini
        $usedAtletIds = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\Atlet')
            ->pluck('peserta_id')
            ->toArray();

        // Query atlet yang belum jadi peserta
        $query = CaborKategoriAtlet::with(['atlet'])
            ->where('cabor_kategori_id', $caborKategoriId)
            ->whereNotIn('atlet_id', $usedAtletIds);

        // (opsional) tambahkan search, pagination, dsb sesuai kebutuhan frontend
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('atlet', function ($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                    ->orWhere('nik', 'like', "%$search%")
                    ->orWhere('no_hp', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }
        $perPage = (int) $request->input('per_page', 10);
        $page    = (int) $request->input('page', 1);
        $result  = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform agar frontend tetap dapat id-key dan name-key
        $data        = $result->items();
        $transformed = collect($data)->map(function ($item) {
            return [
                'id'                => $item->id,
                'atlet_id'          => $item->atlet_id,
                'atlet_nama'        => $item->atlet->nama              ?? '-',
                'nik'               => $item->atlet->nik               ?? '-',
                'jenis_kelamin'     => $item->atlet->jenis_kelamin     ?? '-',
                'tempat_lahir'      => $item->atlet->tempat_lahir      ?? '-',
                'tanggal_lahir'     => $item->atlet->tanggal_lahir     ?? '-',
                'tanggal_bergabung' => $item->atlet->tanggal_bergabung ?? '-',
                'no_hp'             => $item->atlet->no_hp             ?? '-',
                'foto'              => $item->atlet->foto              ?? null,
                'posisi_atlet_nama' => $item->posisi_atlet             ?? '-', // Langsung dari attribute, bukan relasi
            ];
        });

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'total'        => $result->total(),
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'search'       => $request->input('search', ''),
            ],
        ]);
    }
}
//
