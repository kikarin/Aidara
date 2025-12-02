<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TargetLatihanRequest;
use App\Repositories\TargetLatihanRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TargetLatihanController extends Controller
{
    protected $repository;

    public function __construct(TargetLatihanRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * List target latihan per program (mobile)
     */
    public function index(Request $request, int $programId): JsonResponse
    {
        try {
            // Inject filter program_id ke request
            $request->merge(['program_latihan_id' => $programId]);
            $data = $this->repository->customIndex([]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data target latihan berhasil diambil',
                'data'    => $data['data'],
                'meta'    => [
                    'total'        => $data['total'],
                    'current_page' => $data['currentPage'],
                    'per_page'     => $data['perPage'],
                    'search'       => $data['search'],
                    'filters'      => [
                        'jenis_target' => $request->get('jenis_target'),
                        'peruntukan'   => $request->get('peruntukan'),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data target latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail target latihan
     */
    public function show(int $id): JsonResponse
    {
        try {
            $item = $this->repository->getDetailWithRelations($id);

            if (!$item) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target latihan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail target latihan berhasil diambil',
                'data'    => [
                    'id'                   => $item->id,
                    'program_latihan'      => [
                        'id'   => $item->programLatihan->id           ?? null,
                        'nama' => $item->programLatihan->nama_program ?? null,
                    ],
                    'jenis_target'         => $item->jenis_target,
                    'peruntukan'           => $item->peruntukan,
                    'deskripsi'            => $item->deskripsi,
                    'satuan'               => $item->satuan,
                    'nilai_target'         => $item->nilai_target,
                    'created_at'           => $item->created_at,
                    'updated_at'           => $item->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil detail target latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create target latihan (individu/kelompok)
     */
    public function store(TargetLatihanRequest $request): JsonResponse
    {
        try {
            $data = $this->repository->validateRequest($request);
            if ($data['jenis_target'] === 'kelompok') {
                $data['peruntukan'] = null;
            }
            $item = $this->repository->create($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Target latihan berhasil dibuat',
                'data'    => [
                    'id'                   => $item->id,
                    'program_latihan'      => [
                        'id'   => $item->programLatihan->id           ?? null,
                        'nama' => $item->programLatihan->nama_program ?? null,
                    ],
                    'jenis_target'         => $item->jenis_target,
                    'peruntukan'           => $item->peruntukan,
                    'deskripsi'            => $item->deskripsi,
                    'satuan'               => $item->satuan,
                    'nilai_target'         => $item->nilai_target,
                    'created_at'           => $item->created_at,
                    'updated_at'           => $item->updated_at,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal membuat target latihan: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => auth()->id(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat target latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update target latihan
     */
    public function update(TargetLatihanRequest $request, int $id): JsonResponse
    {
        try {
            $existing = $this->repository->getById($id);
            if (!$existing) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target latihan tidak ditemukan',
                ], 404);
            }

            $data = $this->repository->validateRequest($request);
            if ($data['jenis_target'] === 'kelompok') {
                $data['peruntukan'] = null;
            }
            $this->repository->update($id, $data);
            $updated = $this->repository->getDetailWithRelations($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Target latihan berhasil diperbarui',
                'data'    => [
                    'id'                   => $updated->id,
                    'program_latihan'      => [
                        'id'   => $updated->programLatihan->id           ?? null,
                        'nama' => $updated->programLatihan->nama_program ?? null,
                    ],
                    'jenis_target'         => $updated->jenis_target,
                    'peruntukan'           => $updated->peruntukan,
                    'deskripsi'            => $updated->deskripsi,
                    'satuan'               => $updated->satuan,
                    'nilai_target'         => $updated->nilai_target,
                    'created_at'           => $updated->created_at,
                    'updated_at'           => $updated->updated_at,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui target latihan: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => auth()->id(),
                'target_id' => $id,
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui target latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus target latihan
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $existing = $this->repository->getById($id);
            if (!$existing) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target latihan tidak ditemukan',
                ], 404);
            }

            $this->repository->delete($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Target latihan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menghapus target latihan: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => auth()->id(),
                'target_id' => $id,
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus target latihan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
