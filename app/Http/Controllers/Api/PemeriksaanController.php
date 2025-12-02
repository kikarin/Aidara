<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\PemeriksaanRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PemeriksaanController extends Controller
{
    protected $repository;

    public function __construct(PemeriksaanRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list of pemeriksaan with search and filters for mobile
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Add logging untuk debug
            Log::info('Pemeriksaan Mobile API called', [
                'user'            => auth()->user(),
                'user_id'         => auth()->id(),
                'current_role_id' => auth()->user()->current_role_id ?? 'no role',
                'headers'         => $request->headers->all(),
            ]);

            $data = $this->repository->getForMobile($request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data pemeriksaan berhasil diambil',
                'data'    => $data['data'],
                'meta'    => [
                    'total'        => $data['total'],
                    'current_page' => $data['currentPage'],
                    'per_page'     => $data['perPage'],
                    'search'       => $data['search'],
                    'filters'      => [
                        'cabor_id'            => $data['filters']['cabor_id']            ?? null,
                        'tanggal_pemeriksaan' => $data['filters']['tanggal_pemeriksaan'] ?? null,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail pemeriksaan by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $pemeriksaan = $this->repository->getDetailWithRelations($id);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail pemeriksaan berhasil diambil',
                'data'    => [
                    'id'               => $pemeriksaan->id,
                    'nama_pemeriksaan' => $pemeriksaan->nama_pemeriksaan,
                    'cabor'            => [
                        'id'   => $pemeriksaan->cabor->id   ?? null,
                        'nama' => $pemeriksaan->cabor->nama ?? null,
                    ],
                    'kategori' => [
                        'id'   => $pemeriksaan->caborKategori->id   ?? null,
                        'nama' => $pemeriksaan->caborKategori->nama ?? null,
                    ],
                    'tenaga_pendukung' => [
                        'id'   => $pemeriksaan->tenagaPendukung->id   ?? null,
                        'nama' => $pemeriksaan->tenagaPendukung->nama ?? null,
                    ],
                    'tanggal_pemeriksaan'     => $pemeriksaan->tanggal_pemeriksaan,
                    'status'                  => $pemeriksaan->status,
                    'jumlah_parameter'        => $pemeriksaan->pemeriksaanParameter()->count(),
                    'jumlah_peserta'          => $pemeriksaan->pemeriksaanPeserta()->count(),
                    'jumlah_atlet'            => $pemeriksaan->pemeriksaanPeserta()->where('peserta_type', 'App\\Models\\Atlet')->count(),
                    'jumlah_pelatih'          => $pemeriksaan->pemeriksaanPeserta()->where('peserta_type', 'App\\Models\\Pelatih')->count(),
                    'jumlah_tenaga_pendukung' => $pemeriksaan->pemeriksaanPeserta()->where('peserta_type', 'App\\Models\\TenagaPendukung')->count(),
                    'created_at'              => $pemeriksaan->created_at,
                    'updated_at'              => $pemeriksaan->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil detail pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of cabor for filter options
     */
    public function getCaborList(): JsonResponse
    {
        try {
            $caborList = $this->repository->getCaborList();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data cabor berhasil diambil',
                'data'    => $caborList,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data cabor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of peserta pemeriksaan (grouped by type)
     */
    public function peserta(Request $request, int $pemeriksaanId): JsonResponse
    {
        try {
            $pemeriksaan = $this->repository->getDetailWithRelations($pemeriksaanId);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $pesertaData = $this->repository->getPesertaForMobile($pemeriksaanId, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data peserta pemeriksaan berhasil diambil',
                'data'    => [
                    'pemeriksaan' => [
                        'id'                  => $pemeriksaan->id,
                        'nama_pemeriksaan'    => $pemeriksaan->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                        'status'              => $pemeriksaan->status,
                    ],
                    'atlet'           => $pesertaData['atlet'],
                    'pelatih'         => $pesertaData['pelatih'],
                    'tenagaPendukung' => $pesertaData['tenagaPendukung'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data peserta pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of parameter pemeriksaan
     */
    public function parameter(Request $request, int $pemeriksaanId): JsonResponse
    {
        try {
            $pemeriksaan = $this->repository->getDetailWithRelations($pemeriksaanId);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $parameterData = $this->repository->getParameterForMobile($pemeriksaanId, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data parameter pemeriksaan berhasil diambil',
                'data'    => [
                    'pemeriksaan' => [
                        'id'                  => $pemeriksaan->id,
                        'nama_pemeriksaan'    => $pemeriksaan->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                    ],
                    'parameter' => $parameterData,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data parameter pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail parameter pemeriksaan dengan peserta dan nilai
     */
    public function parameterDetail(Request $request, $pemeriksaanId, $parameterId): JsonResponse
    {
        try {
            // Validate numeric route params and cast to int
            if (!is_numeric($pemeriksaanId) || !is_numeric($parameterId)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Parameter route tidak valid',
                ], 400);
            }
            $pemeriksaanId = (int) $pemeriksaanId;
            $parameterId   = (int) $parameterId;

            $pemeriksaan = $this->repository->getDetailWithRelations($pemeriksaanId);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $parameter = $this->repository->getParameterDetail($parameterId);

            if (!$parameter) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Parameter tidak ditemukan',
                ], 404);
            }

            $pesertaData = $this->repository->getPesertaParameterForMobile($pemeriksaanId, $parameterId, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data detail parameter pemeriksaan berhasil diambil',
                'data'    => [
                    'pemeriksaan' => [
                        'id'                  => $pemeriksaan->id,
                        'nama_pemeriksaan'    => $pemeriksaan->nama_pemeriksaan,
                        'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                    ],
                    'parameter' => [
                        'id'             => $parameter->id,
                        'nama_parameter' => $parameter->nama_parameter,
                        'satuan'         => $parameter->satuan,
                    ],
                    'atlet'           => $pesertaData['atlet'],
                    'pelatih'         => $pesertaData['pelatih'],
                    'tenagaPendukung' => $pesertaData['tenagaPendukung'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data detail parameter pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List parameter pemeriksaan yang dimiliki peserta
     */
    public function pesertaParameterList(Request $request, int $pemeriksaanId, int $pesertaId): JsonResponse
    {
        try {
            // Get participant info (peserta_type will be determined from database)
            $pesertaInfo = $this->repository->getParticipantInfo($pesertaId, null, $pemeriksaanId);

            if (!$pesertaInfo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta tidak ditemukan',
                ], 404);
            }

            // Get parameter list for this participant
            $parameterList = $this->repository->getParticipantParameterList($pemeriksaanId, $pesertaId, null);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data parameter pemeriksaan peserta berhasil diambil',
                'data'    => [
                    'pesertaInfo'   => $pesertaInfo,
                    'parameterList' => $parameterList,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil parameter pemeriksaan peserta: ' . $e->getMessage(), [
                'exception'      => $e,
                'pemeriksaan_id' => $pemeriksaanId,
                'peserta_id'     => $pesertaId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil parameter pemeriksaan peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Grafik parameter pemeriksaan individu setiap peserta
     */
    public function pesertaParameterChart(Request $request, int $pemeriksaanId, int $pesertaId, int $parameterId): JsonResponse
    {
        try {
            // Get participant info (peserta_type will be determined from database)
            $pesertaInfo = $this->repository->getParticipantInfo($pesertaId, null, $pemeriksaanId);

            if (!$pesertaInfo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta tidak ditemukan',
                ], 404);
            }

            // Get parameter info
            $parameterInfo = $this->repository->getParameterInfo($parameterId, $pemeriksaanId, $pesertaId, null);

            if (!$parameterInfo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Parameter pemeriksaan tidak ditemukan',
                ], 404);
            }

            // Get chart data
            $chartData = $this->repository->getParticipantParameterChartData($pemeriksaanId, $pesertaId, null, $parameterId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data grafik parameter pemeriksaan peserta berhasil diambil',
                'data'    => [
                    'pesertaInfo'   => $pesertaInfo,
                    'parameterInfo' => $parameterInfo,
                    'chartData'     => $chartData['chartData'],
                    'detailData'    => $chartData['detailData'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil grafik parameter pemeriksaan peserta: ' . $e->getMessage(), [
                'exception'      => $e,
                'pemeriksaan_id' => $pemeriksaanId,
                'peserta_id'     => $pesertaId,
                'parameter_id'   => $parameterId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil grafik parameter pemeriksaan peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of cabor for form create pemeriksaan
     * Filtered by user role - peserta only see their cabor
     */
    public function getCaborListForCreate(): JsonResponse
    {
        try {
            $caborList = $this->repository->getCaborListForCreate();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data cabor untuk form create berhasil diambil',
                'data'    => $caborList,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data cabor untuk form create: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of cabor kategori by cabor ID
     */
    public function getCaborKategoriByCabor(int $caborId): JsonResponse
    {
        try {
            $caborKategoriList = $this->repository->getCaborKategoriByCabor($caborId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data kategori cabor berhasil diambil',
                'data'    => $caborKategoriList,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data kategori cabor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of tenaga pendukung by cabor kategori ID
     */
    public function getTenagaPendukungByCaborKategori(int $caborKategoriId): JsonResponse
    {
        try {
            $tenagaPendukungList = $this->repository->getTenagaPendukungByCaborKategori($caborKategoriId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data tenaga pendukung berhasil diambil',
                'data'    => $tenagaPendukungList,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data tenaga pendukung: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new pemeriksaan
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'cabor_id'            => 'required|exists:cabor,id',
                'cabor_kategori_id'   => 'required|exists:cabor_kategori,id',
                'tenaga_pendukung_id' => 'required|exists:tenaga_pendukungs,id',
                'nama_pemeriksaan'    => 'required|string|max:200',
                'tanggal_pemeriksaan' => 'required|date',
                'status'              => 'required|in:belum,sebagian,selesai',
            ], [
                'cabor_id.required'            => 'Cabor wajib dipilih.',
                'cabor_id.exists'              => 'Cabor tidak valid.',
                'cabor_kategori_id.required'   => 'Kategori wajib dipilih.',
                'cabor_kategori_id.exists'     => 'Kategori tidak valid.',
                'tenaga_pendukung_id.required' => 'Tenaga pendukung wajib dipilih.',
                'tenaga_pendukung_id.exists'   => 'Tenaga pendukung tidak valid.',
                'nama_pemeriksaan.required'    => 'Nama pemeriksaan wajib diisi.',
                'nama_pemeriksaan.max'         => 'Nama pemeriksaan maksimal 200 karakter.',
                'tanggal_pemeriksaan.required' => 'Tanggal pemeriksaan wajib diisi.',
                'tanggal_pemeriksaan.date'     => 'Tanggal pemeriksaan harus berupa tanggal.',
                'status.required'              => 'Status wajib dipilih.',
                'status.in'                    => 'Status harus berupa: belum, sebagian, atau selesai.',
            ]);

            $data = $request->only([
                'cabor_id',
                'cabor_kategori_id',
                'tenaga_pendukung_id',
                'nama_pemeriksaan',
                'tanggal_pemeriksaan',
                'status',
            ]);

            $pemeriksaan = $this->repository->create($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pemeriksaan berhasil dibuat',
                'data'    => [
                    'id'               => $pemeriksaan->id,
                    'nama_pemeriksaan' => $pemeriksaan->nama_pemeriksaan,
                    'cabor'            => [
                        'id'   => $pemeriksaan->cabor->id   ?? null,
                        'nama' => $pemeriksaan->cabor->nama ?? null,
                    ],
                    'kategori' => [
                        'id'   => $pemeriksaan->caborKategori->id   ?? null,
                        'nama' => $pemeriksaan->caborKategori->nama ?? null,
                    ],
                    'tenaga_pendukung' => [
                        'id'   => $pemeriksaan->tenagaPendukung->id   ?? null,
                        'nama' => $pemeriksaan->tenagaPendukung->nama ?? null,
                    ],
                    'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                    'status'              => $pemeriksaan->status,
                    'created_at'          => $pemeriksaan->created_at,
                    'updated_at'          => $pemeriksaan->updated_at,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal membuat pemeriksaan: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => auth()->id(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pemeriksaan
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $pemeriksaan = $this->repository->getById($id);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            // Validate request
            $request->validate([
                'cabor_id'            => 'required|exists:cabor,id',
                'cabor_kategori_id'   => 'required|exists:cabor_kategori,id',
                'tenaga_pendukung_id' => 'required|exists:tenaga_pendukungs,id',
                'nama_pemeriksaan'    => 'required|string|max:200',
                'tanggal_pemeriksaan' => 'required|date',
                'status'              => 'required|in:belum,sebagian,selesai',
            ], [
                'cabor_id.required'            => 'Cabor wajib dipilih.',
                'cabor_id.exists'              => 'Cabor tidak valid.',
                'cabor_kategori_id.required'   => 'Kategori wajib dipilih.',
                'cabor_kategori_id.exists'     => 'Kategori tidak valid.',
                'tenaga_pendukung_id.required' => 'Tenaga pendukung wajib dipilih.',
                'tenaga_pendukung_id.exists'   => 'Tenaga pendukung tidak valid.',
                'nama_pemeriksaan.required'    => 'Nama pemeriksaan wajib diisi.',
                'nama_pemeriksaan.max'         => 'Nama pemeriksaan maksimal 200 karakter.',
                'tanggal_pemeriksaan.required' => 'Tanggal pemeriksaan wajib diisi.',
                'tanggal_pemeriksaan.date'     => 'Tanggal pemeriksaan harus berupa tanggal.',
                'status.required'              => 'Status wajib dipilih.',
                'status.in'                    => 'Status harus berupa: belum, sebagian, atau selesai.',
            ]);

            $data = $request->only([
                'cabor_id',
                'cabor_kategori_id',
                'tenaga_pendukung_id',
                'nama_pemeriksaan',
                'tanggal_pemeriksaan',
                'status',
            ]);

            $this->repository->update($id, $data);
            $updatedPemeriksaan = $this->repository->getDetailWithRelations($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pemeriksaan berhasil diperbarui',
                'data'    => [
                    'id'               => $updatedPemeriksaan->id,
                    'nama_pemeriksaan' => $updatedPemeriksaan->nama_pemeriksaan,
                    'cabor'            => [
                        'id'   => $updatedPemeriksaan->cabor->id   ?? null,
                        'nama' => $updatedPemeriksaan->cabor->nama ?? null,
                    ],
                    'kategori' => [
                        'id'   => $updatedPemeriksaan->caborKategori->id   ?? null,
                        'nama' => $updatedPemeriksaan->caborKategori->nama ?? null,
                    ],
                    'tenaga_pendukung' => [
                        'id'   => $updatedPemeriksaan->tenagaPendukung->id   ?? null,
                        'nama' => $updatedPemeriksaan->tenagaPendukung->nama ?? null,
                    ],
                    'tanggal_pemeriksaan' => $updatedPemeriksaan->tanggal_pemeriksaan,
                    'status'              => $updatedPemeriksaan->status,
                    'created_at'          => $updatedPemeriksaan->created_at,
                    'updated_at'          => $updatedPemeriksaan->updated_at,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui pemeriksaan: ' . $e->getMessage(), [
                'exception'      => $e,
                'user_id'        => auth()->id(),
                'pemeriksaan_id' => $id,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete pemeriksaan
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $pemeriksaan = $this->repository->getById($id);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $this->repository->delete($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pemeriksaan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menghapus pemeriksaan: ' . $e->getMessage(), [
                'exception'      => $e,
                'user_id'        => auth()->id(),
                'pemeriksaan_id' => $id,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
