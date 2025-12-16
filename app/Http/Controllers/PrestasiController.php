<?php

namespace App\Http\Controllers;

use App\Models\AtletPrestasi;
use App\Models\PelatihPrestasi;
use App\Models\TenagaPendukungPrestasi;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PrestasiController extends Controller implements HasMiddleware
{
    use BaseTrait;

    public function __construct()
    {
        $this->initialize();
        $this->route                          = 'prestasi';
        $this->commonData['kode_first_menu']  = 'DASHBOARD';
        $this->commonData['kode_second_menu'] = 'PRESTASI';
    }

    public static function middleware(): array
    {
        return [
            new Middleware("can:Dashboard Show", only: ['index']),
        ];
    }

    public function index()
    {
        $data = $this->commonData + [
            'titlePage' => 'List Prestasi',
        ];

        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }

        return Inertia::render('modules/prestasi/Index', $data);
    }

    public function apiIndex(Request $request)
    {
        $eventName = $request->input('event_name', '');
        $limit = $request->input('limit', 5); // Default 5 untuk preview

        \Log::info('PrestasiController: apiIndex called', [
            'event_name' => $eventName,
            'limit' => $limit,
        ]);

        // Get all prestasi from atlet, pelatih, and tenaga pendukung
        $atletPrestasi = AtletPrestasi::with([
            'atlet' => function ($query) {
                $query->withTrashed()->with(['kategoriPesertas', 'caborKategoriAtlet' => function ($q) {
                    $q->where('is_active', 1)->whereNull('deleted_at')->with('cabor');
                }]);
            },
        ])
            ->whereNull('deleted_at')
            ->when($eventName, function ($query) use ($eventName) {
                $query->where('nama_event', $eventName);
            })
            ->get()
            ->filter(function ($prestasi) {
                return $prestasi->atlet !== null && $prestasi->atlet->deleted_at === null; // Only show active atlet
            })
            ->map(function ($prestasi) {
                $atlet = $prestasi->atlet;
                if (!$atlet) {
                    return null;
                }
                
                $caborKategoriAtlet = $atlet->caborKategoriAtlet->first();
                $cabor = $caborKategoriAtlet?->cabor;
                $kategoriPeserta = $atlet->kategoriPesertas ? $atlet->kategoriPesertas->pluck('nama')->toArray() : [];
                $isNPCI = in_array('NPCI', $kategoriPeserta);
                $isSOIna = in_array('SOIna', $kategoriPeserta);
                
                return [
                    'id' => $prestasi->id,
                    'peserta_type' => 'atlet',
                    'peserta_id' => $atlet->id,
                    'nama' => $atlet->nama ?? '-',
                    'cabor' => $cabor?->nama ?? '-',
                    'nomor_posisi' => $caborKategoriAtlet?->posisi_atlet ?? '-',
                    'juara_medali' => $prestasi->peringkat ?? '-',
                    'kategori_peserta' => !empty($kategoriPeserta) ? implode(', ', $kategoriPeserta) : '-',
                    'bonus' => $prestasi->bonus ?? 0,
                    'nama_event' => $prestasi->nama_event ?: 'Event Tanpa Nama',
                    'tanggal' => $prestasi->tanggal,
                    // NPCI/SOIna fields - hanya tampilkan jika kategori peserta sesuai
                    'disabilitas' => ($isNPCI || $isSOIna) ? ($atlet->disabilitas ?? '-') : null,
                    'klasifikasi' => $isNPCI ? ($atlet->klasifikasi ?? '-') : null,
                    'iq' => $isSOIna ? ($atlet->iq ?? '-') : null,
                ];
            })
            ->filter(); // Remove null values

        $pelatihPrestasi = PelatihPrestasi::with([
            'pelatih' => function ($query) {
                $query->withTrashed()->with(['kategoriPesertas', 'caborKategoriPelatih' => function ($q) {
                    $q->where('is_active', 1)->whereNull('deleted_at')->with('cabor');
                }]);
            },
        ])
            ->whereNull('deleted_at')
            ->when($eventName, function ($query) use ($eventName) {
                $query->where('nama_event', $eventName);
            })
            ->get()
            ->filter(function ($prestasi) {
                return $prestasi->pelatih !== null && $prestasi->pelatih->deleted_at === null; // Only show active pelatih
            })
            ->map(function ($prestasi) {
                $pelatih = $prestasi->pelatih;
                if (!$pelatih) {
                    return null;
                }
                
                $caborKategoriPelatih = $pelatih->caborKategoriPelatih->first();
                $cabor = $caborKategoriPelatih?->cabor;
                $kategoriPeserta = $pelatih->kategoriPesertas ? $pelatih->kategoriPesertas->pluck('nama')->toArray() : [];
                
                return [
                    'id' => $prestasi->id,
                    'peserta_type' => 'pelatih',
                    'peserta_id' => $pelatih->id,
                    'nama' => $pelatih->nama ?? '-',
                    'cabor' => $cabor?->nama ?? '-',
                    'nomor_posisi' => $caborKategoriPelatih?->posisi_atlet ?? '-',
                    'juara_medali' => $prestasi->peringkat ?? '-',
                    'kategori_peserta' => !empty($kategoriPeserta) ? implode(', ', $kategoriPeserta) : '-',
                    'bonus' => $prestasi->bonus ?? 0,
                    'nama_event' => $prestasi->nama_event ?: 'Event Tanpa Nama',
                    'tanggal' => $prestasi->tanggal,
                ];
            })
            ->filter(); // Remove null values

        $tenagaPendukungPrestasi = TenagaPendukungPrestasi::with([
            'tenaga_pendukung' => function ($query) {
                $query->withTrashed()->with(['kategoriPesertas', 'caborKategoriTenagaPendukung' => function ($q) {
                    $q->where('is_active', 1)->whereNull('deleted_at')->with('cabor');
                }]);
            },
        ])
            ->whereNull('deleted_at')
            ->when($eventName, function ($query) use ($eventName) {
                $query->where('nama_event', $eventName);
            })
            ->get()
            ->filter(function ($prestasi) {
                return $prestasi->tenaga_pendukung !== null && $prestasi->tenaga_pendukung->deleted_at === null; // Only show active tenaga pendukung
            })
            ->map(function ($prestasi) {
                $tenagaPendukung = $prestasi->tenaga_pendukung;
                if (!$tenagaPendukung) {
                    return null;
                }
                
                $caborKategoriTenagaPendukung = $tenagaPendukung->caborKategoriTenagaPendukung->first();
                $cabor = $caborKategoriTenagaPendukung?->cabor;
                $kategoriPeserta = $tenagaPendukung->kategoriPesertas ? $tenagaPendukung->kategoriPesertas->pluck('nama')->toArray() : [];
                
                return [
                    'id' => $prestasi->id,
                    'peserta_type' => 'tenaga_pendukung',
                    'peserta_id' => $tenagaPendukung->id,
                    'nama' => $tenagaPendukung->nama ?? '-',
                    'cabor' => $cabor?->nama ?? '-',
                    'nomor_posisi' => $caborKategoriTenagaPendukung?->posisi_atlet ?? '-',
                    'juara_medali' => $prestasi->peringkat ?? '-',
                    'kategori_peserta' => !empty($kategoriPeserta) ? implode(', ', $kategoriPeserta) : '-',
                    'bonus' => $prestasi->bonus ?? 0,
                    'nama_event' => $prestasi->nama_event ?: 'Event Tanpa Nama',
                    'tanggal' => $prestasi->tanggal,
                ];
            })
            ->filter(); // Remove null values

        // Combine all prestasi
        $allPrestasi = $atletPrestasi->concat($pelatihPrestasi)->concat($tenagaPendukungPrestasi);

        \Log::info('PrestasiController: Combined prestasi count', [
            'atlet_count' => $atletPrestasi->count(),
            'pelatih_count' => $pelatihPrestasi->count(),
            'tenaga_pendukung_count' => $tenagaPendukungPrestasi->count(),
            'total_count' => $allPrestasi->count(),
        ]);

        \Log::info('PrestasiController: Combined prestasi count', [
            'atlet_count' => $atletPrestasi->count(),
            'pelatih_count' => $pelatihPrestasi->count(),
            'tenaga_pendukung_count' => $tenagaPendukungPrestasi->count(),
            'total_count' => $allPrestasi->count(),
        ]);

        // Filter out prestasi without nama_event
        // Don't filter if we're looking for a specific event
        if (!$eventName) {
            $allPrestasi = $allPrestasi->filter(function ($prestasi) {
                $namaEvent = $prestasi['nama_event'] ?? null;
                return !empty($namaEvent) && $namaEvent !== '-';
            });
        }

        \Log::info('PrestasiController: After filter nama_event', [
            'count' => $allPrestasi->count(),
            'sample_prestasi' => $allPrestasi->take(3)->map(function ($p) {
                return ['nama_event' => $p['nama_event'] ?? 'NULL', 'nama' => $p['nama'] ?? 'NULL'];
            })->toArray(),
        ]);

        // Group by event name
        $groupedByEvent = $allPrestasi->groupBy('nama_event')->map(function ($prestasiGroup, $eventName) {
            $totalBonus = $prestasiGroup->sum('bonus');
            return [
                'event_name' => $eventName,
                'count' => $prestasiGroup->count(),
                'total_bonus' => $totalBonus,
                'prestasi' => $prestasiGroup->values(),
            ];
        })->values();

        // Get unique event names for tabs (filter out empty/null)
        $eventNames = $allPrestasi->pluck('nama_event')
            ->filter(function ($name) {
                return !empty($name) && $name !== '-';
            })
            ->unique()
            ->values();

        // If limit is set, limit the prestasi per event
        if ($limit > 0 && !$eventName) {
            $groupedByEvent = $groupedByEvent->map(function ($event) use ($limit) {
                $event['prestasi'] = $event['prestasi']->take($limit);
                $event['has_more'] = $event['count'] > $limit;
                return $event;
            });
        }

        return response()->json([
            'data' => $groupedByEvent,
            'event_names' => $eventNames,
            'total_bonus' => $allPrestasi->sum('bonus'),
            'meta' => [
                'total' => $allPrestasi->count(),
                'limit' => $limit,
            ],
        ]);
    }
}

