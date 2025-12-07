<?php

namespace App\Repositories;

use App\Http\Requests\PemeriksaanKhususRequest;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Models\MstTemplatePemeriksaanKhususAspek;
use App\Models\PemeriksaanKhusus;
use App\Models\PemeriksaanKhususAspek;
use App\Models\PemeriksaanKhususItemTes;
use App\Models\PemeriksaanKhususPeserta;
use App\Models\PemeriksaanKhususPesertaAspek;
use App\Models\PemeriksaanKhususPesertaItemTes;
use App\Models\PemeriksaanKhususPesertaKeseluruhan;
use App\Services\PemeriksaanKhususCalculationService;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PemeriksaanKhususRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(PemeriksaanKhusus $model)
    {
        $this->model   = $model;
        $this->request = PemeriksaanKhususRequest::createFromBase(request());
        $this->with    = [
            'cabor',
            'caborKategori',
            'created_by_user',
            'updated_by_user',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model->with($this->with)
            ->withCount([
                'pemeriksaanKhususPeserta as jumlah_peserta',
                'pemeriksaanKhususPeserta as jumlah_atlet' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\Atlet');
                },
                'pemeriksaanKhususPeserta as jumlah_pelatih' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\Pelatih');
                },
                'pemeriksaanKhususPeserta as jumlah_tenaga_pendukung' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\TenagaPendukung');
                },
            ]);

        // Apply filters
        $this->applyFilters($query);

        $sortField = request('sort');
        $order     = request('order', 'asc');

        if ($sortField === 'cabor') {
            $query->join('cabor', 'pemeriksaan_khusus.cabor_id', '=', 'cabor.id')
                ->orderBy('cabor.nama', $order)
                ->select('pemeriksaan_khusus.*');
        } elseif ($sortField === 'cabor_kategori') {
            $query->join('cabor_kategori', 'pemeriksaan_khusus.cabor_kategori_id', '=', 'cabor_kategori.id')
                ->orderBy('cabor_kategori.nama', $order)
                ->select('pemeriksaan_khusus.*');
        } else {
            // Sort by kolom di tabel pemeriksaan_khusus
            $validColumns = ['id', 'cabor_id', 'cabor_kategori_id', 'nama_pemeriksaan', 'tanggal_pemeriksaan', 'status', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $order);
            } else {
                $query->orderBy('id', 'desc');
            }
        }

        if (request('search')) {
            $search = request('search');
            $query->where('nama_pemeriksaan', 'like', "%$search%");
        }

        // Role-based filtering
        $auth = Auth::user();
        if ($auth->current_role_id == 35) { // Atlet
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriAtlet', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('atlet_id', $auth->atlet->id)
                        ->where('is_active', 1);
                });
            });
        } elseif ($auth->current_role_id == 36) { // Pelatih
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriPelatih', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('pelatih_id', $auth->pelatih->id)
                        ->where('is_active', 1);
                });
            });
        } elseif ($auth->current_role_id == 37) { // Tenaga Pendukung
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriTenagaPendukung', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('tenaga_pendukung_id', $auth->tenagaPendukung->id)
                        ->where('is_active', 1);
                });
            });
        }

        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);

        if ($perPage === -1) {
            $all         = $query->get();
            $transformed = collect($all)->map(function ($item) {
                return [
                    'id'                      => $item->id,
                    'cabor'                   => $item->cabor?->nama           ?? '-',
                    'cabor_kategori'          => $item->caborKategori?->nama   ?? '-',
                    'nama_pemeriksaan'        => $item->nama_pemeriksaan,
                    'tanggal_pemeriksaan'     => $item->tanggal_pemeriksaan,
                    'status'                  => $item->status,
                    'jumlah_peserta'          => $item->jumlah_peserta          ?? 0,
                    'jumlah_atlet'            => $item->jumlah_atlet            ?? 0,
                    'jumlah_pelatih'          => $item->jumlah_pelatih          ?? 0,
                    'jumlah_tenaga_pendukung' => $item->jumlah_tenaga_pendukung ?? 0,
                ];
            });
            $data += [
                'pemeriksaan_khusus' => $transformed,
                'total'              => $transformed->count(),
                'currentPage'        => 1,
                'perPage'            => -1,
                'search'             => request('search', ''),
                'sort'               => request('sort', ''),
                'order'              => request('order', 'asc'),
            ];

            return $data;
        }

        $items       = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        $transformed = collect($items->items())->map(function ($item) {
            return [
                'id'                      => $item->id,
                'cabor'                   => $item->cabor?->nama           ?? '-',
                'cabor_kategori'          => $item->caborKategori?->nama   ?? '-',
                'nama_pemeriksaan'        => $item->nama_pemeriksaan,
                'tanggal_pemeriksaan'     => $item->tanggal_pemeriksaan,
                'status'                  => $item->status,
                'jumlah_peserta'          => $item->jumlah_peserta          ?? 0,
                'jumlah_atlet'            => $item->jumlah_atlet            ?? 0,
                'jumlah_pelatih'          => $item->jumlah_pelatih          ?? 0,
                'jumlah_tenaga_pendukung' => $item->jumlah_tenaga_pendukung ?? 0,
            ];
        });
        $data += [
            'pemeriksaan_khusus' => $transformed,
            'total'              => $items->total(),
            'currentPage'        => $items->currentPage(),
            'perPage'            => $items->perPage(),
            'search'             => request('search', ''),
        ];

        return $data;
    }

    /**
     * Apply filters to the query
     */
    protected function applyFilters($query)
    {
        // Filter by cabor_id
        if (request('cabor_id') && request('cabor_id') !== 'all') {
            $query->where('cabor_id', request('cabor_id'));
        }

        // Filter by cabor_kategori_id
        if (request('cabor_kategori_id') && request('cabor_kategori_id') !== 'all') {
            $query->where('cabor_kategori_id', request('cabor_kategori_id'));
        }

        // Filter by date range
        if (request('filter_start_date') && request('filter_end_date')) {
            $query->whereBetween('created_at', [
                request('filter_start_date') . ' 00:00:00',
                request('filter_end_date') . ' 23:59:59',
            ]);
        }
    }

    public function customCreateEdit($data, $item = null)
    {
        $data['item'] = $item;

        return $data;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::id();
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        return $data;
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    public function getById($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }

    /**
     * Callback setelah pemeriksaan khusus dibuat
     * Auto-create peserta dari cabor kategori
     */
    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        $userId = Auth::id();

        // Hanya proses untuk create (store), bukan update
        if ($method === 'store') {
            // Auto-create peserta dari cabor kategori
            $caborKategoriId = $model->cabor_kategori_id;

            // Get Atlet aktif di kategori ini
            $atletIds = CaborKategoriAtlet::where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('atlet_id')
                ->unique();

            foreach ($atletIds as $atletId) {
                PemeriksaanKhususPeserta::create([
                    'pemeriksaan_khusus_id' => $model->id,
                    'peserta_id'            => $atletId,
                    'peserta_type'          => 'App\\Models\\Atlet',
                    'created_by'            => $userId,
                    'updated_by'            => $userId,
                ]);
            }

            // Get Pelatih aktif di kategori ini
            $pelatihIds = CaborKategoriPelatih::where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('pelatih_id')
                ->unique();

            foreach ($pelatihIds as $pelatihId) {
                PemeriksaanKhususPeserta::create([
                    'pemeriksaan_khusus_id' => $model->id,
                    'peserta_id'            => $pelatihId,
                    'peserta_type'          => 'App\\Models\\Pelatih',
                    'created_by'            => $userId,
                    'updated_by'            => $userId,
                ]);
            }

            // Get Tenaga Pendukung aktif di kategori ini
            $tenagaIds = CaborKategoriTenagaPendukung::where('cabor_kategori_id', $caborKategoriId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('tenaga_pendukung_id')
                ->unique();

            foreach ($tenagaIds as $tenagaId) {
                PemeriksaanKhususPeserta::create([
                    'pemeriksaan_khusus_id' => $model->id,
                    'peserta_id'            => $tenagaId,
                    'peserta_type'          => 'App\\Models\\TenagaPendukung',
                    'created_by'            => $userId,
                    'updated_by'            => $userId,
                ]);
            }
        }

        return $model;
    }

    /**
     * Clone template aspek-item tes ke pemeriksaan khusus
     */
    public function cloneFromTemplate($pemeriksaanKhususId, $caborId)
    {
        $userId = Auth::id();

        // Get template untuk cabor ini
        $templateAspek = MstTemplatePemeriksaanKhususAspek::with(['itemTes' => function ($q) {
            $q->orderBy('urutan');
        }])
            ->where('cabor_id', $caborId)
            ->orderBy('urutan')
            ->get();

        if ($templateAspek->isEmpty()) {
            throw new \Exception('Template untuk cabor ini belum ada');
        }

        DB::beginTransaction();
        try {
            // Hapus semua aspek & item tes lama (soft delete) sebelum clone template
            $existingAspek = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaanKhususId)->get();
            
            foreach ($existingAspek as $aspek) {
                // Soft delete semua item tes di aspek ini
                PemeriksaanKhususItemTes::where('pemeriksaan_khusus_aspek_id', $aspek->id)->delete();
            }
            
            // Soft delete semua aspek
            PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaanKhususId)->delete();

            // Clone template ke pemeriksaan khusus
            foreach ($templateAspek as $template) {
                // Create aspek
                $aspek = PemeriksaanKhususAspek::create([
                    'pemeriksaan_khusus_id' => $pemeriksaanKhususId,
                    'nama'                  => $template->nama,
                    'urutan'                => $template->urutan,
                    'mst_template_aspek_id' => $template->id,
                    'created_by'            => $userId,
                    'updated_by'            => $userId,
                ]);

                // Create item tes untuk aspek ini
                foreach ($template->itemTes as $templateItem) {
                    PemeriksaanKhususItemTes::create([
                        'pemeriksaan_khusus_aspek_id' => $aspek->id,
                        'nama'                        => $templateItem->nama,
                        'satuan'                      => $templateItem->satuan,
                        'target_laki_laki'            => $templateItem->target_laki_laki,
                        'target_perempuan'            => $templateItem->target_perempuan,
                        'performa_arah'               => $templateItem->performa_arah,
                        'urutan'                      => $templateItem->urutan,
                        'mst_template_item_tes_id'    => $templateItem->id,
                        'created_by'                  => $userId,
                        'updated_by'                  => $userId,
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Save aspek-item tes secara manual
     */
    public function saveAspekItemTes($pemeriksaanKhususId, $aspekData)
    {
        $userId = Auth::id();

        DB::beginTransaction();
        try {
            // Helper function untuk normalize key
            $normalizeKey = function($nama, $satuan) {
                $nama = strtolower(trim($nama ?? ''));
                $satuan = strtolower(trim($satuan ?? ''));
                return $nama . '|' . ($satuan ?: '');
            };

            // Get semua aspek & item tes yang ada saat ini
            $existingAspekList = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaanKhususId)->get();
            $allExistingItemTes = PemeriksaanKhususItemTes::whereHas('aspek', function ($q) use ($pemeriksaanKhususId) {
                $q->where('pemeriksaan_khusus_id', $pemeriksaanKhususId);
            })->get();
            
            // Buat map item tes yang akan dipertahankan: key = "nama|satuan", value = true
            $itemTesToKeep = [];
            $aspekToKeep = [];
            $processedItemTesIds = [];

            // Process aspek & item tes dari request
            foreach ($aspekData as $index => $aspek) {
                $aspekNama = trim($aspek['nama'] ?? '');
                $aspekToKeep[] = $aspekNama;
                
                // Cari atau create aspek
                $existingAspekModel = $existingAspekList->firstWhere('nama', $aspekNama);
                
                if ($existingAspekModel) {
                    // Update aspek yang sudah ada
                    $existingAspekModel->update([
                        'urutan'                => $aspek['urutan'] ?? ($index + 1),
                        'mst_template_aspek_id' => $aspek['mst_template_aspek_id'] ?? null,
                        'updated_by'            => $userId,
                    ]);
                    $aspekModel = $existingAspekModel;
                } else {
                    // Create aspek baru
                    $aspekModel = PemeriksaanKhususAspek::create([
                        'pemeriksaan_khusus_id' => $pemeriksaanKhususId,
                        'nama'                  => $aspekNama,
                        'urutan'                => $aspek['urutan'] ?? ($index + 1),
                        'mst_template_aspek_id' => $aspek['mst_template_aspek_id'] ?? null,
                        'created_by'            => $userId,
                        'updated_by'            => $userId,
                    ]);
                }

                // Process item tes
                if (isset($aspek['item_tes']) && is_array($aspek['item_tes'])) {
                    foreach ($aspek['item_tes'] as $itemIndex => $item) {
                        $itemNama = trim($item['nama'] ?? '');
                        $itemSatuan = trim($item['satuan'] ?? '');
                        $itemKey = $normalizeKey($itemNama, $itemSatuan);
                        $itemTesToKeep[$itemKey] = true;
                        
                        // Cari item tes yang sudah ada (cek berdasarkan nama + satuan dalam aspek yang sama atau berbeda)
                        $existingItemTes = $allExistingItemTes->first(function ($it) use ($itemNama, $itemSatuan) {
                            return strtolower(trim($it->nama ?? '')) === strtolower($itemNama) 
                                && strtolower(trim($it->satuan ?? '')) === strtolower($itemSatuan);
                        });
                        
                        if ($existingItemTes && !in_array($existingItemTes->id, $processedItemTesIds)) {
                            // Update item tes yang sudah ada (preserve ID untuk menjaga hasil tes)
                            $existingItemTes->update([
                                'pemeriksaan_khusus_aspek_id' => $aspekModel->id,
                                'target_laki_laki'            => $item['target_laki_laki'] ?? null,
                                'target_perempuan'            => $item['target_perempuan'] ?? null,
                                'performa_arah'               => $item['performa_arah'] ?? 'max',
                                'urutan'                      => $item['urutan'] ?? ($itemIndex + 1),
                                'mst_template_item_tes_id'    => $item['mst_template_item_tes_id'] ?? null,
                                'updated_by'                  => $userId,
                            ]);
                            $processedItemTesIds[] = $existingItemTes->id;
                        } else {
                            // Create item tes baru
                            PemeriksaanKhususItemTes::create([
                                'pemeriksaan_khusus_aspek_id' => $aspekModel->id,
                                'nama'                        => $itemNama,
                                'satuan'                      => $itemSatuan ?: null,
                                'target_laki_laki'            => $item['target_laki_laki'] ?? null,
                                'target_perempuan'            => $item['target_perempuan'] ?? null,
                                'performa_arah'               => $item['performa_arah'] ?? 'max',
                                'urutan'                      => $item['urutan'] ?? ($itemIndex + 1),
                                'mst_template_item_tes_id'    => $item['mst_template_item_tes_id'] ?? null,
                                'created_by'                  => $userId,
                                'updated_by'                  => $userId,
                            ]);
                        }
                    }
                }
            }

            // Soft delete aspek yang tidak ada di request
            foreach ($existingAspekList as $existingAspek) {
                if (!in_array($existingAspek->nama, $aspekToKeep)) {
                    $existingAspek->delete();
                }
            }

            // Soft delete item tes yang tidak ada di request
            foreach ($allExistingItemTes as $existingItemTes) {
                if (!in_array($existingItemTes->id, $processedItemTesIds)) {
                    $itemKey = $normalizeKey($existingItemTes->nama, $existingItemTes->satuan);
                    if (!isset($itemTesToKeep[$itemKey])) {
                        $existingItemTes->delete();
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Save as template untuk cabor tertentu
     */
    public function saveAsTemplate($caborId, $aspekData)
    {
        $userId = Auth::id();

        // Hapus template lama jika ada (soft delete)
        MstTemplatePemeriksaanKhususAspek::where('cabor_id', $caborId)->delete();

        DB::beginTransaction();
        try {
            foreach ($aspekData as $index => $aspek) {
                // Create template aspek
                $templateAspek = MstTemplatePemeriksaanKhususAspek::create([
                    'cabor_id'    => $caborId,
                    'nama'        => $aspek['nama'],
                    'urutan'      => $aspek['urutan'] ?? ($index + 1),
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);

                // Create template item tes untuk aspek ini
                if (isset($aspek['item_tes']) && is_array($aspek['item_tes'])) {
                    foreach ($aspek['item_tes'] as $itemIndex => $item) {
                        \App\Models\MstTemplatePemeriksaanKhususItemTes::create([
                            'mst_template_pemeriksaan_khusus_aspek_id' => $templateAspek->id,
                            'nama'                                      => $item['nama'],
                            'satuan'                                    => $item['satuan'] ?? null,
                            'target_laki_laki'                          => $item['target_laki_laki'] ?? null,
                            'target_perempuan'                          => $item['target_perempuan'] ?? null,
                            'performa_arah'                             => $item['performa_arah'] ?? 'max',
                            'urutan'                                    => $item['urutan'] ?? ($itemIndex + 1),
                            'created_by'                                => $userId,
                            'updated_by'                                => $userId,
                        ]);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Save hasil tes per peserta dengan auto-calculation
     */
    public function saveHasilTes($pemeriksaanKhususId, $dataHasilTes)
    {
        $userId = Auth::id();

        DB::beginTransaction();
        try {
            // Get pemeriksaan khusus dengan aspek & item tes
            $pemeriksaanKhusus = PemeriksaanKhusus::with(['aspek.itemTes'])->findOrFail($pemeriksaanKhususId);

            foreach ($dataHasilTes as $pesertaData) {
                $pesertaId = $pesertaData['peserta_id'];

                // Get peserta untuk cek jenis kelamin
                $peserta = PemeriksaanKhususPeserta::with('peserta')->findOrFail($pesertaId);
                $jenisKelamin = $this->getJenisKelaminPeserta($peserta);

                // Save hasil tes per item
                foreach ($pesertaData['item_tes'] as $itemData) {
                    $itemTesId = $itemData['item_tes_id'];
                    $nilaiAktual = $itemData['nilai'] ?? null;

                    // Get item tes untuk ambil target dan performa_arah
                    $itemTes = PemeriksaanKhususItemTes::findOrFail($itemTesId);
                    
                    // Tentukan target berdasarkan jenis kelamin
                    $target = ($jenisKelamin === 'L' || $jenisKelamin === 'Laki-laki') 
                        ? $itemTes->target_laki_laki 
                        : $itemTes->target_perempuan;

                    // Calculate performa
                    $performa = PemeriksaanKhususCalculationService::calculatePerforma(
                        $nilaiAktual,
                        $target,
                        $itemTes->performa_arah
                    );

                    // Get predikat
                    $predikat = PemeriksaanKhususCalculationService::getPredikat(
                        $performa['persentase_performa']
                    );

                    // Save/Update hasil tes
                    PemeriksaanKhususPesertaItemTes::updateOrCreate(
                        [
                            'pemeriksaan_khusus_peserta_id' => $pesertaId,
                            'pemeriksaan_khusus_item_tes_id' => $itemTesId,
                        ],
                        [
                            'pemeriksaan_khusus_id'      => $pemeriksaanKhususId,
                            'nilai'                      => $nilaiAktual,
                            'persentase_performa'        => $performa['persentase_performa'],
                            'persentase_riil'            => $performa['persentase_riil'],
                            'predikat'                   => $predikat,
                            'created_by'                 => $userId,
                            'updated_by'                 => $userId,
                        ]
                    );
                }

                // Calculate dan update nilai aspek
                $this->calculateAndUpdateAspek($pemeriksaanKhususId, $pesertaId, $pemeriksaanKhusus);

                // Calculate dan update nilai keseluruhan
                $this->calculateAndUpdateKeseluruhan($pemeriksaanKhususId, $pesertaId, $pemeriksaanKhusus);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate dan update nilai aspek (rata-rata item tes)
     */
    protected function calculateAndUpdateAspek($pemeriksaanKhususId, $pesertaId, $pemeriksaanKhusus)
    {
        $userId = Auth::id();

        foreach ($pemeriksaanKhusus->aspek as $aspek) {
            // Get semua item tes untuk aspek ini
            $itemTesIds = $aspek->itemTes->pluck('id');

            // Get hasil tes untuk semua item tes di aspek ini
            $hasilTesList = PemeriksaanKhususPesertaItemTes::where('pemeriksaan_khusus_peserta_id', $pesertaId)
                ->whereIn('pemeriksaan_khusus_item_tes_id', $itemTesIds)
                ->whereNotNull('persentase_performa')
                ->get();

            // Calculate rata-rata persentase performa (capped)
            $persentaseList = $hasilTesList->pluck('persentase_performa')->filter(fn($v) => $v !== null)->toArray();
            
            if (empty($persentaseList)) {
                continue; // Skip jika belum ada data
            }

            $nilaiPerforma = PemeriksaanKhususCalculationService::calculateAverage($persentaseList);
            $predikat = PemeriksaanKhususCalculationService::getPredikat($nilaiPerforma);

            // Save/Update nilai aspek
            PemeriksaanKhususPesertaAspek::updateOrCreate(
                [
                    'pemeriksaan_khusus_peserta_id' => $pesertaId,
                    'pemeriksaan_khusus_aspek_id'   => $aspek->id,
                ],
                [
                    'pemeriksaan_khusus_id' => $pemeriksaanKhususId,
                    'nilai_performa'        => $nilaiPerforma,
                    'predikat'              => $predikat,
                ]
            );
        }
    }

    /**
     * Calculate dan update nilai keseluruhan (rata-rata aspek)
     */
    protected function calculateAndUpdateKeseluruhan($pemeriksaanKhususId, $pesertaId, $pemeriksaanKhusus)
    {
        // Get semua nilai aspek untuk peserta ini
        $aspekIds = $pemeriksaanKhusus->aspek->pluck('id');
        $nilaiAspekList = PemeriksaanKhususPesertaAspek::where('pemeriksaan_khusus_peserta_id', $pesertaId)
            ->whereIn('pemeriksaan_khusus_aspek_id', $aspekIds)
            ->whereNotNull('nilai_performa')
            ->get();

        // Calculate rata-rata nilai aspek
        $nilaiList = $nilaiAspekList->pluck('nilai_performa')->filter(fn($v) => $v !== null)->toArray();
        
        if (empty($nilaiList)) {
            return; // Skip jika belum ada data
        }

        $nilaiKeseluruhan = PemeriksaanKhususCalculationService::calculateAverage($nilaiList);
        $predikat = PemeriksaanKhususCalculationService::getPredikat($nilaiKeseluruhan);

        // Save/Update nilai keseluruhan
        PemeriksaanKhususPesertaKeseluruhan::updateOrCreate(
            [
                'pemeriksaan_khusus_peserta_id' => $pesertaId,
            ],
            [
                'pemeriksaan_khusus_id' => $pemeriksaanKhususId,
                'nilai_keseluruhan'     => $nilaiKeseluruhan,
                'predikat'              => $predikat,
            ]
        );
    }

    /**
     * Get jenis kelamin peserta dari polymorphic relationship
     */
    protected function getJenisKelaminPeserta($pemeriksaanKhususPeserta): ?string
    {
        if (!$pemeriksaanKhususPeserta->peserta) {
            return null;
        }

        $peserta = $pemeriksaanKhususPeserta->peserta;

        // Handle different model types
        if (method_exists($peserta, 'jenis_kelamin')) {
            return $peserta->jenis_kelamin;
        }

        // Fallback: cek berdasarkan model type
        $modelType = get_class($peserta);
        
        if (str_contains($modelType, 'Atlet') || str_contains($modelType, 'Pelatih')) {
            return $peserta->jenis_kelamin ?? null;
        }

        return null;
    }
}

