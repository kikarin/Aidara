<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemeriksaanKhususAspekItemTesRequest;
use App\Http\Requests\PemeriksaanKhususHasilTesRequest;
use App\Http\Requests\PemeriksaanKhususRequest;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Models\MstTemplatePemeriksaanKhususAspek;
use App\Models\PemeriksaanKhusus;
use App\Models\PemeriksaanKhususAspek;
use App\Models\PemeriksaanKhususItemTes;
use App\Models\PemeriksaanKhususPeserta;
use App\Repositories\PemeriksaanKhususRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class PemeriksaanKhususController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, PemeriksaanKhususRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = PemeriksaanKhususRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'pemeriksaan-khusus';
        $this->commonData['kode_first_menu']  = 'PEMERIKSAAN';
        $this->commonData['kode_second_menu'] = 'PEMERIKSAAN_KHUSUS';
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
            new Middleware("can:Pemeriksaan Khusus Setup", only: ['setup']),
            new Middleware("can:Pemeriksaan Khusus Input Hasil Tes", only: ['inputHasilTes']),
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

        return inertia('modules/pemeriksaan-khusus/Index', $data);
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + ['item' => null];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/pemeriksaan-khusus/Create', $data);
    }

    public function store(PemeriksaanKhususRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('pemeriksaan-khusus.index')->with('success', 'Pemeriksaan khusus berhasil ditambahkan!');
    }

    public function show($id)
    {
        $item = $this->repository->getById($id);
        
        // Load relasi yang diperlukan untuk tab Informasi
        $item->load([
            'cabor',
            'caborKategori',
            'aspek.itemTes',
            'pemeriksaanKhususPeserta.peserta',
        ]);
        
        $itemArray = $item->toArray();

        return Inertia::render('modules/pemeriksaan-khusus/Show', ['item' => $itemArray]);
    }

    public function setup($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + ['item' => $item];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/pemeriksaan-khusus/Setup', $data);
    }

    public function inputHasilTes($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        
        // Load relasi yang diperlukan (pastikan ter-load dengan benar)
        $item->loadMissing(['cabor', 'caborKategori']);
        
        // Convert to array dengan relasi
        $itemArray = $item->toArray();
        
        // Pastikan caborKategori ada di array
        if (!isset($itemArray['cabor_kategori']) && $item->caborKategori) {
            $itemArray['cabor_kategori'] = $item->caborKategori->toArray();
        }
        // Juga pastikan key 'caborKategori' ada (camelCase untuk frontend)
        if (!isset($itemArray['caborKategori']) && $item->caborKategori) {
            $itemArray['caborKategori'] = $item->caborKategori->toArray();
        }
        
        $data = $this->commonData + ['item' => $itemArray];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/pemeriksaan-khusus/InputHasilTes', $data);
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

        return inertia('modules/pemeriksaan-khusus/Edit', $data);
    }

    public function update(PemeriksaanKhususRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('pemeriksaan-khusus.index')->with('success', 'Pemeriksaan khusus berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('pemeriksaan-khusus.index')->with('success', 'Pemeriksaan khusus berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:pemeriksaan_khusus,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data pemeriksaan khusus berhasil dihapus!']);
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['pemeriksaan_khusus'],
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
     * Digunakan untuk auto-load peserta saat create pemeriksaan khusus
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
            Log::error('Error in apiPesertaByCaborKategori: ' . $e->getMessage(), [
                'cabor_kategori_id' => $cabor_kategori_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data peserta',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk cek apakah template sudah ada untuk cabor tertentu
     */
    public function apiCheckTemplate($cabor_id)
    {
        try {
            $template = MstTemplatePemeriksaanKhususAspek::where('cabor_id', $cabor_id)
                ->with(['itemTes' => function ($q) {
                    $q->orderBy('urutan');
                }])
                ->orderBy('urutan')
                ->get();

            $hasTemplate = $template->count() > 0;

            return response()->json([
                'has_template' => $hasTemplate,
                'template' => $hasTemplate ? $template->map(function ($aspek) {
                    return [
                        'id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'urutan' => $aspek->urutan,
                        'item_tes' => $aspek->itemTes->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'nama' => $item->nama,
                                'satuan' => $item->satuan,
                                'target_laki_laki' => $item->target_laki_laki,
                                'target_perempuan' => $item->target_perempuan,
                                'performa_arah' => $item->performa_arah,
                                'urutan' => $item->urutan,
                            ];
                        }),
                    ];
                }) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiCheckTemplate: ' . $e->getMessage(), [
                'cabor_id' => $cabor_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data template',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk mendapatkan aspek & item tes dari pemeriksaan khusus
     */
    public function apiGetAspekItemTes($pemeriksaan_khusus_id)
    {
        try {
            $pemeriksaan = PemeriksaanKhusus::with([
                'aspek' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                },
                'aspek.itemTes' => function ($q) {
                    $q->whereNull('deleted_at')->orderBy('urutan');
                },
            ])
                ->findOrFail($pemeriksaan_khusus_id);

            $aspek = $pemeriksaan->aspek
                ->filter(fn($a) => $a->deleted_at === null)
                ->unique('id')
                ->map(function ($aspek) {
                    return [
                        'id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'urutan' => $aspek->urutan,
                        'item_tes' => $aspek->itemTes
                            ->filter(fn($it) => $it->deleted_at === null)
                            ->unique('id')
                            ->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'nama' => $item->nama,
                                    'satuan' => $item->satuan,
                                    'target_laki_laki' => $item->target_laki_laki,
                                    'target_perempuan' => $item->target_perempuan,
                                    'performa_arah' => $item->performa_arah,
                                    'urutan' => $item->urutan,
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            return response()->json([
                'aspek' => $aspek,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiGetAspekItemTes: ' . $e->getMessage(), [
                'pemeriksaan_khusus_id' => $pemeriksaan_khusus_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data aspek & item tes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk clone template ke pemeriksaan khusus
     */
    public function apiCloneFromTemplate(Request $request)
    {
        try {
            $request->validate([
                'pemeriksaan_khusus_id' => 'required|exists:pemeriksaan_khusus,id',
                'cabor_id'              => 'required|exists:cabor,id',
            ]);

            $this->repository->cloneFromTemplate(
                $request->pemeriksaan_khusus_id,
                $request->cabor_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Template berhasil di-clone ke pemeriksaan khusus',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiCloneFromTemplate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan saat clone template',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk save aspek-item tes (manual atau dari template)
     */
    public function apiSaveAspekItemTes(PemeriksaanKhususAspekItemTesRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->repository->saveAspekItemTes(
                $validated['pemeriksaan_khusus_id'],
                $validated['aspek']
            );

            return response()->json([
                'success' => true,
                'message' => 'Aspek & item tes berhasil disimpan',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiSaveAspekItemTes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan saat menyimpan aspek & item tes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk save as template
     */
    public function apiSaveAsTemplate(Request $request)
    {
        try {
            $request->validate([
                'cabor_id' => 'required|exists:cabor,id',
                'aspek'    => 'required|array|min:1',
                'aspek.*.nama'         => 'required|string|max:200',
                'aspek.*.urutan'       => 'nullable|integer',
                'aspek.*.item_tes'     => 'required|array|min:1',
                'aspek.*.item_tes.*.nama'             => 'required|string|max:200',
                'aspek.*.item_tes.*.satuan'           => 'nullable|string|max:50',
                'aspek.*.item_tes.*.target_laki_laki' => 'nullable|string',
                'aspek.*.item_tes.*.target_perempuan' => 'nullable|string',
                'aspek.*.item_tes.*.performa_arah'    => 'required|in:max,min',
                'aspek.*.item_tes.*.urutan'           => 'nullable|integer',
            ]);

            $this->repository->saveAsTemplate(
                $request->cabor_id,
                $request->aspek
            );

            return response()->json([
                'success' => true,
                'message' => 'Template berhasil disimpan untuk cabor ini',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiSaveAsTemplate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan saat menyimpan template',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk get peserta pemeriksaan khusus
     */
    public function apiGetPeserta($id)
    {
        try {
            $pemeriksaanKhusus = $this->repository->getById($id);
            
            // Get jenis peserta dari query parameter (optional)
            $jenisPeserta = request('jenis_peserta', 'atlet'); // default atlet

            $pesertaTypeMap = [
                'atlet' => 'App\\Models\\Atlet',
                'pelatih' => 'App\\Models\\Pelatih',
                'tenaga_pendukung' => 'App\\Models\\TenagaPendukung',
            ];

            $pesertaType = $pesertaTypeMap[$jenisPeserta] ?? 'App\\Models\\Atlet';

            $pesertaList = PemeriksaanKhususPeserta::with(['peserta'])
                ->where('pemeriksaan_khusus_id', $id)
                ->where('peserta_type', $pesertaType)
                ->whereNull('deleted_at')
                ->get();

            // Format data untuk frontend
            $formattedPeserta = [];

            $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id;

            foreach ($pesertaList as $peserta) {
                $pesertaData = [
                    'id' => $peserta->id, // pemeriksaan_khusus_peserta id
                    'peserta_id' => $peserta->peserta_id, // id peserta asli (atlet/pelatih/tenaga pendukung)
                    'nama' => $peserta->peserta->nama ?? '-',
                    'jenis_kelamin' => $peserta->peserta->jenis_kelamin ?? null,
                    'tanggal_lahir' => $peserta->peserta->tanggal_lahir ?? null,
                ];

                // Hitung usia jika ada tanggal_lahir
                if ($pesertaData['tanggal_lahir']) {
                    $pesertaData['usia'] = \Carbon\Carbon::parse($pesertaData['tanggal_lahir'])->age;
                } else {
                    $pesertaData['usia'] = null;
                }

                // Tambahkan posisi/jenis berdasarkan tipe peserta
                if ($jenisPeserta === 'atlet' && $caborKategoriId) {
                    $caborKategoriAtlet = \App\Models\CaborKategoriAtlet::where('cabor_kategori_id', $caborKategoriId)
                        ->where('atlet_id', $peserta->peserta_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $pesertaData['posisi_atlet'] = $caborKategoriAtlet ? ($caborKategoriAtlet->posisi_atlet ?? '-') : '-';
                } elseif ($jenisPeserta === 'pelatih' && $caborKategoriId) {
                    $caborKategoriPelatih = \App\Models\CaborKategoriPelatih::where('cabor_kategori_id', $caborKategoriId)
                        ->where('pelatih_id', $peserta->peserta_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $pesertaData['jenis_pelatih'] = $caborKategoriPelatih ? ($caborKategoriPelatih->jenis_pelatih ?? '-') : '-';
                } elseif ($jenisPeserta === 'tenaga_pendukung' && $caborKategoriId) {
                    $caborKategoriTenagaPendukung = \App\Models\CaborKategoriTenagaPendukung::where('cabor_kategori_id', $caborKategoriId)
                        ->where('tenaga_pendukung_id', $peserta->peserta_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $pesertaData['jenis_tenaga_pendukung'] = $caborKategoriTenagaPendukung ? ($caborKategoriTenagaPendukung->jenis_tenaga_pendukung ?? '-') : '-';
                }

                $formattedPeserta[] = $pesertaData;
            }

            return response()->json([
                'success' => true,
                'data' => $formattedPeserta,
                'tipe' => $jenisPeserta,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiGetPeserta: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat mengambil peserta',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get peserta yang tersedia untuk ditambahkan (dari cabor kategori, belum ada di pemeriksaan khusus)
     */
    public function apiAvailablePeserta($id, Request $request)
    {
        try {
            $pemeriksaanKhusus = $this->repository->getById($id);
            
            if (!$pemeriksaanKhusus) {
                return response()->json(['success' => false, 'message' => 'Pemeriksaan khusus tidak ditemukan'], 404);
            }

            $jenisPeserta = $request->input('jenis_peserta', 'atlet');
            $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id;

            // Get peserta yang sudah ada di pemeriksaan khusus ini
            $existingPesertaIds = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $id)
                ->whereNull('deleted_at')
                ->pluck('peserta_id')
                ->toArray();

            $pesertaTypeMap = [
                'atlet' => ['App\\Models\\Atlet', 'CaborKategoriAtlet', 'atlet_id', 'atlet'],
                'pelatih' => ['App\\Models\\Pelatih', 'CaborKategoriPelatih', 'pelatih_id', 'pelatih'],
                'tenaga_pendukung' => ['App\\Models\\TenagaPendukung', 'CaborKategoriTenagaPendukung', 'tenaga_pendukung_id', 'tenagaPendukung'],
            ];

            $config = $pesertaTypeMap[$jenisPeserta] ?? $pesertaTypeMap['atlet'];
            [$pesertaType, $caborKategoriModel, $idKey, $relationName] = $config;

            // Query peserta dari cabor kategori yang belum ada di pemeriksaan khusus
            $modelClass = "App\\Models\\{$caborKategoriModel}";
            $query = $modelClass::with([$relationName])
                ->where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->whereNotIn($idKey, $existingPesertaIds);

            // Search filter
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->whereHas($relationName, function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            }

            $perPage = (int) $request->input('per_page', 10);
            $page = (int) $request->input('page', 1);
            $result = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform data
            $transformed = collect($result->items())->map(function ($item) use ($idKey, $relationName) {
                $peserta = $item->$relationName;
                if (!$peserta) return null;

                $usia = null;
                if ($peserta->tanggal_lahir) {
                    $usia = \Carbon\Carbon::parse($peserta->tanggal_lahir)->age;
                }

                return [
                    'id' => $peserta->id,
                    'nama' => $peserta->nama ?? '-',
                    'jenis_kelamin' => $peserta->jenis_kelamin ?? null,
                    'usia' => $usia,
                    'foto' => $peserta->foto ?? null,
                ];
            })->filter();

            return response()->json([
                'success' => true,
                'data' => $transformed->values(),
                'meta' => [
                    'total' => $result->total(),
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiAvailablePeserta: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data peserta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tambah peserta ke pemeriksaan khusus
     */
    public function storePeserta($id, Request $request)
    {
        // Check permission
        if (!auth()->user()->can('Pemeriksaan Khusus Tambah Peserta')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menambahkan peserta ke pemeriksaan khusus'], 403);
        }

        try {
            $pemeriksaanKhusus = $this->repository->getById($id);
            
            if (!$pemeriksaanKhusus) {
                return response()->json(['success' => false, 'message' => 'Pemeriksaan khusus tidak ditemukan'], 404);
            }

            $request->validate([
                'peserta_ids' => 'required|array|min:1',
                'peserta_ids.*' => 'required|integer',
                'jenis_peserta' => 'required|in:atlet,pelatih,tenaga_pendukung',
            ]);

            $pesertaIds = $request->input('peserta_ids');
            $jenisPeserta = $request->input('jenis_peserta');
            $userId = Auth::id();

            $pesertaTypeMap = [
                'atlet' => 'App\\Models\\Atlet',
                'pelatih' => 'App\\Models\\Pelatih',
                'tenaga_pendukung' => 'App\\Models\\TenagaPendukung',
            ];

            $pesertaType = $pesertaTypeMap[$jenisPeserta] ?? 'App\\Models\\Atlet';

            // Cek peserta yang sudah ada
            $existingPeserta = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $id)
                ->whereIn('peserta_id', $pesertaIds)
                ->where('peserta_type', $pesertaType)
                ->whereNull('deleted_at')
                ->pluck('peserta_id')
                ->toArray();

            $newPesertaIds = array_diff($pesertaIds, $existingPeserta);

            if (empty($newPesertaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semua peserta yang dipilih sudah ada di pemeriksaan khusus ini'
                ], 400);
            }

            // Insert peserta baru
            $insertData = [];
            foreach ($newPesertaIds as $pesertaId) {
                $insertData[] = [
                    'pemeriksaan_khusus_id' => $id,
                    'peserta_id' => $pesertaId,
                    'peserta_type' => $pesertaType,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            PemeriksaanKhususPeserta::insert($insertData);

            return response()->json([
                'success' => true,
                'message' => count($newPesertaIds) . ' peserta berhasil ditambahkan ke pemeriksaan khusus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in storePeserta: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan peserta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus peserta dari pemeriksaan khusus (hanya hapus dari pemeriksaan, tidak dari cabor)
     */
    public function destroyPeserta($id, $pesertaId)
    {
        // Check permission
        if (!auth()->user()->can('Pemeriksaan Khusus Hapus Peserta')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus peserta dari pemeriksaan khusus'], 403);
        }

        try {
            $pemeriksaanKhusus = $this->repository->getById($id);
            
            if (!$pemeriksaanKhusus) {
                return response()->json(['message' => 'Pemeriksaan khusus tidak ditemukan'], 404);
            }

            $pemeriksaanKhususPeserta = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $id)
                ->where('id', $pesertaId)
                ->first();
            
            if (!$pemeriksaanKhususPeserta) {
                return response()->json(['message' => 'Peserta tidak ditemukan dalam pemeriksaan khusus ini'], 404);
            }

            // Soft delete (hanya hapus dari pemeriksaan khusus, tidak dari cabor)
            $pemeriksaanKhususPeserta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Peserta berhasil dihapus dari pemeriksaan khusus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in destroyPeserta: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus peserta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API untuk get hasil tes per pemeriksaan khusus
     */
    public function apiGetHasilTes($id)
    {
        try {
            $pemeriksaanKhusus = $this->repository->getById($id);

            // Get hanya atlet dengan hasil tes mereka (pelatih dan tenaga pendukung tidak dinilai)
            $pesertaList = PemeriksaanKhususPeserta::with([
                'peserta',
                'pemeriksaanKhususPesertaItemTes.itemTes',
            ])
                ->where('pemeriksaan_khusus_id', $id)
                ->where('peserta_type', 'App\\Models\\Atlet')
                ->get();

            // Format data untuk frontend
            $data = [];
            foreach ($pesertaList as $peserta) {
                $pesertaData = [
                    'peserta_id' => $peserta->id,
                    'peserta'    => [
                        'id'            => $peserta->peserta->id ?? null,
                        'nama'          => $peserta->peserta->nama ?? '-',
                        'jenis_kelamin' => $peserta->peserta->jenis_kelamin ?? null,
                    ],
                    'item_tes' => [],
                ];

                // Get hasil tes per item
                foreach ($peserta->pemeriksaanKhususPesertaItemTes as $hasilTes) {
                    $pesertaData['item_tes'][] = [
                        'item_tes_id' => $hasilTes->pemeriksaan_khusus_item_tes_id,
                        'nilai'       => $hasilTes->nilai,
                        'persentase_performa' => $hasilTes->persentase_performa,
                        'persentase_riil'     => $hasilTes->persentase_riil,
                        'predikat'            => $hasilTes->predikat,
                    ];
                }

                $data[] = $pesertaData;
            }

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiGetHasilTes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan saat mengambil hasil tes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk save hasil tes (bulk update)
     */
    public function apiSaveHasilTes(PemeriksaanKhususHasilTesRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->repository->saveHasilTes(
                $validated['pemeriksaan_khusus_id'],
                $validated['data']
            );

            return response()->json([
                'success' => true,
                'message' => 'Hasil tes berhasil disimpan',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiSaveHasilTes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan saat menyimpan hasil tes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk get data visualisasi (aspek & nilai keseluruhan per peserta)
     */
    public function apiGetVisualisasi($id)
    {
        try {
            $pemeriksaanKhusus = $this->repository->getById($id);

            // Load aspek dengan urutan
            $aspekList = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $id)
                ->whereNull('deleted_at')
                ->orderBy('urutan')
                ->get();

            // Load semua item tes dengan aspek untuk mapping
            $itemTesList = PemeriksaanKhususItemTes::with('aspek')
                ->whereHas('aspek', function ($q) use ($id) {
                    $q->where('pemeriksaan_khusus_id', $id)->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->orderBy('pemeriksaan_khusus_aspek_id')
                ->orderBy('urutan')
                ->get();

            // Get hanya atlet dengan hasil aspek, item tes, dan keseluruhan (pelatih dan tenaga pendukung tidak dinilai)
            $pesertaList = PemeriksaanKhususPeserta::with([
                'peserta',
                'hasilAspek.aspek',
                'hasilKeseluruhan',
                'pemeriksaanKhususPesertaItemTes.itemTes.aspek',
            ])
                ->where('pemeriksaan_khusus_id', $id)
                ->where('peserta_type', 'App\\Models\\Atlet')
                ->get();

            // Format data untuk visualisasi
            $data = [];
            $caborKategoriId = $pemeriksaanKhusus->cabor_kategori_id;
            
            foreach ($pesertaList as $peserta) {
                // Get jenis kelamin untuk menentukan target
                $jenisKelamin = $peserta->peserta->jenis_kelamin ?? null;
                $isLakiLaki = ($jenisKelamin === 'L' || $jenisKelamin === 'Laki-laki');

                // Get informasi lengkap peserta (posisi, umur, cabor)
                $posisi = '-';
                $umur = '-';
                $caborNama = $pemeriksaanKhusus->cabor->nama ?? '-';
                
                if ($peserta->peserta_type === 'App\\Models\\Atlet' && $caborKategoriId) {
                    try {
                        $posisi = $this->getAtletPosisi($peserta->peserta_id, $caborKategoriId);
                        if ($peserta->peserta && isset($peserta->peserta->tanggal_lahir)) {
                            $umur = $this->calculateAge($peserta->peserta->tanggal_lahir);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error getting posisi/umur for peserta in visualisasi', [
                            'peserta_id' => $peserta->peserta_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $pesertaData = [
                    'peserta_id' => $peserta->id,
                    'peserta' => [
                        'id' => $peserta->peserta->id ?? null,
                        'nama' => $peserta->peserta->nama ?? '-',
                        'jenis_kelamin' => $jenisKelamin,
                        'posisi' => $posisi,
                        'umur' => $umur,
                        'cabor' => $caborNama,
                    ],
                    'aspek' => [],
                    'item_tes' => [],
                    'nilai_keseluruhan' => null,
                    'predikat_keseluruhan' => null,
                ];

                // Map hasil aspek berdasarkan urutan aspek
                foreach ($aspekList as $aspek) {
                    $hasilAspek = $peserta->hasilAspek->firstWhere('pemeriksaan_khusus_aspek_id', $aspek->id);
                    
                    $pesertaData['aspek'][] = [
                        'aspek_id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'nilai_performa' => $hasilAspek ? (float) $hasilAspek->nilai_performa : null,
                        'predikat' => $hasilAspek->predikat ?? null,
                    ];
                }

                // Map hasil item tes berdasarkan aspek
                foreach ($aspekList as $aspek) {
                    $itemTesInAspek = $itemTesList->where('pemeriksaan_khusus_aspek_id', $aspek->id);
                    
                    foreach ($itemTesInAspek as $itemTes) {
                        $hasilItemTes = $peserta->pemeriksaanKhususPesertaItemTes->firstWhere('pemeriksaan_khusus_item_tes_id', $itemTes->id);
                        
                        // Tentukan target berdasarkan jenis kelamin
                        $target = $isLakiLaki ? $itemTes->target_laki_laki : $itemTes->target_perempuan;
                        
                        $pesertaData['item_tes'][] = [
                            'item_tes_id' => $itemTes->id,
                            'aspek_id' => $aspek->id,
                            'aspek_nama' => $aspek->nama,
                            'nama' => $itemTes->nama,
                            'satuan' => $itemTes->satuan,
                            'target' => $target,
                            'target_laki_laki' => $itemTes->target_laki_laki,
                            'target_perempuan' => $itemTes->target_perempuan,
                            'performa_arah' => $itemTes->performa_arah,
                            'urutan' => $itemTes->urutan,
                            'nilai' => $hasilItemTes->nilai ?? null,
                            'persentase_performa' => $hasilItemTes ? (float) $hasilItemTes->persentase_performa : null,
                            'persentase_riil' => $hasilItemTes ? (float) $hasilItemTes->persentase_riil : null,
                            'predikat' => $hasilItemTes->predikat ?? null,
                        ];
                    }
                }

                // Get nilai keseluruhan
                if ($peserta->hasilKeseluruhan) {
                    $pesertaData['nilai_keseluruhan'] = $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        ? (float) $peserta->hasilKeseluruhan->nilai_keseluruhan 
                        : null;
                    $pesertaData['predikat_keseluruhan'] = $peserta->hasilKeseluruhan->predikat;
                }

                $data[] = $pesertaData;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'aspek_list' => $aspekList->map(fn($a) => [
                    'id' => $a->id,
                    'nama' => $a->nama,
                    'urutan' => $a->urutan,
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiGetVisualisasi: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat mengambil data visualisasi',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function untuk menghitung umur
     */
    private function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $tanggalLahir = new Carbon($tanggalLahir);
            $today = Carbon::today();
            return (int) $tanggalLahir->diffInYears($today);
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Helper function untuk mendapatkan posisi atlet
     */
    private function getAtletPosisi($atletId, $caborKategoriId)
    {
        if (!$caborKategoriId || !$atletId) {
            return '-';
        }

        try {
            $posisi = DB::table('cabor_kategori_atlet')
                ->where('cabor_kategori_atlet.atlet_id', $atletId)
                ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                ->whereNull('cabor_kategori_atlet.deleted_at')
                ->value('cabor_kategori_atlet.posisi_atlet');

            return $posisi ?? '-';
        } catch (\Exception $e) {
            Log::warning('Error in getAtletPosisi', [
                'atlet_id' => $atletId,
                'cabor_kategori_id' => $caborKategoriId,
                'error' => $e->getMessage(),
            ]);
            return '-';
        }
    }
}

