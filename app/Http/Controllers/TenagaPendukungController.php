<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenagaPendukungRequest;
use App\Imports\TenagaPendukungImport;
use App\Models\TenagaPendukung;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanPesertaParameter;
use App\Repositories\TenagaPendukungRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenagaPendukungController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(TenagaPendukungRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = TenagaPendukungRequest::createFromBase($request);
        $this->initialize();
        $this->commonData['kode_first_menu']  = $this->kode_menu;
        $this->commonData['kode_second_menu'] = null;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store', 'import']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['tenaga_pendukungs'],
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

    public function apiShow($id)
    {
        try {
            // Debug logging
            Log::info('Tenaga Pendukung Controller: apiShow method called', [
                'id' => $id,
            ]);

            $item = $this->repository->getDetailWithRelations($id);

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenaga Pendukung  tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $item,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Tenaga Pendukung  detail: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data Tenaga Pendukung ',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(TenagaPendukungRequest $request)
    {
        $data = $this->repository->validateRequest($request);

        // Preserve kategori_pesertas from request (not in validation rules because it's pivot table)
        if ($request->has('kategori_pesertas')) {
            $data['kategori_pesertas'] = $request->input('kategori_pesertas');
        }

        // Handle file upload if exists
        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file');
        }

        $model = $this->repository->create($data);

        return redirect()->route('tenaga-pendukung.edit', $model->id)->with('success', 'Tenaga Pendukung berhasil ditambahkan!');
    }

    public function update(TenagaPendukungRequest $request, $id)
    {
        try {
            Log::info('TenagaPendukungController: update method called', [
                'id'             => $id,
                'all_data'       => $request->all(),
                'validated_data' => $request->validated(),
            ]);
            $data = $this->repository->validateRequest($request);

            // Preserve kategori_pesertas from request (not in validation rules because it's pivot table)
            if ($request->has('kategori_pesertas')) {
                $data['kategori_pesertas'] = $request->input('kategori_pesertas');
            }

            if ($request->hasFile('file')) {
                $data['file'] = $request->file('file');
            }
            Log::info('TenagaPendukungController: data after validation', [
                'data' => $data,
            ]);
            $model = $this->repository->update($id, $data);

            // Refresh model dengan kategoriPesertas untuk memastikan data terbaru
            $model->refresh();
            $model->load('kategoriPesertas');

            // Pertahankan tab parameter jika ada di request, atau gunakan 'tenaga-pendukung-data' sebagai default
            $tab = $request->input('tab', 'tenaga-pendukung-data');
            return redirect()->route('tenaga-pendukung.edit', $model->id)->with('success', 'Tenaga Pendukung berhasil diperbarui!')->with('tab', $tab);
        } catch (\Exception $e) {
            Log::error('Error updating tenaga pendukung: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui data tenaga pendukung.');
        }
    }

    public function show($id)
    {
        $item = $this->repository->getDetailWithRelations($id);

        return Inertia::render('modules/tenaga-pendukung/Show', [
            'item' => $item,
        ]);
    }

    public function index()
    {
        return Inertia::render('modules/tenaga-pendukung/Index', $this->repository->customIndex([]));
    }

    public function create()
    {
        return Inertia::render('modules/tenaga-pendukung/Create', $this->repository->customCreateEdit([]));
    }

    public function edit($id)
    {
        return Inertia::render('modules/tenaga-pendukung/Edit', $this->repository->customCreateEdit([], $this->repository->getById($id)));
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('tenaga-pendukung.index')->with('success', 'Tenaga Pendukung berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenaga_pendukungs,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Tenaga Pendukung terpilih berhasil dihapus!']);
    }

    public function import(Request $request)
    {
        Log::info('TenagaPendukungController: import method called', [
            'file_name' => $request->file('file')?->getClientOriginalName(),
            'file_size' => $request->file('file')?->getSize(),
        ]);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
        try {
            $import = new TenagaPendukungImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
            Log::info('TenagaPendukungController: import successful', [
                'rows_processed' => $import->getRowCount(),
                'success_count'  => $import->getSuccessCount(),
                'error_count'    => $import->getErrorCount(),
            ]);
            $message = 'Import berhasil! ';
            if ($import->getSuccessCount() > 0) {
                $message .= "Berhasil import {$import->getSuccessCount()} data.";
            }
            if ($import->getErrorCount() > 0) {
                $message .= " {$import->getErrorCount()} data gagal diimport.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => [
                    'total_rows'    => $import->getRowCount(),
                    'success_count' => $import->getSuccessCount(),
                    'error_count'   => $import->getErrorCount(),
                    'errors'        => $import->getErrors(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('TenagaPendukungController: import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal import: '.$e->getMessage(),
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function riwayatPemeriksaan($id)
    {
        $tenagaPendukung = $this->repository->getDetailWithRelations($id);

        return Inertia::render('modules/tenaga-pendukung/RiwayatPemeriksaan', [
            'tenagaPendukung' => $tenagaPendukung,
        ]);
    }

    public function parameterDetail($tenagaPendukungId, $pemeriksaanId)
    {
        $tenagaPendukung = $this->repository->getDetailWithRelations($tenagaPendukungId);

        // Ambil data pemeriksaan
        $pemeriksaan = Pemeriksaan::with(['tenagaPendukung'])
            ->findOrFail($pemeriksaanId);

        // Ambil data parameter pemeriksaan untuk tenaga pendukung ini
        $pemeriksaanPeserta = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', TenagaPendukung::class)
            ->where('peserta_id', $tenagaPendukungId)
            ->first();

        $parameters = [];
        if ($pemeriksaanPeserta) {
            $parameters = PemeriksaanPesertaParameter::with(['pemeriksaanParameter.mstParameter'])
                ->where('pemeriksaan_peserta_id', $pemeriksaanPeserta->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'id'             => $item->id,
                        'nama_parameter' => $item->pemeriksaanParameter->mstParameter->nama ?? '-',
                        'nilai'          => $item->nilai,
                        'trend'          => $item->trend,
                    ];
                });
        }

        return Inertia::render('modules/tenaga-pendukung/ParameterDetail', [
            'tenagaPendukung' => $tenagaPendukung,
            'pemeriksaan'     => [
                'id'                  => $pemeriksaan->id,
                'nama_pemeriksaan'    => $pemeriksaan->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                'tenaga_pendukung'    => $pemeriksaan->tenagaPendukung->nama ?? '-',
                'status'              => $pemeriksaan->status,
            ],
            'parameters' => $parameters,
        ]);
    }

    public function apiRiwayatPemeriksaan($id)
    {
        try {
            $tenagaPendukung = $this->repository->getDetailWithRelations($id);

            // Ambil semua pemeriksaan yang melibatkan tenaga pendukung ini
            $pemeriksaanPeserta = PemeriksaanPeserta::where('peserta_type', TenagaPendukung::class)
                ->where('peserta_id', $id)
                ->with(['pemeriksaan.tenagaPendukung', 'pemeriksaanPesertaParameter'])
                ->get();

            $riwayat = $pemeriksaanPeserta->map(function ($item) {
                return [
                    'id'                  => $item->pemeriksaan->id,
                    'nama_pemeriksaan'    => $item->pemeriksaan->nama_pemeriksaan,
                    'tanggal_pemeriksaan' => $item->pemeriksaan->tanggal_pemeriksaan,
                    'tenaga_pendukung'    => $item->pemeriksaan->tenagaPendukung->nama ?? '-',
                    'status'              => $item->pemeriksaan->status,
                    'jumlah_parameter'    => $item->pemeriksaanPesertaParameter->count(),
                ];
            });

            return response()->json([
                'data' => $riwayat,
                'meta' => [
                    'total'        => $riwayat->count(),
                    'current_page' => 1,
                    'per_page'     => $riwayat->count(),
                    'search'       => '',
                    'sort'         => '',
                    'order'        => 'asc',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching riwayat pemeriksaan: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data riwayat pemeriksaan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store akun tenaga pendukung
     */
    public function storeAkun(Request $request, $id)
    {
        try {
            $tenagaPendukung = $this->repository->getDetailWithRelations($id);

            // Validasi request untuk akun
            $request->validate([
                'akun_email'    => 'required|email|unique:users,email',
                'akun_password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in:password,123456,admin',
            ], [
                'akun_email.required'    => 'Email wajib diisi.',
                'akun_email.email'       => 'Format email tidak valid.',
                'akun_email.unique'      => 'Email sudah digunakan.',
                'akun_password.required' => 'Password wajib diisi.',
                'akun_password.min'      => 'Password minimal 8 karakter.',
                'akun_password.regex'    => 'Password harus mengandung huruf kecil, huruf besar, dan angka.',
                'akun_password.not_in'   => 'Password tidak boleh menggunakan kata yang mudah ditebak.',
            ]);

            // Handle akun creation di repository
            $this->repository->handleTenagaPendukungAkun($tenagaPendukung, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Akun tenaga pendukung berhasil dibuat!',
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating akun tenaga pendukung: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat akun tenaga pendukung: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update akun tenaga pendukung
     */
    public function updateAkun(Request $request, $id)
    {
        try {
            $tenagaPendukung = $this->repository->getDetailWithRelations($id);

            $rules = [
                'akun_email' => 'required|email',
            ];

            if ($tenagaPendukung->users_id) {
                $rules['akun_email'] = 'required|email|unique:users,email,'.$tenagaPendukung->users_id;
            } else {
                $rules['akun_email'] = 'required|email|unique:users,email';
            }

            // Password opsional untuk update
            if ($request->akun_password) {
                $rules['akun_password'] = 'string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in:password,123456,admin';
            }

            $request->validate($rules, [
                'akun_email.required'  => 'Email wajib diisi.',
                'akun_email.email'     => 'Format email tidak valid.',
                'akun_email.unique'    => 'Email sudah digunakan.',
                'akun_password.min'    => 'Password minimal 8 karakter.',
                'akun_password.regex'  => 'Password harus mengandung huruf kecil, huruf besar, dan angka.',
                'akun_password.not_in' => 'Password tidak boleh menggunakan kata yang mudah ditebak.',
            ]);

            // Handle akun update di repository
            $this->repository->handleTenagaPendukungAkun($tenagaPendukung, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Akun tenaga pendukung berhasil diperbarui!',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating akun tenaga pendukung: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui akun tenaga pendukung: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show karakteristik tenaga pendukung
     */
    public function karakteristik()
    {
        return Inertia::render('modules/tenaga-pendukung/Karakteristik');
    }

    /**
     * API untuk mendapatkan data karakteristik tenaga pendukung
     */
    public function apiKarakteristik(Request $request)
    {
        try {
            $data = $this->repository->jumlah_karakteristik([
                'tanggal_awal'  => $request->tanggal_awal,
                'tanggal_akhir' => $request->tanggal_akhir,
            ]);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching karakteristik tenaga pendukung: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data karakteristik tenaga pendukung',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
