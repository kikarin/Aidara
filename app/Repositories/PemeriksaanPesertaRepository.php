<?php

namespace App\Repositories;

use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanPeserta;
use App\Models\TenagaPendukung;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PemeriksaanPesertaRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PemeriksaanPeserta $model)
    {
        $this->model = $model;
        $this->with  = ['pemeriksaan', 'status', 'peserta', 'created_by_user', 'updated_by_user', 'pemeriksaanPesertaParameter'];
    }

    public function customIndex($data)
    {
        // Pastikan relasi peserta dimuat dengan benar
        $query = $this->model->with($this->with);

        // Log untuk debugging
        Log::info('customIndex called with data: '.json_encode($data));
        Log::info('Request pemeriksaan_id: '.request('pemeriksaan_id'));
        Log::info('Request jenis_peserta: '.request('jenis_peserta'));

        if (request('pemeriksaan_id')) {
            $query->where('pemeriksaan_id', request('pemeriksaan_id'));
        }

        if (request('jenis_peserta')) {
            $jenis      = request('jenis_peserta');
            $modelClass = match ($jenis) {
                'atlet'            => Atlet::class,
                'pelatih'          => Pelatih::class,
                'tenaga-pendukung' => TenagaPendukung::class,
                default            => null,
            };
            if ($modelClass) {
                $query->where('peserta_type', $modelClass);
                Log::info('Filtering by peserta_type: '.$modelClass);
            }
        }

        if (request('search')) {
            $search = request('search');
            $query->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $sortField = request('sort');
        $order     = request('order', 'asc');
        $perPage   = (int) request('per_page', 10);
        $page      = (int) request('page', 1);

        if ($sortField) {
            $jenis = request('jenis_peserta');
            $table = match ($jenis) {
                'atlet'            => 'atlets',
                'pelatih'          => 'pelatihs',
                'tenaga-pendukung' => 'tenaga_pendukungs',
                default            => null,
            };
            if (str_starts_with($sortField, 'peserta.')) {
                $field = explode('.', $sortField)[1];
                if ($field !== 'foto' && $table) {
                    $query->leftJoin($table, 'pemeriksaan_peserta.peserta_id', '=', "$table.id")
                        ->orderBy("$table.$field", $order)
                        ->select('pemeriksaan_peserta.*');
                }
            } elseif ($sortField === 'status') {
                $query->leftJoin('ref_status_pemeriksaan', 'pemeriksaan_peserta.ref_status_pemeriksaan_id', '=', 'ref_status_pemeriksaan.id')
                    ->orderBy('ref_status_pemeriksaan.nama', $order)
                    ->select('pemeriksaan_peserta.*');
            } elseif ($sortField === 'jumlah_parameter') {
            } else {
                $query->orderBy($sortField, $order);
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $query->with(['peserta']);

        // Handle show entries all
        if ($perPage == -1) {
            $all         = $query->get();
            $transformed = collect($all)->map(function ($item) {
                $peserta = $item->peserta;
                if (is_object($peserta)) {
                    $pesertaData = [
                        'id'            => $peserta->id,
                        'nama'          => $peserta->nama          ?? null,
                        'tempat_lahir'  => $peserta->tempat_lahir  ?? null,
                        'jenis_kelamin' => $peserta->jenis_kelamin ?? null,
                        'tanggal_lahir' => $peserta->tanggal_lahir ?? null,
                        'foto'          => $peserta->foto          ?? null,
                    ];
                } else {
                    $pesertaData = null;
                }

                return [
                    'id'             => $item->id,
                    'pemeriksaan_id' => $item->pemeriksaan_id,
                    'peserta_type'   => $item->peserta_type,
                    'peserta_id'     => $item->peserta_id,
                    'peserta'        => $pesertaData,
                    'status'         => [
                        'id'   => $item->status?->id   ?? '',
                        'nama' => $item->status?->nama ?? '',
                    ],
                    'catatan_umum'      => $item->catatan_umum,
                    'created_at'        => $item->created_at,
                    'updated_at'        => $item->updated_at,
                    'parameter_peserta' => true,
                    'jumlah_parameter'  => $item->pemeriksaanParameter ? $item->pemeriksaanParameter()->count() : 0,
                ];
            });
            if ($sortField === 'jumlah_parameter') {
                $transformed = $order === 'asc'
                    ? $transformed->sortBy('jumlah_parameter')->values()
                    : $transformed->sortByDesc('jumlah_parameter')->values();
            }

            return [
                'data' => $transformed,
                'meta' => [
                    'total'        => $transformed->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => $sortField ?: '',
                    'order'        => $order,
                ],
            ];
        }

        $items       = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        $transformed = collect($items->items())->map(function ($item) {
            $peserta = $item->peserta;
            if (is_object($peserta)) {
                $pesertaData = [
                    'id'            => $peserta->id,
                    'nama'          => $peserta->nama          ?? null,
                    'tempat_lahir'  => $peserta->tempat_lahir  ?? null,
                    'jenis_kelamin' => $peserta->jenis_kelamin ?? null,
                    'tanggal_lahir' => $peserta->tanggal_lahir ?? null,
                    'foto'          => $peserta->foto          ?? null,
                ];
            } else {
                $pesertaData = null;
            }
            $parameterPeserta = [];
            if ($item->relationLoaded('pemeriksaanPesertaParameter')) {
                $parameterPeserta = $item->pemeriksaanPesertaParameter->map(function ($pp) {
                    return [
                        'id'                       => $pp->id,
                        'pemeriksaan_parameter_id' => $pp->pemeriksaan_parameter_id,
                        'nilai'                    => $pp->nilai,
                        'trend'                    => $pp->trend,
                    ];
                })->toArray();
            }

            return [
                'id'             => $item->id,
                'pemeriksaan_id' => $item->pemeriksaan_id,
                'peserta_type'   => $item->peserta_type,
                'peserta_id'     => $item->peserta_id,
                'peserta'        => $pesertaData,
                'status'         => [
                    'id'   => $item->status?->id   ?? '',
                    'nama' => $item->status?->nama ?? '',
                ],
                'catatan_umum'                => $item->catatan_umum,
                'created_at'                  => $item->created_at,
                'updated_at'                  => $item->updated_at,
                'parameter_peserta'           => true,
                'jumlah_parameter'            => $item->pemeriksaanPesertaParameter ? $item->pemeriksaanPesertaParameter->count() : 0,
                'pemeriksaanPesertaParameter' => $parameterPeserta,
            ];
        });

        return [
            'data' => $transformed,
            'meta' => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'search'       => request('search', ''),
                'sort'         => $sortField ?: '',
                'order'        => $order,
            ],
        ];
    }

    public function createMultiple(Pemeriksaan $pemeriksaan, array $data)
    {
        DB::beginTransaction();
        try {
            $this->createForType($pemeriksaan, $data, 'atlet_ids', Atlet::class);
            $this->createForType($pemeriksaan, $data, 'pelatih_ids', Pelatih::class);
            $this->createForType($pemeriksaan, $data, 'tenaga_pendukung_ids', TenagaPendukung::class);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createForType(Pemeriksaan $pemeriksaan, array $data, string $idKey, string $typeClass)
    {
        if (empty($data[$idKey])) {
            return;
        }

        $existingPesertaIds = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaan->id)
            ->where('peserta_type', $typeClass)
            ->pluck('peserta_id')
            ->toArray();

        $newPesertaIds = array_diff($data[$idKey], $existingPesertaIds);

        $insertData = [];
        $now        = now();
        $userId     = Auth::id();

        foreach ($newPesertaIds as $pesertaId) {
            $insertData[] = [
                'pemeriksaan_id'            => $pemeriksaan->id,
                'peserta_id'                => $pesertaId,
                'peserta_type'              => $typeClass,
                'ref_status_pemeriksaan_id' => $data['ref_status_pemeriksaan_id'],
                'catatan_umum'              => $data['catatan_umum'],
                'created_at'                => $now,
                'updated_at'                => $now,
                'created_by'                => $userId,
                'updated_by'                => $userId,
            ];
        }

        if (! empty($insertData)) {
            PemeriksaanPeserta::insert($insertData);
        }
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

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }

    public function getDetailWithRelations($id)
    {
        return $this->model->with($this->with)->findOrFail($id);
    }
}
