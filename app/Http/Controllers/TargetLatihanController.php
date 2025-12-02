<?php

namespace App\Http\Controllers;

use App\Http\Requests\TargetLatihanRequest;
use App\Models\ProgramLatihan;
use App\Models\TargetLatihan;
use App\Models\RencanaLatihan;
use App\Repositories\TargetLatihanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TargetLatihanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(Request $request, TargetLatihanRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->route                          = 'target-latihan';
        $this->commonData['kode_first_menu']  = 'PROGRAM-LATIHAN';
        $this->commonData['kode_second_menu'] = 'TARGET-LATIHAN';
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
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index', 'nestedIndex']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show', 'nestedShow']),
            new Middleware("can:$permission Edit", only: ['edit', 'update', 'nestedEdit', 'nestedUpdate']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected', 'nestedDestroy', 'nestedDestroySelected']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['data'],
            'meta' => [
                'total'        => $data['total'],
                'current_page' => $data['currentPage'],
                'per_page'     => $data['perPage'],
                'search'       => $data['search'],
                'sort'         => $data['sort'],
                'order'        => $data['order'],
            ],
        ]);
    }

    public function apiShow($id)
    {
        $target = $this->repository->getById($id);

        return response()->json([
            'id'                 => $target->id,
            'program_latihan_id' => $target->program_latihan_id,
            'jenis_target'       => $target->jenis_target,
            'peruntukan'         => $target->peruntukan,
            'deskripsi'          => $target->deskripsi,
            'satuan'             => $target->satuan,
            'nilai_target'       => $target->nilai_target,
            'performa_arah'      => $target->performa_arah ?? 'max',
        ]);
    }

    public function index(Request $request)
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);

        $programLatihan = null;
        if ($request->has('program_latihan_id')) {
            $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->find($request->program_latihan_id);
        }
        $data['infoHeader'] = [
            'nama_program'    => $programLatihan?->nama_program    ?? '-',
            'cabor_nama'      => $programLatihan?->cabor?->nama    ?? '-',
            'periode_mulai'   => $programLatihan?->periode_mulai   ?? '-',
            'periode_selesai' => $programLatihan?->periode_selesai ?? '-',
            'jenis_target'    => $request->jenis_target            ?? '-',
        ];

        return inertia('modules/target-latihan/Index', $data);
    }

    public function create(Request $request)
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        // Ambil info program/cabor/periode/jenis target dari query param jika ada
        $programLatihan = null;
        if ($request->has('program_latihan_id')) {
            $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->find($request->program_latihan_id);
        }
        $data['infoHeader'] = [
            'nama_program'    => $programLatihan?->nama_program    ?? '-',
            'cabor_nama'      => $programLatihan?->cabor?->nama    ?? '-',
            'periode_mulai'   => $programLatihan?->periode_mulai   ?? '-',
            'periode_selesai' => $programLatihan?->periode_selesai ?? '-',
            'jenis_target'    => $request->jenis_target            ?? '-',
        ];
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/target-latihan/Create', $data);
    }

    public function store(TargetLatihanRequest $request)
    {
        $data = $this->repository->validateRequest($request);

        // Untuk target kelompok, set peruntukan ke null
        if ($data['jenis_target'] === 'kelompok') {
            $data['peruntukan'] = null;
        }

        $this->repository->create($data);

        return redirect()->route('target-latihan.index')->with('success', 'Target latihan berhasil ditambahkan!');
    }

    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/target-latihan/Edit', $data);
    }

    public function update(TargetLatihanRequest $request, $id)
    {
        $data = $this->repository->validateRequest($request);

        // Untuk target kelompok, set peruntukan ke null
        if ($data['jenis_target'] === 'kelompok') {
            $data['peruntukan'] = null;
        }

        $this->repository->update($id, $data);

        return redirect()->route('target-latihan.index')->with('success', 'Target latihan berhasil diperbarui!');
    }

    public function show($id)
    {
        $item = $this->repository->getDetailWithRelations($id);

        return Inertia::render('modules/target-latihan/Show', [
            'item' => $item,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('target-latihan.index')->with('success', 'Target latihan berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:target_latihan,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Target latihan terpilih berhasil dihapus!']);
    }

    // =====================
    // NESTED MODULAR CRUD
    // =====================
    public function nestedIndex($program_id, $jenis_target, Request $request)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        // Filter by program_id & jenis_target
        $request->merge(['program_latihan_id' => $program_id, 'jenis_target' => $jenis_target]);

        // Filter by peruntukan only for target individu
        $peruntukan = $request->get('peruntukan');
        if ($peruntukan && $jenis_target === 'individu') {
            $request->merge(['peruntukan' => $peruntukan]);
        }

        // Set default peruntukan if not provided for target individu
        if (!$peruntukan && $jenis_target === 'individu') {
            $request->merge(['peruntukan' => 'atlet']);
        }
        $data               = $this->repository->customIndex($data);
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor ? $programLatihan->cabor->nama : '-',
            'cabor_kategori_nama' => $programLatihan->caborKategori ? $programLatihan->caborKategori->nama : null,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
            'jenis_target'        => $jenis_target,
            'peruntukan'          => $jenis_target === 'individu' ? ($peruntukan ?: 'atlet') : null,
        ];

        return inertia('modules/target-latihan/Index', $data);
    }

    public function nestedCreate($program_id, $jenis_target)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + ['item' => null];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor ? $programLatihan->cabor->nama : '-',
            'cabor_kategori_nama' => $programLatihan->caborKategori ? $programLatihan->caborKategori->nama : null,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
            'jenis_target'        => $jenis_target,
        ];
        $data = $this->repository->customCreateEdit($data);

        return inertia('modules/target-latihan/Create', $data);
    }

    public function nestedStore($program_id, $jenis_target, Request $request)
    {
        $programLatihan = ProgramLatihan::findOrFail($program_id);
        $request->merge([
            'program_latihan_id' => $program_id,
            'jenis_target'       => $jenis_target,
        ]);
        $data = $this->repository->validateRequest($request);

        // Untuk target kelompok, set peruntukan ke null
        if ($jenis_target === 'kelompok') {
            $data['peruntukan'] = null;
        }

        $this->repository->create($data);

        return redirect()->route('program-latihan.target-latihan.index', [$program_id, $jenis_target])->with('success', 'Target latihan berhasil ditambahkan!');
    }

    public function nestedShow($program_id, $jenis_target, $target_id)
    {
        $item = $this->repository->getDetailWithRelations($target_id);

        return inertia('modules/target-latihan/Show', ['item' => $item]);
    }

    public function nestedEdit($program_id, $jenis_target, $target_id)
    {
        $item           = $this->repository->getById($target_id);
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $data           = $this->commonData + ['item' => $item];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor ? $programLatihan->cabor->nama : '-',
            'cabor_kategori_nama' => $programLatihan->caborKategori ? $programLatihan->caborKategori->nama : null,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
            'jenis_target'        => $jenis_target,
        ];
        $data = $this->repository->customCreateEdit($data, $item);

        return inertia('modules/target-latihan/Edit', $data);
    }

    public function nestedUpdate($program_id, $jenis_target, $target_id, Request $request)
    {
        $request->merge([
            'program_latihan_id' => $program_id,
            'jenis_target'       => $jenis_target,
        ]);
        $data = $this->repository->validateRequest($request);

        // Untuk target kelompok, set peruntukan ke null
        if ($jenis_target === 'kelompok') {
            $data['peruntukan'] = null;
        }

        $this->repository->update($target_id, $data);

        return redirect()->route('program-latihan.target-latihan.index', [$program_id, $jenis_target])->with('success', 'Target latihan berhasil diperbarui!');
    }

    public function nestedDestroy($program_id, $jenis_target, $target_id)
    {
        $this->repository->delete($target_id);

        return redirect()->route('program-latihan.target-latihan.index', [$program_id, $jenis_target])->with('success', 'Target latihan berhasil dihapus!');
    }

    public function nestedDestroySelected(Request $request, $program_id, $jenis_target)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:target_latihan,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Target latihan terpilih berhasil dihapus!']);
    }

    public function nestedStatistik($program_id, $jenis_target, $target_id)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $targetLatihan  = $this->repository->getById($target_id);

        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor ? $programLatihan->cabor->nama : '-',
            'cabor_kategori_nama' => $programLatihan->caborKategori ? $programLatihan->caborKategori->nama : null,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
            'jenis_target'        => $jenis_target,
            'target_id'           => $target_id,
        ];

        return inertia('modules/target-latihan/Statistik', $data);
    }

    public function nestedChart($program_id, $jenis_target, $target_id)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        $targetLatihan  = $this->repository->getById($target_id);

        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        $data['infoHeader'] = [
            'program_latihan_id'  => $programLatihan->id,
            'nama_program'        => $programLatihan->nama_program,
            'cabor_nama'          => $programLatihan->cabor ? $programLatihan->cabor->nama : '-',
            'cabor_kategori_nama' => $programLatihan->caborKategori ? $programLatihan->caborKategori->nama : null,
            'periode_mulai'       => $programLatihan->periode_mulai,
            'periode_selesai'     => $programLatihan->periode_selesai,
            'jenis_target'        => $jenis_target,
            'target_id'           => $target_id,
        ];

        return inertia('modules/target-latihan/Chart', $data);
    }

    public function apiStatistik(Request $request)
    {
        $request->validate([
            'program_latihan_id' => 'required|exists:program_latihan,id',
            'target_latihan_id'  => 'required|exists:target_latihan,id',
            'jenis_peserta'      => 'required|in:atlet,pelatih,tenaga-pendukung',
        ]);

        $programId    = $request->program_latihan_id;
        $targetId     = $request->target_latihan_id;
        $jenisPeserta = $request->jenis_peserta;

        // Ambil rencana latihan berdasarkan filter
        $rencanaLatihan = RencanaLatihan::where('program_latihan_id', $programId)
            ->whereHas('targetLatihan', function ($q) use ($targetId) {
                $q->where('target_latihan.id', $targetId);
            })
            ->select('id', 'tanggal', 'materi', 'lokasi_latihan')
            ->orderBy('tanggal')
            ->get();

        // Ambil data statistik dari tabel rencana_latihan_peserta_target
        $statistikData = DB::table('rencana_latihan_peserta_target')
            ->join('rencana_latihan', 'rencana_latihan_peserta_target.rencana_latihan_id', '=', 'rencana_latihan.id')
            ->where('rencana_latihan_peserta_target.target_latihan_id', $targetId)
            ->where('rencana_latihan_peserta_target.peserta_type', $this->getPesertaType($jenisPeserta))
            ->whereIn('rencana_latihan_peserta_target.rencana_latihan_id', $rencanaLatihan->pluck('id'))
            ->select(
                'rencana_latihan_peserta_target.peserta_id',
                'rencana_latihan_peserta_target.rencana_latihan_id',
                'rencana_latihan_peserta_target.nilai',
                'rencana_latihan_peserta_target.trend',
                'rencana_latihan.tanggal'
            )
            ->get();

        // Ambil data peserta dari semua rencana latihan
        $allPeserta = collect();
        foreach ($rencanaLatihan as $rencana) {
            $peserta    = $this->getPesertaFromRencana($rencana->id, $jenisPeserta);
            $allPeserta = $allPeserta->merge($peserta);
        }

        // Remove duplicates berdasarkan ID
        $uniquePeserta = $allPeserta->unique('id')->values();

        // Ambil info target latihan
        $targetInfo = TargetLatihan::find($targetId);

        // Hitung persentase performa untuk setiap data statistik
        $controller = $this;
        $statistikDataWithPerforma = $statistikData->map(function ($item) use ($targetInfo, $controller) {
            $nilaiAktual  = $controller->parseNumber($item->nilai);
            $nilaiTarget  = $targetInfo ? $controller->parseNumber($targetInfo->nilai_target) : null;
            $performaArah = $targetInfo && $targetInfo->performa_arah ? $targetInfo->performa_arah : 'max';

            $persentasePerforma = null;
            if ($nilaiAktual !== null && $nilaiTarget !== null && $nilaiTarget > 0) {
                if ($performaArah === 'min') {
                    // Semakin kecil nilai semakin baik
                    // Contoh: target 12 detik, aktual 14 detik = 85.7% (12/14 * 100)
                    // Aktual 12 detik = 100% (12/12 * 100)
                    // Aktual 11 detik = 109% (12/11 * 100)
                    $persentasePerforma = ($nilaiTarget / $nilaiAktual) * 100;
                } else {
                    // Semakin besar nilai semakin baik (default)
                    // Contoh: target 80, aktual 70 = 87.5% (70/80 * 100)
                    // Aktual 80 = 100% (80/80 * 100)
                    // Aktual 90 = 112.5% (90/80 * 100)
                    $persentasePerforma = ($nilaiAktual / $nilaiTarget) * 100;
                }
            }

            return [
                'peserta_id'          => $item->peserta_id,
                'rencana_latihan_id'  => $item->rencana_latihan_id,
                'nilai'               => $item->nilai,
                'trend'               => $item->trend,
                'tanggal'             => $item->tanggal,
                'persentase_performa' => $persentasePerforma !== null ? round($persentasePerforma, 2) : null,
            ];
        });

        return response()->json([
            'data'            => $statistikDataWithPerforma,
            'rencana_latihan' => $rencanaLatihan,
            'peserta'         => $uniquePeserta,
            'target_info'     => $targetInfo,
        ]);
    }

    private function getPesertaFromRencana($rencanaId, $jenisPeserta)
    {
        $rencana = RencanaLatihan::find($rencanaId);
        if (!$rencana) {
            return collect();
        }

        $caborKategoriId = $rencana->programLatihan->cabor_kategori_id;

        switch ($jenisPeserta) {
            case 'atlet':
                return $rencana->atlets()
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
                        'mst_posisi_atlet.nama as posisi_atlet_nama'
                    )
                    ->get()
                    ->map(function ($atlet) {
                        return [
                            'id'            => $atlet->id,
                            'nama'          => $atlet->nama,
                            'jenis_kelamin' => $atlet->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                            'posisi'        => $atlet->posisi_atlet_nama ?? '-',
                        ];
                    });

            case 'pelatih':
                return $rencana->pelatihs()
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
                        'mst_jenis_pelatih.nama as jenis_pelatih_nama'
                    )
                    ->get()
                    ->map(function ($pelatih) {
                        return [
                            'id'            => $pelatih->id,
                            'nama'          => $pelatih->nama,
                            'jenis_kelamin' => $pelatih->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                            'posisi'        => $pelatih->jenis_pelatih_nama ?? '-',
                        ];
                    });

            case 'tenaga-pendukung':
                return $rencana->tenagaPendukung()
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
                        'mst_jenis_tenaga_pendukung.nama as jenis_tenaga_pendukung_nama'
                    )
                    ->get()
                    ->map(function ($tenaga) {
                        return [
                            'id'            => $tenaga->id,
                            'nama'          => $tenaga->nama,
                            'jenis_kelamin' => $tenaga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                            'posisi'        => $tenaga->jenis_tenaga_pendukung_nama ?? '-',
                        ];
                    });

            default:
                return collect();
        }
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
