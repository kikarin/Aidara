<?php

namespace App\Http\Controllers;

use App\Models\MstParameter;
use App\Models\PemeriksaanParameter;
use App\Repositories\MstParameterRepository;
use App\Traits\BaseTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Pemeriksaan;
use Illuminate\Support\Facades\DB;

class AllParameterController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    public function __construct(MstParameterRepository $repository)
    {
        $this->repository = $repository;
        $this->initialize();
        $this->route = 'all-parameter';
    }

    /**
     * Helper function to parse number with comma as decimal separator
     * Handles both Indonesian format (comma) and international format (dot)
     *
     * @param string|null $value
     * @return float|null
     */
    private function parseNumber($value)
    {
        if (empty($value)) {
            return null;
        }

        // Convert to string and trim whitespace
        $strValue = trim((string) $value);
        if (empty($strValue)) {
            return null;
        }

        // Replace comma with dot for decimal separator (Indonesian format)
        // Handle both formats: "15,6" and "15.6"
        $normalizedValue = str_replace(',', '.', $strValue);
        $parsed = (float) $normalizedValue;

        return is_nan($parsed) ? null : $parsed;
    }

    public static function middleware(): array
    {
        return [];
    }

    public function index()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        // Get all unique parameters that are used in pemeriksaan
        $parameters = MstParameter::whereHas('pemeriksaanParameters')
            ->withCount('pemeriksaanParameters')
            ->get()
            ->map(function ($item) {
                return [
                    'id'                 => $item->id,
                    'nama'               => $item->nama,
                    'satuan'             => $item->satuan,
                    'jumlah_pemeriksaan' => $item->pemeriksaan_parameters_count,
                ];
            });

        $data['parameters'] = $parameters;

        return inertia('modules/all-parameter/Index', $data);
    }

    public function show($parameterId)
    {
        $parameter = MstParameter::findOrFail($parameterId);

        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        $data['parameter'] = [
            'id'     => $parameter->id,
            'nama'   => $parameter->nama,
            'satuan' => $parameter->satuan,
        ];

        return inertia('modules/all-parameter/Statistik', $data);
    }

    public function apiIndex()
    {
        // Get all unique parameters that are used in pemeriksaan
        $parameters = MstParameter::whereHas('pemeriksaanParameters')
            ->withCount('pemeriksaanParameters')
            ->get()
            ->map(function ($item) {
                return [
                    'id'                 => $item->id,
                    'nama'               => $item->nama,
                    'satuan'             => $item->satuan,
                    'jumlah_pemeriksaan' => $item->pemeriksaan_parameters_count,
                ];
            });

        return response()->json([
            'data'  => $parameters,
            'total' => $parameters->count(),
        ]);
    }

    public function apiStatistik($parameterId)
    {
        $parameter = MstParameter::findOrFail($parameterId);
        $caborId = request('cabor_id');

        // Get all pemeriksaan that use this parameter
        $pemeriksaanIds = PemeriksaanParameter::where('mst_parameter_id', $parameterId)
            ->pluck('pemeriksaan_id')
            ->unique();

        // Get all pemeriksaan with their details
        $pemeriksaanList = Pemeriksaan::whereIn('id', $pemeriksaanIds)
            ->select('id', 'nama_pemeriksaan', 'tanggal_pemeriksaan')
            ->get();

        // Get all statistik data for this parameter across all pemeriksaan
        $statistikData = DB::table('pemeriksaan_peserta_parameter')
            ->join('pemeriksaan_peserta', 'pemeriksaan_peserta_parameter.pemeriksaan_peserta_id', '=', 'pemeriksaan_peserta.id')
            ->join('pemeriksaan_parameter', 'pemeriksaan_peserta_parameter.pemeriksaan_parameter_id', '=', 'pemeriksaan_parameter.id')
            ->where('pemeriksaan_parameter.mst_parameter_id', $parameterId)
            ->select(
                'pemeriksaan_peserta_parameter.pemeriksaan_peserta_id',
                'pemeriksaan_peserta.peserta_id',
                'pemeriksaan_peserta.peserta_type',
                'pemeriksaan_peserta_parameter.nilai',
                'pemeriksaan_peserta_parameter.trend',
                'pemeriksaan_parameter.pemeriksaan_id'
            )
            ->get();

        // Get peserta list dengan filter cabor
        $pesertaList = collect();
        foreach ($statistikData as $data) {
            $pesertaType = $data->peserta_type;
            $pesertaId   = $data->peserta_id;

            // Filter berdasarkan cabor jika dipilih
            $isInCabor = false;
            if ($caborId) {
                switch ($pesertaType) {
                    case 'App\\Models\\Atlet':
                        $isInCabor = DB::table('cabor_kategori_atlet')
                            ->where('atlet_id', $pesertaId)
                            ->where('cabor_id', $caborId)
                            ->whereNull('deleted_at')
                            ->exists();
                        break;

                    case 'App\\Models\\Pelatih':
                        $isInCabor = DB::table('cabor_kategori_pelatih')
                            ->where('pelatih_id', $pesertaId)
                            ->where('cabor_id', $caborId)
                            ->whereNull('deleted_at')
                            ->exists();
                        break;

                    case 'App\\Models\\TenagaPendukung':
                        $isInCabor = DB::table('cabor_kategori_tenaga_pendukung')
                            ->where('tenaga_pendukung_id', $pesertaId)
                            ->where('cabor_id', $caborId)
                            ->whereNull('deleted_at')
                            ->exists();
                        break;
                }
            } else {
                // Jika tidak ada filter cabor, tampilkan semua
                $isInCabor = true;
            }

            if (!$isInCabor) {
                continue;
            }

            switch ($pesertaType) {
                case 'App\\Models\\Atlet':
                    $atlet = DB::table('atlets')
                        ->where('id', $pesertaId)
                        ->select('id', 'nama', 'jenis_kelamin')
                        ->first();
                    if ($atlet) {
                        $pesertaList->push([
                            'id'            => $atlet->id,
                            'nama'          => $atlet->nama,
                            'jenis_peserta' => 'atlet',
                            'jenis_kelamin' => $atlet->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                        ]);
                    }
                    break;

                case 'App\\Models\\Pelatih':
                    // Hanya untuk parameter kesehatan
                    if ($parameter->kategori === 'kesehatan') {
                        $pelatih = DB::table('pelatihs')
                            ->where('id', $pesertaId)
                            ->select('id', 'nama', 'jenis_kelamin')
                            ->first();
                        if ($pelatih) {
                            $pesertaList->push([
                                'id'            => $pelatih->id,
                                'nama'          => $pelatih->nama,
                                'jenis_peserta' => 'pelatih',
                                'jenis_kelamin' => $pelatih->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                            ]);
                        }
                    }
                    break;

                case 'App\\Models\\TenagaPendukung':
                    // Hanya untuk parameter kesehatan
                    if ($parameter->kategori === 'kesehatan') {
                        $tenaga = DB::table('tenaga_pendukungs')
                            ->where('id', $pesertaId)
                            ->select('id', 'nama', 'jenis_kelamin')
                            ->first();
                        if ($tenaga) {
                            $pesertaList->push([
                                'id'            => $tenaga->id,
                                'nama'          => $tenaga->nama,
                                'jenis_peserta' => 'tenaga-pendukung',
                                'jenis_kelamin' => $tenaga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                            ]);
                        }
                    }
                    break;
            }
        }

        // Filter statistikData berdasarkan peserta yang ada di pesertaList
        $pesertaIds = $pesertaList->pluck('id')->toArray();
        $transformedData = $statistikData
            ->filter(function ($item) use ($pesertaIds) {
                return in_array($item->peserta_id, $pesertaIds);
            })
            ->map(function ($item) use ($pemeriksaanList) {
            $pemeriksaan = $pemeriksaanList->firstWhere('id', $item->pemeriksaan_id);

            return [
                'peserta_id'             => $item->peserta_id,
                'pemeriksaan_peserta_id' => $item->pemeriksaan_peserta_id,
                'nilai'                  => $item->nilai,
                'trend'                  => $item->trend,
                'tanggal_pemeriksaan'    => $pemeriksaan->tanggal_pemeriksaan ?? null,
                'pemeriksaan_id'         => $item->pemeriksaan_id,
            ];
        });

        return response()->json([
            'data'                => $transformedData,
            'rencana_pemeriksaan' => $pemeriksaanList->toArray(),
            'peserta'             => $pesertaList->unique('id')->values()->toArray(),
            'parameter_info'      => [
                'id'            => $parameter->id,
                'nama'          => $parameter->nama,
                'satuan'        => $parameter->satuan,
                'kategori'      => $parameter->kategori ?? 'kesehatan',
                'nilai_target'  => $parameter->nilai_target,
                'performa_arah' => $parameter->performa_arah ?? 'max',
            ],
        ]);
    }
}
