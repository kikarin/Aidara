<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RencanaLatihan;
use App\Models\RencanaLatihanPesertaTarget;
use App\Models\ProgramLatihan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RencanaLatihanTargetController extends Controller
{
    /**
     * Get target mapping data for participants (individual)
     */
    public function getParticipantTargetMapping(Request $request, int $rencanaId): JsonResponse
    {
        try {
            $rencana = RencanaLatihan::with([
                'programLatihan.cabor',
                'programLatihan.caborKategori',
                'targetLatihan',
            ])->find($rencanaId);

            if (!$rencana) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Rencana latihan tidak ditemukan',
                ], 404);
            }

            $jenisPeserta = $request->get('jenis_peserta', 'atlet');

            // Get participants based on type
            $pesertaList = $this->getPesertaList($rencanaId, $jenisPeserta);

            // Get existing target mappings
            $existingMappings = $this->getExistingParticipantMappings($rencanaId, $jenisPeserta);

            // Get target latihan for this rencana with proper filtering
            $peruntukan = $this->mapJenisPesertaToPeruntukan($jenisPeserta);

            $targets = $rencana->targetLatihan()
                ->where('jenis_target', 'individu') // Filter hanya target individu
                ->where('peruntukan', $peruntukan) // Filter berdasarkan peruntukan
                ->get()
                ->map(function ($target) {
                    return [
                        'id'           => $target->id,
                        'deskripsi'    => $target->deskripsi,
                        'satuan'       => $target->satuan,
                        'nilai_target' => $target->nilai_target,
                        'jenis_target' => $target->jenis_target,
                        'peruntukan'   => $target->peruntukan,
                    ];
                });

            // Format data for mobile
            $formattedData = $pesertaList->map(function ($peserta) use ($targets, $existingMappings) {
                $pesertaId   = $peserta['id'];
                $targetsData = $targets->map(function ($target) use ($pesertaId, $existingMappings) {
                    $existing = $existingMappings[$pesertaId][$target['id']] ?? [];
                    return [
                        'target_latihan_id' => $target['id'],
                        'deskripsi'         => $target['deskripsi'],
                        'satuan'            => $target['satuan'],
                        'nilai_target'      => $target['nilai_target'],
                        'nilai'             => $existing['nilai'] ?? '',
                        'trend'             => $existing['trend'] ?? 'stabil',
                    ];
                });

                return [
                    'peserta_id' => $pesertaId,
                    'peserta'    => $peserta,
                    'targets'    => $targetsData,
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data mapping target peserta berhasil diambil',
                'data'    => [
                    'rencana_latihan' => [
                        'id'              => $rencana->id,
                        'tanggal'         => $rencana->tanggal,
                        'materi'          => $rencana->materi,
                        'lokasi_latihan'  => $rencana->lokasi_latihan,
                        'program_latihan' => [
                            'nama_program'        => $rencana->programLatihan->nama_program,
                            'cabor_nama'          => $rencana->programLatihan->cabor->nama,
                            'cabor_kategori_nama' => $rencana->programLatihan->caborKategori->nama,
                        ],
                    ],
                    'jenis_peserta' => $jenisPeserta,
                    'peserta_list'  => $formattedData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting participant target mapping: ' . $e->getMessage(), [
                'rencana_id' => $rencanaId,
                'exception'  => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data mapping target peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get target mapping data for group/kelompok
     */
    public function getGroupTargetMapping(Request $request, int $programId): JsonResponse
    {
        try {
            $program = ProgramLatihan::with([
                'cabor',
                'caborKategori',
                'rencanaLatihan.targetLatihan',
            ])->find($programId);

            if (!$program) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Program latihan tidak ditemukan',
                ], 404);
            }

            // Get all rencana latihan with their targets (only kelompok targets)
            $rencanaList = $program->rencanaLatihan->map(function ($rencana) {
                return [
                    'id'                      => $rencana->id,
                    'tanggal'                 => $rencana->tanggal,
                    'materi'                  => $rencana->materi,
                    'lokasi_latihan'          => $rencana->lokasi_latihan,
                    'jumlah_atlet'            => $rencana->atlets()->count(),
                    'jumlah_pelatih'          => $rencana->pelatihs()->count(),
                    'jumlah_tenaga_pendukung' => $rencana->tenagaPendukung()->count(),
                    'target_latihan'          => $rencana->targetLatihan()
                        ->where('jenis_target', 'kelompok')
                        ->get()
                        ->map(function ($target) {
                            return [
                                'id'           => $target->id,
                                'deskripsi'    => $target->deskripsi,
                                'satuan'       => $target->satuan,
                                'nilai_target' => $target->nilai_target,
                            ];
                        }),
                ];
            });

            // Get existing group target mappings
            $existingMappings = $this->getExistingGroupMappings($programId);

            // Format data for mobile
            $formattedData = $rencanaList->map(function ($rencana) use ($existingMappings) {
                $rencanaId   = $rencana['id'];
                $targetsData = $rencana['target_latihan']->map(function ($target) use ($rencanaId, $existingMappings) {
                    $existing = $existingMappings[$rencanaId][$target['id']] ?? [];
                    return [
                        'target_latihan_id' => $target['id'],
                        'deskripsi'         => $target['deskripsi'],
                        'satuan'            => $target['satuan'],
                        'nilai_target'      => $target['nilai_target'],
                        'nilai'             => $existing['nilai'] ?? '',
                        'trend'             => $existing['trend'] ?? 'stabil',
                    ];
                });

                return [
                    'rencana_id'              => $rencanaId,
                    'tanggal'                 => $rencana['tanggal'],
                    'materi'                  => $rencana['materi'],
                    'lokasi_latihan'          => $rencana['lokasi_latihan'],
                    'jumlah_atlet'            => $rencana['jumlah_atlet'],
                    'jumlah_pelatih'          => $rencana['jumlah_pelatih'],
                    'jumlah_tenaga_pendukung' => $rencana['jumlah_tenaga_pendukung'],
                    'targets'                 => $targetsData,
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data mapping target kelompok berhasil diambil',
                'data'    => [
                    'program_latihan' => [
                        'nama_program'        => $program->nama_program,
                        'cabor_nama'          => $program->cabor->nama,
                        'cabor_kategori_nama' => $program->caborKategori->nama,
                    ],
                    'rencana_latihan_list' => $formattedData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting group target mapping: ' . $e->getMessage(), [
                'program_id' => $programId,
                'exception'  => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data mapping target kelompok: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update participant target values
     */
    public function bulkUpdateParticipantTargets(Request $request, $rencanaId): JsonResponse
    {
        try {
            // Validate rencanaId parameter
            if (!is_numeric($rencanaId)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'ID rencana latihan tidak valid',
                ], 400);
            }

            $rencanaId = (int) $rencanaId;

            $request->validate([
                'data'                     => 'required|array',
                'data.*.peserta_id'        => 'required|integer',
                'data.*.target_latihan_id' => 'required|integer|exists:target_latihan,id',
                'data.*.nilai'             => 'nullable|string',
                'data.*.trend'             => 'required|in:naik,stabil,turun',
            ], [
                'data.required'                     => 'Data wajib diisi',
                'data.*.peserta_id.required'        => 'ID peserta wajib diisi',
                'data.*.target_latihan_id.required' => 'ID target latihan wajib diisi',
                'data.*.target_latihan_id.exists'   => 'Target latihan tidak valid',
                'data.*.trend.required'             => 'Trend wajib diisi',
                'data.*.trend.in'                   => 'Trend harus berupa: naik, stabil, atau turun',
            ]);

            $rencana = RencanaLatihan::find($rencanaId);
            if (!$rencana) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Rencana latihan tidak ditemukan',
                ], 404);
            }

            DB::beginTransaction();

            try {
                foreach ($request->data as $item) {
                    // Determine peserta_type based on the participant
                    $pesertaType = $this->getPesertaType($rencanaId, $item['peserta_id']);

                    RencanaLatihanPesertaTarget::updateOrCreate(
                        [
                            'rencana_latihan_id' => $rencanaId,
                            'peserta_id'         => $item['peserta_id'],
                            'target_latihan_id'  => $item['target_latihan_id'],
                            'peserta_type'       => $pesertaType,
                        ],
                        [
                            'nilai'      => $item['nilai'],
                            'trend'      => $item['trend'],
                            'updated_by' => Auth::id(),
                        ]
                    );
                }

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data target peserta berhasil diperbarui',
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
            Log::error('Error bulk updating participant targets: ' . $e->getMessage(), [
                'rencana_id' => $rencanaId,
                'exception'  => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data target peserta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update group target values
     */
    public function bulkUpdateGroupTargets(Request $request, $programId): JsonResponse
    {
        try {
            // Validate programId parameter
            if (!is_numeric($programId)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'ID program latihan tidak valid',
                ], 400);
            }

            $programId = (int) $programId;

            $request->validate([
                'data'                      => 'required|array',
                'data.*.rencana_latihan_id' => 'required|integer|exists:rencana_latihan,id',
                'data.*.target_latihan_id'  => 'required|integer|exists:target_latihan,id',
                'data.*.nilai'              => 'nullable|string',
                'data.*.trend'              => 'required|in:naik,stabil,turun',
            ], [
                'data.required'                      => 'Data wajib diisi',
                'data.*.rencana_latihan_id.required' => 'ID rencana latihan wajib diisi',
                'data.*.rencana_latihan_id.exists'   => 'Rencana latihan tidak valid',
                'data.*.target_latihan_id.required'  => 'ID target latihan wajib diisi',
                'data.*.target_latihan_id.exists'    => 'Target latihan tidak valid',
                'data.*.trend.required'              => 'Trend wajib diisi',
                'data.*.trend.in'                    => 'Trend harus berupa: naik, stabil, atau turun',
            ]);

            DB::beginTransaction();

            try {
                foreach ($request->data as $item) {
                    // Check if data exists
                    $existing = DB::table('rencana_latihan_target_latihan')
                        ->where('rencana_latihan_id', $item['rencana_latihan_id'])
                        ->where('target_latihan_id', $item['target_latihan_id'])
                        ->first();

                    if ($existing) {
                        // Update existing data
                        DB::table('rencana_latihan_target_latihan')
                            ->where('rencana_latihan_id', $item['rencana_latihan_id'])
                            ->where('target_latihan_id', $item['target_latihan_id'])
                            ->update([
                                'nilai' => $item['nilai'],
                                'trend' => $item['trend'],
                            ]);
                    } else {
                        // Insert new data
                        DB::table('rencana_latihan_target_latihan')->insert([
                            'rencana_latihan_id' => $item['rencana_latihan_id'],
                            'target_latihan_id'  => $item['target_latihan_id'],
                            'nilai'              => $item['nilai'],
                            'trend'              => $item['trend'],
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data target kelompok berhasil diperbarui',
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
            Log::error('Error bulk updating group targets: ' . $e->getMessage(), [
                'program_id' => $programId,
                'exception'  => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data target kelompok: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get participant list based on type
     */
    private function getPesertaList(int $rencanaId, string $jenisPeserta)
    {
        $rencana         = RencanaLatihan::with(['programLatihan.caborKategori'])->find($rencanaId);
        $caborKategoriId = $rencana->programLatihan->cabor_kategori_id;

        switch ($jenisPeserta) {
            case 'atlet':
                return $rencana->atlets()->with(['caborKategoriAtlet' => function ($query) use ($caborKategoriId) {
                    $query->where('cabor_kategori_id', $caborKategoriId);
                }])->get()->map(function ($atlet) {
                    $posisi = $atlet->caborKategoriAtlet->first()?->posisi_atlet ?? '-'; // Langsung dari attribute
                    return [
                        'id'            => $atlet->id,
                        'nama'          => $atlet->nama,
                        'foto'          => $atlet->foto,
                        'jenis_kelamin' => $this->mapJenisKelamin($atlet->jenis_kelamin),
                        'usia'          => $this->calculateAge($atlet->tanggal_lahir),
                        'posisi'        => $posisi,
                    ];
                });

            case 'pelatih':
                return $rencana->pelatihs()->with(['caborKategoriPelatih' => function ($query) use ($caborKategoriId) {
                    $query->where('cabor_kategori_id', $caborKategoriId);
                }])->get()->map(function ($pelatih) {
                    $jenisPelatih = $pelatih->caborKategoriPelatih->first()?->jenis_pelatih ?? '-'; // Langsung dari attribute
                    return [
                        'id'            => $pelatih->id,
                        'nama'          => $pelatih->nama,
                        'foto'          => $pelatih->foto,
                        'jenis_kelamin' => $this->mapJenisKelamin($pelatih->jenis_kelamin),
                        'usia'          => $this->calculateAge($pelatih->tanggal_lahir),
                        'jenis_pelatih' => $jenisPelatih,
                    ];
                });

            case 'tenaga-pendukung':
                return $rencana->tenagaPendukung()->with(['caborKategoriTenagaPendukung' => function ($query) use ($caborKategoriId) {
                    $query->where('cabor_kategori_id', $caborKategoriId);
                }])->get()->map(function ($tenaga) {
                    $jenisTenaga = $tenaga->caborKategoriTenagaPendukung->first()?->jenis_tenaga_pendukung ?? '-'; // Langsung dari attribute
                    return [
                        'id'                     => $tenaga->id,
                        'nama'                   => $tenaga->nama,
                        'foto'                   => $tenaga->foto,
                        'jenis_kelamin'          => $this->mapJenisKelamin($tenaga->jenis_kelamin),
                        'usia'                   => $this->calculateAge($tenaga->tanggal_lahir),
                        'jenis_tenaga_pendukung' => $jenisTenaga,
                    ];
                });

            default:
                return collect([]);
        }
    }

    /**
     * Get existing participant target mappings
     */
    private function getExistingParticipantMappings(int $rencanaId, string $jenisPeserta)
    {
        // Map jenis peserta to model class
        $pesertaType = match ($jenisPeserta) {
            'atlet'            => 'App\\Models\\Atlet',
            'pelatih'          => 'App\\Models\\Pelatih',
            'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
            default            => 'App\\Models\\Atlet',
        };

        $mappings = RencanaLatihanPesertaTarget::where('rencana_latihan_id', $rencanaId)
            ->where('peserta_type', $pesertaType)
            ->get()
            ->groupBy('peserta_id')
            ->map(function ($group) {
                return $group->keyBy('target_latihan_id')->map(function ($item) {
                    return [
                        'nilai' => $item->nilai,
                        'trend' => $item->trend,
                    ];
                });
            });

        return $mappings->toArray();
    }

    /**
     * Get existing group target mappings
     */
    private function getExistingGroupMappings(int $programId)
    {
        // Get all rencana latihan for this program
        $rencanaIds = RencanaLatihan::where('program_latihan_id', $programId)->pluck('id');

        // Get target mappings from pivot table
        $mappings = DB::table('rencana_latihan_target_latihan')
            ->whereIn('rencana_latihan_id', $rencanaIds)
            ->whereNotNull('nilai')
            ->get()
            ->groupBy('rencana_latihan_id')
            ->map(function ($group) {
                return $group->keyBy('target_latihan_id')->map(function ($item) {
                    return [
                        'nilai' => $item->nilai,
                        'trend' => $item->trend,
                    ];
                });
            });

        return $mappings->toArray();
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

    /**
     * Get peserta type based on rencana and peserta ID
     */
    private function getPesertaType($rencanaId, $pesertaId)
    {
        $rencana = RencanaLatihan::find($rencanaId);

        if (!$rencana) {
            return 'App\\Models\\Atlet'; // Default fallback
        }

        // Check if peserta is an atlet
        if ($rencana->atlets()->where('atlets.id', $pesertaId)->exists()) {
            return 'App\\Models\\Atlet';
        }

        // Check if peserta is a pelatih
        if ($rencana->pelatihs()->where('pelatihs.id', $pesertaId)->exists()) {
            return 'App\\Models\\Pelatih';
        }

        // Check if peserta is a tenaga pendukung
        if ($rencana->tenagaPendukung()->where('tenaga_pendukungs.id', $pesertaId)->exists()) {
            return 'App\\Models\\TenagaPendukung';
        }

        // Default fallback
        return 'App\\Models\\Atlet';
    }

    /**
     * Map jenis peserta to peruntukan
     */
    private function mapJenisPesertaToPeruntukan($jenisPeserta)
    {
        return match ($jenisPeserta) {
            'atlet'            => 'atlet',
            'pelatih'          => 'pelatih',
            'tenaga-pendukung' => 'tenaga-pendukung',
            default            => 'atlet',
        };
    }
}
