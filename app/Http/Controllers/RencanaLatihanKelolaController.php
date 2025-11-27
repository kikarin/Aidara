<?php

namespace App\Http\Controllers;

use App\Models\RencanaLatihan;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RencanaLatihanKelolaController extends Controller implements HasMiddleware
{
    use BaseTrait;

    public function __construct()
    {
        $this->initialize();
        $this->route                          = 'rencana-latihan-kelola';
        $this->commonData['kode_first_menu']  = 'RENCANA-LATIHAN';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function index($program_id, $rencana_id, $jenis_peserta)
    {
        $rencanaLatihan = RencanaLatihan::with(['programLatihan.cabor', 'programLatihan.caborKategori', 'targetLatihan'])
            ->findOrFail($rencana_id);

        // Ambil target latihan individu dari rencana latihan yang dipilih berdasarkan peruntukan
        $targetLatihan = $rencanaLatihan->targetLatihan()
            ->where('jenis_target', 'individu')
            ->where('peruntukan', $jenis_peserta)
            ->select('target_latihan.id', 'target_latihan.deskripsi', 'target_latihan.satuan', 'target_latihan.nilai_target', 'target_latihan.peruntukan')
            ->get();

        // Ambil peserta berdasarkan jenis
        $pesertaList = $this->getPesertaList($rencana_id, $jenis_peserta);

        $data = $this->commonData + [
            'titlePage'       => 'Kelola Pemetaan Rencana Latihan',
            'program_id'      => $program_id,
            'rencana_latihan' => [
                'id'              => $rencanaLatihan->id,
                'tanggal'         => $rencanaLatihan->tanggal,
                'materi'          => $rencanaLatihan->materi,
                'lokasi_latihan'  => $rencanaLatihan->lokasi_latihan,
                'program_latihan' => [
                    'nama_program'        => $rencanaLatihan->programLatihan->nama_program,
                    'cabor_nama'          => $rencanaLatihan->programLatihan->cabor->nama,
                    'cabor_kategori_nama' => $rencanaLatihan->programLatihan->caborKategori->nama,
                ],
            ],
            'jenis_peserta'  => $jenis_peserta,
            'target_latihan' => $targetLatihan,
            'peserta_list'   => $pesertaList,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return Inertia::render('modules/rencana-latihan/index/MassEdit', $data);
    }

    public function getTargetMapping($rencana_id, Request $request)
    {
        $jenis_peserta = $request->input('jenis_peserta');
        $pesertaType   = $this->getPesertaType($jenis_peserta);

        $mapping = DB::table('rencana_latihan_peserta_target')
            ->where('rencana_latihan_id', $rencana_id)
            ->where('peserta_type', $pesertaType)
            ->get()
            ->groupBy(['peserta_id', 'target_latihan_id'])
            ->map(function ($pesertaGroup) {
                return $pesertaGroup->map(function ($targetGroup) {
                    return $targetGroup->map(function ($item) {
                        return [
                            'nilai' => $item->nilai,
                            'trend' => $item->trend,
                        ];
                    })->first();
                });
            });

        return response()->json($mapping);
    }

    public function bulkUpdate(Request $request, $program_id, $rencana_id, $jenis_peserta)
    {
        $request->validate([
            'data'                     => 'required|array',
            'data.*.peserta_id'        => 'required|integer',
            'data.*.target_latihan_id' => 'required|integer',
            'data.*.nilai'             => 'nullable|string',
            'data.*.trend'             => 'required|in:naik,stabil,turun',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->data as $data) {
                // Cek apakah data sudah ada untuk peserta dan target tertentu
                $existing = DB::table('rencana_latihan_peserta_target')
                    ->where('rencana_latihan_id', $rencana_id)
                    ->where('target_latihan_id', $data['target_latihan_id'])
                    ->where('peserta_id', $data['peserta_id'])
                    ->where('peserta_type', $this->getPesertaType($jenis_peserta))
                    ->first();

                if ($existing) {
                    // Update data yang sudah ada
                    DB::table('rencana_latihan_peserta_target')
                        ->where('rencana_latihan_id', $rencana_id)
                        ->where('target_latihan_id', $data['target_latihan_id'])
                        ->where('peserta_id', $data['peserta_id'])
                        ->where('peserta_type', $this->getPesertaType($jenis_peserta))
                        ->update([
                            'nilai' => $data['nilai'],
                            'trend' => $data['trend'],
                        ]);
                } else {
                    // Insert data baru
                    DB::table('rencana_latihan_peserta_target')->insert([
                        'rencana_latihan_id' => $rencana_id,
                        'target_latihan_id'  => $data['target_latihan_id'],
                        'peserta_id'         => $data['peserta_id'],
                        'peserta_type'       => $this->getPesertaType($jenis_peserta),
                        'nilai'              => $data['nilai'],
                        'trend'              => $data['trend'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pemetaan berhasil disimpan!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getPesertaList($rencana_id, $jenis_peserta)
    {
        $rencanaLatihan  = RencanaLatihan::with(['programLatihan'])->findOrFail($rencana_id);
        $caborKategoriId = $rencanaLatihan->programLatihan->cabor_kategori_id;

        switch ($jenis_peserta) {
            case 'atlet':
                $query = $rencanaLatihan->atlets()
                    ->join('cabor_kategori_atlet', function ($join) use ($caborKategoriId) {
                        $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                            ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                            ->whereNull('cabor_kategori_atlet.deleted_at');
                    })
                    ->leftJoin('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
                    ->select(
                        'atlets.id',
                        'atlets.nama',
                        'atlets.jenis_kelamin',
                        'atlets.tanggal_lahir',
                        'mst_posisi_atlet.nama as posisi_atlet_nama'
                    );

                return $query->get()->map(function ($atlet) {
                    return [
                        'id'            => $atlet->id,
                        'nama'          => $atlet->nama,
                        'jenis_kelamin' => $atlet->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                        'usia'          => $this->calculateAge($atlet->tanggal_lahir),
                        'posisi'        => $atlet->posisi_atlet_nama ?? '-',
                    ];
                });

            case 'pelatih':
                $query = $rencanaLatihan->pelatihs()
                    ->join('cabor_kategori_pelatih', function ($join) use ($caborKategoriId) {
                        $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                            ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
                            ->whereNull('cabor_kategori_pelatih.deleted_at');
                    })
                    ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
                    ->select(
                        'pelatihs.id',
                        'pelatihs.nama',
                        'pelatihs.jenis_kelamin',
                        'pelatihs.tanggal_lahir',
                        'mst_jenis_pelatih.nama as jenis_pelatih_nama'
                    );

                return $query->get()->map(function ($pelatih) {
                    return [
                        'id'            => $pelatih->id,
                        'nama'          => $pelatih->nama,
                        'jenis_kelamin' => $pelatih->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                        'usia'          => $this->calculateAge($pelatih->tanggal_lahir),
                        'jenis_pelatih' => $pelatih->jenis_pelatih_nama ?? '-',
                    ];
                });

            case 'tenaga-pendukung':
                $query = $rencanaLatihan->tenagaPendukung()
                    ->join('cabor_kategori_tenaga_pendukung', function ($join) use ($caborKategoriId) {
                        $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                            ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
                            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                    })
                    ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
                    ->select(
                        'tenaga_pendukungs.id',
                        'tenaga_pendukungs.nama',
                        'tenaga_pendukungs.jenis_kelamin',
                        'tenaga_pendukungs.tanggal_lahir',
                        'mst_jenis_tenaga_pendukung.nama as jenis_tenaga_pendukung_nama'
                    );

                return $query->get()->map(function ($tenaga) {
                    return [
                        'id'                     => $tenaga->id,
                        'nama'                   => $tenaga->nama,
                        'jenis_kelamin'          => $tenaga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                        'usia'                   => $this->calculateAge($tenaga->tanggal_lahir),
                        'jenis_tenaga_pendukung' => $tenaga->jenis_tenaga_pendukung_nama ?? '-',
                    ];
                });

            default:
                return [];
        }
    }

    private function calculateAge($birthDate)
    {
        if (! $birthDate) {
            return '-';
        }

        $today = new \DateTime();
        $birth = new \DateTime($birthDate);
        $age   = $today->diff($birth);

        return $age->y;
    }

    private function getPesertaType($jenis_peserta)
    {
        switch ($jenis_peserta) {
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
}
