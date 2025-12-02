<?php

namespace App\Repositories;

use App\Http\Requests\PemeriksaanRequest;
use App\Models\Pemeriksaan;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanParameter;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\CaborKategoriTenagaPendukung;
use Illuminate\Support\Facades\Log;

class PemeriksaanRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(Pemeriksaan $model)
    {
        $this->model   = $model;
        $this->request = PemeriksaanRequest::createFromBase(request());
        $this->with    = [
            'cabor',
            'caborKategori',
            'tenagaPendukung',
            'created_by_user',
            'updated_by_user',
        ];
    }

    public function customIndex($data)
    {
        $query = $this->model->with($this->with)
            ->withCount([
                'pemeriksaanParameter as jumlah_parameter',
                'pemeriksaanPeserta as jumlah_peserta',
                'pemeriksaanPeserta as jumlah_atlet' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\Atlet');
                },
                'pemeriksaanPeserta as jumlah_pelatih' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\Pelatih');
                },
                'pemeriksaanPeserta as jumlah_tenaga_pendukung' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\TenagaPendukung');
                },
            ]);

        // Apply filters
        $this->applyFilters($query);

        $sortField = request('sort');
        $order     = request('order', 'asc');

        if ($sortField === 'cabor') {
            $query->join('cabor', 'pemeriksaan.cabor_id', '=', 'cabor.id')
                ->orderBy('cabor.nama', $order)
                ->select('pemeriksaan.*');
        } elseif ($sortField === 'cabor_kategori') {
            $query->join('cabor_kategori', 'pemeriksaan.cabor_kategori_id', '=', 'cabor_kategori.id')
                ->orderBy('cabor_kategori.nama', $order)
                ->select('pemeriksaan.*');
        } elseif ($sortField === 'tenaga_pendukung') {
            $query->join('tenaga_pendukungs', 'pemeriksaan.tenaga_pendukung_id', '=', 'tenaga_pendukungs.id')
                ->orderBy('tenaga_pendukungs.nama', $order)
                ->select('pemeriksaan.*');
        } else {
            // Sort by kolom di tabel pemeriksaan
            $validColumns = ['id', 'cabor_id', 'cabor_kategori_id', 'tenaga_pendukung_id', 'nama_pemeriksaan', 'tanggal_pemeriksaan', 'status', 'created_at', 'updated_at'];
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

        $auth = Auth::user();
        if ($auth->current_role_id == 35) {
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriAtlet', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('atlet_id', $auth->atlet->id);
                });
            });
        }

        $auth = Auth::user();
        if ($auth->current_role_id == 36) {
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriPelatih', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('pelatih_id', $auth->pelatih->id);
                });
            });
        }

        $auth = Auth::user();
        if ($auth->current_role_id == 37) {
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('tenagaPendukung', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('tenaga_pendukung_id', $auth->tenagaPendukung->id);
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
                    'tenaga_pendukung'        => $item->tenagaPendukung?->nama ?? '-',
                    'nama_pemeriksaan'        => $item->nama_pemeriksaan,
                    'tanggal_pemeriksaan'     => $item->tanggal_pemeriksaan,
                    'status'                  => $item->status,
                    'jumlah_parameter'        => $item->jumlah_parameter        ?? 0,
                    'jumlah_peserta'          => $item->jumlah_peserta          ?? 0,
                    'jumlah_atlet'            => $item->jumlah_atlet            ?? 0,
                    'jumlah_pelatih'          => $item->jumlah_pelatih          ?? 0,
                    'jumlah_tenaga_pendukung' => $item->jumlah_tenaga_pendukung ?? 0,
                ];
            });
            $data += [
                'pemeriksaan' => $transformed,
                'total'       => $transformed->count(),
                'currentPage' => 1,
                'perPage'     => -1,
                'search'      => request('search', ''),
                'sort'        => request('sort', ''),
                'order'       => request('order', 'asc'),
            ];

            return $data;
        }
        $items       = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        $transformed = collect($items->items())->map(function ($item) {
            return [
                'id'                      => $item->id,
                'cabor'                   => $item->cabor?->nama           ?? '-',
                'cabor_kategori'          => $item->caborKategori?->nama   ?? '-',
                'tenaga_pendukung'        => $item->tenagaPendukung?->nama ?? '-',
                'nama_pemeriksaan'        => $item->nama_pemeriksaan,
                'tanggal_pemeriksaan'     => $item->tanggal_pemeriksaan,
                'status'                  => $item->status,
                'jumlah_parameter'        => $item->jumlah_parameter        ?? 0,
                'jumlah_peserta'          => $item->jumlah_peserta          ?? 0,
                'jumlah_atlet'            => $item->jumlah_atlet            ?? 0,
                'jumlah_pelatih'          => $item->jumlah_pelatih          ?? 0,
                'jumlah_tenaga_pendukung' => $item->jumlah_tenaga_pendukung ?? 0,
            ];
        });
        $data += [
            'pemeriksaan' => $transformed,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => request('search', ''),
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
     * Get data for mobile API with search and filters
     */
    public function getForMobile($request)
    {
        $query = $this->model->with($this->with)
            ->withCount([
                'pemeriksaanParameter as jumlah_parameter',
                'pemeriksaanPeserta as jumlah_peserta',
                'pemeriksaanPeserta as jumlah_atlet' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\Atlet');
                },
                'pemeriksaanPeserta as jumlah_pelatih' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\Pelatih');
                },
                'pemeriksaanPeserta as jumlah_tenaga_pendukung' => function ($q) {
                    $q->where('peserta_type', 'App\\Models\\TenagaPendukung');
                },
            ]);

        // Apply filters
        $this->applyMobileFilters($query, $request);

        // Apply role-based filtering
        $this->applyRoleFilter($query);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pemeriksaan', 'like', "%{$search}%")
                  ->orWhereHas('cabor', function ($subQ) use ($search) {
                      $subQ->where('nama', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tenagaPendukung', function ($subQ) use ($search) {
                      $subQ->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        // Sort
        $query->orderBy('tanggal_pemeriksaan', 'desc');

        // Pagination
        $perPage = (int) $request->get('per_page', 10);
        $page    = (int) $request->get('page', 1);

        if ($perPage === -1) {
            $all         = $query->get();
            $transformed = $all->map(function ($item) {
                return $this->transformPemeriksaanForMobile($item);
            });

            return [
                'data'        => $transformed,
                'total'       => $transformed->count(),
                'currentPage' => 1,
                'perPage'     => -1,
                'search'      => $request->get('search', ''),
                'filters'     => [
                    'cabor_id'            => $request->get('cabor_id'),
                    'tanggal_pemeriksaan' => $request->get('tanggal_pemeriksaan'),
                ],
            ];
        }

        $items       = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        $transformed = collect($items->items())->map(function ($item) {
            return $this->transformPemeriksaanForMobile($item);
        });

        return [
            'data'        => $transformed,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => $request->get('search', ''),
            'filters'     => [
                'cabor_id'            => $request->get('cabor_id'),
                'tanggal_pemeriksaan' => $request->get('tanggal_pemeriksaan'),
            ],
        ];
    }

    /**
     * Apply mobile-specific filters
     */
    protected function applyMobileFilters($query, $request)
    {
        // Filter by cabor_id
        if ($request->filled('cabor_id') && $request->cabor_id !== 'all') {
            $query->where('cabor_id', $request->cabor_id);
        }

        // Filter by tanggal_pemeriksaan
        if ($request->filled('tanggal_pemeriksaan')) {
            $query->whereDate('tanggal_pemeriksaan', $request->tanggal_pemeriksaan);
        }
    }

    /**
     * Apply role-based filtering
     */
    protected function applyRoleFilter($query)
    {
        $auth = Auth::user();

        if ($auth->current_role_id == 35) { // Atlet
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriAtlet', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('atlet_id', $auth->atlet->id);
                });
            });
        } elseif ($auth->current_role_id == 36) { // Pelatih
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('caborKategoriPelatih', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('pelatih_id', $auth->pelatih->id);
                });
            });
        } elseif ($auth->current_role_id == 37) { // Tenaga Pendukung
            $query->whereHas('caborKategori', function ($sub_query) use ($auth) {
                $sub_query->whereHas('tenagaPendukung', function ($sub_sub_query) use ($auth) {
                    $sub_sub_query->where('tenaga_pendukung_id', $auth->tenagaPendukung->id);
                });
            });
        }
    }

    /**
     * Transform pemeriksaan data for mobile
     */
    protected function transformPemeriksaanForMobile($item)
    {
        return [
            'id'                      => $item->id,
            'nama'                    => $item->nama_pemeriksaan,
            'cabor'                   => $item->cabor?->nama           ?? '-',
            'kategori'                => $item->caborKategori?->nama   ?? '-',
            'tenagaPendukung'         => $item->tenagaPendukung?->nama ?? '-',
            'peserta'                 => $item->jumlah_peserta         ?? 0,
            'tanggal'                 => $item->tanggal_pemeriksaan,
            'status'                  => $this->mapStatus($item->status),
            'jumlah_parameter'        => $item->jumlah_parameter        ?? 0,
            'jumlah_atlet'            => $item->jumlah_atlet            ?? 0,
            'jumlah_pelatih'          => $item->jumlah_pelatih          ?? 0,
            'jumlah_tenaga_pendukung' => $item->jumlah_tenaga_pendukung ?? 0,
        ];
    }

    /**
     * Map status to Indonesian
     */
    protected function mapStatus($status)
    {
        return match ($status) {
            'selesai'  => 'Sudah',
            'sebagian' => 'Sebagian',
            'belum'    => 'Belum',
            default    => $status,
        };
    }

    /**
     * Get cabor list for filter
     */
    public function getCaborList()
    {
        return $this->model->with('cabor')
            ->get()
            ->pluck('cabor.nama')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get peserta for mobile
     */
    public function getPesertaForMobile($pemeriksaanId, $request)
    {
        $pemeriksaan     = $this->model->findOrFail($pemeriksaanId);
        $caborKategoriId = $pemeriksaan->cabor_kategori_id;
        $auth            = Auth::user();

        // Get atlet
        $atletQuery = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\Atlet');

        // Role Atlet: hanya dirinya sendiri
        if ($auth && (int) ($auth->current_role_id) === 35) {
            $authAtletId = optional($auth->atlet)->id;
            $atletQuery->when($authAtletId, function ($q) use ($authAtletId) {
                $q->where('peserta_id', $authAtletId);
            });
        }

        // Get pelatih
        $pelatihQuery = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\Pelatih');

        // Role Atlet: sembunyikan pelatih
        if ($auth && (int) ($auth->current_role_id) === 35) {
            $pelatihQuery->whereRaw('1=0');
        }

        // Get tenaga pendukung
        $tenagaQuery = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\TenagaPendukung');

        // Role Atlet: sembunyikan tenaga pendukung
        if ($auth && (int) ($auth->current_role_id) === 35) {
            $tenagaQuery->whereRaw('1=0');
        }

        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $atletQuery->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
            $pelatihQuery->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
            $tenagaQuery->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $atlet = $atletQuery->get()->map(function ($item) use ($caborKategoriId) {
            $atletModel = $item->peserta;
            $posisi     = $this->getAtletPosisi($atletModel->id, $caborKategoriId);

            return [
                'id'                     => $atletModel->id,
                'pemeriksaan_peserta_id' => $item->id,
                'nama'                   => $atletModel->nama,
                'foto'                   => $atletModel->foto,
                'jenisKelamin'           => $this->mapJenisKelamin($atletModel->jenis_kelamin),
                'usia'                   => $this->calculateAge($atletModel->tanggal_lahir),
                'posisi'                 => $posisi,
                'statusPemeriksaan'      => $item->status?->nama ?? null,
            ];
        });

        $pelatih = $pelatihQuery->get()->map(function ($item) use ($caborKategoriId) {
            $pelatihModel = $item->peserta;
            $jenisPelatih = $this->getPelatihJenis($pelatihModel->id, $caborKategoriId);

            return [
                'id'                     => $pelatihModel->id,
                'pemeriksaan_peserta_id' => $item->id,
                'nama'                   => $pelatihModel->nama,
                'foto'                   => $pelatihModel->foto,
                'jenisKelamin'           => $this->mapJenisKelamin($pelatihModel->jenis_kelamin),
                'usia'                   => $this->calculateAge($pelatihModel->tanggal_lahir),
                'jenisPelatih'           => $jenisPelatih,
                'statusPemeriksaan'      => $item->status?->nama ?? null,
            ];
        });

        $tenaga = $tenagaQuery->get()->map(function ($item) use ($caborKategoriId) {
            $tenagaModel = $item->peserta;
            $jenisTenaga = $this->getTenagaPendukungJenis($tenagaModel->id, $caborKategoriId);

            return [
                'id'                     => $tenagaModel->id,
                'pemeriksaan_peserta_id' => $item->id,
                'nama'                   => $tenagaModel->nama,
                'foto'                   => $tenagaModel->foto,
                'jenisKelamin'           => $this->mapJenisKelamin($tenagaModel->jenis_kelamin),
                'usia'                   => $this->calculateAge($tenagaModel->tanggal_lahir),
                'jenisTenagaPendukung'   => $jenisTenaga,
                'statusPemeriksaan'      => $item->status?->nama ?? null,
            ];
        });

        return [
            'atlet'           => $atlet,
            'pelatih'         => $pelatih,
            'tenagaPendukung' => $tenaga,
        ];
    }

    /**
     * Get atlet posisi
     */
    protected function getAtletPosisi($atletId, $caborKategoriId)
    {
        $posisi = DB::table('cabor_kategori_atlet')
            ->join('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
            ->where('cabor_kategori_atlet.atlet_id', $atletId)
            ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
            ->whereNull('cabor_kategori_atlet.deleted_at')
            ->value('mst_posisi_atlet.nama');

        return $posisi ?? '-';
    }

    /**
     * Get pelatih jenis
     */
    protected function getPelatihJenis($pelatihId, $caborKategoriId)
    {
        $jenis = DB::table('cabor_kategori_pelatih')
            ->join('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
            ->where('cabor_kategori_pelatih.pelatih_id', $pelatihId)
            ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
            ->whereNull('cabor_kategori_pelatih.deleted_at')
            ->value('mst_jenis_pelatih.nama');

        return $jenis ?? '-';
    }

    /**
     * Get tenaga pendukung jenis
     */
    protected function getTenagaPendukungJenis($tenagaId, $caborKategoriId)
    {
        $jenis = DB::table('cabor_kategori_tenaga_pendukung')
            ->join('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
            ->where('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', $tenagaId)
            ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
            ->value('mst_jenis_tenaga_pendukung.nama');

        return $jenis ?? '-';
    }

    /**
     * Get parameter for mobile
     */
    public function getParameterForMobile($pemeriksaanId, $request)
    {
        $query = PemeriksaanParameter::where('pemeriksaan_id', $pemeriksaanId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('mstParameter', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('satuan', 'like', "%{$search}%");
            });
        }

        return $query->with('mstParameter')->orderBy('mst_parameter_id')->get()->map(function ($item) {
            return [
                'id'             => $item->id,
                'nama_parameter' => $item->mstParameter?->nama,
                'satuan'         => $item->mstParameter?->satuan,
            ];
        });
    }

    /**
     * Map jenis kelamin
     */
    protected function mapJenisKelamin($jenisKelamin)
    {
        if ($jenisKelamin === 'L') {
            return 'Laki-laki';
        }
        if ($jenisKelamin === 'P') {
            return 'Perempuan';
        }
        return '-';
    }

    /**
     * Calculate age
     */
    protected function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $tanggalLahir = new Carbon($tanggalLahir);
            $today        = Carbon::today();
            return (int) $tanggalLahir->diffInYears($today);
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Get detail with relations
     */
    public function getDetailWithRelations($id)
    {
        return $this->model->with($this->with)->find($id);
    }

    /**
     * Get parameter detail
     */
    public function getParameterDetail($parameterId)
    {
        return PemeriksaanParameter::find($parameterId);
    }

    /**
     * Get peserta parameter for mobile
     */
    public function getPesertaParameterForMobile($pemeriksaanId, $parameterId, $request)
    {
        $pemeriksaan     = $this->model->findOrFail($pemeriksaanId);
        $caborKategoriId = $pemeriksaan->cabor_kategori_id;

        // Get atlet dengan nilai parameter
        $atletQuery = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\Atlet')
            ->whereHas('pemeriksaanPesertaParameter', function ($q) use ($parameterId) {
                $q->where('pemeriksaan_parameter_id', $parameterId);
            });

        // Get pelatih dengan nilai parameter
        $pelatihQuery = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\Pelatih')
            ->whereHas('pemeriksaanPesertaParameter', function ($q) use ($parameterId) {
                $q->where('pemeriksaan_parameter_id', $parameterId);
            });

        // Get tenaga pendukung dengan nilai parameter
        $tenagaQuery = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', 'App\\Models\\TenagaPendukung')
            ->whereHas('pemeriksaanPesertaParameter', function ($q) use ($parameterId) {
                $q->where('pemeriksaan_parameter_id', $parameterId);
            });

        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $atletQuery->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
            $pelatihQuery->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
            $tenagaQuery->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $atlet = $atletQuery->get()->map(function ($item) use ($caborKategoriId, $parameterId) {
            $atletModel    = $item->peserta;
            $posisi        = $this->getAtletPosisi($atletModel->id, $caborKategoriId);
            $parameterData = $this->getPesertaParameterData($item->id, $parameterId);

            return [
                'id'                     => $atletModel->id,
                'pemeriksaan_peserta_id' => $item->id,
                'nama'                   => $atletModel->nama,
                'foto'                   => $atletModel->foto,
                'jenisKelamin'           => $this->mapJenisKelamin($atletModel->jenis_kelamin),
                'usia'                   => $this->calculateAge($atletModel->tanggal_lahir),
                'posisi'                 => $posisi,
                'nilai'                  => $parameterData['nilai'],
                'status'                 => $this->mapTrendStatus($parameterData['trend']),
            ];
        });

        $pelatih = $pelatihQuery->get()->map(function ($item) use ($caborKategoriId, $parameterId) {
            $pelatihModel  = $item->peserta;
            $jenisPelatih  = $this->getPelatihJenis($pelatihModel->id, $caborKategoriId);
            $parameterData = $this->getPesertaParameterData($item->id, $parameterId);

            return [
                'id'                     => $pelatihModel->id,
                'pemeriksaan_peserta_id' => $item->id,
                'nama'                   => $pelatihModel->nama,
                'foto'                   => $pelatihModel->foto,
                'jenisKelamin'           => $this->mapJenisKelamin($pelatihModel->jenis_kelamin),
                'usia'                   => $this->calculateAge($pelatihModel->tanggal_lahir),
                'jenisPelatih'           => $jenisPelatih,
                'nilai'                  => $parameterData['nilai'],
                'status'                 => $this->mapTrendStatus($parameterData['trend']),
            ];
        });

        $tenaga = $tenagaQuery->get()->map(function ($item) use ($caborKategoriId, $parameterId) {
            $tenagaModel   = $item->peserta;
            $jenisTenaga   = $this->getTenagaPendukungJenis($tenagaModel->id, $caborKategoriId);
            $parameterData = $this->getPesertaParameterData($item->id, $parameterId);

            return [
                'id'                     => $tenagaModel->id,
                'pemeriksaan_peserta_id' => $item->id,
                'nama'                   => $tenagaModel->nama,
                'foto'                   => $tenagaModel->foto,
                'jenisKelamin'           => $this->mapJenisKelamin($tenagaModel->jenis_kelamin),
                'usia'                   => $this->calculateAge($tenagaModel->tanggal_lahir),
                'jenisTenagaPendukung'   => $jenisTenaga,
                'nilai'                  => $parameterData['nilai'],
                'status'                 => $this->mapTrendStatus($parameterData['trend']),
            ];
        });

        return [
            'atlet'           => $atlet,
            'pelatih'         => $pelatih,
            'tenagaPendukung' => $tenaga,
        ];
    }

    /**
     * Get peserta parameter data (nilai dan trend)
     */
    protected function getPesertaParameterData($pemeriksaanPesertaId, $parameterId)
    {
        $data = DB::table('pemeriksaan_peserta_parameter')
            ->where('pemeriksaan_peserta_id', $pemeriksaanPesertaId)
            ->where('pemeriksaan_parameter_id', $parameterId)
            ->first();

        return [
            'nilai' => $data->nilai ?? '-',
            'trend' => $data->trend ?? 'stabil',
        ];
    }

    /**
     * Map trend status to Indonesian
     */
    protected function mapTrendStatus($trend)
    {
        return match ($trend) {
            'stabil'    => 'Stabil',
            'kenaikan'  => 'Kenaikan',
            'penurunan' => 'Penurunan',
            default     => 'Stabil',
        };
    }

    /**
     * Get participant info for pemeriksaan
     */
    public function getParticipantInfo($pesertaId, $pesertaType, $pemeriksaanId)
    {
        try {
            // First, get the pemeriksaan_peserta record
            $pemeriksaanPeserta = PemeriksaanPeserta::where('id', $pesertaId)
                ->where('pemeriksaan_id', $pemeriksaanId)
                ->first();

            if (!$pemeriksaanPeserta) {
                return null;
            }

            $pemeriksaan = Pemeriksaan::find($pemeriksaanId);
            if (!$pemeriksaan) {
                return null;
            }

            $caborKategoriId   = $pemeriksaan->cabor_kategori_id;
            $actualPesertaId   = $pemeriksaanPeserta->peserta_id;
            $actualPesertaType = $pemeriksaanPeserta->peserta_type;

            switch ($actualPesertaType) {
                case 'App\\Models\\Atlet':
                    $atlet = Atlet::with(['media'])
                        ->leftJoin('cabor_kategori_atlet', function ($join) use ($caborKategoriId) {
                            $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                                ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                                ->whereNull('cabor_kategori_atlet.deleted_at');
                        })
                        ->leftJoin('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
                        ->where('atlets.id', $actualPesertaId)
                        ->select(
                            'atlets.*',
                            DB::raw("COALESCE(mst_posisi_atlet.nama, '-') as posisi")
                        )
                        ->first();

                    if (!$atlet) {
                        return null;
                    }

                    return [
                        'id'           => $atlet->id,
                        'nama'         => $atlet->nama,
                        'foto'         => $atlet->foto,
                        'jenisKelamin' => $this->mapJenisKelamin($atlet->jenis_kelamin),
                        'usia'         => $this->calculateAge($atlet->tanggal_lahir),
                        'posisi'       => $atlet->posisi,
                    ];

                case 'App\\Models\\Pelatih':
                    $pelatih = Pelatih::with(['media'])
                        ->leftJoin('cabor_kategori_pelatih', function ($join) use ($caborKategoriId) {
                            $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                                ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
                                ->whereNull('cabor_kategori_pelatih.deleted_at');
                        })
                        ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
                        ->where('pelatihs.id', $actualPesertaId)
                        ->select(
                            'pelatihs.*',
                            DB::raw("COALESCE(mst_jenis_pelatih.nama, '-') as posisi")
                        )
                        ->first();

                    if (!$pelatih) {
                        return null;
                    }

                    return [
                        'id'           => $pelatih->id,
                        'nama'         => $pelatih->nama,
                        'foto'         => $pelatih->foto,
                        'jenisKelamin' => $this->mapJenisKelamin($pelatih->jenis_kelamin),
                        'usia'         => $this->calculateAge($pelatih->tanggal_lahir),
                        'posisi'       => $pelatih->posisi,
                    ];

                case 'App\\Models\\TenagaPendukung':
                    $tenaga = TenagaPendukung::with(['media'])
                        ->leftJoin('cabor_kategori_tenaga_pendukung', function ($join) use ($caborKategoriId) {
                            $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                                ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
                                ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                        })
                        ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
                        ->where('tenaga_pendukungs.id', $actualPesertaId)
                        ->select(
                            'tenaga_pendukungs.*',
                            DB::raw("COALESCE(mst_jenis_tenaga_pendukung.nama, '-') as posisi")
                        )
                        ->first();

                    if (!$tenaga) {
                        return null;
                    }

                    return [
                        'id'           => $tenaga->id,
                        'nama'         => $tenaga->nama,
                        'foto'         => $tenaga->foto,
                        'jenisKelamin' => $this->mapJenisKelamin($tenaga->jenis_kelamin),
                        'usia'         => $this->calculateAge($tenaga->tanggal_lahir),
                        'posisi'       => $tenaga->posisi,
                    ];

                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Error in getParticipantInfo: ' . $e->getMessage(), [
                'peserta_id'     => $pesertaId,
                'peserta_type'   => $pesertaType,
                'pemeriksaan_id' => $pemeriksaanId,
                'exception'      => $e,
            ]);
            return null;
        }
    }

    /**
     * Get participant parameter list
     */
    public function getParticipantParameterList($pemeriksaanId, $pesertaId, $pesertaType)
    {
        try {
            // Get all parameters for this participant using pemeriksaan_peserta.id
            $parameters = DB::table('pemeriksaan_peserta_parameter')
                ->join('pemeriksaan_parameter', 'pemeriksaan_peserta_parameter.pemeriksaan_parameter_id', '=', 'pemeriksaan_parameter.id')
                ->join('mst_parameter', 'pemeriksaan_parameter.mst_parameter_id', '=', 'mst_parameter.id')
                ->where('pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', $pesertaId)
                ->where('pemeriksaan_peserta_parameter.pemeriksaan_id', $pemeriksaanId)
                ->select(
                    'mst_parameter.id',
                    'mst_parameter.nama as nama_parameter',
                    'mst_parameter.satuan',
                    'pemeriksaan_peserta_parameter.nilai',
                    'pemeriksaan_peserta_parameter.trend'
                )
                ->orderBy('mst_parameter.nama')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'            => $item->id,
                        'nama'          => $item->nama_parameter,
                        'parameter'     => $item->nama_parameter . ' (' . $item->satuan . ')',
                        'nilaiTerakhir' => $item->nilai ?? '-',
                        'status'        => $this->mapTrendStatus($item->trend ?? 'stabil'),
                    ];
                });

            return $parameters;
        } catch (\Exception $e) {
            Log::error('Error in getParticipantParameterList: ' . $e->getMessage(), [
                'pemeriksaan_id' => $pemeriksaanId,
                'peserta_id'     => $pesertaId,
                'peserta_type'   => $pesertaType,
                'exception'      => $e,
            ]);
            return collect([]);
        }
    }

    /**
     * Get parameter info
     */
    public function getParameterInfo($parameterId, $pemeriksaanId, $pesertaId, $pesertaType)
    {
        $parameter = DB::table('pemeriksaan_peserta_parameter')
            ->join('pemeriksaan_parameter', 'pemeriksaan_peserta_parameter.pemeriksaan_parameter_id', '=', 'pemeriksaan_parameter.id')
            ->join('mst_parameter', 'pemeriksaan_parameter.mst_parameter_id', '=', 'mst_parameter.id')
            ->where('pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', $pesertaId)
            ->where('pemeriksaan_peserta_parameter.pemeriksaan_id', $pemeriksaanId)
            ->where('mst_parameter.id', $parameterId)
            ->select(
                'mst_parameter.id',
                'mst_parameter.nama as nama_parameter',
                'mst_parameter.satuan',
                'pemeriksaan_peserta_parameter.nilai',
                'pemeriksaan_peserta_parameter.trend'
            )
            ->first();

        if (!$parameter) {
            return null;
        }

        return [
            'id'            => $parameter->id,
            'nama'          => $parameter->nama_parameter,
            'parameter'     => $parameter->nama_parameter . ' (' . $parameter->satuan . ')',
            'nilaiTerakhir' => $parameter->nilai ?? '-',
            'status'        => $this->mapTrendStatus($parameter->trend ?? 'stabil'),
        ];
    }

    /**
     * Get participant parameter chart data
     */
    public function getParticipantParameterChartData($pemeriksaanId, $pesertaId, $pesertaType, $parameterId)
    {
        // First, get the pemeriksaan_peserta record to get the actual participant info
        $pemeriksaanPeserta = PemeriksaanPeserta::where('id', $pesertaId)
            ->where('pemeriksaan_id', $pemeriksaanId)
            ->first();

        if (!$pemeriksaanPeserta) {
            return [
                'chartData'  => [],
                'detailData' => [],
            ];
        }

        $actualPesertaId   = $pemeriksaanPeserta->peserta_id;
        $actualPesertaType = $pemeriksaanPeserta->peserta_type;

        // Get all data for this parameter and participant across all pemeriksaan
        $data = DB::table('pemeriksaan_peserta_parameter')
            ->join('pemeriksaan_peserta', 'pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', '=', 'pemeriksaan_peserta.id')
            ->join('pemeriksaan', 'pemeriksaan_peserta.pemeriksaan_id', '=', 'pemeriksaan.id')
            ->join('pemeriksaan_parameter', 'pemeriksaan_peserta_parameter.pemeriksaan_parameter_id', '=', 'pemeriksaan_parameter.id')
            ->join('mst_parameter', 'pemeriksaan_parameter.mst_parameter_id', '=', 'mst_parameter.id')
            ->where('pemeriksaan_peserta.peserta_id', $actualPesertaId)
            ->where('pemeriksaan_peserta.peserta_type', $actualPesertaType)
            ->where('mst_parameter.id', $parameterId)
            ->select(
                'pemeriksaan_peserta_parameter.nilai',
                'pemeriksaan_peserta_parameter.trend',
                'pemeriksaan.tanggal_pemeriksaan',
                'pemeriksaan.nama_pemeriksaan'
            )
            ->orderBy('pemeriksaan.tanggal_pemeriksaan', 'asc')
            ->get();

        // Format chart data
        $chartData = $data->map(function ($item, $index) {
            return [
                'month' => $this->formatDateForChart($item->tanggal_pemeriksaan),
                'nilai' => $item->nilai ? (float) $item->nilai : null,
                'trend' => $this->mapTrendStatus($item->trend ?? 'stabil'),
            ];
        })->filter(function ($item) {
            return $item['nilai'] !== null;
        })->values();

        // Format detail data
        $detailData = $data->map(function ($item) {
            return [
                'tanggal'     => $this->formatDateForDetail($item->tanggal_pemeriksaan),
                'pemeriksaan' => $item->nama_pemeriksaan,
                'nilai'       => $item->nilai ?? '-',
                'status'      => $this->mapTrendStatus($item->trend ?? 'stabil'),
            ];
        })->values();

        return [
            'chartData'  => $chartData,
            'detailData' => $detailData,
        ];
    }

    /**
     * Get peserta type class
     */
    private function getPesertaTypeClass($pesertaType)
    {
        switch ($pesertaType) {
            case 'atlet':
                return 'App\\Models\\Atlet';
            case 'pelatih':
                return 'App\\Models\\Pelatih';
            case 'tenaga-pendukung':
                return 'App\\Models\\TenagaPendukung';
            default:
                return 'App\\Models\\Atlet';
        }
    }

    /**
     * Format date for chart
     */
    private function formatDateForChart($date)
    {
        return Carbon::parse($date)->format('M');
    }

    /**
     * Format date for detail
     */
    private function formatDateForDetail($date)
    {
        return Carbon::parse($date)->format('d/m');
    }

    /**
     * Get list of cabor for form create pemeriksaan
     * Filtered by user role - peserta only see their cabor
     */
    public function getCaborListForCreate()
    {
        $auth = Auth::user();

        // Non-peserta (Superadmin, Admin) - lihat semua cabor
        if (!in_array($auth->current_role_id, [35, 36, 37])) {
            return Cabor::select('id', 'nama')
                ->orderBy('nama')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'   => $item->id,
                        'nama' => $item->nama,
                    ];
                });
        }

        // Peserta (Atlet, Pelatih, Tenaga Pendukung) - hanya cabor mereka
        $caborIds = collect();

        if ($auth->current_role_id == 35) { // Atlet
            $caborIds = \App\Models\CaborKategoriAtlet::where('atlet_id', $auth->atlet->id)
                ->join('cabor_kategori', 'cabor_kategori_atlet.cabor_kategori_id', '=', 'cabor_kategori.id')
                ->pluck('cabor_kategori.cabor_id')
                ->unique();
        }

        if ($auth->current_role_id == 36) { // Pelatih
            $caborIds = \App\Models\CaborKategoriPelatih::where('pelatih_id', $auth->pelatih->id)
                ->join('cabor_kategori', 'cabor_kategori_pelatih.cabor_kategori_id', '=', 'cabor_kategori.id')
                ->pluck('cabor_kategori.cabor_id')
                ->unique();
        }

        if ($auth->current_role_id == 37) { // Tenaga Pendukung
            $caborIds = CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $auth->tenagaPendukung->id)
                ->join('cabor_kategori', 'cabor_kategori_tenaga_pendukung.cabor_kategori_id', '=', 'cabor_kategori.id')
                ->pluck('cabor_kategori.cabor_id')
                ->unique();
        }

        return Cabor::whereIn('id', $caborIds)
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get()
            ->map(function ($item) {
                return [
                    'id'   => $item->id,
                    'nama' => $item->nama,
                ];
            });
    }

    /**
     * Get list of cabor kategori by cabor ID
     */
    public function getCaborKategoriByCabor($caborId)
    {
        return CaborKategori::where('cabor_id', $caborId)
            ->select('id', 'nama')
            ->get()
            ->map(function ($item) {
                return [
                    'id'   => $item->id,
                    'nama' => $item->nama,
                ];
            });
    }

    /**
     * Get list of tenaga pendukung by cabor kategori ID
     */
    public function getTenagaPendukungByCaborKategori($caborKategoriId)
    {
        return TenagaPendukung::whereHas('caborKategoriTenagaPendukung', function ($query) use ($caborKategoriId) {
            $query->where('cabor_kategori_id', $caborKategoriId);
        })
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get()
            ->map(function ($item) {
                return [
                    'id'   => $item->id,
                    'nama' => $item->nama,
                ];
            });
    }
}
