<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Atlet;
use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Models\Pelatih;
use App\Models\Pemeriksaan;
use App\Models\ProgramLatihan;
use App\Models\TenagaPendukung;
use App\Traits\BaseTrait;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller implements HasMiddleware
{
    use BaseTrait;

    public function __construct()
    {
        $this->initialize();
        $this->route                          = 'dashboard';
        $this->commonData['kode_first_menu']  = 'DASHBOARD';
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

    public function index()
    {
        $now              = Carbon::now();
        $startOfMonth     = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth   = $now->copy()->subMonth()->endOfMonth();

        // ✅ OPTIMASI #1: Batch query untuk stats - 1 query per model dengan CASE WHEN
        $stats = $this->getOptimizedStats($startOfMonth, $now, $startOfLastMonth, $endOfLastMonth);

        $data = $this->commonData + [
            'titlePage' => 'Dashboard',
            'stats'     => $stats,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        // ✅ OPTIMASI #2: Eager load semua relasi + withCount untuk latestPrograms
        $latestPrograms = $this->getOptimizedLatestPrograms();

        // ✅ OPTIMASI #3: Eager load semua relasi + withCount untuk latestPemeriksaan
        $latestPemeriksaan = $this->getOptimizedLatestPemeriksaan();

        // Ambil 8 aktivitas terbaru
        $latestActivities = ActivityLog::with(['causer', 'causer.role'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get()
            ->map(function ($item) {
                $causerName = $item->causer ? $item->causer->name : 'System';
                $roleName   = $item->causer && $item->causer->role ? $item->causer->role->name : '';
                $userInfo   = $roleName ? "$causerName - $roleName" : $causerName;

                // Gunakan getFileUrlAttribute untuk avatar
                $avatar = $item->causer ? $item->causer->getFileUrlAttribute() : null;

                // Perbaiki format waktu dengan timezone yang benar dan bahasa Indonesia
                $time = '-';
                if ($item->created_at) {
                    $time = $this->formatTimeAgo($item->created_at);
                }

                return [
                    'id'          => $item->id,
                    'title'       => $this->getActivityTitle($item),
                    'description' => $item->description,
                    'time'        => $time,
                    'avatar'      => $avatar,
                    'initials'    => $item->causer ? strtoupper(substr($item->causer->name, 0, 2)) : 'SY',
                    'causer_name' => $userInfo,
                ];
            });

        $data['latest_programs']    = $latestPrograms;
        $data['latest_pemeriksaan'] = $latestPemeriksaan;
        $data['latest_activities']  = $latestActivities;

        // Data untuk grafik berdasarkan tanggal bergabung per tahun
        $chartData          = $this->getChartData();
        $data['chart_data'] = $chartData;

        // ✅ OPTIMASI #4: Data rekapitulasi dengan GROUP BY (3 query total, bukan 450)
        $rekapData          = $this->getRekapData();
        $data['rekap_data'] = $rekapData;

        return Inertia::render('Dashboard', $data);
    }

    /**
     * ✅ OPTIMASI: Batch query untuk stats - 1 query per model
     * Sebelum: 21 query (3 query x 7 model)
     * Sesudah: 7 query (1 query x 7 model)
     */
    private function getOptimizedStats($startOfMonth, $now, $startOfLastMonth, $endOfLastMonth)
    {
        $models = [
            ['model' => Atlet::class, 'label' => 'Total Atlet', 'icon' => 'UserCircle2', 'href' => '/atlet'],
            ['model' => Pelatih::class, 'label' => 'Total Pelatih', 'icon' => 'HandHeart', 'href' => '/pelatih'],
            ['model' => TenagaPendukung::class, 'label' => 'Total Tenaga Pendukung', 'icon' => 'HeartHandshake', 'href' => '/tenaga-pendukung'],
            ['model' => Cabor::class, 'label' => 'Total Cabor', 'icon' => 'Flag', 'href' => '/cabor'],
            ['model' => CaborKategori::class, 'label' => 'Total Cabor Kategori', 'icon' => 'Ungroup', 'href' => '/cabor-kategori'],
            ['model' => ProgramLatihan::class, 'label' => 'Total Program Latihan', 'icon' => 'ClipboardCheck', 'href' => '/program-latihan'],
            ['model' => Pemeriksaan::class, 'label' => 'Total Pemeriksaan', 'icon' => 'Stethoscope', 'href' => '/pemeriksaan'],
        ];

        $stats = [];

        foreach ($models as $item) {
            $model = $item['model'];

            // 1 query per model dengan CASE WHEN untuk hitung semua sekaligus
            $result = $model::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_month
            ", [$startOfMonth, $now, $startOfLastMonth, $endOfLastMonth])
                ->first();

            $total     = $result->total ?? 0;
            $thisMonth = $result->this_month ?? 0;
            $lastMonth = $result->last_month ?? 0;

            // Hitung perubahan
            if ($lastMonth == 0) {
                if ($thisMonth == 0) {
                    $changeLabel    = '0%';
                    $trend          = 'up';
                    $changeAbsLabel = '0 data dibanding bulan lalu';
                } else {
                    $changeLabel    = 'Baru';
                    $trend          = 'up';
                    $changeAbsLabel = '+'.$thisMonth.' data';
                }
            } else {
                $change         = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
                $changeLabel    = ($change > 0 ? '+' : '').$change.'%';
                $trend          = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'up');
                $changeAbs      = $thisMonth - $lastMonth;
                $changeAbsLabel = ($changeAbs > 0 ? '+' : '').$changeAbs.' data';
            }

            $stats[] = [
                'title'         => $item['label'],
                'value'         => $total,
                'change'        => $changeLabel,
                'change_abs'    => $changeAbsLabel,
                'trend'         => $trend,
                'icon'          => $item['icon'],
                'href'          => $item['href'],
                'compare_label' => '',
            ];
        }

        return $stats;
    }

    /**
     * ✅ OPTIMASI: Latest Programs dengan withCount + eager load constraint
     * Sebelum: 10+ query (2 query per item)
     * Sesudah: 2-3 query total
     */
    private function getOptimizedLatestPrograms()
    {
        return ProgramLatihan::with([
            'caborKategori',
            'cabor',
            'rencanaLatihan' => function ($query) {
                $query->orderByDesc('tanggal')->limit(15); // limit lebih untuk cover 5 program x 3 item
            },
        ])
            ->withCount('rencanaLatihan')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($item) {
                // Hitung durasi periode
                $durasi = '-';
                if ($item->periode_mulai && $item->periode_selesai) {
                    $startDate  = Carbon::parse($item->periode_mulai);
                    $endDate    = Carbon::parse($item->periode_selesai);
                    $diffInDays = $startDate->diffInDays($endDate) + 1;

                    if ($diffInDays <= 30) {
                        $durasi = $diffInDays.' hari';
                    } else {
                        $months        = floor($diffInDays / 30);
                        $remainingDays = $diffInDays % 30;

                        if ($remainingDays == 0) {
                            $durasi = $months.' bulan';
                        } else {
                            $durasi = $months.' bulan '.$remainingDays.' hari';
                        }
                    }
                }

                return [
                    'id'                     => $item->id,
                    'nama_program'           => $item->nama_program,
                    'cabor_nama'             => $item->cabor?->nama         ?? '-',
                    'cabor_kategori_nama'    => $item->caborKategori?->nama ?? '-',
                    'periode'                => $durasi,
                    'jumlah_rencana_latihan' => $item->rencana_latihan_count, // dari withCount
                    'rencana_latihan_list'   => $item->rencanaLatihan->take(3)->pluck('materi')->map(function ($materi) {
                        return Str::limit($materi, 30);
                    })->toArray(),
                ];
            });
    }

    /**
     * ✅ OPTIMASI: Latest Pemeriksaan dengan withCount + eager load
     * Sebelum: 35+ query (7+ query per item)
     * Sesudah: 3-4 query total
     */
    private function getOptimizedLatestPemeriksaan()
    {
        return Pemeriksaan::with([
            'caborKategori',
            'cabor',
            'tenagaPendukung',
            'pemeriksaanParameter' => function ($query) {
                $query->with('mstParameter')->limit(15);
            },
            'pemeriksaanPeserta' => function ($query) {
                $query->with('peserta')->limit(15);
            },
        ])
            ->withCount([
                'pemeriksaanParameter',
                'pemeriksaanPeserta',
                'pemeriksaanPeserta as peserta_atlet_count' => function ($query) {
                    $query->where('peserta_type', 'App\\Models\\Atlet');
                },
                'pemeriksaanPeserta as peserta_pelatih_count' => function ($query) {
                    $query->where('peserta_type', 'App\\Models\\Pelatih');
                },
                'pemeriksaanPeserta as peserta_tenaga_pendukung_count' => function ($query) {
                    $query->where('peserta_type', 'App\\Models\\TenagaPendukung');
                },
            ])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id'                      => $item->id,
                    'nama_pemeriksaan'        => $item->nama_pemeriksaan,
                    'cabor_kategori_nama'     => $item->caborKategori?->nama   ?? '-',
                    'tenaga_pendukung_nama'   => $item->tenagaPendukung?->nama ?? '-',
                    'tanggal_pemeriksaan'     => $item->tanggal_pemeriksaan,
                    'status'                  => $item->status,
                    'jumlah_parameter'        => $item->pemeriksaan_parameter_count,
                    'jumlah_peserta'          => $item->pemeriksaan_peserta_count,
                    'cabor_nama'              => $item->cabor?->nama ?? '-',
                    'parameter_list'          => $item->pemeriksaanParameter->take(3)->pluck('mstParameter.nama')->toArray(),
                    'peserta_list'            => $item->pemeriksaanPeserta->take(3)->map(fn ($p) => $p->peserta?->nama ?? '-')->toArray(),
                    'jumlah_atlet'            => $item->peserta_atlet_count,
                    'jumlah_pelatih'          => $item->peserta_pelatih_count,
                    'jumlah_tenaga_pendukung' => $item->peserta_tenaga_pendukung_count,
                ];
            });
    }

    private function formatTimeAgo($datetime)
    {
        $createdAt = Carbon::parse($datetime);
        $now       = Carbon::now();

        // Perbaiki logika perhitungan waktu - gunakan abs() untuk nilai absolut dan format yang rapi
        $diffInSeconds = abs($now->diffInSeconds($createdAt));
        $diffInMinutes = abs($now->diffInMinutes($createdAt));
        $diffInHours   = abs($now->diffInHours($createdAt));
        $diffInDays    = abs($now->diffInDays($createdAt));
        $diffInWeeks   = abs($now->diffInWeeks($createdAt));
        $diffInMonths  = abs($now->diffInMonths($createdAt));
        $diffInYears   = abs($now->diffInYears($createdAt));

        if ($diffInSeconds < 60) {
            return 'Baru saja';
        } elseif ($diffInMinutes < 60) {
            return round($diffInMinutes).' menit yang lalu';
        } elseif ($diffInHours < 24) {
            return round($diffInHours).' jam yang lalu';
        } elseif ($diffInDays < 7) {
            return round($diffInDays).' hari yang lalu';
        } elseif ($diffInWeeks < 4) {
            return round($diffInWeeks).' minggu yang lalu';
        } elseif ($diffInMonths < 12) {
            return round($diffInMonths).' bulan yang lalu';
        } else {
            return round($diffInYears).' tahun yang lalu';
        }
    }

    private function getActivityTitle($activity)
    {
        $subjectType = class_basename($activity->subject_type);
        $event       = $activity->event;

        switch ($subjectType) {
            case 'Atlet':
                return $event === 'created' ? 'Atlet baru ditambahkan' : 'Data atlet diperbarui';
            case 'Pelatih':
                return $event === 'created' ? 'Pelatih baru ditambahkan' : 'Data pelatih diperbarui';
            case 'TenagaPendukung':
                return $event === 'created' ? 'Tenaga pendukung baru ditambahkan' : 'Data tenaga pendukung diperbarui';
            case 'ProgramLatihan':
                return $event === 'created' ? 'Program latihan dibuat' : 'Program latihan diperbarui';
            case 'Pemeriksaan':
                return $event === 'created' ? 'Pemeriksaan dibuat' : 'Pemeriksaan diperbarui';
            case 'Cabor':
                return $event === 'created' ? 'Cabor baru ditambahkan' : 'Data cabor diperbarui';
            case 'CaborKategori':
                return $event === 'created' ? 'Kategori cabor ditambahkan' : 'Data kategori diperbarui';
            case 'User':
                return $event === 'created' ? 'User baru ditambahkan' : 'Data user diperbarui';
            case 'UsersMenu':
                return $event === 'created' ? 'Menu baru ditambahkan' : 'Data menu diperbarui';
            default:
                // Jika tidak ada di switch case, gunakan description atau buat title dari subject type
                if ($activity->description) {
                    return $activity->description;
                }

                return ucfirst(strtolower(str_replace('_', ' ', $subjectType))).' '.($event === 'created' ? 'ditambahkan' : 'diperbarui');
        }
    }

    /**
     * ✅ OPTIMASI: Chart data dengan GROUP BY
     * Sebelum: 6 query untuk min/max + 60 query untuk data (jika 20 tahun)
     * Sesudah: 3 query total (1 per tabel dengan GROUP BY)
     */
    private function getChartData()
    {
        $driver = DB::getDriverName();
        $yearFn = $driver === 'sqlite'
            ? "strftime('%Y', tanggal_bergabung)"
            : 'YEAR(tanggal_bergabung)';

        // ✅ 1 query per tabel dengan GROUP BY (sudah include min/max dari hasil)
        $atletByYear = Atlet::whereNotNull('tanggal_bergabung')
            ->selectRaw("$yearFn as y, COUNT(*) as total")
            ->groupBy('y')
            ->pluck('total', 'y')
            ->toArray();

        $pelatihByYear = Pelatih::whereNotNull('tanggal_bergabung')
            ->selectRaw("$yearFn as y, COUNT(*) as total")
            ->groupBy('y')
            ->pluck('total', 'y')
            ->toArray();

        $tenagaByYear = TenagaPendukung::whereNotNull('tanggal_bergabung')
            ->selectRaw("$yearFn as y, COUNT(*) as total")
            ->groupBy('y')
            ->pluck('total', 'y')
            ->toArray();

        // Ambil min/max year dari hasil query (tanpa query tambahan)
        $allYears = array_merge(
            array_keys($atletByYear),
            array_keys($pelatihByYear),
            array_keys($tenagaByYear)
        );

        if (empty($allYears)) {
            return [
                'years'  => [],
                'series' => [
                    ['name' => 'Atlet', 'data' => []],
                    ['name' => 'Pelatih', 'data' => []],
                    ['name' => 'Tenaga Pendukung', 'data' => []],
                ],
            ];
        }

        $minYear = min($allYears);
        $maxYear = max($allYears);
        $years   = range($minYear, $maxYear);

        // Bentuk data sesuai range tahun
        $atletData           = [];
        $pelatihData         = [];
        $tenagaPendukungData = [];

        foreach ($years as $year) {
            $atletData[]           = $atletByYear[$year] ?? 0;
            $pelatihData[]         = $pelatihByYear[$year] ?? 0;
            $tenagaPendukungData[] = $tenagaByYear[$year] ?? 0;
        }

        return [
            'years'  => $years,
            'series' => [
                ['name' => 'Atlet', 'data' => $atletData],
                ['name' => 'Pelatih', 'data' => $pelatihData],
                ['name' => 'Tenaga Pendukung', 'data' => $tenagaPendukungData],
            ],
        ];
    }


    /**
     * ✅ OPTIMASI: Rekap data dengan GROUP BY
     * Sebelum: 450 query (3 count query x 150 kategori)
     * Sesudah: 4 query total (1 untuk kategori + 3 GROUP BY)
     */
    private function getRekapData()
    {
        // ✅ Query 1: Ambil semua cabor kategori dengan relasi cabor
        $caborKategoris = CaborKategori::with('cabor')
            ->orderBy('cabor_id')
            ->orderBy('nama')
            ->get();

        // ✅ Query 2: Hitung semua atlet per kategori sekaligus
        $atletCounts = CaborKategoriAtlet::select('cabor_kategori_id', DB::raw('COUNT(*) as total'))
            ->where('is_active', 1)
            ->groupBy('cabor_kategori_id')
            ->pluck('total', 'cabor_kategori_id')
            ->toArray();

        // ✅ Query 3: Hitung semua pelatih per kategori sekaligus
        $pelatihCounts = CaborKategoriPelatih::select('cabor_kategori_id', DB::raw('COUNT(*) as total'))
            ->where('is_active', 1)
            ->groupBy('cabor_kategori_id')
            ->pluck('total', 'cabor_kategori_id')
            ->toArray();

        // ✅ Query 4: Hitung semua tenaga pendukung per kategori sekaligus
        $tenagaCounts = CaborKategoriTenagaPendukung::select('cabor_kategori_id', DB::raw('COUNT(*) as total'))
            ->where('is_active', 1)
            ->groupBy('cabor_kategori_id')
            ->pluck('total', 'cabor_kategori_id')
            ->toArray();

        // Bentuk data dari hasil lookup (tanpa query tambahan)
        $rekapData = [];

        foreach ($caborKategoris as $caborKategori) {
            $jumlahAtlet           = $atletCounts[$caborKategori->id]   ?? 0;
            $jumlahPelatih         = $pelatihCounts[$caborKategori->id] ?? 0;
            $jumlahTenagaPendukung = $tenagaCounts[$caborKategori->id]  ?? 0;

            $rekapData[] = [
                'id'                      => $caborKategori->id,
                'cabor_nama'              => $caborKategori->cabor->nama ?? '-',
                'nama'                    => $caborKategori->nama,
                'jumlah_atlet'            => $jumlahAtlet,
                'jumlah_pelatih'          => $jumlahPelatih,
                'jumlah_tenaga_pendukung' => $jumlahTenagaPendukung,
                'total'                   => $jumlahAtlet + $jumlahPelatih + $jumlahTenagaPendukung,
            ];
        }

        return $rekapData;
    }
}
