<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaborKategoriPelatihRequest;
use App\Models\CaborKategori;
use App\Models\CaborKategoriPelatih;
use App\Models\PemeriksaanPeserta;
use App\Repositories\CaborKategoriPelatihRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class CaborKategoriPelatihController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, CaborKategoriPelatihRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->route                          = 'cabor-kategori-pelatih';
        $this->commonData['kode_first_menu']  = 'CABOR';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index', 'pelatihByKategori', 'createMultiple']),
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
            'titlePage' => 'Cabor Kategori Pelatih',
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/cabor-kategori-pelatih/Index', $data);
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'titlePage' => 'Tambah Cabor Kategori Pelatih',
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/cabor-kategori-pelatih/Create', $data);
    }

    public function store(CaborKategoriPelatihRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->batchInsert($data);

        return redirect()->route('cabor-kategori-pelatih.index')->with('success', 'Pelatih berhasil ditambahkan ke kategori!');
    }

    public function show($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'titlePage' => 'Detail Cabor Kategori Pelatih',
            'item'      => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);

        return inertia('modules/cabor-kategori-pelatih/Show', $data);
    }

    public function edit($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'titlePage' => 'Edit Cabor Kategori Pelatih',
            'item'      => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/cabor-kategori-pelatih/Edit', $data);
    }

    public function update(CaborKategoriPelatihRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        $item       = $this->repository->getById($id);
        $kategoriId = $item->cabor_kategori_id ?? null;
        if ($kategoriId) {
            return redirect()->route('cabor-kategori-pelatih.pelatih-by-kategori', $kategoriId)
                ->with('success', 'Jenis pelatih berhasil diperbarui!');
        }
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Data berhasil dihapus']);
        }

        return redirect()->route('cabor-kategori-pelatih.index')->with('success', 'Cabor Kategori Pelatih berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Pilih data yang akan dihapus!');
        }
        $this->repository->delete_selected($ids);

        return redirect()->route('cabor-kategori-pelatih.index')->with('success', 'Data berhasil dihapus!');
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

    // Method untuk halaman daftar pelatih per kategori
    public function pelatihByKategori($caborKategoriId)
    {
        $this->repository->customProperty(__FUNCTION__, ['cabor_kategori_id' => $caborKategoriId]);
        $caborKategori = app(CaborKategori::class)->with('cabor')->find($caborKategoriId);

        if (! $caborKategori) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan!');
        }

        $data = $this->commonData + [
            'titlePage'     => 'Daftar Pelatih - '.$caborKategori->nama,
            'caborKategori' => $caborKategori,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/cabor-kategori-pelatih/PelatihByKategori', $data);
    }

    // Method untuk halaman tambah multiple pelatih
    public function createMultiple($caborKategoriId)
    {
        $this->repository->customProperty(__FUNCTION__, ['cabor_kategori_id' => $caborKategoriId]);
        $caborKategori = app(CaborKategori::class)->with('cabor')->find($caborKategoriId);

        if (! $caborKategori) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan!');
        }

        $data = $this->commonData + [
            'titlePage'     => 'Tambah Multiple Pelatih - '.$caborKategori->nama,
            'caborKategori' => $caborKategori,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/cabor-kategori-pelatih/CreateMultiple', $data);
    }

    // Method untuk store multiple pelatih
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
            ]);

            $validatedData = $this->repository->validateRequest($request);

            // Pastikan is_active selalu integer (1/0)
            $validatedData['is_active'] = (int) $request->input('is_active', 1);
            Log::info('Validated data', $validatedData);

            $this->repository->batchInsert($validatedData);

            Log::info('Batch insert successful');

            return redirect()->route('cabor-kategori-pelatih.pelatih-by-kategori', $caborKategoriId)
                ->with('success', 'Pelatih berhasil ditambahkan ke kategori!');
        } catch (\Exception $e) {
            Log::error('Error in storeMultiple', [
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Gagal menambahkan pelatih: '.$e->getMessage());
        }
    }

    // Endpoint khusus untuk pemeriksaan peserta: hanya tampilkan pelatih yang belum jadi peserta di pemeriksaan ini
    public function apiAvailableForPemeriksaan(Request $request)
    {
        $caborKategoriId = $request->input('cabor_kategori_id');
        $pemeriksaanId   = $request->input('pemeriksaan_id');

        // Ambil semua pelatih_id yang sudah jadi peserta di pemeriksaan ini
        $usedPelatihIds = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\Pelatih')
            ->pluck('peserta_id')
            ->toArray();

        // Query pelatih yang belum jadi peserta
        $query = CaborKategoriPelatih::with(['pelatih'])
            ->where('cabor_kategori_id', $caborKategoriId)
            ->whereNotIn('pelatih_id', $usedPelatihIds);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('pelatih', function ($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                    ->orWhere('nik', 'like', "%$search%")
                    ->orWhere('no_hp', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }
        $perPage = (int) $request->input('per_page', 10);
        $page    = (int) $request->input('page', 1);
        $result  = $query->paginate($perPage, ['*'], 'page', $page);

        $data        = $result->items();
        $transformed = collect($data)->map(function ($item) {
            return [
                'id'                 => $item->id,
                'pelatih_id'         => $item->pelatih_id,
                'pelatih_nama'       => $item->pelatih->nama              ?? '-',
                'nik'                => $item->pelatih->nik               ?? '-',
                'jenis_kelamin'      => $item->pelatih->jenis_kelamin     ?? '-',
                'tempat_lahir'       => $item->pelatih->tempat_lahir      ?? '-',
                'tanggal_lahir'      => $item->pelatih->tanggal_lahir     ?? '-',
                'tanggal_bergabung'  => $item->pelatih->tanggal_bergabung ?? '-',
                'no_hp'              => $item->pelatih->no_hp             ?? '-',
                'foto'               => $item->pelatih->foto              ?? null,
                'jenis_pelatih_nama' => $item->jenis_pelatih              ?? '-', // Langsung dari attribute, bukan relasi
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
