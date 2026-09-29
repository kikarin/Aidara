<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaborRequest;
use App\Models\PemeriksaanKhusus;
use App\Models\PemeriksaanKhususAspek;
use App\Models\PemeriksaanKhususPeserta;
use App\Models\PemeriksaanKhususPesertaAspek;
use App\Models\PemeriksaanKhususPesertaKeseluruhan;
use App\Repositories\CaborRepository;
use App\Traits\BaseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CaborController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, CaborRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = CaborRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'cabor';
        $this->commonData['kode_first_menu']  = 'CABOR';
        $this->commonData['kode_second_menu'] = 'CABOR';
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

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['cabors'],
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

        return inertia('modules/cabor/Index', $data);
    }

    public function store(CaborRequest $request)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->create($data);

        return redirect()->route('cabor.index')->with('success', 'Data cabor berhasil ditambahkan!');
    }

    public function update(CaborRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('cabor.index')->with('success', 'Data cabor berhasil diperbarui!');
    }

    public function show($id)
    {
        $item      = $this->repository->getById($id);
        $itemArray = $item->toArray();

        return Inertia::render('modules/cabor/Show', [
            'item' => $itemArray,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('cabor.index')->with('success', 'Data cabor berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:cabor,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data cabor berhasil dihapus!']);
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

        return inertia('modules/cabor/Create', $data);
    }

    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
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

        return inertia('modules/cabor/Edit', $data);
    }

    public function getPeserta($id, $tipe)
    {
        $peserta = $this->repository->getPesertaByCabor($id, $tipe);

        return response()->json([
            'data'     => $peserta,
            'tipe'     => $tipe,
            'cabor_id' => $id,
        ]);
    }

    /**
     * Halaman untuk tambah multiple peserta ke cabor
     */
    public function createMultiplePeserta($id, $tipe)
    {
        $cabor = $this->repository->getById($id);
        
        if (!$cabor) {
            return redirect()->back()->with('error', 'Cabor tidak ditemukan!');
        }

        // Pastikan kategori_peserta_id ter-include
        $caborArray = $cabor->toArray();
        if (!isset($caborArray['kategori_peserta_id'])) {
            $caborArray['kategori_peserta_id'] = $cabor->kategori_peserta_id;
        }

        $data = $this->commonData + [
            'cabor' => $caborArray,
            'tipe'  => $tipe,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return inertia('modules/cabor/CreateMultiplePeserta', $data);
    }

    /**
     * Store multiple peserta ke cabor
     */
    public function storeMultiplePeserta(Request $request, $id, $tipe)
    {
        if (!auth()->user()->can('Cabor Tambah Peserta')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menambahkan peserta ke cabor');
        }

        $request->validate([
            'peserta_ids' => 'required|array|min:1',
            'peserta_ids.*' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $cabor = $this->repository->getById($id);
        
        if (!$cabor) {
            return redirect()->back()->with('error', 'Cabor tidak ditemukan!');
        }

        try {
            $pesertaIds = $request->input('peserta_ids');
            $isActive = $request->input('is_active', 1);
            $posisi = $request->input('posisi'); // posisi_atlet, jenis_pelatih, atau jenis_tenaga_pendukung
            $userId = auth()->id();

            foreach ($pesertaIds as $pesertaId) {
                $data = [
                    'cabor_id' => $id,
                    'cabor_kategori_id' => null, // Langsung ke cabor tanpa kategori
                    'is_active' => $isActive,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($tipe === 'atlet') {
                    $data['atlet_id'] = $pesertaId;
                    $data['posisi_atlet'] = $posisi;
                    
                    // Cek existing (unique: cabor_id + atlet_id)
                    $existing = \App\Models\CaborKategoriAtlet::withTrashed()
                        ->where('cabor_id', $id)
                        ->where('atlet_id', $pesertaId)
                        ->first();
                    
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update([
                            'is_active' => $isActive,
                            'posisi_atlet' => $posisi,
                            'updated_by' => $userId,
                        ]);
                    } else {
                        \App\Models\CaborKategoriAtlet::create($data);
                    }
                } elseif ($tipe === 'pelatih') {
                    $data['pelatih_id'] = $pesertaId;
                    $data['jenis_pelatih'] = $posisi;
                    
                    // Cek existing (unique: cabor_id + cabor_kategori_id + pelatih_id)
                    $existing = \App\Models\CaborKategoriPelatih::withTrashed()
                        ->where('cabor_id', $id)
                        ->whereNull('cabor_kategori_id')
                        ->where('pelatih_id', $pesertaId)
                        ->first();
                    
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update([
                            'is_active' => $isActive,
                            'jenis_pelatih' => $posisi,
                            'updated_by' => $userId,
                        ]);
                    } else {
                        \App\Models\CaborKategoriPelatih::create($data);
                    }
                } elseif ($tipe === 'tenaga_pendukung') {
                    $data['tenaga_pendukung_id'] = $pesertaId;
                    $data['jenis_tenaga_pendukung'] = $posisi;
                    
                    // Cek existing (unique: cabor_id + cabor_kategori_id + tenaga_pendukung_id)
                    $existing = \App\Models\CaborKategoriTenagaPendukung::withTrashed()
                        ->where('cabor_id', $id)
                        ->whereNull('cabor_kategori_id')
                        ->where('tenaga_pendukung_id', $pesertaId)
                        ->first();
                    
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update([
                            'is_active' => $isActive,
                            'jenis_tenaga_pendukung' => $posisi,
                            'updated_by' => $userId,
                        ]);
                    } else {
                        \App\Models\CaborKategoriTenagaPendukung::create($data);
                    }
                }
            }

            $tipeLabel = match($tipe) {
                'atlet' => 'Atlet',
                'pelatih' => 'Pelatih',
                'tenaga_pendukung' => 'Tenaga Pendukung',
                default => 'Peserta',
            };

            return redirect()->route('cabor.index')
                ->with('success', "{$tipeLabel} berhasil ditambahkan ke cabor!");
        } catch (\Exception $e) {
            \Log::error('Error storing multiple peserta', [
                'error' => $e->getMessage(),
                'cabor_id' => $id,
                'tipe' => $tipe,
            ]);
            
            return redirect()->back()->with('error', 'Gagal menambahkan peserta: ' . $e->getMessage());
        }
    }

    /**
     * Hapus peserta dari cabor
     */
    public function destroyPeserta($id, $tipe, $pesertaId)
    {
        // Check permission
        if (!auth()->user()->can('Cabor Hapus Peserta')) {
            return response()->json(['message' => 'Anda tidak memiliki izin untuk menghapus peserta dari cabor'], 403);
        }

        $cabor = $this->repository->getById($id);
        
        if (!$cabor) {
            return response()->json(['message' => 'Cabor tidak ditemukan'], 404);
        }

        try {
            switch ($tipe) {
                case 'atlet':
                    $caborKategoriAtlet = \App\Models\CaborKategoriAtlet::where('cabor_id', $id)
                        ->where('atlet_id', $pesertaId)
                        ->first();
                    
                    if ($caborKategoriAtlet) {
                        $caborKategoriAtlet->delete(); // Soft delete
                        return response()->json(['message' => 'Atlet berhasil dihapus dari cabor']);
                    }
                    break;
                    
                case 'pelatih':
                    $caborKategoriPelatih = \App\Models\CaborKategoriPelatih::where('cabor_id', $id)
                        ->where('pelatih_id', $pesertaId)
                        ->first();
                    
                    if ($caborKategoriPelatih) {
                        $caborKategoriPelatih->delete(); // Soft delete
                        return response()->json(['message' => 'Pelatih berhasil dihapus dari cabor']);
                    }
                    break;
                    
                case 'tenaga_pendukung':
                    $caborKategoriTenagaPendukung = \App\Models\CaborKategoriTenagaPendukung::where('cabor_id', $id)
                        ->where('tenaga_pendukung_id', $pesertaId)
                        ->first();
                    
                    if ($caborKategoriTenagaPendukung) {
                        $caborKategoriTenagaPendukung->delete(); // Soft delete
                        return response()->json(['message' => 'Tenaga Pendukung berhasil dihapus dari cabor']);
                    }
                    break;
            }
            
            return response()->json(['message' => 'Peserta tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus peserta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API untuk perbandingan multi-tes
     */
    public function apiPerbandinganMultiTes(Request $request, $cabor_id)
    {
        try {
            // Validasi input
            $request->validate([
                'pemeriksaan_khusus_ids' => 'required|array|min:2',
                'pemeriksaan_khusus_ids.*' => 'required|integer|exists:pemeriksaan_khusus,id',
                'cabor_kategori_id' => 'nullable|integer|exists:cabor_kategori,id',
            ]);

            $pemeriksaanIds = $request->input('pemeriksaan_khusus_ids');
            $caborKategoriId = $request->input('cabor_kategori_id');

            // Get pemeriksaan list
            $pemeriksaanList = PemeriksaanKhusus::whereIn('id', $pemeriksaanIds)
                ->where('cabor_id', $cabor_id)
                ->with(['caborKategori'])
                ->orderBy('tanggal_pemeriksaan')
                ->get();

            if ($pemeriksaanList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            // Get semua aspek dari semua pemeriksaan (union berdasarkan nama)
            $allAspek = collect();
            foreach ($pemeriksaanList as $pemeriksaan) {
                $aspekList = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                    ->whereNull('deleted_at')
                    ->orderBy('urutan')
                    ->get();

                foreach ($aspekList as $aspek) {
                    // Cek apakah aspek dengan nama sama sudah ada
                    $existingAspek = $allAspek->firstWhere('nama', $aspek->nama);
                    if (!$existingAspek) {
                        $allAspek->push([
                            'id' => $aspek->id,
                            'nama' => $aspek->nama,
                            'urutan' => $aspek->urutan ?? 0,
                        ]);
                    }
                }
            }

            // Sort aspek by nama (atau urutan jika ada)
            $allAspek = $allAspek->sortBy('nama')->values();

            // Get semua peserta yang akan dibandingkan
            $pesertaQuery = PemeriksaanKhususPeserta::with(['peserta'])
                ->whereIn('pemeriksaan_khusus_id', $pemeriksaanIds);

            // Filter by kategori jika dipilih
            if ($caborKategoriId) {
                $pesertaQuery->whereHas('pemeriksaanKhusus', function ($q) use ($caborKategoriId) {
                    $q->where('cabor_kategori_id', $caborKategoriId);
                });
            }

            $allPeserta = $pesertaQuery->get();

            // Group peserta by peserta_id dan peserta_type (unique peserta)
            $uniquePeserta = collect();
            foreach ($allPeserta as $p) {
                $key = $p->peserta_id . '_' . $p->peserta_type;
                if (!$uniquePeserta->has($key)) {
                    // Get cabor_kategori_id from pemeriksaan khusus yang sudah di-load
                    $caborKategoriIdFromPemeriksaan = null;
                    $pemeriksaan = $pemeriksaanList->firstWhere('id', $p->pemeriksaan_khusus_id);
                    if ($pemeriksaan) {
                        $caborKategoriIdFromPemeriksaan = $pemeriksaan->cabor_kategori_id;
                    }
                    if (!$caborKategoriIdFromPemeriksaan && $caborKategoriId) {
                        $caborKategoriIdFromPemeriksaan = $caborKategoriId;
                    }
                    
                    // Get posisi and usia
                    $posisi = '-';
                    $usia = '-';
                    
                    try {
                        if ($p->peserta_type === 'App\\Models\\Atlet' && $caborKategoriIdFromPemeriksaan) {
                            $posisi = $this->getAtletPosisi($p->peserta_id, $caborKategoriIdFromPemeriksaan);
                            if ($p->peserta && isset($p->peserta->tanggal_lahir)) {
                                $usia = $this->calculateAge($p->peserta->tanggal_lahir);
                            }
                        }
                    } catch (\Exception $e) {
                        // Jika error, tetap gunakan default value
                        Log::warning('Error getting posisi/usia for peserta in perbandingan', [
                            'peserta_id' => $p->peserta_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    
                    $uniquePeserta->put($key, [
                        'peserta_id' => $p->peserta_id,
                        'peserta_type' => $p->peserta_type,
                        'nama' => $p->peserta ? ($p->peserta->nama ?? '-') : '-',
                        'jenis_kelamin' => $p->peserta ? ($p->peserta->jenis_kelamin ?? null) : null,
                        'posisi' => $posisi,
                        'usia' => $usia,
                    ]);
                }
            }

            // Format data perbandingan per peserta
            $perbandinganPerPeserta = [];
            $rataRataKeseluruhanPerPemeriksaan = [];
            
            foreach ($uniquePeserta as $pesertaData) {
                $perbandinganAspek = [];
                $nilaiKeseluruhanPerTes = [];

                // Loop setiap aspek
                foreach ($allAspek as $aspek) {
                    $nilaiPerTes = [];
                    $nilaiList = [];

                    // Loop setiap pemeriksaan
                    foreach ($pemeriksaanList as $pemeriksaan) {
                        // Cari aspek dengan nama yang sama di pemeriksaan ini
                        $aspekInPemeriksaan = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                            ->where('nama', $aspek['nama'])
                            ->whereNull('deleted_at')
                            ->first();

                        if (!$aspekInPemeriksaan) {
                            $nilaiPerTes[] = [
                                'pemeriksaan_id' => $pemeriksaan->id,
                                'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                                'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                                'nilai_performa' => null,
                                'predikat' => null,
                            ];
                            continue;
                        }

                        // Get peserta di pemeriksaan ini
                        $pesertaInPemeriksaan = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                            ->where('peserta_id', $pesertaData['peserta_id'])
                            ->where('peserta_type', $pesertaData['peserta_type'])
                            ->first();

                        if (!$pesertaInPemeriksaan) {
                            $nilaiPerTes[] = [
                                'pemeriksaan_id' => $pemeriksaan->id,
                                'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                                'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                                'nilai_performa' => null,
                                'predikat' => null,
                            ];
                            continue;
                        }

                        // Get nilai aspek
                        $hasilAspek = PemeriksaanKhususPesertaAspek::where('pemeriksaan_khusus_peserta_id', $pesertaInPemeriksaan->id)
                            ->where('pemeriksaan_khusus_aspek_id', $aspekInPemeriksaan->id)
                            ->first();

                        $nilaiPerforma = $hasilAspek ? (float) $hasilAspek->nilai_performa : null;
                        $predikat = $hasilAspek->predikat ?? null;

                        if ($nilaiPerforma !== null) {
                            $nilaiList[] = $nilaiPerforma;
                        }

                        $nilaiPerTes[] = [
                            'pemeriksaan_id' => $pemeriksaan->id,
                            'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                            'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                            'nilai_performa' => $nilaiPerforma,
                            'predikat' => $predikat,
                        ];
                    }

                    // Hitung trend dan selisih
                    $trend = 'stabil';
                    $selisih = null;

                    if (count($nilaiList) >= 2) {
                        $nilaiPertama = $nilaiList[0];
                        $nilaiTerakhir = $nilaiList[count($nilaiList) - 1];

                        if ($nilaiPertama !== null && $nilaiTerakhir !== null) {
                            $selisih = $nilaiTerakhir - $nilaiPertama;

                            if ($selisih > 0.5) {
                                $trend = 'naik';
                            } elseif ($selisih < -0.5) {
                                $trend = 'turun';
                            } else {
                                $trend = 'stabil';
                            }
                        }
                    }

                    $perbandinganAspek[] = [
                        'aspek_id' => $aspek['id'],
                        'aspek_nama' => $aspek['nama'],
                        'nilai_per_tes' => $nilaiPerTes,
                        'trend' => $trend,
                        'selisih' => $selisih,
                    ];
                }

                // Get nilai keseluruhan per pemeriksaan untuk peserta ini
                $nilaiKeseluruhanList = [];
                foreach ($pemeriksaanList as $pemeriksaan) {
                    $pesertaInPemeriksaan = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                        ->where('peserta_id', $pesertaData['peserta_id'])
                        ->where('peserta_type', $pesertaData['peserta_type'])
                        ->first();

                    $nilaiKeseluruhan = null;
                    $predikatKeseluruhan = null;

                    if ($pesertaInPemeriksaan) {
                        $hasilKeseluruhan = PemeriksaanKhususPesertaKeseluruhan::where('pemeriksaan_khusus_peserta_id', $pesertaInPemeriksaan->id)
                            ->first();

                        if ($hasilKeseluruhan) {
                            $nilaiKeseluruhan = $hasilKeseluruhan->nilai_keseluruhan ? (float) $hasilKeseluruhan->nilai_keseluruhan : null;
                            $predikatKeseluruhan = $hasilKeseluruhan->predikat;
                        }
                    }

                    $nilaiKeseluruhanPerTes[] = [
                        'pemeriksaan_id' => $pemeriksaan->id,
                        'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                        'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                        'nilai_keseluruhan' => $nilaiKeseluruhan,
                        'predikat' => $predikatKeseluruhan,
                    ];

                    // Collect untuk rata-rata
                    if ($nilaiKeseluruhan !== null) {
                        if (!isset($rataRataKeseluruhanPerPemeriksaan[$pemeriksaan->id])) {
                            $rataRataKeseluruhanPerPemeriksaan[$pemeriksaan->id] = [
                                'pemeriksaan_id' => $pemeriksaan->id,
                                'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                                'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                                'nilai_list' => [],
                            ];
                        }
                        $rataRataKeseluruhanPerPemeriksaan[$pemeriksaan->id]['nilai_list'][] = $nilaiKeseluruhan;
                    }
                }

                // Hitung trend nilai keseluruhan
                $nilaiKeseluruhanFiltered = array_filter(array_column($nilaiKeseluruhanPerTes, 'nilai_keseluruhan'), fn($v) => $v !== null);
                $trendKeseluruhan = 'stabil';
                $selisihKeseluruhan = null;

                if (count($nilaiKeseluruhanFiltered) >= 2) {
                    $nilaiKeseluruhanArray = array_values($nilaiKeseluruhanFiltered);
                    $nilaiKeseluruhanPertama = $nilaiKeseluruhanArray[0];
                    $nilaiKeseluruhanTerakhir = $nilaiKeseluruhanArray[count($nilaiKeseluruhanArray) - 1];

                    $selisihKeseluruhan = $nilaiKeseluruhanTerakhir - $nilaiKeseluruhanPertama;

                    if ($selisihKeseluruhan > 0.5) {
                        $trendKeseluruhan = 'naik';
                    } elseif ($selisihKeseluruhan < -0.5) {
                        $trendKeseluruhan = 'turun';
                    } else {
                        $trendKeseluruhan = 'stabil';
                    }
                }

                $perbandinganPerPeserta[] = [
                    'peserta_id' => $pesertaData['peserta_id'],
                    'peserta_type' => $pesertaData['peserta_type'],
                    'nama' => $pesertaData['nama'],
                    'jenis_kelamin' => $pesertaData['jenis_kelamin'],
                    'posisi' => $pesertaData['posisi'] ?? '-',
                    'usia' => $pesertaData['usia'] ?? '-',
                    'perbandingan_aspek' => $perbandinganAspek,
                    'nilai_keseluruhan' => [
                        'nilai_per_tes' => $nilaiKeseluruhanPerTes,
                        'trend' => $trendKeseluruhan,
                        'selisih' => $selisihKeseluruhan,
                    ],
                ];
            }

            // Hitung rata-rata nilai keseluruhan per pemeriksaan (sesuai urutan)
            $rataRataKeseluruhanFormatted = [];
            foreach ($pemeriksaanList as $pemeriksaan) {
                $data = $rataRataKeseluruhanPerPemeriksaan[$pemeriksaan->id] ?? null;
                
                if ($data && !empty($data['nilai_list'])) {
                    $rataRata = array_sum($data['nilai_list']) / count($data['nilai_list']);
                    $rataRataKeseluruhanFormatted[] = [
                        'pemeriksaan_id' => $pemeriksaan->id,
                        'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                        'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                        'rata_rata' => round($rataRata, 2),
                    ];
                } else {
                    $rataRataKeseluruhanFormatted[] = [
                        'pemeriksaan_id' => $pemeriksaan->id,
                        'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                        'tanggal' => $pemeriksaan->tanggal_pemeriksaan,
                        'rata_rata' => null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pemeriksaan_list' => $pemeriksaanList->map(fn($p) => [
                        'id' => $p->id,
                        'nama_pemeriksaan' => $p->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $p->tanggal_pemeriksaan,
                        'cabor_kategori' => $p->caborKategori?->nama ?? '-',
                    ])->toArray(),
                    'aspek_list' => $allAspek->toArray(),
                    'perbandingan_per_peserta' => $perbandinganPerPeserta,
                    'rata_rata_keseluruhan' => $rataRataKeseluruhanFormatted,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiPerbandinganMultiTes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'cabor_id' => $cabor_id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data perbandingan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk ranking berdasarkan nilai keseluruhan
     */
    public function apiRanking($cabor_id)
    {
        try {
            // Get semua pemeriksaan khusus untuk cabor ini
            $pemeriksaanList = PemeriksaanKhusus::where('cabor_id', $cabor_id)
                ->with(['caborKategori'])
                ->orderBy('tanggal_pemeriksaan')
                ->get();

            if ($pemeriksaanList->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'pemeriksaan_list' => [],
                        'ranking_total_rata_rata' => [],
                        'ranking_per_tes' => [],
                    ],
                ]);
            }

            // Get semua peserta yang pernah mengikuti pemeriksaan khusus ini
            $allPeserta = PemeriksaanKhususPeserta::with(['peserta'])
                ->whereIn('pemeriksaan_khusus_id', $pemeriksaanList->pluck('id'))
                ->get();

            // Group peserta by peserta_id dan peserta_type (unique peserta)
            $uniquePeserta = collect();
            foreach ($allPeserta as $p) {
                $key = $p->peserta_id . '_' . $p->peserta_type;
                if (!$uniquePeserta->has($key)) {
                    // Get cabor_kategori_id from pemeriksaan khusus yang sudah di-load
                    $caborKategoriIdFromPemeriksaan = null;
                    $pemeriksaan = $pemeriksaanList->firstWhere('id', $p->pemeriksaan_khusus_id);
                    if ($pemeriksaan) {
                        $caborKategoriIdFromPemeriksaan = $pemeriksaan->cabor_kategori_id;
                    }
                    
                    // Get posisi and usia
                    $posisi = '-';
                    $usia = '-';
                    
                    try {
                        if ($p->peserta_type === 'App\\Models\\Atlet' && $caborKategoriIdFromPemeriksaan) {
                            $posisi = $this->getAtletPosisi($p->peserta_id, $caborKategoriIdFromPemeriksaan);
                            if ($p->peserta && isset($p->peserta->tanggal_lahir)) {
                                $usia = $this->calculateAge($p->peserta->tanggal_lahir);
                            }
                        }
                    } catch (\Exception $e) {
                        // Jika error, tetap gunakan default value
                        Log::warning('Error getting posisi/usia for peserta in ranking', [
                            'peserta_id' => $p->peserta_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    
                    $uniquePeserta->put($key, [
                        'peserta_id' => $p->peserta_id,
                        'peserta_type' => $p->peserta_type,
                        'nama' => $p->peserta ? ($p->peserta->nama ?? '-') : '-',
                        'jenis_kelamin' => $p->peserta ? ($p->peserta->jenis_kelamin ?? null) : null,
                        'posisi' => $posisi,
                        'usia' => $usia,
                    ]);
                }
            }

            // Ranking Total Rata-rata (rata-rata dari semua tes)
            $rankingTotalRataRata = [];
            foreach ($uniquePeserta as $pesertaData) {
                $nilaiList = [];

                // Loop setiap pemeriksaan untuk mendapatkan nilai keseluruhan
                foreach ($pemeriksaanList as $pemeriksaan) {
                    $pesertaInPemeriksaan = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                        ->where('peserta_id', $pesertaData['peserta_id'])
                        ->where('peserta_type', $pesertaData['peserta_type'])
                        ->first();

                    if ($pesertaInPemeriksaan) {
                        $hasilKeseluruhan = PemeriksaanKhususPesertaKeseluruhan::where('pemeriksaan_khusus_peserta_id', $pesertaInPemeriksaan->id)
                            ->first();

                        if ($hasilKeseluruhan && $hasilKeseluruhan->nilai_keseluruhan !== null) {
                            $nilaiList[] = (float) $hasilKeseluruhan->nilai_keseluruhan;
                        }
                    }
                }

                // Hitung rata-rata
                if (!empty($nilaiList)) {
                    $rataRata = array_sum($nilaiList) / count($nilaiList);
                    
                    // Tentukan predikat berdasarkan rata-rata
                    $predikat = $this->getPredikatFromPercentage($rataRata);

                    $rankingTotalRataRata[] = [
                        'peserta_id' => $pesertaData['peserta_id'],
                        'peserta_type' => $pesertaData['peserta_type'],
                        'nama' => $pesertaData['nama'],
                        'jenis_kelamin' => $pesertaData['jenis_kelamin'],
                        'posisi' => $pesertaData['posisi'] ?? '-',
                        'usia' => $pesertaData['usia'] ?? '-',
                        'nilai' => round($rataRata, 2),
                        'predikat' => $predikat,
                    ];
                }
            }

            // Sort by nilai descending
            usort($rankingTotalRataRata, fn($a, $b) => $b['nilai'] <=> $a['nilai']);

            // Ranking Per Tes
            $rankingPerTes = [];
            foreach ($pemeriksaanList as $pemeriksaan) {
                $rankingPerTesItem = [];

                // Get semua peserta di pemeriksaan ini
                $pesertaInPemeriksaan = PemeriksaanKhususPeserta::with(['peserta'])
                    ->where('pemeriksaan_khusus_id', $pemeriksaan->id)
                    ->get();

                foreach ($pesertaInPemeriksaan as $peserta) {
                    $hasilKeseluruhan = PemeriksaanKhususPesertaKeseluruhan::where('pemeriksaan_khusus_peserta_id', $peserta->id)
                        ->first();

                    if ($hasilKeseluruhan && $hasilKeseluruhan->nilai_keseluruhan !== null) {
                        $nilai = (float) $hasilKeseluruhan->nilai_keseluruhan;
                        $predikat = $hasilKeseluruhan->predikat;

                        // Get cabor_kategori_id from pemeriksaan khusus
                        $caborKategoriIdFromPemeriksaan = $pemeriksaan->cabor_kategori_id ?? null;
                        
                        // Get posisi and usia
                        $posisi = '-';
                        $usia = '-';
                        
                        try {
                            if ($peserta->peserta_type === 'App\\Models\\Atlet' && $caborKategoriIdFromPemeriksaan) {
                                $posisi = $this->getAtletPosisi($peserta->peserta_id, $caborKategoriIdFromPemeriksaan);
                                if ($peserta->peserta && isset($peserta->peserta->tanggal_lahir)) {
                                    $usia = $this->calculateAge($peserta->peserta->tanggal_lahir);
                                }
                            }
                        } catch (\Exception $e) {
                            // Jika error, tetap gunakan default value
                            Log::warning('Error getting posisi/usia for peserta in ranking per tes', [
                                'peserta_id' => $peserta->peserta_id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $rankingPerTesItem[] = [
                            'peserta_id' => $peserta->peserta_id,
                            'peserta_type' => $peserta->peserta_type,
                            'nama' => $peserta->peserta->nama ?? '-',
                            'jenis_kelamin' => $peserta->peserta->jenis_kelamin ?? null,
                            'posisi' => $posisi,
                            'usia' => $usia,
                            'nilai' => round($nilai, 2),
                            'predikat' => $predikat,
                            'pemeriksaan_khusus_id' => $pemeriksaan->id,
                        ];
                    }
                }

                // Sort by nilai descending
                usort($rankingPerTesItem, fn($a, $b) => $b['nilai'] <=> $a['nilai']);

                $rankingPerTes[] = [
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'data' => $rankingPerTesItem,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pemeriksaan_list' => $pemeriksaanList->map(fn($p) => [
                        'id' => $p->id,
                        'nama_pemeriksaan' => $p->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $p->tanggal_pemeriksaan,
                        'cabor_kategori' => $p->caborKategori?->nama ?? '-',
                    ])->toArray(),
                    'ranking_total_rata_rata' => $rankingTotalRataRata,
                    'ranking_per_tes' => $rankingPerTes,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiRanking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'cabor_id' => $cabor_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data ranking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function untuk mendapatkan predikat dari persentase
     */
    private function getPredikatFromPercentage($percentage)
    {
        if ($percentage >= 100) {
            return 'target';
        } elseif ($percentage >= 80) {
            return 'mendekati_target';
        } elseif ($percentage >= 60) {
            return 'sedang';
        } elseif ($percentage >= 40) {
            return 'kurang';
        } elseif ($percentage >= 20) {
            return 'sangat_kurang';
        } else {
            return 'sangat_kurang';
        }
    }

    /**
     * Helper function untuk menghitung usia
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

    /**
     * API: Get 3 pemeriksaan khusus terakhir untuk atlet tertentu
     */
    public function apiGetLastThreePemeriksaan(Request $request, $cabor_id, $atlet_id)
    {
        try {
            // Get 3 pemeriksaan khusus terakhir untuk atlet ini di cabor ini
            $pemeriksaanList = PemeriksaanKhusus::where('cabor_id', $cabor_id)
                ->whereHas('pemeriksaanKhususPeserta', function ($q) use ($atlet_id) {
                    $q->where('peserta_id', $atlet_id)
                        ->where('peserta_type', 'App\\Models\\Atlet');
                })
                ->with(['caborKategori'])
                ->orderBy('tanggal_pemeriksaan', 'desc')
                ->limit(3)
                ->get();

            if ($pemeriksaanList->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Tidak ada data pemeriksaan khusus untuk atlet ini',
                ]);
            }

            // Load aspek dan hasil untuk setiap pemeriksaan
            $result = [];
            foreach ($pemeriksaanList as $pemeriksaan) {
                // Load aspek dengan urutan
                $aspekList = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                    ->whereNull('deleted_at')
                    ->orderBy('urutan')
                    ->get();

                // Get peserta untuk atlet ini di pemeriksaan ini
                $peserta = PemeriksaanKhususPeserta::with([
                    'hasilAspek.aspek',
                    'hasilKeseluruhan',
                ])
                    ->where('pemeriksaan_khusus_id', $pemeriksaan->id)
                    ->where('peserta_id', $atlet_id)
                    ->where('peserta_type', 'App\\Models\\Atlet')
                    ->first();

                if (!$peserta) {
                    continue;
                }

                // Format data aspek
                $aspekData = [];
                foreach ($aspekList as $aspek) {
                    $hasilAspek = $peserta->hasilAspek->firstWhere('pemeriksaan_khusus_aspek_id', $aspek->id);
                    $aspekData[] = [
                        'aspek_id' => $aspek->id,
                        'nama' => $aspek->nama,
                        'nilai_performa' => $hasilAspek ? (float) $hasilAspek->nilai_performa : null,
                        'predikat' => $hasilAspek->predikat ?? null,
                    ];
                }

                $result[] = [
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'nama_pemeriksaan' => $pemeriksaan->nama_pemeriksaan,
                    'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                    'cabor_kategori' => $pemeriksaan->caborKategori->nama ?? '-',
                    'aspek_list' => $aspekList->map(fn($a) => [
                        'id' => $a->id,
                        'nama' => $a->nama,
                        'urutan' => $a->urutan,
                    ])->toArray(),
                    'aspek' => $aspekData,
                    'nilai_keseluruhan' => $peserta->hasilKeseluruhan ? (float) $peserta->hasilKeseluruhan->nilai_keseluruhan : null,
                    'predikat_keseluruhan' => $peserta->hasilKeseluruhan->predikat ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiGetLastThreePemeriksaan', [
                'cabor_id' => $cabor_id,
                'atlet_id' => $atlet_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pemeriksaan khusus',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
