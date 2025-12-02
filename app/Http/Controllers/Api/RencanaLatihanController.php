<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RencanaLatihan;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use App\Repositories\RencanaLatihanRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TargetLatihan;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;

class RencanaLatihanController extends Controller
{
    protected $repository;

    public function __construct(RencanaLatihanRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list of rencana latihan for mobile
     */
    public function index(Request $request, int $programId): JsonResponse
    {
        try {
            $data = $this->repository->getForMobile($request, $programId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data rencana latihan berhasil diambil',
                'data'    => $data['data'],
                'meta'    => [
                    'total'        => $data['total'],
                    'current_page' => $data['currentPage'],
                    'per_page'     => $data['perPage'],
                    'search'       => $data['search'],
                    'filters'      => $data['filters'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data rencana latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail rencana latihan by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $rencana = $this->repository->getDetailWithRelations($id);

            if (!$rencana) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Rencana latihan tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail rencana latihan berhasil diambil',
                'data'    => [
                    'id'            => $rencana->id,
                    'tanggal'       => $rencana->tanggal,
                    'materi'        => $rencana->materi,
                    'lokasi'        => $rencana->lokasi_latihan,
                    'catatan'       => $rencana->catatan,
                    'targetLatihan' => $rencana->targetLatihan->map(function ($target) {
                        return [
                            'id'           => $target->id,
                            'deskripsi'    => $target->deskripsi,
                            'nilai_target' => $target->nilai_target,
                            'satuan'       => $target->satuan,
                        ];
                    }),
                    'jumlah_atlet'            => $rencana->atlets->count(),
                    'jumlah_pelatih'          => $rencana->pelatihs->count(),
                    'jumlah_tenaga_pendukung' => $rencana->tenagaPendukung->count(),
                    'created_at'              => $rencana->created_at,
                    'updated_at'              => $rencana->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil detail rencana latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Daftar peserta rencana latihan (grouped untuk mobile)
     */
    public function participants(Request $request, int $rencanaId): JsonResponse
    {
        try {
            $pesertaData = $this->repository->getPesertaForMobile($rencanaId, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data peserta berhasil diambil',
                'data'    => $pesertaData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk set kehadiran peserta rencana latihan (Mobile)
     * Body: { ids: number[], kehadiran: string, by?: 'entity'|'pivot' }
     * jenisPeserta: atlet | pelatih | tenaga-pendukung
     */
    public function bulkSetKehadiran(Request $request, int $rencanaId, string $jenisPeserta): JsonResponse
    {
        try {
            $request->validate([
                'ids'       => 'required|array|min:1',
                'ids.*'     => 'integer',
                'kehadiran' => 'required|in:Hadir,Tidak Hadir,Izin,Sakit',
                'by'        => 'nullable|in:entity,pivot',
            ], [
                'kehadiran.in' => 'Kehadiran harus salah satu dari: Hadir, Tidak Hadir, Izin, Sakit.',
            ]);

            $jenis = strtolower($jenisPeserta);
            $by    = $request->get('by', 'entity');
            $ids   = $request->ids;

            switch ($jenis) {
                case 'atlet':
                    \DB::table('rencana_latihan_atlet')
                        ->where('rencana_latihan_id', $rencanaId)
                        ->whereIn('atlet_id', $ids)
                        ->update([
                            'kehadiran' => $request->kehadiran,
                        ]);
                    break;

                case 'pelatih':
                    $query = \DB::table('rencana_latihan_pelatih')
                        ->where('rencana_latihan_id', $rencanaId);
                    if ($by === 'pivot') {
                        $query->whereIn('id', $ids);
                    } else {
                        $query->whereIn('pelatih_id', $ids);
                    }
                    $query->update([
                        'kehadiran' => $request->kehadiran,
                    ]);
                    break;

                case 'tenaga-pendukung':
                case 'tenaga_pendukung':
                    $query = \DB::table('rencana_latihan_tenaga_pendukung')
                        ->where('rencana_latihan_id', $rencanaId);
                    if ($by === 'pivot') {
                        $query->whereIn('id', $ids);
                    } else {
                        $query->whereIn('tenaga_pendukung_id', $ids);
                    }
                    $query->update([
                        'kehadiran' => $request->kehadiran,
                    ]);
                    break;

                default:
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Jenis peserta tidak valid',
                    ], 422);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Kehadiran peserta berhasil diperbarui',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui kehadiran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get full photo URL
     */
    private function getFullPhotoUrl($photoPath): ?string
    {
        if (!$photoPath) {
            return null;
        }

        // Check if it's already a full URL
        if (filter_var($photoPath, FILTER_VALIDATE_URL)) {
            return $photoPath;
        }

        // Return full URL
        return url('storage/' . $photoPath);
    }

    /**
     * Map jenis kelamin
     */
    private function mapJenisKelamin($jenisKelamin): string
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
    private function calculateAge($tanggalLahir): int|string
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $tanggalLahir = new \Carbon\Carbon($tanggalLahir);
            $today        = \Carbon\Carbon::today();
            return (int) $tanggalLahir->diffInYears($today);
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Get list of targets for rencana latihan
     */
    public function targets(Request $request, int $rencanaId): JsonResponse
    {
        try {
            $rencana = RencanaLatihan::with(['targetLatihan', 'programLatihan'])->findOrFail($rencanaId);

            // Pisahkan target berdasarkan jenis
            $targetIndividu = $rencana->targetLatihan->filter(function ($target) {
                return $target->jenis_target === 'individu';
            })->map(function ($target) {
                return [
                    'id'      => $target->id,
                    'nama'    => $target->deskripsi,
                    'target'  => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                    'peserta' => ucfirst($target->peruntukan ?? 'Semua'),
                ];
            })->values();

            $targetKelompok = $rencana->targetLatihan->filter(function ($target) {
                return $target->jenis_target === 'kelompok';
            })->map(function ($target) use ($rencanaId) {
                // Ambil nilai dan trend dari pivot table untuk target kelompok
                $pivotData = DB::table('rencana_latihan_target_latihan')
                    ->where('rencana_latihan_id', $rencanaId)
                    ->where('target_latihan_id', $target->id)
                    ->first();

                return [
                    'id'     => $target->id,
                    'nama'   => $target->deskripsi,
                    'target' => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                    'nilai'  => $pivotData->nilai ?? null,
                    'trend'  => $pivotData->trend ?? null,
                ];
            })->values();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data target latihan berhasil diambil',
                'data'    => [
                    'rencana' => [
                        'id'      => $rencana->id,
                        'materi'  => $rencana->materi,
                        'tanggal' => $rencana->tanggal,
                    ],
                    'targetIndividu' => $targetIndividu,
                    'targetKelompok' => $targetKelompok,
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
     * Get detail target latihan
     */
    public function targetDetail(Request $request, int $rencanaId, int $targetId): JsonResponse
    {
        try {
            $rencana = RencanaLatihan::with(['programLatihan'])->findOrFail($rencanaId);
            $target  = $rencana->targetLatihan()->where('target_latihan.id', $targetId)->first();

            if (!$target) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target latihan tidak ditemukan',
                ], 404);
            }

            // Ambil peserta yang sudah memiliki nilai untuk target ini
            $pesertaData = [];

            if ($target->peruntukan === 'atlet' || $target->peruntukan === 'semua') {
                $atletData = DB::table('rencana_latihan_peserta_target')
                    ->join('atlets', 'rencana_latihan_peserta_target.peserta_id', '=', 'atlets.id')
                    ->leftJoin('cabor_kategori_atlet', function ($join) use ($rencana) {
                        $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                            ->where('cabor_kategori_atlet.cabor_kategori_id', $rencana->programLatihan->cabor_kategori_id)
                            ->whereNull('cabor_kategori_atlet.deleted_at');
                    })
                    ->leftJoin('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
                    ->where('rencana_latihan_peserta_target.rencana_latihan_id', $rencanaId)
                    ->where('rencana_latihan_peserta_target.target_latihan_id', $targetId)
                    ->where('rencana_latihan_peserta_target.peserta_type', 'App\\Models\\Atlet')
                    ->select(
                        'atlets.id',
                        'atlets.nama',
                        'atlets.jenis_kelamin',
                        'atlets.tanggal_lahir',
                        DB::raw("COALESCE(mst_posisi_atlet.nama, '-') as posisi"),
                        'rencana_latihan_peserta_target.nilai',
                        'rencana_latihan_peserta_target.trend'
                    )
                    ->get();

                foreach ($atletData as $atlet) {
                    $atletModel    = Atlet::find($atlet->id);
                    $pesertaData[] = [
                        'id'           => $atlet->id,
                        'nama'         => $atlet->nama,
                        'foto'         => $atletModel ? $atletModel->foto : null,
                        'jenisKelamin' => $this->mapJenisKelamin($atlet->jenis_kelamin),
                        'usia'         => $this->calculateAge($atlet->tanggal_lahir),
                        'posisi'       => $atlet->posisi,
                        'nilai'        => $atlet->nilai,
                        'target'       => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                        'trend'        => ucfirst($atlet->trend ?? 'stabil'),
                    ];
                }
            }

            if ($target->peruntukan === 'pelatih' || $target->peruntukan === 'semua') {
                $pelatihData = DB::table('rencana_latihan_peserta_target')
                    ->join('pelatihs', 'rencana_latihan_peserta_target.peserta_id', '=', 'pelatihs.id')
                    ->leftJoin('cabor_kategori_pelatih', function ($join) use ($rencana) {
                        $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                            ->where('cabor_kategori_pelatih.cabor_kategori_id', $rencana->programLatihan->cabor_kategori_id)
                            ->whereNull('cabor_kategori_pelatih.deleted_at');
                    })
                    ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
                    ->where('rencana_latihan_peserta_target.rencana_latihan_id', $rencanaId)
                    ->where('rencana_latihan_peserta_target.target_latihan_id', $targetId)
                    ->where('rencana_latihan_peserta_target.peserta_type', 'App\\Models\\Pelatih')
                    ->select(
                        'pelatihs.id',
                        'pelatihs.nama',
                        'pelatihs.jenis_kelamin',
                        'pelatihs.tanggal_lahir',
                        DB::raw("COALESCE(mst_jenis_pelatih.nama, '-') as posisi"),
                        'rencana_latihan_peserta_target.nilai',
                        'rencana_latihan_peserta_target.trend'
                    )
                    ->get();

                foreach ($pelatihData as $pelatih) {
                    $pelatihModel  = Pelatih::find($pelatih->id);
                    $pesertaData[] = [
                        'id'           => $pelatih->id,
                        'nama'         => $pelatih->nama,
                        'foto'         => $pelatihModel ? $pelatihModel->foto : null,
                        'jenisKelamin' => $this->mapJenisKelamin($pelatih->jenis_kelamin),
                        'usia'         => $this->calculateAge($pelatih->tanggal_lahir),
                        'posisi'       => $pelatih->posisi,
                        'nilai'        => $pelatih->nilai,
                        'target'       => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                        'trend'        => ucfirst($pelatih->trend ?? 'stabil'),
                    ];
                }
            }

            if ($target->peruntukan === 'tenaga-pendukung' || $target->peruntukan === 'semua') {
                $tenagaData = DB::table('rencana_latihan_peserta_target')
                    ->join('tenaga_pendukungs', 'rencana_latihan_peserta_target.peserta_id', '=', 'tenaga_pendukungs.id')
                    ->leftJoin('cabor_kategori_tenaga_pendukung', function ($join) use ($rencana) {
                        $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                            ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $rencana->programLatihan->cabor_kategori_id)
                            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                    })
                    ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
                    ->where('rencana_latihan_peserta_target.rencana_latihan_id', $rencanaId)
                    ->where('rencana_latihan_peserta_target.target_latihan_id', $targetId)
                    ->where('rencana_latihan_peserta_target.peserta_type', 'App\\Models\\TenagaPendukung')
                    ->select(
                        'tenaga_pendukungs.id',
                        'tenaga_pendukungs.nama',
                        'tenaga_pendukungs.jenis_kelamin',
                        'tenaga_pendukungs.tanggal_lahir',
                        DB::raw("COALESCE(mst_jenis_tenaga_pendukung.nama, '-') as posisi"),
                        'rencana_latihan_peserta_target.nilai',
                        'rencana_latihan_peserta_target.trend'
                    )
                    ->get();

                foreach ($tenagaData as $tenaga) {
                    $tenagaModel   = TenagaPendukung::find($tenaga->id);
                    $pesertaData[] = [
                        'id'           => $tenaga->id,
                        'nama'         => $tenaga->nama,
                        'foto'         => $tenagaModel ? $tenagaModel->foto : null,
                        'jenisKelamin' => $this->mapJenisKelamin($tenaga->jenis_kelamin),
                        'usia'         => $this->calculateAge($tenaga->tanggal_lahir),
                        'posisi'       => $tenaga->posisi,
                        'nilai'        => $tenaga->nilai,
                        'target'       => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                        'trend'        => ucfirst($tenaga->trend ?? 'stabil'),
                    ];
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail target latihan berhasil diambil',
                'data'    => [
                    'target' => [
                        'id'      => $target->id,
                        'nama'    => $target->deskripsi,
                        'target'  => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                        'peserta' => ucfirst($target->peruntukan ?? 'Semua'),
                    ],
                    'rencana' => [
                        'id'      => $rencana->id,
                        'materi'  => $rencana->materi,
                        'tanggal' => $rencana->tanggal,
                    ],
                    'pesertaTarget' => $pesertaData,
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
     * Get target latihan list for specific participant (mobile)
     */
    public function participantTargets(Request $request, int $programId, int $rencanaId, int $pesertaId, string $pesertaType = 'atlet'): JsonResponse
    {
        try {
            $user = auth()->user();
            // Use URL parameter first, fallback to query parameter, then default to 'atlet'
            $pesertaType = $pesertaType ?? $request->get('peserta_type', 'atlet');

            // Get participant info - pesertaId is the pivot table ID for pelatih/tenaga, or actual ID for atlet
            $pesertaInfo = $this->repository->getParticipantInfoFromPivot($pesertaId, $pesertaType, $programId, $rencanaId);

            if (!$pesertaInfo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta tidak ditemukan',
                ], 404);
            }

            // Get target latihan for this participant
            $targets = $this->repository->getParticipantTargets($programId, $pesertaInfo['actual_peserta_id'], $pesertaType);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data target latihan peserta berhasil diambil',
                'data'    => [
                    'pesertaInfo' => $pesertaInfo,
                    'targets'     => $targets,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil target latihan peserta: ' . $e->getMessage(), [
                'exception'  => $e,
                'program_id' => $programId,
                'rencana_id' => $rencanaId,
                'peserta_id' => $pesertaId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil target latihan peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get target latihan chart data for specific participant (mobile)
     */
    public function participantTargetChart(Request $request, int $programId, int $rencanaId, int $pesertaId, int $targetId, string $pesertaType = 'atlet'): JsonResponse
    {
        try {
            $user = auth()->user();
            // Use URL parameter first, fallback to query parameter, then default to 'atlet'
            $pesertaType = $pesertaType ?? $request->get('peserta_type', 'atlet');

            // Get participant info - pesertaId is the pivot table ID for pelatih/tenaga, or actual ID for atlet
            $pesertaInfo = $this->repository->getParticipantInfoFromPivot($pesertaId, $pesertaType, $programId, $rencanaId);

            if (!$pesertaInfo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta tidak ditemukan',
                ], 404);
            }

            // Get target info
            $targetInfo = $this->repository->getTargetInfo($targetId, $programId, $pesertaInfo['actual_peserta_id'], $pesertaType);

            if (!$targetInfo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Target latihan tidak ditemukan',
                ], 404);
            }

            // Get chart data
            $chartData = $this->repository->getParticipantTargetChartData($programId, $pesertaInfo['actual_peserta_id'], $pesertaType, $targetId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data grafik target latihan peserta berhasil diambil',
                'data'    => [
                    'pesertaInfo' => $pesertaInfo,
                    'targetInfo'  => $targetInfo,
                    'chartData'   => $chartData['chartData'],
                    'detailData'  => $chartData['detailData'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil grafik target latihan peserta: ' . $e->getMessage(), [
                'exception'  => $e,
                'program_id' => $programId,
                'rencana_id' => $rencanaId,
                'peserta_id' => $pesertaId,
                'target_id'  => $targetId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil grafik target latihan peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map trend status to Indonesian
     */
    private function mapTrendStatus($trend)
    {
        return match ($trend) {
            'stabil' => 'Stabil',
            'naik'   => 'Naik',
            'turun'  => 'Turun',
            default  => 'Stabil',
        };
    }

    /**
     * Get target latihan list for form step 2 (Mobile)
     */
    public function getTargetLatihanList(Request $request, int $programId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = TargetLatihan::where('program_latihan_id', $programId);

            if ($search) {
                $query->where('deskripsi', 'like', "%{$search}%");
            }

            $targets = $query->orderBy('deskripsi')->get();

            $data = $targets->map(function ($target) {
                return [
                    'id'           => $target->id,
                    'deskripsi'    => $target->deskripsi,
                    'peruntukan'   => $target->peruntukan ?? '-',
                    'jenis_target' => $target->jenis_target,
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data target latihan berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data target latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get atlet list for form step 3 (Mobile)
     */
    public function getAtletList(Request $request, int $caborKategoriId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = CaborKategoriAtlet::with(['atlet'])
                ->where('cabor_kategori_id', $caborKategoriId);

            if ($search) {
                $query->whereHas('atlet', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
            }

            $atletList = $query->get();

            $data = $atletList->map(function ($item) {
                $atlet = $item->atlet;
                return [
                    'id'             => $atlet->id,
                    'nama'           => $atlet->nama,
                    'foto'           => $atlet->foto,
                    'posisi'         => $item->posisi_atlet ?? '-', // Langsung dari attribute
                    'jenis_kelamin'  => $this->mapJenisKelamin($atlet->jenis_kelamin),
                    'usia'           => $this->calculateAge($atlet->tanggal_lahir),
                    'lama_bergabung' => $this->getLamaBergabung($atlet->tanggal_bergabung),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data atlet berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data atlet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pelatih list for form step 3 (Mobile)
     */
    public function getPelatihList(Request $request, int $caborKategoriId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = CaborKategoriPelatih::with(['pelatih'])
                ->where('cabor_kategori_id', $caborKategoriId);

            if ($search) {
                $query->whereHas('pelatih', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
            }

            $pelatihList = $query->get();

            $data = $pelatihList->map(function ($item) {
                $pelatih = $item->pelatih;
                return [
                    'id'             => $pelatih->id,
                    'nama'           => $pelatih->nama,
                    'foto'           => $pelatih->foto,
                    'jenis_pelatih'  => $item->jenis_pelatih ?? '-', // Langsung dari attribute
                    'jenis_kelamin'  => $this->mapJenisKelamin($pelatih->jenis_kelamin),
                    'usia'           => $this->calculateAge($pelatih->tanggal_lahir),
                    'lama_bergabung' => $this->getLamaBergabung($pelatih->tanggal_bergabung),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data pelatih berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data pelatih: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tenaga pendukung list for form step 3 (Mobile)
     */
    public function getTenagaPendukungList(Request $request, int $caborKategoriId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = CaborKategoriTenagaPendukung::with(['tenagaPendukung'])
                ->where('cabor_kategori_id', $caborKategoriId);

            if ($search) {
                $query->whereHas('tenagaPendukung', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
            }

            $tenagaList = $query->get();

            $data = $tenagaList->map(function ($item) {
                $tenaga = $item->tenagaPendukung;
                return [
                    'id'                     => $tenaga->id,
                    'nama'                   => $tenaga->nama,
                    'foto'                   => $tenaga->foto,
                    'jenis_tenaga_pendukung' => $item->jenis_tenaga_pendukung ?? '-', // Langsung dari attribute
                    'jenis_kelamin'          => $this->mapJenisKelamin($tenaga->jenis_kelamin),
                    'usia'                   => $this->calculateAge($tenaga->tanggal_lahir),
                    'lama_bergabung'         => $this->getLamaBergabung($tenaga->tanggal_bergabung),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data tenaga pendukung berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data tenaga pendukung: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new rencana latihan (Mobile)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'program_latihan_id'     => 'required|exists:program_latihan,id',
                'tanggal'                => 'required|date',
                'materi'                 => 'required|string|max:255',
                'lokasi_latihan'         => 'required|string|max:255',
                'catatan'                => 'nullable|string',
                'target_latihan_ids'     => 'nullable|array',
                'target_latihan_ids.*'   => 'exists:target_latihan,id',
                'atlet_ids'              => 'nullable|array',
                'atlet_ids.*'            => 'exists:atlets,id',
                'pelatih_ids'            => 'nullable|array',
                'pelatih_ids.*'          => 'exists:pelatihs,id',
                'tenaga_pendukung_ids'   => 'nullable|array',
                'tenaga_pendukung_ids.*' => 'exists:tenaga_pendukungs,id',
            ], [
                'program_latihan_id.required' => 'Program latihan wajib dipilih.',
                'program_latihan_id.exists'   => 'Program latihan tidak valid.',
                'tanggal.required'            => 'Tanggal wajib diisi.',
                'tanggal.date'                => 'Tanggal harus berupa tanggal yang valid.',
                'materi.required'             => 'Materi wajib diisi.',
                'lokasi_latihan.required'     => 'Lokasi latihan wajib diisi.',
            ]);

            $data = $request->only([
                'program_latihan_id',
                'tanggal',
                'materi',
                'lokasi_latihan',
                'catatan',
            ]);

            // Create rencana latihan with relations
            $rencana = $this->repository->createWithRelations([
                ...$data,
                'target_latihan_ids'   => $request->target_latihan_ids   ?? [],
                'atlet_ids'            => $request->atlet_ids            ?? [],
                'pelatih_ids'          => $request->pelatih_ids          ?? [],
                'tenaga_pendukung_ids' => $request->tenaga_pendukung_ids ?? [],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Rencana latihan berhasil dibuat',
                'data'    => [
                    'id'             => $rencana->id,
                    'tanggal'        => $rencana->tanggal,
                    'materi'         => $rencana->materi,
                    'lokasi_latihan' => $rencana->lokasi_latihan,
                    'catatan'        => $rencana->catatan,
                    'created_at'     => $rencana->created_at,
                    'updated_at'     => $rencana->updated_at,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat rencana latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update rencana latihan (Mobile)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rencana = $this->repository->getDetailWithRelations($id);

            if (!$rencana) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Rencana latihan tidak ditemukan',
                ], 404);
            }

            $request->validate([
                'program_latihan_id'     => 'required|exists:program_latihan,id',
                'tanggal'                => 'required|date',
                'materi'                 => 'required|string|max:255',
                'lokasi_latihan'         => 'required|string|max:255',
                'catatan'                => 'nullable|string',
                'target_latihan_ids'     => 'nullable|array',
                'target_latihan_ids.*'   => 'exists:target_latihan,id',
                'atlet_ids'              => 'nullable|array',
                'atlet_ids.*'            => 'exists:atlets,id',
                'pelatih_ids'            => 'nullable|array',
                'pelatih_ids.*'          => 'exists:pelatihs,id',
                'tenaga_pendukung_ids'   => 'nullable|array',
                'tenaga_pendukung_ids.*' => 'exists:tenaga_pendukungs,id',
            ], [
                'program_latihan_id.required' => 'Program latihan wajib dipilih.',
                'program_latihan_id.exists'   => 'Program latihan tidak valid.',
                'tanggal.required'            => 'Tanggal wajib diisi.',
                'tanggal.date'                => 'Tanggal harus berupa tanggal yang valid.',
                'materi.required'             => 'Materi wajib diisi.',
                'lokasi_latihan.required'     => 'Lokasi latihan wajib diisi.',
            ]);

            $data = $request->only([
                'program_latihan_id',
                'tanggal',
                'materi',
                'lokasi_latihan',
                'catatan',
            ]);

            // Update rencana latihan with relations
            $updatedRencana = $this->repository->updateWithRelations($id, [
                ...$data,
                'target_latihan_ids'   => $request->target_latihan_ids   ?? [],
                'atlet_ids'            => $request->atlet_ids            ?? [],
                'pelatih_ids'          => $request->pelatih_ids          ?? [],
                'tenaga_pendukung_ids' => $request->tenaga_pendukung_ids ?? [],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Rencana latihan berhasil diperbarui',
                'data'    => [
                    'id'             => $updatedRencana->id,
                    'tanggal'        => $updatedRencana->tanggal,
                    'materi'         => $updatedRencana->materi,
                    'lokasi_latihan' => $updatedRencana->lokasi_latihan,
                    'catatan'        => $updatedRencana->catatan,
                    'created_at'     => $updatedRencana->created_at,
                    'updated_at'     => $updatedRencana->updated_at,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui rencana latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete rencana latihan (Mobile)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $rencana = $this->repository->getDetailWithRelations($id);

            if (!$rencana) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Rencana latihan tidak ditemukan',
                ], 404);
            }

            $this->repository->delete($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Rencana latihan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus rencana latihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate lama bergabung
     */
    private function getLamaBergabung($tanggalBergabung)
    {
        if (!$tanggalBergabung) {
            return '-';
        }

        $start = new \DateTime($tanggalBergabung);
        $now   = new \DateTime();
        $diff  = $start->diff($now);

        $tahun = $diff->y;
        $bulan = $diff->m;

        $result = '';
        if ($tahun > 0) {
            $result .= $tahun . ' tahun ';
        }
        if ($bulan > 0) {
            $result .= $bulan . ' bulan';
        }
        if (!$result) {
            $result = 'Kurang dari 1 bulan';
        }

        return trim($result);
    }
}
