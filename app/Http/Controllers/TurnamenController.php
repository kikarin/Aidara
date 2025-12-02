<?php

namespace App\Http\Controllers;

use App\Http\Requests\TurnamenRequest;
use App\Repositories\TurnamenRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;

class TurnamenController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, TurnamenRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = TurnamenRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'turnamen';
        $this->commonData['kode_first_menu']  = 'TURNAMEN';
        $this->commonData['kode_second_menu'] = 'TURNAMEN';
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
            new Middleware('auth', only: ['apiPesertaTurnamen', 'storePeserta', 'destroyPeserta', 'destroySelectedPeserta', 'pesertaIndex']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['turnamens'],
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

    public function index()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);

        return inertia('modules/turnamen/Index', $data);
    }

    public function store(TurnamenRequest $request)
    {
        // Handle foto upload SEBELUM validasi untuk memastikan file tidak hilang
        $fotoPath = null;
        
        // Debug: cek semua file yang diterima
        $allFiles = $request->allFiles();
        Log::info('Store - All files received:', ['files' => array_keys($allFiles)]);
        Log::info('Store - hasFile foto:', ['hasFile' => $request->hasFile('foto')]);
        
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $fotoPath = $foto->store('turnamen', 'public');
            Log::info('Store - Foto stored:', ['path' => $fotoPath]);
        } elseif (isset($allFiles['foto'])) {
            // Fallback: coba ambil dari allFiles jika hasFile tidak bekerja
            $foto = $allFiles['foto'];
            $fotoPath = $foto->store('turnamen', 'public');
            Log::info('Store - Foto stored from allFiles:', ['path' => $fotoPath]);
        }
        
        $data = $this->repository->validateRequest($request);
        
        // Set foto path ke data jika ada
        if ($fotoPath) {
            $data['foto'] = $fotoPath;
        }
        
        $turnamen = $this->repository->create($data);

        // Sync peserta jika ada
        if ($request->has('peserta_data')) {
            $this->repository->syncPeserta($turnamen->id, $request->peserta_data);
        }

        return redirect()->route('turnamen.index')->with('success', 'Data turnamen berhasil ditambahkan!');
    }

    public function update(TurnamenRequest $request, $id)
    {
        // Handle foto upload SEBELUM validasi untuk memastikan file tidak hilang
        $fotoPath = null;
        
        // Debug: cek semua file yang diterima
        $allFiles = $request->allFiles();
        Log::info('Update - All files received:', ['files' => array_keys($allFiles)]);
        Log::info('Update - hasFile foto:', ['hasFile' => $request->hasFile('foto')]);
        
        if ($request->hasFile('foto')) {
            $turnamen = $this->repository->find($id);
            // Hapus foto lama jika ada
            if ($turnamen && $turnamen->foto) {
                Storage::disk('public')->delete($turnamen->foto);
            }
            
            $foto = $request->file('foto');
            $fotoPath = $foto->store('turnamen', 'public');
            Log::info('Update - Foto stored:', ['path' => $fotoPath]);
        } elseif (isset($allFiles['foto'])) {
            // Fallback: coba ambil dari allFiles jika hasFile tidak bekerja
            $turnamen = $this->repository->find($id);
            // Hapus foto lama jika ada
            if ($turnamen && $turnamen->foto) {
                Storage::disk('public')->delete($turnamen->foto);
            }
            
            $foto = $allFiles['foto'];
            $fotoPath = $foto->store('turnamen', 'public');
            Log::info('Update - Foto stored from allFiles:', ['path' => $fotoPath]);
        }
        
        $data = $this->repository->validateRequest($request);
        
        // Set foto path ke data jika ada
        if ($fotoPath) {
            $data['foto'] = $fotoPath;
        }
        
        $this->repository->update($id, $data);

        // Sync peserta jika ada
        if ($request->has('peserta_data')) {
            $this->repository->syncPeserta($id, $request->peserta_data);
        }

        return redirect()->route('turnamen.index')->with('success', 'Data turnamen berhasil diperbarui!');
    }

    public function show($id)
    {
        $item = $this->repository->getDetailWithUserTrack($id);
        if (!$item) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }
        $itemArray = $item->toArray();

        return Inertia::render('modules/turnamen/Show', [
            'item' => $itemArray,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('turnamen.index')->with('success', 'Data turnamen berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:turnamen,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data turnamen berhasil dihapus!']);
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);
        if (! is_array($data)) {
            return $data;
        }

        return inertia('modules/turnamen/Create', $data);
    }

    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getDetailWithUserTrack($id);
        if (!$item) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);
        if (! is_array($data)) {
            return $data;
        }

        return inertia('modules/turnamen/Edit', $data);
    }

    public function apiPesertaByCaborKategori(Request $request)
    {
        try {
            $caborKategoriId = $request->get('cabor_kategori_id');
            $jenisPeserta    = $request->get('jenis_peserta', 'atlet');
            $turnamenId      = $request->get('turnamen_id'); // Optional: untuk exclude peserta yang sudah ada di turnamen

            if (!$caborKategoriId) {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            $perPage = (int) $request->get('per_page', 10);
            $page    = (int) $request->get('page', 1);
            $search  = $request->get('search', '');

            // Get peserta IDs yang sudah ada di turnamen (jika turnamen_id diberikan)
            $excludeIds = [];
            if ($turnamenId) {
                $pesertaType = match($jenisPeserta) {
                    'atlet' => 'App\\Models\\Atlet',
                    'pelatih' => 'App\\Models\\Pelatih',
                    'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
                    default => null,
                };
                
                if ($pesertaType) {
                    $excludeIds = DB::table('turnamen_peserta')
                        ->where('turnamen_id', $turnamenId)
                        ->where('peserta_type', $pesertaType)
                        ->pluck('peserta_id')
                        ->toArray();
                }
            }

            // Use simple query with whereIn to avoid join issues
            if ($jenisPeserta === 'atlet') {
                $atletIds = DB::table('cabor_kategori_atlet')
                    ->where('cabor_kategori_id', $caborKategoriId)
                    ->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->pluck('atlet_id');

                if ($atletIds->isEmpty()) {
                    return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => $page, 'per_page' => $perPage, 'search' => $search]]);
                }

                $query = Atlet::query()
                    ->whereIn('id', $atletIds)
                    ->whereNull('deleted_at')
                    ->select('id', 'nama', 'foto', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'tanggal_bergabung', 'no_hp');
                
                // Exclude peserta yang sudah ada di turnamen
                if (!empty($excludeIds)) {
                    $query->whereNotIn('id', $excludeIds);
                }
            } elseif ($jenisPeserta === 'pelatih') {
                $pelatihIds = DB::table('cabor_kategori_pelatih')
                    ->where('cabor_kategori_id', $caborKategoriId)
                    ->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->pluck('pelatih_id');

                if ($pelatihIds->isEmpty()) {
                    return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => $page, 'per_page' => $perPage, 'search' => $search]]);
                }

                $query = Pelatih::query()
                    ->whereIn('id', $pelatihIds)
                    ->whereNull('deleted_at')
                    ->select('id', 'nama', 'foto', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'tanggal_bergabung', 'no_hp');
                
                // Exclude peserta yang sudah ada di turnamen
                if (!empty($excludeIds)) {
                    $query->whereNotIn('id', $excludeIds);
                }
            } elseif ($jenisPeserta === 'tenaga-pendukung') {
                $tenagaIds = DB::table('cabor_kategori_tenaga_pendukung')
                    ->where('cabor_kategori_id', $caborKategoriId)
                    ->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->pluck('tenaga_pendukung_id');

                if ($tenagaIds->isEmpty()) {
                    return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => $page, 'per_page' => $perPage, 'search' => $search]]);
                }

                $query = TenagaPendukung::query()
                    ->whereIn('id', $tenagaIds)
                    ->whereNull('deleted_at')
                    ->select('id', 'nama', 'foto', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'tanggal_bergabung', 'no_hp');
                
                // Exclude peserta yang sudah ada di turnamen
                if (!empty($excludeIds)) {
                    $query->whereNotIn('id', $excludeIds);
                }
            } else {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            if ($search) {
                $query->where('nama', 'like', "%$search%");
            }

            // Order by nama untuk konsistensi
            $query->orderBy('nama', 'asc');

            $result = $query->paginate($perPage, ['*'], 'page', $page)->appends($request->all());

            // Add posisi/jenis information to each item
            $items = collect($result->items())->map(function ($item) use ($jenisPeserta, $caborKategoriId) {
                $itemArray = $item->toArray();
                
                if ($jenisPeserta === 'atlet') {
                    $caborKategoriAtlet = DB::table('cabor_kategori_atlet')
                        ->where('atlet_id', $item->id)
                        ->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $itemArray['posisi_atlet_nama'] = $caborKategoriAtlet && isset($caborKategoriAtlet->posisi_atlet) ? $caborKategoriAtlet->posisi_atlet : '-';
                    $itemArray['kategori_is_active'] = $caborKategoriAtlet ? ($caborKategoriAtlet->is_active ?? 1) : 1;
                } elseif ($jenisPeserta === 'pelatih') {
                    $caborKategoriPelatih = DB::table('cabor_kategori_pelatih')
                        ->where('pelatih_id', $item->id)
                        ->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $itemArray['jenis_pelatih_nama'] = $caborKategoriPelatih && isset($caborKategoriPelatih->jenis_pelatih) ? $caborKategoriPelatih->jenis_pelatih : '-';
                    $itemArray['kategori_is_active'] = $caborKategoriPelatih ? ($caborKategoriPelatih->is_active ?? 1) : 1;
                } elseif ($jenisPeserta === 'tenaga-pendukung') {
                    $caborKategoriTenaga = DB::table('cabor_kategori_tenaga_pendukung')
                        ->where('tenaga_pendukung_id', $item->id)
                        ->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $itemArray['jenis_tenaga_pendukung_nama'] = $caborKategoriTenaga && isset($caborKategoriTenaga->jenis_tenaga_pendukung) ? $caborKategoriTenaga->jenis_tenaga_pendukung : '-';
                    $itemArray['kategori_is_active'] = $caborKategoriTenaga ? ($caborKategoriTenaga->is_active ?? 1) : 1;
                }
                
                return $itemArray;
            });

            return response()->json([
                'data' => $items->toArray(),
                'meta' => [
                    'total'        => $result->total(),
                    'current_page' => $result->currentPage(),
                    'per_page'     => $result->perPage(),
                    'search'       => $search,
                    'sort'         => $request->input('sort', ''),
                    'order'        => $request->input('order', 'asc'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiPesertaByCaborKategori: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            $perPage = (int) $request->get('per_page', 10);
            $search  = $request->get('search', '');

            return response()->json([
                'data' => [],
                'meta' => [
                    'total'        => 0,
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'search'       => $search,
                    'error'        => 'Terjadi kesalahan saat mengambil data peserta',
                ],
            ], 500);
        }
    }

    // Method untuk menampilkan halaman peserta turnamen
    public function pesertaIndex($turnamenId, Request $request)
    {
        $turnamen = $this->repository->getDetailWithUserTrack($turnamenId);
        if (!$turnamen) {
            return redirect()->back()->with('error', 'Turnamen tidak ditemukan');
        }

        $jenisPeserta = $request->get('jenis_peserta', 'atlet');

        $data = $this->commonData + [
            'turnamen'      => $turnamen,
            'turnamen_id'   => $turnamenId,
            'jenis_peserta' => $jenisPeserta,
        ];

        return inertia('modules/turnamen/peserta/Index', $data);
    }

    // API untuk mendapatkan peserta turnamen
    public function apiPesertaTurnamen($turnamenId, Request $request)
    {
        $turnamen = $this->repository->getDetailWithUserTrack($turnamenId);
        if (!$turnamen) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $jenisPeserta = $request->get('jenis_peserta', 'atlet');
        $perPage      = (int) $request->get('per_page', 10);
        $page         = (int) $request->get('page', 1);
        $search       = $request->get('search', '');
        $caborKategoriId = $turnamen->cabor_kategori_id;

        // Use raw SQL queries to get correct data from pivot tables
        if ($jenisPeserta === 'atlet') {
            $query = Atlet::query()
                ->join('turnamen_peserta', function ($join) use ($turnamenId) {
                    $join->on('atlets.id', '=', 'turnamen_peserta.peserta_id')
                        ->where('turnamen_peserta.turnamen_id', $turnamenId)
                        ->where('turnamen_peserta.peserta_type', 'App\\Models\\Atlet');
                })
                ->leftJoin('cabor_kategori_atlet', function ($join) use ($caborKategoriId) {
                    $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                        ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_atlet.deleted_at');
                })
                ->select(
                    'atlets.id',
                    'atlets.nama',
                    'atlets.foto',
                    'atlets.jenis_kelamin',
                    'atlets.tempat_lahir',
                    'atlets.tanggal_lahir',
                    'atlets.tanggal_bergabung',
                    'atlets.no_hp',
                    'cabor_kategori_atlet.posisi_atlet as posisi_atlet_nama'
                );
        } elseif ($jenisPeserta === 'pelatih') {
            $query = Pelatih::query()
                ->join('turnamen_peserta', function ($join) use ($turnamenId) {
                    $join->on('pelatihs.id', '=', 'turnamen_peserta.peserta_id')
                        ->where('turnamen_peserta.turnamen_id', $turnamenId)
                        ->where('turnamen_peserta.peserta_type', 'App\\Models\\Pelatih');
                })
                ->leftJoin('cabor_kategori_pelatih', function ($join) use ($caborKategoriId) {
                    $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                        ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_pelatih.deleted_at');
                })
                ->select(
                    'pelatihs.id',
                    'pelatihs.nama',
                    'pelatihs.foto',
                    'pelatihs.jenis_kelamin',
                    'pelatihs.tempat_lahir',
                    'pelatihs.tanggal_lahir',
                    'pelatihs.tanggal_bergabung',
                    'pelatihs.no_hp',
                    'cabor_kategori_pelatih.jenis_pelatih as jenis_pelatih_nama'
                );
        } elseif ($jenisPeserta === 'tenaga-pendukung') {
            $query = TenagaPendukung::query()
                ->join('turnamen_peserta', function ($join) use ($turnamenId) {
                    $join->on('tenaga_pendukungs.id', '=', 'turnamen_peserta.peserta_id')
                        ->where('turnamen_peserta.turnamen_id', $turnamenId)
                        ->where('turnamen_peserta.peserta_type', 'App\\Models\\TenagaPendukung');
                })
                ->leftJoin('cabor_kategori_tenaga_pendukung', function ($join) use ($caborKategoriId) {
                    $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                        ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                })
                ->select(
                    'tenaga_pendukungs.id',
                    'tenaga_pendukungs.nama',
                    'tenaga_pendukungs.foto',
                    'tenaga_pendukungs.jenis_kelamin',
                    'tenaga_pendukungs.tempat_lahir',
                    'tenaga_pendukungs.tanggal_lahir',
                    'tenaga_pendukungs.tanggal_bergabung',
                    'tenaga_pendukungs.no_hp',
                    'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung as jenis_tenaga_pendukung_nama'
                );
        } else {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        if ($search) {
            $tableName = $jenisPeserta === 'atlet' ? 'atlets' : ($jenisPeserta === 'pelatih' ? 'pelatihs' : 'tenaga_pendukungs');
            $query->where("$tableName.nama", 'like', "%$search%");
        }

        // Jika per_page = -1, kembalikan semua data tanpa pagination
        if ($perPage === -1) {
            $allData = $query->get();
            return response()->json([
                'data' => $allData->toArray(),
                'meta' => [
                    'total'        => $allData->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => $search,
                    'sort'         => $request->input('sort', ''),
                    'order'        => $request->input('order', 'asc'),
                ],
            ]);
        }

        $result = $query->paginate($perPage)->appends($request->all());

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'total'        => $result->total(),
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'search'       => $search,
                'sort'         => $request->input('sort', ''),
                'order'        => $request->input('order', 'asc'),
            ],
        ]);
    }

    // Method untuk menambahkan peserta ke turnamen
    public function storePeserta($turnamenId, Request $request)
    {
        $request->validate([
            'jenis_peserta' => 'required|string|in:atlet,pelatih,tenaga-pendukung',
            'peserta_ids'   => 'required|array',
            'peserta_ids.*' => 'required|numeric',
        ]);

        $turnamen = $this->repository->getDetailWithUserTrack($turnamenId);
        if (!$turnamen) {
            return response()->json(['message' => 'Turnamen tidak ditemukan'], 404);
        }

        $jenisPeserta = $request->jenis_peserta;
        $pesertaIds   = $request->peserta_ids;

        // Get existing peserta IDs to avoid duplicates
        $existingIds = [];
        switch ($jenisPeserta) {
            case 'atlet':
                $existingIds = $turnamen->peserta()->pluck('atlets.id')->toArray();
                $newIds      = array_diff($pesertaIds, $existingIds);
                if (!empty($newIds)) {
                    $turnamen->peserta()->attach($newIds);
                }
                break;
            case 'pelatih':
                $existingIds = $turnamen->pelatihPeserta()->pluck('pelatihs.id')->toArray();
                $newIds      = array_diff($pesertaIds, $existingIds);
                if (!empty($newIds)) {
                    $turnamen->pelatihPeserta()->attach($newIds);
                }
                break;
            case 'tenaga-pendukung':
                $existingIds = $turnamen->tenagaPendukungPeserta()->pluck('tenaga_pendukungs.id')->toArray();
                $newIds      = array_diff($pesertaIds, $existingIds);
                if (!empty($newIds)) {
                    $turnamen->tenagaPendukungPeserta()->attach($newIds);
                }
                break;
            default:
                return response()->json(['message' => 'Jenis peserta tidak valid'], 400);
        }

        $addedCount = count($newIds ?? []);
        $skippedCount = count($pesertaIds) - $addedCount;

        $message = "{$addedCount} peserta berhasil ditambahkan";
        if ($skippedCount > 0) {
            $message .= ", {$skippedCount} peserta sudah ada sebelumnya";
        }

        return response()->json(['message' => $message, 'added_count' => $addedCount, 'skipped_count' => $skippedCount]);
    }

    // Method untuk menghapus peserta dari turnamen
    public function destroyPeserta($turnamenId, $jenisPeserta, $pesertaId)
    {
        $turnamen = $this->repository->getDetailWithUserTrack($turnamenId);
        if (!$turnamen) {
            return response()->json(['message' => 'Turnamen tidak ditemukan'], 404);
        }

        switch ($jenisPeserta) {
            case 'atlet':
                $turnamen->peserta()->detach($pesertaId);
                break;
            case 'pelatih':
                $turnamen->pelatihPeserta()->detach($pesertaId);
                break;
            case 'tenaga-pendukung':
                $turnamen->tenagaPendukungPeserta()->detach($pesertaId);
                break;
            default:
                return response()->json(['message' => 'Jenis peserta tidak valid'], 400);
        }

        return response()->json(['message' => 'Peserta berhasil dihapus dari turnamen']);
    }

    // Method untuk menghapus multiple peserta dari turnamen
    public function destroySelectedPeserta($turnamenId, $jenisPeserta, Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric',
        ]);

        $turnamen = $this->repository->getDetailWithUserTrack($turnamenId);
        if (!$turnamen) {
            return response()->json(['message' => 'Turnamen tidak ditemukan'], 404);
        }

        switch ($jenisPeserta) {
            case 'atlet':
                $turnamen->peserta()->detach($request->ids);
                break;
            case 'pelatih':
                $turnamen->pelatihPeserta()->detach($request->ids);
                break;
            case 'tenaga-pendukung':
                $turnamen->tenagaPendukungPeserta()->detach($request->ids);
                break;
            default:
                return response()->json(['message' => 'Jenis peserta tidak valid'], 400);
        }

        return response()->json(['message' => 'Peserta berhasil dihapus dari turnamen']);
    }
}
