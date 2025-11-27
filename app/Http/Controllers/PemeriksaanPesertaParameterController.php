<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemeriksaanPesertaParameterRequest;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanParameter;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanPesertaParameter;
use App\Repositories\PemeriksaanPesertaParameterRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PemeriksaanPesertaParameterController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, PemeriksaanPesertaParameterRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = PemeriksaanPesertaParameterRequest::createFromBase($request);
        $this->initialize();
        $this->route = 'pemeriksaan-peserta-parameter';
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index', 'massEdit']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function index(Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta)
    {
        $pemeriksaan->load(['cabor', 'caborKategori', 'tenagaPendukung']);
        $peserta->load(['peserta']);
        $this->repository->customProperty(__FUNCTION__);
        $data = [
            'pemeriksaan' => $pemeriksaan,
            'peserta'     => $peserta,
        ];
        request()->merge([
            'pemeriksaan_id'         => $pemeriksaan->id,
            'pemeriksaan_peserta_id' => $peserta->id,
        ]);
        $data = $this->repository->customIndex($data);

        return inertia('modules/pemeriksaan-peserta-parameter/Index', $data + [
            'pemeriksaan'   => $pemeriksaan,
            'peserta'       => $peserta,
            'jenis_peserta' => request('jenis_peserta', 'atlet'),
        ]);
    }

    public function create(Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta)
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = [
            'item'        => null,
            'pemeriksaan' => $pemeriksaan,
            'peserta'     => $peserta,
            'parameters'  => PemeriksaanParameter::where('pemeriksaan_id', $pemeriksaan->id)->with('mstParameter')->get(),
        ];
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/pemeriksaan-peserta-parameter/Create', $data + [
            'jenis_peserta' => request('jenis_peserta', 'atlet'),
        ]);
    }

    public function store(PemeriksaanPesertaParameterRequest $request, Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta)
    {
        $data                           = $this->repository->validateRequest($request);
        $data['pemeriksaan_id']         = $pemeriksaan->id;
        $data['pemeriksaan_peserta_id'] = $peserta->id;
        $this->repository->create($data);

        return redirect()->route('pemeriksaan.peserta.parameter.index', [$pemeriksaan->id, $peserta->id])->with('success', 'Parameter peserta berhasil ditambahkan!');
    }

    public function show(Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta, $id)
    {
        $item = $this->repository->getByIdArray($id);

        return Inertia::render('modules/pemeriksaan-peserta-parameter/Show', [
            'item'          => $item,
            'pemeriksaan'   => $pemeriksaan,
            'peserta'       => $peserta,
            'jenis_peserta' => request('jenis_peserta', 'atlet'),
        ]);
    }

    public function edit(Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta, $id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getByIdArray($id);
        $data = [
            'item'        => $item,
            'pemeriksaan' => $pemeriksaan,
            'peserta'     => $peserta,
            'parameters'  => PemeriksaanParameter::where('pemeriksaan_id', $pemeriksaan->id)->with('mstParameter')->get(),
        ];
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/pemeriksaan-peserta-parameter/Edit', $data + [
            'jenis_peserta' => request('jenis_peserta', 'atlet'),
        ]);
    }

    public function update(PemeriksaanPesertaParameterRequest $request, Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta, $id)
    {
        $data                           = $this->repository->validateRequest($request);
        $data['pemeriksaan_id']         = $pemeriksaan->id;
        $data['pemeriksaan_peserta_id'] = $peserta->id;
        $this->repository->update($id, $data);

        return redirect()->route('pemeriksaan.peserta.parameter.index', [$pemeriksaan->id, $peserta->id])->with('success', 'Parameter peserta berhasil diperbarui!');
    }

    public function destroy(Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta, $id)
    {
        $this->repository->delete($id);

        return redirect()->route('pemeriksaan.peserta.parameter.index', [$pemeriksaan->id, $peserta->id])->with('success', 'Parameter peserta berhasil dihapus!');
    }

    public function destroy_selected(Request $request, Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:pemeriksaan_peserta_parameter,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Parameter peserta berhasil dihapus!']);
    }

    public function apiIndex(Pemeriksaan $pemeriksaan, PemeriksaanPeserta $peserta)
    {
        request()->merge([
            'pemeriksaan_id'         => $pemeriksaan->id,
            'pemeriksaan_peserta_id' => $peserta->id,
        ]);
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['data'],
            'meta' => [
                'total'        => $data['total'],
                'current_page' => $data['currentPage'],
                'per_page'     => $data['perPage'],
                'search'       => $data['search'],
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ]);
    }

    public function massEdit(Pemeriksaan $pemeriksaan, Request $request)
    {
        $jenisPeserta = $request->query('jenis_peserta', 'atlet');

        // Data dummy, nanti diisi fetch peserta, parameter, dan nilai
        return Inertia::render('modules/pemeriksaan-peserta-parameter/MassEdit', [
            'pemeriksaan' => [
                'id'               => $pemeriksaan->id,
                'nama'             => $pemeriksaan->nama_pemeriksaan,
                'cabor'            => $pemeriksaan->cabor?->nama,
                'kategori'         => $pemeriksaan->caborKategori?->nama,
                'tenaga_pendukung' => $pemeriksaan->tenagaPendukung?->nama,
            ],
            'jenis_peserta' => $jenisPeserta,
        ]);
    }

    public function bulkUpdate(Request $request, Pemeriksaan $pemeriksaan)
    {
        $data = $request->validate([
            'data'                             => 'required|array',
            'data.*.peserta_id'                => 'required|integer|exists:pemeriksaan_peserta,id',
            'data.*.status'                    => 'nullable|exists:ref_status_pemeriksaan,id',
            'data.*.catatan'                   => 'nullable|string',
            'data.*.parameters'                => 'required|array',
            'data.*.parameters.*.parameter_id' => 'required|integer|exists:pemeriksaan_parameter,id',
            'data.*.parameters.*.nilai'        => 'nullable|string',
            'data.*.parameters.*.trend'        => 'nullable|in:stabil,kenaikan,penurunan',
        ]);

        DB::beginTransaction();
        try {
            foreach ($data['data'] as $pesertaData) {
                $peserta = PemeriksaanPeserta::findOrFail($pesertaData['peserta_id']);
                $peserta->update([
                    'ref_status_pemeriksaan_id' => $pesertaData['status'],
                    'catatan_umum'              => $pesertaData['catatan'],
                ]);

                foreach ($pesertaData['parameters'] as $param) {
                    PemeriksaanPesertaParameter::updateOrCreate(
                        [
                            'pemeriksaan_id'           => $pemeriksaan->id,
                            'pemeriksaan_peserta_id'   => $pesertaData['peserta_id'],
                            'pemeriksaan_parameter_id' => $param['parameter_id'],
                        ],
                        [
                            'nilai' => $param['nilai'],
                            'trend' => $param['trend'],
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in bulk update: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data, terjadi kesalahan server.',
            ], 500);
        }
    }
}
