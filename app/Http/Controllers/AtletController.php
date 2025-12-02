<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtletRequest;
use App\Imports\AtletImport;
use App\Models\Atlet;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanPesertaParameter;
use App\Repositories\AtletRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AtletController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(AtletRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AtletRequest::createFromBase($request);
        $this->initialize();
        $this->commonData['kode_first_menu']  = $this->kode_menu;
        $this->commonData['kode_second_menu'] = null;
    }

    /**
     * Helper function to parse number with comma as decimal separator
     * Handles both Indonesian format (comma) and international format (dot)
     *
     * @param string|null $value
     * @return float|null
     */
    private function parseNumber($value)
    {
        if (empty($value)) {
            return null;
        }

        // Convert to string and trim whitespace
        $strValue = trim((string) $value);
        if (empty($strValue)) {
            return null;
        }

        // Replace comma with dot for decimal separator (Indonesian format)
        // Handle both formats: "15,6" and "15.6"
        $normalizedValue = str_replace(',', '.', $strValue);
        $parsed = (float) $normalizedValue;

        return is_nan($parsed) ? null : $parsed;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index','riwayatPemeriksaan', 'getByAtletId',]),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update' ]),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function index()
    {
        return Inertia::render('modules/atlet/Index');
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['atlets'],
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

    public function show($id)
    {
        $item = $this->repository->getDetailWithRelations($id);

        return Inertia::render('modules/atlet/Show', [
            'item' => $item,
        ]);
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

        return inertia('modules/atlet/Edit', $data);
    }

    public function update()
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $this->request->id]);
        $data   = $this->request->validate($this->request->rules());
        $data   = $this->request->all();
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        $model = $this->repository->update($this->request->id, $data);
        if (! ($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
        }

        // Refresh model dengan kategoriPesertas untuk memastikan data terbaru
        $model->refresh();
        $model->load('kategoriPesertas');

        // Pertahankan tab parameter jika ada di request, atau gunakan 'atlet-data' sebagai default
        $tab = $this->request->input('tab', 'atlet-data');
        return redirect()->route('atlet.edit', $model->id)->with('success', 'Atlet berhasil diperbarui!')->with('tab', $tab);
    }

    public function apiShow($id)
    {
        try {
            // Debug logging
            Log::info('AtletController: apiShow method called', [
                'id' => $id,
            ]);

            $item = $this->repository->getDetailWithRelations($id);

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Atlet tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $item,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching atlet detail: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data atlet',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        Log::info('AtletController: import method called', [
            'file_name' => $request->file('file')?->getClientOriginalName(),
            'file_size' => $request->file('file')?->getSize(),
        ]);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            $import = new AtletImport();
            Excel::import($import, $request->file('file'));

            Log::info('AtletController: import successful', [
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
            Log::error('AtletController: import failed', [
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
        $atlet = $this->repository->getDetailWithRelations($id);

        return Inertia::render('modules/atlet/RiwayatPemeriksaan', [
            'atlet' => $atlet,
        ]);
    }

    public function parameterDetail($atletId, $pemeriksaanId)
    {
        $atlet = $this->repository->getDetailWithRelations($atletId);

        // Ambil data pemeriksaan
        $pemeriksaan = Pemeriksaan::with(['tenagaPendukung'])
            ->findOrFail($pemeriksaanId);

        // Ambil data parameter pemeriksaan untuk atlet ini
        $pemeriksaanPeserta = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', Atlet::class)
            ->where('peserta_id', $atletId)
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

        return Inertia::render('modules/atlet/ParameterDetail', [
            'atlet'       => $atlet,
            'pemeriksaan' => [
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
            $atlet = $this->repository->getDetailWithRelations($id);

            // Ambil semua pemeriksaan yang melibatkan atlet ini
            $pemeriksaanPeserta = PemeriksaanPeserta::where('peserta_type', Atlet::class)
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
     * Handle akun atlet
     */
    public function handleAkun($id)
    {
        try {
            $atlet = $this->repository->getDetailWithRelations($id);

            return Inertia::render('modules/atlet/Edit', [
                'item' => $atlet,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling akun atlet: '.$e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses akun atlet');
        }
    }

    /**
     * Store akun atlet
     */
    public function storeAkun(Request $request, $id)
    {
        try {
            $atlet = $this->repository->getDetailWithRelations($id);

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
            $this->repository->handleAtletAkun($atlet, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Akun atlet berhasil dibuat!',
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating akun atlet: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat akun atlet: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update akun atlet
     */
    public function updateAkun(Request $request, $id)
    {
        try {
            $atlet = $this->repository->getDetailWithRelations($id);

            // Validasi request untuk akun
            $rules = [
                'akun_email' => 'required|email',
            ];

            // Jika ada users_id, validasi email unique kecuali untuk user yang sama
            if ($atlet->users_id) {
                $rules['akun_email'] = 'required|email|unique:users,email,'.$atlet->users_id;
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
            $this->repository->handleAtletAkun($atlet, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Akun atlet berhasil diperbarui!',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating akun atlet: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui akun atlet: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show karakteristik atlet
     */
    public function karakteristik()
    {
        return Inertia::render('modules/atlet/Karakteristik');
    }

    /**
     * API untuk mendapatkan data karakteristik atlet
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
            Log::error('Error fetching karakteristik atlet: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data karakteristik atlet',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk mendapatkan rekap latihan atlet
     */
    public function apiRekapLatihan($id)
    {
        try {
            $atlet = $this->repository->getDetailWithRelations($id);

            if (!$atlet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Atlet tidak ditemukan',
                ], 404);
            }

            // Ambil semua target latihan yang diikuti atlet ini
            $rekapData = DB::table('rencana_latihan_peserta_target')
                ->join('target_latihan', 'rencana_latihan_peserta_target.target_latihan_id', '=', 'target_latihan.id')
                ->join('rencana_latihan', 'rencana_latihan_peserta_target.rencana_latihan_id', '=', 'rencana_latihan.id')
                ->join('program_latihan', 'rencana_latihan.program_latihan_id', '=', 'program_latihan.id')
                ->where('rencana_latihan_peserta_target.peserta_id', $id)
                ->where('rencana_latihan_peserta_target.peserta_type', 'App\\Models\\Atlet')
                ->select(
                    'target_latihan.id as target_id',
                    'target_latihan.deskripsi',
                    'target_latihan.nilai_target',
                    'target_latihan.satuan',
                    'target_latihan.performa_arah',
                    'program_latihan.id as program_id',
                    'program_latihan.nama_program',
                    'rencana_latihan.id as rencana_id',
                    'rencana_latihan.tanggal',
                    'rencana_latihan.materi',
                    'rencana_latihan_peserta_target.nilai',
                    'rencana_latihan_peserta_target.trend'
                )
                ->orderBy('target_latihan.id')
                ->orderBy('rencana_latihan.tanggal')
                ->get();

            // Group by target_id untuk mendapatkan semua rencana latihan per target
            $controller = $this;
            $groupedData = $rekapData->groupBy('target_id')->map(function ($items, $targetId) use ($controller) {
                $firstItem    = $items->first();
                $nilaiTarget  = $controller->parseNumber($firstItem->nilai_target);
                $performaArah = $firstItem->performa_arah ?? 'max';

                // Hitung persentase performa untuk setiap rencana
                $rencanaList = $items->map(function ($item) use ($nilaiTarget, $performaArah, $controller) {
                    $nilaiAktual        = $controller->parseNumber($item->nilai);
                    $persentasePerforma = null;

                    if ($nilaiAktual !== null && $nilaiTarget !== null && $nilaiTarget > 0) {
                        if ($performaArah === 'min') {
                            $persentasePerforma = ($nilaiTarget / $nilaiAktual) * 100;
                        } else {
                            $persentasePerforma = ($nilaiAktual / $nilaiTarget) * 100;
                        }
                    }

                    return [
                        'rencana_id'          => $item->rencana_id,
                        'tanggal'             => $item->tanggal,
                        'materi'              => $item->materi,
                        'nilai'               => $item->nilai,
                        'trend'               => $item->trend,
                        'persentase_performa' => $persentasePerforma !== null ? round($persentasePerforma, 2) : null,
                    ];
                })->values();

                return [
                    'target_id'     => (int) $targetId,
                    'deskripsi'     => $firstItem->deskripsi,
                    'nilai_target'  => $firstItem->nilai_target,
                    'satuan'        => $firstItem->satuan,
                    'performa_arah' => $performaArah,
                    'program_id'    => $firstItem->program_id,
                    'nama_program'  => $firstItem->nama_program,
                    'rencana_list'  => $rencanaList,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data'    => $groupedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching rekap latihan atlet: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data rekap latihan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function apiParameterUmum($id)
    {
        try {
            $atlet = $this->repository->getDetailWithRelations($id);
            if (!$atlet) {
                return response()->json(['success' => false, 'message' => 'Atlet tidak ditemukan'], 404);
            }

            // Ambil SEMUA parameter umum dari master, lalu left join nilai atlet jika ada
            $parameterUmum = DB::table('mst_parameter')
                ->leftJoin('atlet_parameter_umum', function ($join) use ($id) {
                    $join->on('mst_parameter.id', '=', 'atlet_parameter_umum.mst_parameter_id')
                        ->where('atlet_parameter_umum.atlet_id', '=', $id)
                        ->whereNull('atlet_parameter_umum.deleted_at');
                })
                ->whereNull('mst_parameter.deleted_at')
                ->where('mst_parameter.kategori', 'umum')
                ->select(
                    'mst_parameter.id',
                    'mst_parameter.nama',
                    'mst_parameter.satuan',
                    'mst_parameter.nilai_target',
                    'mst_parameter.performa_arah',
                    'atlet_parameter_umum.nilai'
                )
                ->orderBy('mst_parameter.nama')
                ->get();

            return response()->json(['success' => true, 'data' => $parameterUmum]);
        } catch (\Exception $e) {
            Log::error('Error fetching parameter umum atlet: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat mengambil data parameter umum', 'error' => $e->getMessage()], 500);
        }
    }

    public function apiRekapParameterKhusus($id)
    {
        try {
            $atlet = $this->repository->getDetailWithRelations($id);
            if (!$atlet) {
                return response()->json(['success' => false, 'message' => 'Atlet tidak ditemukan'], 404);
            }

            $rekapData = DB::table('pemeriksaan_peserta_parameter')
                ->join('pemeriksaan_peserta', 'pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', '=', 'pemeriksaan_peserta.id')
                ->join('pemeriksaan_parameter', 'pemeriksaan_peserta_parameter.pemeriksaan_parameter_id', '=', 'pemeriksaan_parameter.id')
                ->join('mst_parameter', 'pemeriksaan_parameter.mst_parameter_id', '=', 'mst_parameter.id')
                ->join('pemeriksaan', 'pemeriksaan_parameter.pemeriksaan_id', '=', 'pemeriksaan.id')
                ->where('pemeriksaan_peserta.peserta_id', $id)
                ->where('pemeriksaan_peserta.peserta_type', 'App\\Models\\Atlet')
                ->where('mst_parameter.kategori', 'khusus')
                ->select(
                    'mst_parameter.id as parameter_id',
                    'mst_parameter.nama as nama_parameter',
                    'mst_parameter.satuan',
                    'mst_parameter.nilai_target',
                    'mst_parameter.performa_arah',
                    'pemeriksaan.id as pemeriksaan_id',
                    'pemeriksaan.nama_pemeriksaan',
                    'pemeriksaan.tanggal_pemeriksaan',
                    'pemeriksaan_peserta_parameter.nilai',
                    'pemeriksaan_peserta_parameter.trend'
                )
                ->orderBy('mst_parameter.id')
                ->orderBy('pemeriksaan.tanggal_pemeriksaan')
                ->get();

            $controller = $this;
            $groupedData = $rekapData->groupBy('parameter_id')->map(function ($items, $parameterId) use ($controller) {
                $firstItem    = $items->first();
                $nilaiTarget  = $controller->parseNumber($firstItem->nilai_target);
                $performaArah = $firstItem->performa_arah ?? 'max';

                $pemeriksaanList = $items->map(function ($item) use ($nilaiTarget, $performaArah, $controller) {
                    $nilaiAktual        = $controller->parseNumber($item->nilai);
                    $persentasePerforma = null;
                    if ($nilaiAktual !== null && $nilaiTarget !== null && $nilaiTarget > 0) {
                        if ($performaArah === 'min') {
                            $persentasePerforma = ($nilaiTarget / $nilaiAktual) * 100;
                        } else {
                            $persentasePerforma = ($nilaiAktual / $nilaiTarget) * 100;
                        }
                    }
                    return [
                        'pemeriksaan_id'      => $item->pemeriksaan_id,
                        'tanggal'             => $item->tanggal_pemeriksaan,
                        'nama_pemeriksaan'    => $item->nama_pemeriksaan,
                        'nilai'               => $item->nilai,
                        'trend'               => $item->trend,
                        'persentase_performa' => $persentasePerforma !== null ? round($persentasePerforma, 2) : null,
                    ];
                })->values();

                return [
                    'parameter_id'     => (int) $parameterId,
                    'nama_parameter'   => $firstItem->nama_parameter,
                    'nilai_target'     => $firstItem->nilai_target,
                    'satuan'           => $firstItem->satuan,
                    'performa_arah'    => $performaArah,
                    'pemeriksaan_list' => $pemeriksaanList,
                ];
            })->values();

            return response()->json(['success' => true, 'data' => $groupedData]);
        } catch (\Exception $e) {
            Log::error('Error fetching rekap parameter khusus atlet: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat mengambil data rekap parameter khusus', 'error' => $e->getMessage()], 500);
        }
    }

    // Tambahan: update parameter umum via API
    public function apiUpdateParameterUmum(Request $request, $id)
    {
        try {
            $request->validate([
                'parameter_umum'                    => 'required|array',
                'parameter_umum.*.mst_parameter_id' => 'required|exists:mst_parameter,id',
                'parameter_umum.*.nilai'            => 'nullable|string|max:255',
            ]);

            $this->repository->upsertParameterUmum((int) $id, $request->input('parameter_umum', []));

            return response()->json(['success' => true, 'message' => 'Parameter umum berhasil diperbarui']);
        } catch (\Exception $e) {
            Log::error('Error updating parameter umum atlet: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui parameter umum', 'error' => $e->getMessage()], 500);
        }
    }
}
