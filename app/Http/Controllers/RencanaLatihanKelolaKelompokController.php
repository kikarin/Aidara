<?php

namespace App\Http\Controllers;

use App\Models\RencanaLatihan;
use App\Models\TargetLatihan;
use App\Models\ProgramLatihan;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class RencanaLatihanKelolaKelompokController extends Controller implements HasMiddleware
{
    use BaseTrait;

    public function __construct()
    {
        $this->initialize();
        $this->route                          = 'rencana-latihan-kelola-kelompok';
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

    public function index($program_id)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);

        $rencanaLatihanList = RencanaLatihan::with([
            'targetLatihan' => function ($query) {
                $query->where('jenis_target', 'kelompok');
            },
        ])->where('program_latihan_id', $program_id)
          ->orderBy('id', 'desc')
          ->get();

        $targetLatihan = TargetLatihan::where('program_latihan_id', $program_id)
            ->where('jenis_target', 'kelompok')
            ->get();

        $data = $this->commonData + [
            'titlePage'       => 'Kelola Target Kelompok Rencana Latihan',
            'program_id'      => $program_id,
            'program_latihan' => [
                'nama_program'        => $programLatihan->nama_program,
                'cabor_nama'          => $programLatihan->cabor->nama,
                'cabor_kategori_nama' => $programLatihan->caborKategori->nama,
            ],
            'rencana_latihan_list' => $rencanaLatihanList->map(function ($rencana) {
                return [
                    'id'                      => $rencana->id,
                    'tanggal'                 => $rencana->tanggal,
                    'materi'                  => $rencana->materi,
                    'lokasi_latihan'          => $rencana->lokasi_latihan,
                    'jumlah_atlet'            => $rencana->atlets()->count(),
                    'jumlah_pelatih'          => $rencana->pelatihs()->count(),
                    'jumlah_tenaga_pendukung' => $rencana->tenagaPendukung()->count(),
                    'target_latihan'          => $rencana->targetLatihan->where('jenis_target', 'kelompok')->map(function ($target) {
                        return [
                            'id'           => $target->id,
                            'deskripsi'    => $target->deskripsi,
                            'satuan'       => $target->satuan,
                            'nilai_target' => $target->nilai_target,
                        ];
                    }),
                ];
            }),
            'target_latihan' => $targetLatihan->map(function ($target) {
                return [
                    'id'           => $target->id,
                    'deskripsi'    => $target->deskripsi,
                    'satuan'       => $target->satuan,
                    'nilai_target' => $target->nilai_target,
                ];
            }),
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return Inertia::render('modules/rencana-latihan/MassEdit', $data);
    }

    public function getTargetKelompokMapping($rencana_id, Request $request)
    {
        try {
            $rencanaLatihan = RencanaLatihan::findOrFail($rencana_id);

            // Ambil data dari pivot table rencana_latihan_target_latihan
            $existingData = DB::table('rencana_latihan_target_latihan')
                ->join('target_latihan', 'rencana_latihan_target_latihan.target_latihan_id', '=', 'target_latihan.id')
                ->where('rencana_latihan_target_latihan.rencana_latihan_id', $rencana_id)
                ->where('target_latihan.jenis_target', 'kelompok')
                ->select('rencana_latihan_target_latihan.*')
                ->get();

            $mapping = [];
            foreach ($existingData as $data) {
                $mapping[$data->target_latihan_id] = [
                    'nilai' => $data->nilai,
                    'trend' => $data->trend,
                ];
            }

            return response()->json($mapping);
        } catch (\Exception $e) {
            Log::error('Error in getTargetKelompokMapping: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function bulkUpdate(Request $request, $program_id)
    {
        $request->validate([
            'data'                      => 'required|array',
            'data.*.rencana_latihan_id' => 'required|exists:rencana_latihan,id',
            'data.*.target_latihan_id'  => 'required|exists:target_latihan,id',
            'data.*.nilai'              => 'required|string',
            'data.*.trend'              => 'required|in:naik,stabil,turun',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->data as $item) {
                $targetLatihan = TargetLatihan::find($item['target_latihan_id']);
                if (!$targetLatihan || $targetLatihan->jenis_target !== 'kelompok') {
                    continue;
                }

                // Cek apakah data sudah ada
                $existing = DB::table('rencana_latihan_target_latihan')
                    ->where('rencana_latihan_id', $item['rencana_latihan_id'])
                    ->where('target_latihan_id', $item['target_latihan_id'])
                    ->first();

                if ($existing) {
                    // Update data yang sudah ada
                    DB::table('rencana_latihan_target_latihan')
                        ->where('rencana_latihan_id', $item['rencana_latihan_id'])
                        ->where('target_latihan_id', $item['target_latihan_id'])
                        ->update([
                            'nilai' => $item['nilai'],
                            'trend' => $item['trend'],
                        ]);
                } else {
                    // Insert data baru
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
                'success' => true,
                'message' => 'Data target kelompok berhasil disimpan!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
