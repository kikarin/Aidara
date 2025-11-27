<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanPesertaParameter;
use App\Models\PemeriksaanParameter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PemeriksaanPesertaParameterController extends Controller
{
    /**
     * Get parameter pemeriksaan untuk mobile
     */
    public function getParameterPemeriksaan(Request $request, int $pemeriksaanId): JsonResponse
    {
        try {
            $pemeriksaan = Pemeriksaan::find($pemeriksaanId);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            $parameters = PemeriksaanParameter::with('mstParameter')
                ->where('pemeriksaan_id', $pemeriksaanId)
                ->get()
                ->map(function ($param) {
                    return [
                        'id'             => $param->id,
                        'nama_parameter' => $param->mstParameter?->nama   ?? '-',
                        'satuan'         => $param->mstParameter?->satuan ?? '-',
                    ];
                });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data parameter pemeriksaan berhasil diambil',
                'data'    => $parameters,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil parameter pemeriksaan: ' . $e->getMessage(), [
                'exception'      => $e,
                'pemeriksaan_id' => $pemeriksaanId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil parameter pemeriksaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get peserta pemeriksaan dengan parameter values untuk mobile
     */
    public function getPesertaWithParameters(Request $request, int $pemeriksaanId, string $jenisPeserta): JsonResponse
    {
        try {
            $pemeriksaan = Pemeriksaan::find($pemeriksaanId);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            // Map jenis peserta to model class
            $pesertaType = match ($jenisPeserta) {
                'atlet'            => 'App\\Models\\Atlet',
                'pelatih'          => 'App\\Models\\Pelatih',
                'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
                default            => 'App\\Models\\Atlet',
            };

            // Get peserta pemeriksaan
            $pesertaList = PemeriksaanPeserta::with(['peserta', 'status', 'pemeriksaanPesertaParameter'])
                ->where('pemeriksaan_id', $pemeriksaanId)
                ->where('peserta_type', $pesertaType)
                ->get()
                ->map(function ($peserta) {
                    $pesertaData  = $peserta->peserta;
                    $jenisKelamin = $this->mapJenisKelamin($pesertaData?->jenis_kelamin);
                    $usia         = $this->calculateAge($pesertaData?->tanggal_lahir);

                    return [
                        'id'         => $peserta->id,
                        'peserta_id' => $peserta->peserta_id,
                        'peserta'    => [
                            'nama'          => $pesertaData?->nama ?? '-',
                            'jenis_kelamin' => $jenisKelamin,
                            'usia'          => $usia,
                            'foto'          => $pesertaData?->foto ?? null,
                        ],
                        'status' => [
                            'id'   => $peserta->status?->id   ?? null,
                            'nama' => $peserta->status?->nama ?? null,
                        ],
                        'ref_status_pemeriksaan_id' => $peserta->ref_status_pemeriksaan_id,
                        'catatan_umum'              => $peserta->catatan_umum,
                        'parameters'                => $peserta->pemeriksaanPesertaParameter->map(function ($param) {
                            return [
                                'parameter_id' => $param->pemeriksaan_parameter_id,
                                'nilai'        => $param->nilai ?? '',
                                'trend'        => $param->trend ?? 'stabil',
                            ];
                        }),
                    ];
                });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data peserta dengan parameter berhasil diambil',
                'data'    => $pesertaList,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil peserta dengan parameter: ' . $e->getMessage(), [
                'exception'      => $e,
                'pemeriksaan_id' => $pemeriksaanId,
                'jenis_peserta'  => $jenisPeserta,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil peserta dengan parameter: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update parameter peserta pemeriksaan
     */
    public function bulkUpdateParameterPeserta(Request $request, int $pemeriksaanId): JsonResponse
    {
        try {
            $pemeriksaan = Pemeriksaan::find($pemeriksaanId);

            if (!$pemeriksaan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            // Validate request
            $request->validate([
                'data'                             => 'required|array',
                'data.*.peserta_id'                => 'required|integer|exists:pemeriksaan_peserta,id',
                'data.*.status'                    => 'nullable|integer|exists:ref_status_pemeriksaan,id',
                'data.*.catatan'                   => 'nullable|string',
                'data.*.parameters'                => 'required|array',
                'data.*.parameters.*.parameter_id' => 'required|integer|exists:pemeriksaan_parameter,id',
                'data.*.parameters.*.nilai'        => 'nullable|string',
                'data.*.parameters.*.trend'        => 'required|in:stabil,kenaikan,penurunan',
            ], [
                'data.required'                             => 'Data wajib diisi',
                'data.*.peserta_id.required'                => 'ID peserta wajib diisi',
                'data.*.peserta_id.exists'                  => 'Peserta pemeriksaan tidak valid',
                'data.*.status.exists'                      => 'Status pemeriksaan tidak valid',
                'data.*.parameters.required'                => 'Parameter wajib diisi',
                'data.*.parameters.*.parameter_id.required' => 'ID parameter wajib diisi',
                'data.*.parameters.*.parameter_id.exists'   => 'Parameter pemeriksaan tidak valid',
                'data.*.parameters.*.trend.required'        => 'Trend wajib diisi',
                'data.*.parameters.*.trend.in'              => 'Trend harus berupa: stabil, kenaikan, atau penurunan',
            ]);

            DB::beginTransaction();

            try {
                foreach ($request->data as $pesertaData) {
                    // Update peserta pemeriksaan status dan catatan
                    PemeriksaanPeserta::where('id', $pesertaData['peserta_id'])
                        ->where('pemeriksaan_id', $pemeriksaanId)
                        ->update([
                            'ref_status_pemeriksaan_id' => $pesertaData['status'],
                            'catatan_umum'              => $pesertaData['catatan'],
                            'updated_by'                => Auth::id(),
                        ]);

                    // Update atau create parameter values
                    foreach ($pesertaData['parameters'] as $paramData) {
                        PemeriksaanPesertaParameter::updateOrCreate(
                            [
                                'pemeriksaan_id'           => $pemeriksaanId,
                                'pemeriksaan_peserta_id'   => $pesertaData['peserta_id'],
                                'pemeriksaan_parameter_id' => $paramData['parameter_id'],
                            ],
                            [
                                'nilai'      => $paramData['nilai'],
                                'trend'      => $paramData['trend'],
                                'updated_by' => Auth::id(),
                            ]
                        );
                    }
                }

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data parameter peserta berhasil diperbarui',
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal bulk update parameter peserta: ' . $e->getMessage(), [
                'exception'      => $e,
                'pemeriksaan_id' => $pemeriksaanId,
                'user_id'        => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data parameter peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single peserta parameter values
     */
    public function getPesertaParameter(Request $request, int $pemeriksaanId, int $pesertaId): JsonResponse
    {
        try {
            $peserta = PemeriksaanPeserta::with(['peserta', 'status', 'pemeriksaanPesertaParameter.pemeriksaanParameter.mstParameter'])
                ->where('id', $pesertaId)
                ->where('pemeriksaan_id', $pemeriksaanId)
                ->first();

            if (!$peserta) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta pemeriksaan tidak ditemukan',
                ], 404);
            }

            $pesertaData  = $peserta->peserta;
            $jenisKelamin = $this->mapJenisKelamin($pesertaData?->jenis_kelamin);
            $usia         = $this->calculateAge($pesertaData?->tanggal_lahir);

            $data = [
                'id'         => $peserta->id,
                'peserta_id' => $peserta->peserta_id,
                'peserta'    => [
                    'nama'          => $pesertaData?->nama ?? '-',
                    'jenis_kelamin' => $jenisKelamin,
                    'usia'          => $usia,
                    'foto'          => $pesertaData?->foto ?? null,
                ],
                'status' => [
                    'id'   => $peserta->status?->id   ?? null,
                    'nama' => $peserta->status?->nama ?? null,
                ],
                'ref_status_pemeriksaan_id' => $peserta->ref_status_pemeriksaan_id,
                'catatan_umum'              => $peserta->catatan_umum,
                'parameters'                => $peserta->pemeriksaanPesertaParameter->map(function ($param) {
                    return [
                        'parameter_id'   => $param->pemeriksaan_parameter_id,
                        'nama_parameter' => $param->pemeriksaanParameter?->mstParameter?->nama   ?? '-',
                        'satuan'         => $param->pemeriksaanParameter?->mstParameter?->satuan ?? '-',
                        'nilai'          => $param->nilai                                        ?? '',
                        'trend'          => $param->trend                                        ?? 'stabil',
                    ];
                }),
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Data parameter peserta berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil parameter peserta: ' . $e->getMessage(), [
                'exception'      => $e,
                'pemeriksaan_id' => $pemeriksaanId,
                'peserta_id'     => $pesertaId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil parameter peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map jenis kelamin
     */
    private function mapJenisKelamin($jenisKelamin)
    {
        return match ($jenisKelamin) {
            'L'     => 'Laki-laki',
            'P'     => 'Perempuan',
            default => '-',
        };
    }

    /**
     * Calculate age
     */
    private function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $birth = new \DateTime($tanggalLahir);
            $today = new \DateTime();
            return (int) $birth->diff($today)->y;
        } catch (\Exception $e) {
            return '-';
        }
    }
}
