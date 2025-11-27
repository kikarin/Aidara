<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PemeriksaanParameterRequest;
use App\Models\Pemeriksaan;
use App\Repositories\PemeriksaanParameterRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PemeriksaanParameterController extends Controller
{
    protected $repository;

    public function __construct(PemeriksaanParameterRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create pemeriksaan parameter (attach master parameter to pemeriksaan)
     */
    public function store(PemeriksaanParameterRequest $request, int $pemeriksaanId): JsonResponse
    {
        try {
            $pemeriksaan = Pemeriksaan::find($pemeriksaanId);
            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $data                   = $this->repository->validateRequest($request);
            $data['pemeriksaan_id'] = $pemeriksaan->id;
            $item                   = $this->repository->create($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Parameter pemeriksaan berhasil ditambahkan',
                'data'    => [
                    'id'               => $item->id,
                    'pemeriksaan_id'   => $item->pemeriksaan_id,
                    'mst_parameter_id' => $item->mst_parameter_id,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal membuat parameter pemeriksaan: ' . $e->getMessage(), [
                'exception'       => $e,
                'pemeriksaan_id'  => $pemeriksaanId,
                'user_id'         => auth()->id(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat parameter pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pemeriksaan parameter
     */
    public function update(PemeriksaanParameterRequest $request, int $pemeriksaanId, int $id): JsonResponse
    {
        try {
            $pemeriksaan = Pemeriksaan::find($pemeriksaanId);
            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $existing = $this->repository->getById($id);
            if (!$existing || $existing->pemeriksaan_id != $pemeriksaanId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Parameter pemeriksaan tidak ditemukan',
                ], 404);
            }

            $data                   = $this->repository->validateRequest($request);
            $data['pemeriksaan_id'] = $pemeriksaan->id;
            $this->repository->update($id, $data);
            $updated = $this->repository->getDetailWithRelations($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Parameter pemeriksaan berhasil diperbarui',
                'data'    => [
                    'id'               => $updated->id,
                    'pemeriksaan_id'   => $updated->pemeriksaan_id,
                    'mst_parameter_id' => $updated->mst_parameter_id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui parameter pemeriksaan: ' . $e->getMessage(), [
                'exception'       => $e,
                'pemeriksaan_id'  => $pemeriksaanId,
                'parameter_id'    => $id,
                'user_id'         => auth()->id(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui parameter pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete pemeriksaan parameter
     */
    public function destroy(int $pemeriksaanId, int $id): JsonResponse
    {
        try {
            $existing = $this->repository->getById($id);
            if (!$existing || $existing->pemeriksaan_id != $pemeriksaanId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Parameter pemeriksaan tidak ditemukan',
                ], 404);
            }

            $this->repository->delete($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Parameter pemeriksaan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menghapus parameter pemeriksaan: ' . $e->getMessage(), [
                'exception'       => $e,
                'pemeriksaan_id'  => $pemeriksaanId,
                'parameter_id'    => $id,
                'user_id'         => auth()->id(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus parameter pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
