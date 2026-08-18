<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeleksiPendaftarRequest;
use App\Http\Requests\UpdateSeleksiTesRequest;
use App\Models\SeleksiBerkas;
use App\Models\SeleksiCaborSyarat;
use App\Models\SeleksiPendaftar;
use App\Models\SeleksiPeriode;
use App\Repositories\SeleksiPendaftarRepository;
use App\Services\SeleksiPpopm\SeleksiPromoteService;
use App\Services\SeleksiPpopm\SeleksiScoringService;
use App\Services\SeleksiPpopm\SeleksiValidationService;
use App\Support\SeleksiPpopm;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RuntimeException;

class SeleksiPpopmController extends Controller implements HasMiddleware
{
    use BaseTrait;

    public function __construct(
        private readonly SeleksiPendaftarRepository $pendaftarRepository,
        private readonly SeleksiValidationService $validationService,
        private readonly SeleksiScoringService $scoringService,
        private readonly SeleksiPromoteService $promoteService,
    ) {
        $this->initialize();
        $this->route                          = 'seleksi-ppopm';
        $this->permission_main                = 'Seleksi PPOPM';
        $this->commonData['kode_first_menu']  = 'SELEKSI-PPOPM';
        $this->commonData['kode_second_menu'] = null;
        $this->commonData['permission_main']  = 'Seleksi PPOPM';
        $this->commonData['titlePage']        = 'Seleksi PPOPM';
        $this->commonData['route']            = 'seleksi-ppopm';
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can:Seleksi PPOPM Show', only: ['index', 'pendaftarIndex', 'show', 'apiPendaftar', 'pleno', 'syarat']),
            new Middleware('can:Seleksi PPOPM Add', only: ['create', 'store']),
            new Middleware('can:Seleksi PPOPM Detail', only: ['show']),
            new Middleware('can:Seleksi PPOPM Edit', only: ['updateTes']),
            new Middleware('can:Seleksi PPOPM Delete', only: ['destroy']),
            new Middleware('can:Seleksi PPOPM Verifikasi', only: ['verify', 'reject']),
            new Middleware('can:Seleksi PPOPM Input Tes', only: ['updateTes']),
            new Middleware('can:Seleksi PPOPM Pleno', only: ['runPleno', 'promote']),
        ];
    }

    public function index()
    {
        $periode = $this->activePeriode();
        $stats   = [
            'total'               => 0,
            'submitted'           => 0,
            'lulus_administrasi'  => 0,
            'lulus'               => 0,
            'observasi'           => 0,
            'tidak_lulus'         => 0,
        ];

        if ($periode) {
            $base = SeleksiPendaftar::query()->where('periode_id', $periode->id);
            $stats['total']              = (clone $base)->count();
            $stats['submitted']          = (clone $base)->where('status', SeleksiPpopm::STATUS_SUBMITTED)->count();
            $stats['lulus_administrasi'] = (clone $base)->where('status', SeleksiPpopm::STATUS_LULUS_ADMINISTRASI)->count();
            $stats['lulus']              = (clone $base)->where('status', SeleksiPpopm::STATUS_LULUS)->count();
            $stats['observasi']          = (clone $base)->where('status', SeleksiPpopm::STATUS_OBSERVASI)->count();
            $stats['tidak_lulus']        = (clone $base)->whereIn('status', [
                SeleksiPpopm::STATUS_TIDAK_LULUS,
                SeleksiPpopm::STATUS_GUGUR_KESEHATAN,
                SeleksiPpopm::STATUS_GUGUR_ANTROPOMETRI,
                SeleksiPpopm::STATUS_GUGUR_KECABANGAN,
                SeleksiPpopm::STATUS_DITOLAK_ADMIN,
            ])->count();
        }

        return Inertia::render('modules/seleksi-ppopm/Index', $this->pageData([
            'periode' => $periode,
            'syarat'  => $periode?->caborSyarat ?? [],
            'stats'   => $stats,
        ]));
    }

    public function syarat()
    {
        $periode = $this->activePeriode();

        return Inertia::render('modules/seleksi-ppopm/Syarat', $this->pageData([
            'periode' => $periode,
            'syarat'  => $periode?->caborSyarat ?? [],
        ]));
    }

    public function pendaftarIndex()
    {
        $periode = $this->activePeriode();

        return Inertia::render('modules/seleksi-ppopm/pendaftar/Index', $this->pageData([
            'periode'      => $periode,
            'caborOptions' => $periode?->caborSyarat?->map(fn ($item) => [
                'value' => $item->id,
                'label' => $item->nama_cabor,
            ]) ?? [],
            'statusOptions' => collect(SeleksiPpopm::STATUS_LABELS)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
        ]));
    }

    public function apiPendaftar(Request $request)
    {
        $periode = $this->activePeriode();

        return response()->json($this->pendaftarRepository->paginate([
            'periode_id'      => $periode?->id,
            'cabor_syarat_id' => $request->input('cabor_syarat_id'),
            'status'          => $request->input('status'),
            'search'          => $request->input('search'),
            'page'            => $request->input('page', 1),
            'per_page'        => $request->input('per_page', 10),
            'sort'            => $request->input('sort', 'id'),
            'order'           => $request->input('order', 'desc'),
        ]));
    }

    public function create()
    {
        $periode = $this->activePeriode();

        return Inertia::render('modules/seleksi-ppopm/pendaftar/Create', $this->pageData([
            'periode'      => $periode,
            'pendaftaranBuka' => (bool) $periode?->isPendaftaranBuka(),
            'caborSyarat'  => $periode?->caborSyarat?->map(fn (SeleksiCaborSyarat $item) => $this->syaratPayload($item)) ?? [],
            'berkasLabels' => SeleksiPpopm::BERKAS_LABELS,
        ]));
    }

    public function store(StoreSeleksiPendaftarRequest $request)
    {
        $periode = SeleksiPeriode::query()->findOrFail($request->integer('periode_id'));
        if (! $periode->isPendaftaranBuka()) {
            return back()->with('error', 'Pendaftaran seleksi sedang ditutup.');
        }

        $syarat = SeleksiCaborSyarat::query()->findOrFail($request->integer('cabor_syarat_id'));
        $this->validationService->assertEligible($syarat, $request->validated(), $request->hasFile('berkas_piagam'));

        $pendaftar = SeleksiPendaftar::create([
            'periode_id'               => $periode->id,
            'cabor_syarat_id'          => $syarat->id,
            'user_id'                  => $request->user()?->id,
            'nama'                     => $request->string('nama'),
            'nik'                      => $request->input('nik'),
            'nisn'                     => $request->input('nisn'),
            'jenis_kelamin'            => $request->string('jenis_kelamin'),
            'tempat_lahir'             => $request->input('tempat_lahir'),
            'tanggal_lahir'            => $request->input('tanggal_lahir'),
            'alamat'                   => $request->input('alamat'),
            'no_hp'                    => $request->input('no_hp'),
            'email'                    => $request->input('email'),
            'sekolah'                  => $request->input('sekolah'),
            'kelas_sekolah'            => $request->input('kelas_sekolah'),
            'asal_kabupaten_bogor'     => $request->boolean('asal_kabupaten_bogor'),
            'tinggi_badan'             => $request->input('tinggi_badan'),
            'berat_badan'              => $request->input('berat_badan'),
            'posisi'                   => $request->input('posisi'),
            'nomor_kelas'              => $request->input('nomor_kelas'),
            'vertical_jump'            => $request->input('vertical_jump'),
            'bisa_dua_posisi'          => $request->boolean('bisa_dua_posisi'),
            'bisa_berenang'            => $request->boolean('bisa_berenang'),
            'kuasai_poomsae'           => $request->boolean('kuasai_poomsae'),
            'bersedia_pindah_domisili' => $request->boolean('bersedia_pindah_domisili'),
            'setuju_perjanjian'        => $request->boolean('setuju_perjanjian'),
            'status'                   => SeleksiPpopm::STATUS_SUBMITTED,
        ]);

        foreach (array_merge(SeleksiPpopm::BERKAS_WAJIB, ['piagam']) as $jenis) {
            $file = $request->file('berkas_'.$jenis);
            if (! $file) {
                continue;
            }

            $path = $file->store("seleksi-ppopm/{$pendaftar->id}", 'public');
            SeleksiBerkas::create([
                'pendaftar_id' => $pendaftar->id,
                'jenis'        => $jenis,
                'file_path'    => $path,
                'file_nama'    => $file->getClientOriginalName(),
            ]);
        }

        return redirect()
            ->route('seleksi-ppopm.pendaftar.show', $pendaftar->id)
            ->with('success', 'Pendaftaran berhasil dikirim. Menunggu verifikasi administrasi.');
    }

    public function show(int $id)
    {
        $pendaftar = SeleksiPendaftar::query()->findOrFail($id);

        return Inertia::render('modules/seleksi-ppopm/pendaftar/Show', $this->pageData([
            'pendaftar' => $this->pendaftarRepository->transformDetail($pendaftar),
        ]));
    }

    public function verify(int $id)
    {
        $pendaftar = SeleksiPendaftar::query()->with(['periode', 'caborSyarat'])->findOrFail($id);

        if ($pendaftar->status !== SeleksiPpopm::STATUS_SUBMITTED && $pendaftar->status !== SeleksiPpopm::STATUS_DITOLAK_ADMIN) {
            return back()->with('error', 'Pendaftar ini tidak dalam status menunggu verifikasi.');
        }

        $syarat = $pendaftar->caborSyarat;
        $wajib  = $this->validationService->berkasWajib($syarat);
        $jenis  = $pendaftar->berkas()->pluck('jenis')->all();
        $kurang = array_diff($wajib, $jenis);
        if ($kurang !== []) {
            $labels = collect($kurang)->map(fn ($item) => SeleksiPpopm::BERKAS_LABELS[$item] ?? $item)->implode(', ');

            return back()->with('error', 'Berkas belum lengkap: '.$labels);
        }

        $pendaftar->status       = SeleksiPpopm::STATUS_LULUS_ADMINISTRASI;
        $pendaftar->alasan_tolak = null;
        $pendaftar->nomor_tes    = $pendaftar->nomor_tes ?: $this->pendaftarRepository->nextNomorTes(
            $pendaftar->periode_id,
            (int) $pendaftar->periode->tahun
        );
        $pendaftar->save();

        return back()->with('success', 'Pendaftar lulus administrasi. Nomor tes: '.$pendaftar->nomor_tes);
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'alasan_tolak' => 'required|string|max:1000',
        ]);

        $pendaftar = SeleksiPendaftar::query()->findOrFail($id);
        $pendaftar->status       = SeleksiPpopm::STATUS_DITOLAK_ADMIN;
        $pendaftar->alasan_tolak = $request->string('alasan_tolak');
        $pendaftar->save();

        return back()->with('success', 'Pendaftar ditolak pada tahap administrasi.');
    }

    public function updateTes(UpdateSeleksiTesRequest $request, int $id)
    {
        $pendaftar = SeleksiPendaftar::query()->findOrFail($id);

        if (! $pendaftar->nomor_tes) {
            return back()->with('error', 'Pendaftar belum memiliki nomor tes.');
        }

        $this->scoringService->applyTes($pendaftar, $request->validated());

        return back()->with('success', 'Nilai tes berhasil disimpan.');
    }

    public function pleno()
    {
        $periode = $this->activePeriode();
        $rows    = [];

        if ($periode) {
            $rows = SeleksiPendaftar::query()
                ->with('caborSyarat')
                ->where('periode_id', $periode->id)
                ->whereNotNull('nilai_akhir')
                ->orderBy('cabor_syarat_id')
                ->orderBy('ranking')
                ->get()
                ->map(fn (SeleksiPendaftar $item) => $this->pendaftarRepository->transform($item));
        }

        return Inertia::render('modules/seleksi-ppopm/pleno/Index', $this->pageData([
            'periode' => $periode,
            'rows'    => $rows,
        ]));
    }

    public function runPleno()
    {
        $periode = $this->activePeriode();
        if (! $periode) {
            return back()->with('error', 'Periode seleksi belum tersedia.');
        }

        $this->scoringService->runPleno($periode->id);

        return back()->with('success', 'Pleno selesai. Ranking dan status kelulusan telah dihitung.');
    }

    public function promote(int $id)
    {
        $pendaftar = SeleksiPendaftar::query()->with('caborSyarat')->findOrFail($id);

        try {
            $atlet = $this->promoteService->promote($pendaftar);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pendaftar diangkat menjadi atlet PPOPM (ID {$atlet->id}).");
    }

    public function destroy(int $id)
    {
        $pendaftar = SeleksiPendaftar::query()->with('berkas')->findOrFail($id);

        if ($pendaftar->atlet_id) {
            return back()->with('error', 'Pendaftar yang sudah diangkat menjadi atlet tidak dapat dihapus.');
        }

        foreach ($pendaftar->berkas as $berkas) {
            Storage::disk('public')->delete($berkas->file_path);
        }

        $pendaftar->delete();

        return redirect()->route('seleksi-ppopm.pendaftar.index')->with('success', 'Pendaftar berhasil dihapus.');
    }

    private function activePeriode(): ?SeleksiPeriode
    {
        return SeleksiPeriode::query()
            ->with('caborSyarat')
            ->orderByDesc('tahun')
            ->first();
    }

    private function syaratPayload(SeleksiCaborSyarat $item): array
    {
        return [
            'id'                      => $item->id,
            'kode'                    => $item->kode,
            'nama_cabor'              => $item->nama_cabor,
            'kelompok_map'            => $item->kelompok_map,
            'map_label'               => $item->mapLabel(),
            'tahun_lahir_min'         => $item->tahun_lahir_min,
            'tahun_lahir_max'         => $item->tahun_lahir_max,
            'tinggi_min_putra'        => $item->tinggi_min_putra,
            'tinggi_min_putri'        => $item->tinggi_min_putri,
            'tinggi_per_posisi'       => $item->tinggi_per_posisi,
            'posisi'                  => $item->posisi ?? [],
            'kuota_putra'             => $item->kuota_putra,
            'kuota_putri'             => $item->kuota_putri,
            'kuota_detail'            => $item->kuota_detail,
            'jenis_kelamin_diizinkan' => $item->jenis_kelamin_diizinkan ?? ['L', 'P'],
            'wajib_piagam'            => $item->wajib_piagam,
            'wajib_berenang'          => $item->wajib_berenang,
            'wajib_dua_posisi'        => $item->wajib_dua_posisi,
            'prioritas_tinggi'        => $item->prioritas_tinggi,
            'toleransi_tinggi'        => $item->toleransi_tinggi,
            'syarat_tambahan'         => $item->syarat_tambahan,
        ];
    }

    private function pageData(array $extra = []): array
    {
        $data = $this->commonData + $extra;
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return $data;
    }
}
