<?php

namespace App\Repositories;

use App\Models\RencanaLatihan;
use App\Models\ProgramLatihan;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RencanaLatihanRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(RencanaLatihan $model)
    {
        $this->model = $model;
        $this->with  = ['programLatihan', 'targetLatihan', 'atlets', 'pelatihs', 'tenagaPendukung', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model
            ->with($this->with)
            ->withCount(['targetLatihan', 'atlets', 'pelatihs', 'tenagaPendukung']);
        if (request('program_latihan_id')) {
            $query->where('program_latihan_id', request('program_latihan_id'));
        }
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('materi', 'like', "%$search%")
                    ->orWhere('lokasi_latihan', 'like', "%$search%")
                    ->orWhere('catatan', 'like', "%$search%");
            });
        }
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'tanggal', 'lokasi_latihan', 'materi', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $order);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }
        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);
        if ($perPage === -1) {
            $all         = $query->get();
            $transformed = collect($all)->map(function ($item) {
                return array_merge(
                    $item->toArray(),
                    [
                        'jumlah_target'           => $item->target_latihan_count,
                        'jumlah_atlet'            => $item->atlets_count,
                        'jumlah_pelatih'          => $item->pelatihs_count,
                        'jumlah_tenaga_pendukung' => $item->tenaga_pendukung_count,
                    ]
                );
            });
            $data += [
                'data'        => $transformed,
                'total'       => $transformed->count(),
                'currentPage' => 1,
                'perPage'     => -1,
                'search'      => request('search', ''),
                'sort'        => request('sort', ''),
                'order'       => request('order', 'asc'),
            ];

            return $data;
        }
        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformed     = collect($items->items())->map(function ($item) {
            return array_merge(
                $item->toArray(),
                [
                    'jumlah_target'           => $item->target_latihan_count,
                    'jumlah_atlet'            => $item->atlets_count,
                    'jumlah_pelatih'          => $item->pelatihs_count,
                    'jumlah_tenaga_pendukung' => $item->tenaga_pendukung_count,
                ]
            );
        });
        $data += [
            'data'        => $transformed,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => request('search', ''),
            'sort'        => request('sort', ''),
            'order'       => request('order', 'asc'),
        ];

        return $data;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::id();
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        return $data;
    }

    public function createWithRelations($data)
    {
        DB::beginTransaction();
        try {
            $main = $this->model->create($data);
            if (! empty($data['target_latihan_ids'])) {
                $main->targetLatihan()->sync($data['target_latihan_ids']);
            }
            if (! empty($data['atlet_ids'])) {
                $main->atlets()->sync($data['atlet_ids']);
            }
            if (! empty($data['pelatih_ids'])) {
                $main->pelatihs()->sync($data['pelatih_ids']);
            }
            if (! empty($data['tenaga_pendukung_ids'])) {
                $main->tenagaPendukung()->sync($data['tenaga_pendukung_ids']);
            }
            DB::commit();

            return $main;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateWithRelations($id, $data)
    {
        DB::beginTransaction();
        try {
            $main = $this->model->findOrFail($id);
            $main->update($data);
            if (isset($data['target_latihan_ids'])) {
                $main->targetLatihan()->sync($data['target_latihan_ids']);
            }
            if (isset($data['atlet_ids'])) {
                $main->atlets()->sync($data['atlet_ids']);
            }
            if (isset($data['pelatih_ids'])) {
                $main->pelatihs()->sync($data['pelatih_ids']);
            }
            if (isset($data['tenaga_pendukung_ids'])) {
                $main->tenagaPendukung()->sync($data['tenaga_pendukung_ids']);
            }
            DB::commit();

            return $main;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function getDetailWithRelations($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }

    /**
     * Listing rencana latihan untuk mobile per program
     */
    public function getForMobile($request, int $programId)
    {
        $query = $this->model
            ->with(['targetLatihan'])
            ->withCount(['atlets', 'pelatihs', 'tenagaPendukung'])
            ->where('program_latihan_id', $programId);

        // Search by materi/lokasi/catatan
        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('materi', 'like', "%$search%")
                  ->orWhere('lokasi_latihan', 'like', "%$search%")
                  ->orWhere('catatan', 'like', "%$search%");
            });
        }

        // Filter by exact date (YYYY-MM-DD)
        if (!empty($request->date)) {
            $query->whereDate('tanggal', $request->date);
        }

        // Default sort by tanggal desc, then id desc
        $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

        $perPage = (int) ($request->per_page ?? 10);
        $page    = (int) ($request->page ?? 1);

        $items = $query->paginate($perPage, ['*'], 'page', $page);

        $transformed = collect($items->items())->map(function ($item) {
            $targets    = $item->targetLatihan->pluck('deskripsi')->toArray();
            $targetText = implode(', ', $targets);

            return [
                'id'                      => $item->id,
                'tanggal'                 => $item->tanggal,
                'materi'                  => $item->materi,
                'lokasi'                  => $item->lokasi_latihan,
                'catatan'                 => $item->catatan,
                'targetLatihan'           => $targetText,
                'jumlah_atlet'            => $item->atlets_count,
                'jumlah_pelatih'          => $item->pelatihs_count,
                'jumlah_tenaga_pendukung' => $item->tenaga_pendukung_count,
                'total_peserta'           => ($item->atlets_count + $item->pelatihs_count + $item->tenaga_pendukung_count),
            ];
        })->values();

        return [
            'data'        => $transformed,
            'total'       => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'     => $items->perPage(),
            'search'      => $request->search ?? '',
            'filters'     => [
                'date' => $request->date ?? null,
            ],
        ];
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    /**
     * Get peserta for mobile
     */
    public function getPesertaForMobile($rencanaId, $request)
    {
        $rencana         = $this->model->findOrFail($rencanaId);
        $program         = ProgramLatihan::find($rencana->program_latihan_id);
        $caborKategoriId = $program->cabor_kategori_id;
        $auth            = Auth::user();

        // Get atlet
        $atletIds = DB::table('rencana_latihan_atlet')
            ->where('rencana_latihan_id', $rencanaId)
            ->pluck('atlet_id');

        // Jika role Atlet, batasi hanya dirinya sendiri
        if ($auth && (int) ($auth->current_role_id) === 35) {
            $authAtletId = optional($auth->atlet)->id;
            if ($authAtletId) {
                $atletIds = $atletIds->filter(function ($id) use ($authAtletId) {
                    return (int) $id === (int) $authAtletId;
                })->values();
            } else {
                // Tidak ada mapping atlet untuk user ini -> kosongkan
                $atletIds = collect([]);
            }
        }

        $atletQuery = Atlet::with(['media'])
            ->whereIn('atlets.id', $atletIds)
            ->leftJoin('cabor_kategori_atlet', function ($join) use ($caborKategoriId) {
                $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                    ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                    ->whereNull('cabor_kategori_atlet.deleted_at');
            })
            ->leftJoin('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
            ->leftJoin('rencana_latihan_atlet', function ($join) use ($rencanaId) {
                $join->on('atlets.id', '=', 'rencana_latihan_atlet.atlet_id')
                    ->where('rencana_latihan_atlet.rencana_latihan_id', $rencanaId);
            })
            ->select(
                'atlets.*',
                DB::raw("COALESCE(mst_posisi_atlet.nama, '-') as posisi"),
                'rencana_latihan_atlet.kehadiran as kehadiran'
            );

        // Get pelatih
        $pelatihIds = DB::table('rencana_latihan_pelatih')
            ->where('rencana_latihan_id', $rencanaId)
            ->pluck('pelatih_id');

        // Role Atlet tidak boleh melihat pelatih
        if ($auth && (int) ($auth->current_role_id) === 35) {
            $pelatihIds = collect([]);
        }

        $pelatihQuery = Pelatih::with(['media'])
            ->whereIn('pelatihs.id', $pelatihIds)
            ->leftJoin('cabor_kategori_pelatih', function ($join) use ($caborKategoriId) {
                $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                    ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
                    ->whereNull('cabor_kategori_pelatih.deleted_at');
            })
            ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
            ->leftJoin('rencana_latihan_pelatih', function ($join) use ($rencanaId) {
                $join->on('pelatihs.id', '=', 'rencana_latihan_pelatih.pelatih_id')
                    ->where('rencana_latihan_pelatih.rencana_latihan_id', $rencanaId);
            })
            ->select(
                'pelatihs.*',
                DB::raw("COALESCE(mst_jenis_pelatih.nama, '-') as jenis_pelatih"),
                'rencana_latihan_pelatih.kehadiran as kehadiran',
                'rencana_latihan_pelatih.id as pivot_id'
            );

        // Get tenaga pendukung
        $tenagaIds = DB::table('rencana_latihan_tenaga_pendukung')
            ->where('rencana_latihan_id', $rencanaId)
            ->pluck('tenaga_pendukung_id');

        // Role Atlet tidak boleh melihat tenaga pendukung
        if ($auth && (int) ($auth->current_role_id) === 35) {
            $tenagaIds = collect([]);
        }

        $tenagaQuery = TenagaPendukung::with(['media'])
            ->whereIn('tenaga_pendukungs.id', $tenagaIds)
            ->leftJoin('cabor_kategori_tenaga_pendukung', function ($join) use ($caborKategoriId) {
                $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                    ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
                    ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
            })
            ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
            ->leftJoin('rencana_latihan_tenaga_pendukung', function ($join) use ($rencanaId) {
                $join->on('tenaga_pendukungs.id', '=', 'rencana_latihan_tenaga_pendukung.tenaga_pendukung_id')
                    ->where('rencana_latihan_tenaga_pendukung.rencana_latihan_id', $rencanaId);
            })
            ->select(
                'tenaga_pendukungs.*',
                DB::raw("COALESCE(mst_jenis_tenaga_pendukung.nama, '-') as jenis_tenaga_pendukung"),
                'rencana_latihan_tenaga_pendukung.kehadiran as kehadiran',
                'rencana_latihan_tenaga_pendukung.id as pivot_id'
            );

        // Optional search by name
        if ($request->filled('search')) {
            $keyword = '%' . $request->search . '%';
            $atletQuery->where('atlets.nama', 'like', $keyword);
            $pelatihQuery->where('pelatihs.nama', 'like', $keyword);
            $tenagaQuery->where('tenaga_pendukungs.nama', 'like', $keyword);
        }

        $atlet = $atletQuery->orderBy('atlets.nama')->get()->map(function ($item) {
            return [
                'id'                         => $item->id,
                'rencana_latihan_peserta_id' => $item->id, // For atlet, use actual atlet ID
                'nama'                       => $item->nama,
                'foto'                       => $item->foto,
                'jenisKelamin'               => $this->mapJenisKelamin($item->jenis_kelamin),
                'usia'                       => $this->calculateAge($item->tanggal_lahir),
                'posisi'                     => $item->posisi,
                'kehadiran'                  => $item->kehadiran,
            ];
        });

        $pelatih = $pelatihQuery->orderBy('pelatihs.nama')->get()->map(function ($item) {
            return [
                'id'                         => $item->id,
                'rencana_latihan_peserta_id' => $item->pivot_id, // Use pivot table ID for navigation
                'nama'                       => $item->nama,
                'foto'                       => $item->foto,
                'jenisKelamin'               => $this->mapJenisKelamin($item->jenis_kelamin),
                'usia'                       => $this->calculateAge($item->tanggal_lahir),
                'jenisPelatih'               => $item->jenis_pelatih,
                'kehadiran'                  => $item->kehadiran,
            ];
        });

        $tenaga = $tenagaQuery->orderBy('tenaga_pendukungs.nama')->get()->map(function ($item) {
            return [
                'id'                         => $item->id,
                'rencana_latihan_peserta_id' => $item->pivot_id, // Use pivot table ID for navigation
                'nama'                       => $item->nama,
                'foto'                       => $item->foto,
                'jenisKelamin'               => $this->mapJenisKelamin($item->jenis_kelamin),
                'usia'                       => $this->calculateAge($item->tanggal_lahir),
                'jenisTenagaPendukung'       => $item->jenis_tenaga_pendukung,
                'kehadiran'                  => $item->kehadiran,
            ];
        });

        return [
            'atlet'           => $atlet->values(),
            'pelatih'         => $pelatih->values(),
            'tenagaPendukung' => $tenaga->values(),
        ];
    }

    /**
     * Get participant info from pivot table
     */
    public function getParticipantInfoFromPivot($pesertaId, $pesertaType, $programId, $rencanaId)
    {
        $program = ProgramLatihan::find($programId);
        if (!$program) {
            return null;
        }

        $caborKategoriId = $program->cabor_kategori_id;

        switch ($pesertaType) {
            case 'atlet':
                // For atlet, pesertaId is the actual atlet ID
                $atlet = Atlet::with(['media'])
                    ->leftJoin('cabor_kategori_atlet', function ($join) use ($caborKategoriId) {
                        $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                            ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                            ->whereNull('cabor_kategori_atlet.deleted_at');
                    })
                    ->leftJoin('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
                    ->where('atlets.id', $pesertaId)
                    ->select(
                        'atlets.*',
                        DB::raw("COALESCE(mst_posisi_atlet.nama, '-') as posisi")
                    )
                    ->first();

                if (!$atlet) {
                    return null;
                }

                return [
                    'id'                => $atlet->id,
                    'actual_peserta_id' => $atlet->id,
                    'nama'              => $atlet->nama,
                    'foto'              => $atlet->foto,
                    'jenisKelamin'      => $this->mapJenisKelamin($atlet->jenis_kelamin),
                    'usia'              => $this->calculateAge($atlet->tanggal_lahir),
                    'posisi'            => $atlet->posisi,
                ];

            case 'pelatih':
                // For pelatih, pesertaId is the rencana_latihan_pelatih.id
                $pivotRecord = DB::table('rencana_latihan_pelatih')
                    ->where('id', $pesertaId)
                    ->where('rencana_latihan_id', $rencanaId)
                    ->first();

                if (!$pivotRecord) {
                    return null;
                }

                $pelatih = Pelatih::with(['media'])
                    ->leftJoin('cabor_kategori_pelatih', function ($join) use ($caborKategoriId) {
                        $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                            ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
                            ->whereNull('cabor_kategori_pelatih.deleted_at');
                    })
                    ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
                    ->where('pelatihs.id', $pivotRecord->pelatih_id)
                    ->select(
                        'pelatihs.*',
                        DB::raw("COALESCE(mst_jenis_pelatih.nama, '-') as posisi")
                    )
                    ->first();

                if (!$pelatih) {
                    return null;
                }

                return [
                    'id'                => $pelatih->id,
                    'actual_peserta_id' => $pelatih->id,
                    'nama'              => $pelatih->nama,
                    'foto'              => $pelatih->foto,
                    'jenisKelamin'      => $this->mapJenisKelamin($pelatih->jenis_kelamin),
                    'usia'              => $this->calculateAge($pelatih->tanggal_lahir),
                    'posisi'            => $pelatih->posisi,
                ];

            case 'tenaga-pendukung':
                // For tenaga pendukung, pesertaId is the rencana_latihan_tenaga_pendukung.id
                $pivotRecord = DB::table('rencana_latihan_tenaga_pendukung')
                    ->where('id', $pesertaId)
                    ->where('rencana_latihan_id', $rencanaId)
                    ->first();

                if (!$pivotRecord) {
                    return null;
                }

                $tenaga = TenagaPendukung::with(['media'])
                    ->leftJoin('cabor_kategori_tenaga_pendukung', function ($join) use ($caborKategoriId) {
                        $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                            ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
                            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                    })
                    ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
                    ->where('tenaga_pendukungs.id', $pivotRecord->tenaga_pendukung_id)
                    ->select(
                        'tenaga_pendukungs.*',
                        DB::raw("COALESCE(mst_jenis_tenaga_pendukung.nama, '-') as posisi")
                    )
                    ->first();

                if (!$tenaga) {
                    return null;
                }

                return [
                    'id'                => $tenaga->id,
                    'actual_peserta_id' => $tenaga->id,
                    'nama'              => $tenaga->nama,
                    'foto'              => $tenaga->foto,
                    'jenisKelamin'      => $this->mapJenisKelamin($tenaga->jenis_kelamin),
                    'usia'              => $this->calculateAge($tenaga->tanggal_lahir),
                    'posisi'            => $tenaga->posisi,
                ];

            default:
                return null;
        }
    }

    /**
     * Get participant targets
     */
    public function getParticipantTargets($programId, $pesertaId, $pesertaType)
    {
        $pesertaTypeClass = $this->getPesertaTypeClass($pesertaType);

        // Get all targets for this participant
        $targets = DB::table('rencana_latihan_peserta_target')
            ->join('target_latihan', 'rencana_latihan_peserta_target.target_latihan_id', '=', 'target_latihan.id')
            ->join('rencana_latihan', 'rencana_latihan_peserta_target.rencana_latihan_id', '=', 'rencana_latihan.id')
            ->where('target_latihan.program_latihan_id', $programId)
            ->where('rencana_latihan_peserta_target.peserta_id', $pesertaId)
            ->where('rencana_latihan_peserta_target.peserta_type', $pesertaTypeClass)
            ->select(
                'target_latihan.id',
                'target_latihan.deskripsi as nama',
                'target_latihan.nilai_target',
                'target_latihan.satuan',
                'rencana_latihan_peserta_target.nilai',
                'rencana_latihan_peserta_target.trend',
                'rencana_latihan.tanggal'
            )
            ->orderBy('rencana_latihan.tanggal', 'desc')
            ->get()
            ->groupBy('id')
            ->map(function ($group) {
                $target     = $group->first();
                $latestData = $group->sortByDesc('tanggal')->first();

                return [
                    'id'            => $target->id,
                    'nama'          => $target->nama,
                    'target'        => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
                    'nilaiTerakhir' => $latestData->nilai ?? '-',
                    'trend'         => $this->mapTrendStatus($latestData->trend ?? 'stabil'),
                ];
            })
            ->values();

        return $targets;
    }

    /**
     * Get target info
     */
    public function getTargetInfo($targetId, $programId = null, $pesertaId = null, $pesertaType = null)
    {
        $query = DB::table('target_latihan')
            ->where('target_latihan.id', $targetId);

        if ($programId) {
            $query->where('target_latihan.program_latihan_id', $programId);
        }

        $target = $query->first();

        if (!$target) {
            return null;
        }

        return [
            'id'     => $target->id,
            'nama'   => $target->deskripsi,
            'target' => ($target->nilai_target ?? '') . ' ' . ($target->satuan ?? ''),
        ];
    }

    /**
     * Get participant target chart data
     */
    public function getParticipantTargetChartData($programId, $pesertaId, $pesertaType, $targetId)
    {
        $pesertaTypeClass = $this->getPesertaTypeClass($pesertaType);

        // Get all data for this parameter and participant across all rencana
        $data = DB::table('rencana_latihan_peserta_target')
            ->join('rencana_latihan', 'rencana_latihan_peserta_target.rencana_latihan_id', '=', 'rencana_latihan.id')
            ->join('target_latihan', 'rencana_latihan_peserta_target.target_latihan_id', '=', 'target_latihan.id')
            ->where('target_latihan.program_latihan_id', $programId)
            ->where('rencana_latihan_peserta_target.peserta_id', $pesertaId)
            ->where('rencana_latihan_peserta_target.peserta_type', $pesertaTypeClass)
            ->where('target_latihan.id', $targetId)
            ->select(
                'rencana_latihan_peserta_target.nilai',
                'rencana_latihan_peserta_target.trend',
                'rencana_latihan.tanggal',
                'rencana_latihan.materi as nama_rencana'
            )
            ->orderBy('rencana_latihan.tanggal', 'asc')
            ->get();

        // Format chart data
        $chartData = $data->map(function ($item, $index) {
            return [
                'month' => $this->formatDateForChart($item->tanggal),
                'nilai' => $item->nilai ? (float) $item->nilai : null,
                'trend' => $this->mapTrendStatus($item->trend ?? 'stabil'),
            ];
        })->filter(function ($item) {
            return $item['nilai'] !== null;
        })->values();

        // Format detail data
        $detailData = $data->map(function ($item) {
            return [
                'tanggal' => $this->formatDateForDetail($item->tanggal),
                'rencana' => $item->nama_rencana,
                'nilai'   => $item->nilai ?? '-',
                'status'  => $this->mapTrendStatus($item->trend ?? 'stabil'),
            ];
        })->values();

        return [
            'chartData'  => $chartData,
            'detailData' => $detailData,
        ];
    }

    /**
     * Map jenis kelamin
     */
    protected function mapJenisKelamin($jenisKelamin)
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
    protected function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $tanggalLahir = new Carbon($tanggalLahir);
            $today        = Carbon::today();
            return (int) $tanggalLahir->diffInYears($today);
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Get peserta type class
     */
    private function getPesertaTypeClass($pesertaType)
    {
        switch ($pesertaType) {
            case 'atlet':
                return 'App\\Models\\Atlet';
            case 'pelatih':
                return 'App\\Models\\Pelatih';
            case 'tenaga-pendukung':
                return 'App\\Models\\TenagaPendukung';
            default:
                return 'App\\Models\\Atlet';
        }
    }

    /**
     * Map trend status to Indonesian
     */
    protected function mapTrendStatus($trend)
    {
        return match ($trend) {
            'stabil' => 'Stabil',
            'naik'   => 'Naik',
            'turun'  => 'Turun',
            default  => 'Stabil',
        };
    }

    /**
     * Format date for chart
     */
    private function formatDateForChart($date)
    {
        return Carbon::parse($date)->format('M');
    }

    /**
     * Format date for detail
     */
    private function formatDateForDetail($date)
    {
        return Carbon::parse($date)->format('d/m');
    }
}
