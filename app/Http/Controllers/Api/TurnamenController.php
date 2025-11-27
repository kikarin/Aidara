<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\TurnamenRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TurnamenController extends Controller
{
    protected $repository;

    public function __construct(TurnamenRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list of turnamen for mobile
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->repository->getForMobile($request);

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
                        'cabor_id'   => $data['filters']['cabor_id']   ?? null,
                        'start_date' => $data['filters']['start_date'] ?? null,
                        'end_date'   => $data['filters']['end_date']   ?? null,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail turnamen by ID
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

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail turnamen berhasil diambil',
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
                    'periode' => [
                        'mulai'     => $turnamen->tanggal_mulai,
                        'selesai'   => $turnamen->tanggal_selesai,
                        'formatted' => $this->formatPeriode($turnamen->tanggal_mulai, $turnamen->tanggal_selesai),
                    ],
                    'tingkat' => [
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
                    'jumlah_peserta' => $this->getTotalPeserta($turnamen->id),
                    'created_at'     => $turnamen->created_at,
                    'updated_at'     => $turnamen->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil detail turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of cabor for filter
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
     * Get peserta turnamen grouped by type
     */
    public function peserta(Request $request, int $turnamenId): JsonResponse
    {
        try {
            $turnamen = $this->repository->getDetailWithRelations($turnamenId);

            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            $pesertaData = $this->repository->getPesertaForMobile($turnamenId, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data peserta turnamen berhasil diambil',
                'data'    => [
                    'turnamen' => [
                        'id'              => $turnamen->id,
                        'nama'            => $turnamen->nama,
                        'tanggal_mulai'   => $turnamen->tanggal_mulai,
                        'tanggal_selesai' => $turnamen->tanggal_selesai,
                    ],
                    'atlet'           => $pesertaData['atlet'],
                    'pelatih'         => $pesertaData['pelatih'],
                    'tenagaPendukung' => $pesertaData['tenagaPendukung'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in peserta turnamen API: ' . $e->getMessage(), [
                'turnamenId' => $turnamenId,
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data peserta turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format periode tanggal untuk mobile app
     */
    private function formatPeriode($startDate, $endDate): string
    {
        if (!$startDate || !$endDate) {
            return '-';
        }

        $start = new \DateTime($startDate);
        $end   = new \DateTime($endDate);

        $startDay   = $start->format('j');
        $startMonth = $this->getIndonesianMonth($start->format('n'));
        $startYear  = $start->format('Y');

        $endDay   = $end->format('j');
        $endMonth = $this->getIndonesianMonth($end->format('n'));
        $endYear  = $end->format('Y');

        // Jika tahun sama
        if ($startYear === $endYear) {
            // Jika bulan sama
            if ($startMonth === $endMonth) {
                return "{$startDay}-{$endDay} {$startMonth} {$startYear}";
            } else {
                // Jika bulan berbeda
                return "{$startDay} {$startMonth} - {$endDay} {$endMonth} {$startYear}";
            }
        } else {
            // Jika tahun berbeda
            return "{$startDay} {$startMonth} {$startYear} - {$endDay} {$endMonth} {$endYear}";
        }
    }

    /**
     * Get Indonesian month name
     */
    private function getIndonesianMonth($monthNumber): string
    {
        $months = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$monthNumber] ?? '';
    }

    /**
     * Get total peserta count
     */
    private function getTotalPeserta($turnamenId): int
    {
        $counts = $this->repository->getPesertaCount($turnamenId);
        return $counts['atlet'] + $counts['pelatih'] + $counts['tenaga_pendukung'];
    }
}
