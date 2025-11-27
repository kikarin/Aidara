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

        $stat = function ($model, $label, $icon, $href) use ($startOfMonth, $now, $startOfLastMonth, $endOfLastMonth) {
            $thisMonth = $model::whereBetween('created_at', [$startOfMonth, $now])->count();
            $lastMonth = $model::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

            if ($lastMonth == 0) {
                if ($thisMonth == 0) {
                    $change         = 0;
                    $changeLabel    = '0%';
                    $trend          = 'up';
                    $changeAbs      = 0;
                    $changeAbsLabel = '0 data dibanding bulan lalu';
                } else {
                    $change         = null;
                    $changeLabel    = 'Baru';
                    $trend          = 'up';
                    $changeAbs      = $thisMonth;
                    $changeAbsLabel = '+'.$thisMonth.' data';
                }
            } else {
                $change         = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
                $changeLabel    = ($change > 0 ? '+' : '').$change.'%';
                $trend          = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'up');
                $changeAbs      = $thisMonth - $lastMonth;
                $changeAbsLabel = ($changeAbs > 0 ? '+' : '').$changeAbs.' data';
            }

            return [
                'title'         => $label,
                'value'         => $model::count(),
                'change'        => $changeLabel,
                'change_abs'    => $changeAbsLabel,
                'trend'         => $trend,
                'icon'          => $icon,
                'href'          => $href,
                'compare_label' => '',
            ];
        };

        $stats = [
            $stat(Atlet::class, 'Total Atlet', 'UserCircle2', '/atlet'),
            $stat(Pelatih::class, 'Total Pelatih', 'HandHeart', '/pelatih'),
            $stat(TenagaPendukung::class, 'Total Tenaga Pendukung', 'HeartHandshake', '/tenaga-pendukung'),
            $stat(Cabor::class, 'Total Cabor', 'Flag', '/cabor'),
            $stat(CaborKategori::class, 'Total Cabor Kategori', 'Ungroup', '/cabor-kategori'),
            $stat(ProgramLatihan::class, 'Total Program Latihan', 'ClipboardCheck', '/program-latihan'),
            $stat(Pemeriksaan::class, 'Total Pemeriksaan', 'Stethoscope', '/pemeriksaan'),
        ];

        $data = $this->commonData + [
            'titlePage' => 'Dashboard',
            'stats'     => $stats,
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        // Ambil 5 data terbaru Program Latihan beserta relasi
        $latestPrograms = ProgramLatihan::with(['caborKategori', 'cabor'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($item) {
                // Hitung durasi periode
                $durasi = '-';
                if ($item->periode_mulai && $item->periode_selesai) {
                    $startDate  = Carbon::parse($item->periode_mulai);
                    $endDate    = Carbon::parse($item->periode_selesai);
                    $diffInDays = $startDate->diffInDays($endDate) + 1; // +1 karena inclusive

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
                    'jumlah_rencana_latihan' => $item->rencanaLatihan()->count(),
                    'rencana_latihan_list'   => $item->rencanaLatihan()->orderByDesc('tanggal')->limit(3)->pluck('materi')->map(function ($materi) {
                        return Str::limit($materi, 30);
                    })->toArray(),
                ];
            });

        // Ambil 5 data terbaru Pemeriksaan beserta relasi
        $latestPemeriksaan = Pemeriksaan::with(['caborKategori', 'cabor', 'tenagaPendukung'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($item) {
                // Hitung jumlah peserta berdasarkan jenis menggunakan peserta_type
                $pesertaAtlet           = $item->pemeriksaanPeserta()->where('peserta_type', 'App\\Models\\Atlet')->count();
                $pesertaPelatih         = $item->pemeriksaanPeserta()->where('peserta_type', 'App\\Models\\Pelatih')->count();
                $pesertaTenagaPendukung = $item->pemeriksaanPeserta()->where('peserta_type', 'App\\Models\\TenagaPendukung')->count();

                return [
                    'id'                    => $item->id,
                    'nama_pemeriksaan'      => $item->nama_pemeriksaan,
                    'cabor_kategori_nama'   => $item->caborKategori?->nama   ?? '-',
                    'tenaga_pendukung_nama' => $item->tenagaPendukung?->nama ?? '-',
                    'tanggal_pemeriksaan'   => $item->tanggal_pemeriksaan,
                    'status'                => $item->status,
                    'jumlah_parameter'      => $item->pemeriksaanParameter()->count(),
                    'jumlah_peserta'        => $item->pemeriksaanPeserta()->count(),
                    'cabor_nama'            => $item->cabor?->nama ?? '-',
                    'parameter_list'        => $item->pemeriksaanParameter()->with('mstParameter')->limit(3)->get()->pluck('mstParameter.nama')->toArray(),
                    'peserta_list'          => $item->pemeriksaanPeserta()->with('peserta')->limit(3)->get()->map(function ($peserta) {
                        // Cek field nama di model morph (Atlet, Pelatih, TenagaPendukung)
                        return $peserta->peserta?->nama ?? '-';
                    })->toArray(),
                    'jumlah_atlet'            => $pesertaAtlet,
                    'jumlah_pelatih'          => $pesertaPelatih,
                    'jumlah_tenaga_pendukung' => $pesertaTenagaPendukung,
                ];
            });

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

        // Data rekapitulasi per cabor kategori
        $rekapData          = $this->getRekapData();
        $data['rekap_data'] = $rekapData;

        return Inertia::render('Dashboard', $data);
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

    private function getChartData()
    {
        $driver = DB::getDriverName();
        $yearFn = $driver === 'sqlite'
            ? "strftime('%Y', tanggal_bergabung)"
            : 'YEAR(tanggal_bergabung)';

        $minAtletYear           = Atlet::whereNotNull('tanggal_bergabung')->selectRaw("MIN($yearFn) as year")->value('year');
        $minPelatihYear         = Pelatih::whereNotNull('tanggal_bergabung')->selectRaw("MIN($yearFn) as year")->value('year');
        $minTenagaPendukungYear = TenagaPendukung::whereNotNull('tanggal_bergabung')->selectRaw("MIN($yearFn) as year")->value('year');

        $maxAtletYear           = Atlet::whereNotNull('tanggal_bergabung')->selectRaw("MAX($yearFn) as year")->value('year');
        $maxPelatihYear         = Pelatih::whereNotNull('tanggal_bergabung')->selectRaw("MAX($yearFn) as year")->value('year');
        $maxTenagaPendukungYear = TenagaPendukung::whereNotNull('tanggal_bergabung')->selectRaw("MAX($yearFn) as year")->value('year');

        $minValues = array_filter([$minAtletYear, $minPelatihYear, $minTenagaPendukungYear]);
        $maxValues = array_filter([$maxAtletYear, $maxPelatihYear, $maxTenagaPendukungYear]);

        if (empty($minValues) || empty($maxValues)) {
            return [
                'years'  => [],
                'series' => [
                    ['name' => 'Atlet', 'data' => []],
                    ['name' => 'Pelatih', 'data' => []],
                    ['name' => 'Tenaga Pendukung', 'data' => []],
                ],
            ];
        }

        $minYear = min($minValues);
        $maxYear = max($maxValues);

        // Buat range tahun dari data yang ada
        $years = range($minYear, $maxYear);

        $atletData           = [];
        $pelatihData         = [];
        $tenagaPendukungData = [];

        foreach ($years as $year) {
            // Data Atlet per tahun
            $atletCount  = Atlet::whereYear('tanggal_bergabung', $year)->count();
            $atletData[] = $atletCount;

            // Data Pelatih per tahun
            $pelatihCount  = Pelatih::whereYear('tanggal_bergabung', $year)->count();
            $pelatihData[] = $pelatihCount;

            // Data Tenaga Pendukung per tahun
            $tenagaPendukungCount  = TenagaPendukung::whereYear('tanggal_bergabung', $year)->count();
            $tenagaPendukungData[] = $tenagaPendukungCount;
        }

        return [
            'years'  => $years,
            'series' => [
                [
                    'name' => 'Atlet',
                    'data' => $atletData,
                ],
                [
                    'name' => 'Pelatih',
                    'data' => $pelatihData,
                ],
                [
                    'name' => 'Tenaga Pendukung',
                    'data' => $tenagaPendukungData,
                ],
            ],
        ];
    }


    private function getRekapData()
    {
        // Ambil semua cabor kategori dengan relasi cabor
        $caborKategoris = CaborKategori::with('cabor')
            ->orderBy('cabor_id')
            ->orderBy('nama')
            ->get();

        $rekapData = [];

        foreach ($caborKategoris as $caborKategori) {
            // Hitung jumlah atlet di kategori ini
            $jumlahAtlet = CaborKategoriAtlet::where('cabor_kategori_id', $caborKategori->id)
                ->where('is_active', 1)
                ->count();

            // Hitung jumlah pelatih di kategori ini
            $jumlahPelatih = CaborKategoriPelatih::where('cabor_kategori_id', $caborKategori->id)
                ->where('is_active', 1)
                ->count();

            // Hitung jumlah tenaga pendukung di kategori ini
            $jumlahTenagaPendukung = CaborKategoriTenagaPendukung::where('cabor_kategori_id', $caborKategori->id)
                ->where('is_active', 1)
                ->count();

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
