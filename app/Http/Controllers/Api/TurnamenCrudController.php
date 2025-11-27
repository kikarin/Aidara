<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TurnamenRequest;
use App\Repositories\TurnamenRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TurnamenCrudController extends Controller
{
    protected $repository;

    public function __construct(TurnamenRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list of turnamen for CRUD
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->repository->getForCrud($request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data turnamen berhasil diambil',
                'data'    => $data['data'],
                'meta'    => [
                    'total'        => $data['total'],
                    'current_page' => $data['currentPage'],
                    'per_page'     => $data['perPage'],
                    'search'       => $data['search'],
                    'filters'      => [
                        'cabor_kategori_id' => $data['filters']['cabor_kategori_id'] ?? null,
                        'tingkat_id'        => $data['filters']['tingkat_id']        ?? null,
                        'start_date'        => $data['filters']['start_date']        ?? null,
                        'end_date'          => $data['filters']['end_date']          ?? null,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in turnamen CRUD index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail turnamen by ID for CRUD
     */
    public function show(int $id): JsonResponse
    {
        try {
            $turnamen = $this->repository->getDetailWithRelations($id);

            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            // Get peserta data
            $pesertaData = $this->repository->getPesertaForCrud($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail turnamen berhasil diambil',
                'data'    => [
                    'id'                => $turnamen->id,
                    'nama'              => $turnamen->nama,
                    'cabor_kategori_id' => $turnamen->cabor_kategori_id,
                    'cabor'             => [
                        'id'   => $turnamen->caborKategori->cabor->id   ?? null,
                        'nama' => $turnamen->caborKategori->cabor->nama ?? null,
                    ],
                    'kategori' => [
                        'id'   => $turnamen->caborKategori->id   ?? null,
                        'nama' => $turnamen->caborKategori->nama ?? null,
                    ],
                    'tanggal_mulai'   => $turnamen->tanggal_mulai,
                    'tanggal_selesai' => $turnamen->tanggal_selesai,
                    'tingkat_id'      => $turnamen->tingkat_id,
                    'tingkat'         => [
                        'id'   => $turnamen->tingkat->id   ?? null,
                        'nama' => $turnamen->tingkat->nama ?? null,
                    ],
                    'lokasi'   => $turnamen->lokasi,
                    'juara_id' => $turnamen->juara_id,
                    'juara'    => [
                        'id'   => $turnamen->juara->id   ?? null,
                        'nama' => $turnamen->juara->nama ?? null,
                    ],
                    'hasil'          => $turnamen->hasil,
                    'evaluasi'       => $turnamen->evaluasi,
                    'peserta'        => $pesertaData,
                    'created_at'     => $turnamen->created_at,
                    'updated_at'     => $turnamen->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in turnamen CRUD show: ' . $e->getMessage(), [
                'turnamen_id' => $id,
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil detail turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new turnamen
     */
    public function store(TurnamenRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data   = $request->validated();
            $userId = auth()->id();

            // Create turnamen
            $turnamen = $this->repository->create([
                'cabor_kategori_id' => $data['cabor_kategori_id'],
                'nama'              => $data['nama'],
                'tanggal_mulai'     => $data['tanggal_mulai'],
                'tanggal_selesai'   => $data['tanggal_selesai'],
                'tingkat_id'        => $data['tingkat_id'],
                'lokasi'            => $data['lokasi'],
                'juara_id'          => $data['juara_id'] ?? null,
                'hasil'             => $data['hasil']    ?? null,
                'evaluasi'          => $data['evaluasi'] ?? null,
                'created_by'        => $userId,
                'updated_by'        => $userId,
            ]);

            // Load relations for response
            $turnamen->load(['caborKategori.cabor', 'tingkat', 'juara']);

            // Sync peserta if provided
            if (!empty($data['atlet_ids']) || !empty($data['pelatih_ids']) || !empty($data['tenaga_pendukung_ids'])) {
                $this->repository->syncPeserta($turnamen->id, [
                    'atlet_ids'            => $data['atlet_ids']            ?? [],
                    'pelatih_ids'          => $data['pelatih_ids']          ?? [],
                    'tenaga_pendukung_ids' => $data['tenaga_pendukung_ids'] ?? [],
                ]);
            }

            DB::commit();

            // Log activity
            activity()->event('Create Turnamen')->performedOn($turnamen)->log('Turnamen');

            return response()->json([
                'status'  => 'success',
                'message' => 'Turnamen berhasil dibuat',
                'data'    => [
                    'id'    => $turnamen->id,
                    'nama'  => $turnamen->nama,
                    'cabor' => [
                        'id'   => $turnamen->caborKategori->cabor->id   ?? null,
                        'nama' => $turnamen->caborKategori->cabor->nama ?? null,
                    ],
                    'kategori' => [
                        'id'   => $turnamen->caborKategori->id   ?? null,
                        'nama' => $turnamen->caborKategori->nama ?? null,
                    ],
                    'tanggal_mulai'   => $turnamen->tanggal_mulai,
                    'tanggal_selesai' => $turnamen->tanggal_selesai,
                    'tingkat'         => [
                        'id'   => $turnamen->tingkat->id   ?? null,
                        'nama' => $turnamen->tingkat->nama ?? null,
                    ],
                    'lokasi' => $turnamen->lokasi,
                    'juara'  => [
                        'id'   => $turnamen->juara->id   ?? null,
                        'nama' => $turnamen->juara->nama ?? null,
                    ],
                    'hasil'          => $turnamen->hasil,
                    'evaluasi'       => $turnamen->evaluasi,
                    'created_at'     => $turnamen->created_at,
                    'updated_at'     => $turnamen->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating turnamen: ' . $e->getMessage(), [
                'data'  => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update turnamen
     */
    public function update(TurnamenRequest $request, int $id): JsonResponse
    {
        try {
            $turnamen = $this->repository->find($id);

            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            DB::beginTransaction();

            $data   = $request->validated();
            $userId = auth()->id();

            // Update turnamen
            $turnamen->update([
                'cabor_kategori_id' => $data['cabor_kategori_id'],
                'nama'              => $data['nama'],
                'tanggal_mulai'     => $data['tanggal_mulai'],
                'tanggal_selesai'   => $data['tanggal_selesai'],
                'tingkat_id'        => $data['tingkat_id'],
                'lokasi'            => $data['lokasi'],
                'juara_id'          => $data['juara_id'] ?? null,
                'hasil'             => $data['hasil']    ?? null,
                'evaluasi'          => $data['evaluasi'] ?? null,
                'updated_by'        => $userId,
            ]);

            // Load relations for response
            $turnamen->load(['caborKategori.cabor', 'tingkat', 'juara']);

            // Sync peserta if provided
            if (isset($data['atlet_ids']) || isset($data['pelatih_ids']) || isset($data['tenaga_pendukung_ids'])) {
                $this->repository->syncPeserta($turnamen->id, [
                    'atlet_ids'            => $data['atlet_ids']            ?? [],
                    'pelatih_ids'          => $data['pelatih_ids']          ?? [],
                    'tenaga_pendukung_ids' => $data['tenaga_pendukung_ids'] ?? [],
                ]);
            }

            DB::commit();

            // Log activity
            activity()->event('Update Turnamen')->performedOn($turnamen)->log('Turnamen');

            return response()->json([
                'status'  => 'success',
                'message' => 'Turnamen berhasil diperbarui',
                'data'    => [
                    'id'    => $turnamen->id,
                    'nama'  => $turnamen->nama,
                    'cabor' => [
                        'id'   => $turnamen->caborKategori->cabor->id   ?? null,
                        'nama' => $turnamen->caborKategori->cabor->nama ?? null,
                    ],
                    'kategori' => [
                        'id'   => $turnamen->caborKategori->id   ?? null,
                        'nama' => $turnamen->caborKategori->nama ?? null,
                    ],
                    'tanggal_mulai'   => $turnamen->tanggal_mulai,
                    'tanggal_selesai' => $turnamen->tanggal_selesai,
                    'tingkat'         => [
                        'id'   => $turnamen->tingkat->id   ?? null,
                        'nama' => $turnamen->tingkat->nama ?? null,
                    ],
                    'lokasi' => $turnamen->lokasi,
                    'juara'  => [
                        'id'   => $turnamen->juara->id   ?? null,
                        'nama' => $turnamen->juara->nama ?? null,
                    ],
                    'hasil'          => $turnamen->hasil,
                    'evaluasi'       => $turnamen->evaluasi,
                    'created_at'     => $turnamen->created_at,
                    'updated_at'     => $turnamen->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating turnamen: ' . $e->getMessage(), [
                'turnamen_id' => $id,
                'data'        => $request->all(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete turnamen
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $turnamen = $this->repository->find($id);

            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            DB::beginTransaction();

            // Delete related peserta first
            $turnamen->peserta()->delete();
            $turnamen->pelatihPeserta()->delete();
            $turnamen->tenagaPendukungPeserta()->delete();

            // Delete turnamen
            $turnamen->delete();

            DB::commit();

            // Log activity
            activity()->event('Delete Turnamen')->performedOn($turnamen)->log('Turnamen');

            return response()->json([
                'status'  => 'success',
                'message' => 'Turnamen berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting turnamen: ' . $e->getMessage(), [
                'turnamen_id' => $id,
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }
}
