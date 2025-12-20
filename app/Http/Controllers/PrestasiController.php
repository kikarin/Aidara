<?php

namespace App\Http\Controllers;

use App\Models\AtletPrestasi;
use App\Models\PelatihPrestasi;
use App\Models\TenagaPendukungPrestasi;
use App\Models\MstKategoriPeserta;
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
        $kategoriPesertaId = $request->input('kategori_peserta_id', '');
        $jenisPrestasi = $request->input('jenis_prestasi', '');
        $limit = $request->input('limit', 5); // Default 5 untuk preview

        \Log::info('PrestasiController: apiIndex called', [
            'kategori_peserta_id' => $kategoriPesertaId,
            'limit' => $limit,
        ]);

        // Get all prestasi from atlet, pelatih, and tenaga pendukung
        $atletPrestasi = AtletPrestasi::with([
            'atlet' => function ($query) {
                $query->withTrashed()->with(['kategoriPesertas', 'caborKategoriAtlet' => function ($q) {
                    $q->where('is_active', 1)->whereNull('deleted_at')->with('cabor');
                }]);
            },
            'kategoriPeserta',
            'tingkat',
            'anggotaBeregu.atlet', // Load anggota beregu untuk mendapatkan nama semua anggota
        ])
            ->whereNull('deleted_at')
            ->when($kategoriPesertaId && $kategoriPesertaId !== 'all', function ($query) use ($kategoriPesertaId) {
                $query->where('kategori_peserta_id', $kategoriPesertaId);
            })
            ->when($jenisPrestasi && $jenisPrestasi !== 'all', function ($query) use ($jenisPrestasi) {
                $query->where('jenis_prestasi', $jenisPrestasi);
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
                $kategoriPeserta = $prestasi->kategoriPeserta?->nama ?? '-';
                $isNPCI = $kategoriPeserta === 'NPCI';
                $isSOIna = $kategoriPeserta === 'SOIna';
                
                return [
                    'id' => $prestasi->id,
                    'prestasi_group_id' => $prestasi->prestasi_group_id,
                    'jenis_prestasi' => $prestasi->jenis_prestasi,
                    'peserta_type' => 'atlet',
                    'peserta_id' => $atlet->id,
                    'nama' => $atlet->nama ?? '-',
                    'cabor' => $cabor?->nama ?? '-',
                    'nomor_posisi' => $caborKategoriAtlet?->posisi_atlet ?? '-',
                    'juara' => $prestasi->juara ?? '-',
                    'medali' => $prestasi->medali ?? '-',
                    'kategori_peserta' => $kategoriPeserta,
                    'kategori_peserta_id' => $prestasi->kategori_peserta_id,
                    'bonus' => $prestasi->bonus ?? 0,
                    'nama_event' => $prestasi->nama_event ?: 'Event Tanpa Nama',
                    'tanggal' => $prestasi->tanggal,
                    'tingkat' => $prestasi->tingkat?->nama ?? '-',
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
            'kategoriPeserta',
            'tingkat',
        ])
            ->whereNull('deleted_at')
            ->when($kategoriPesertaId && $kategoriPesertaId !== 'all', function ($query) use ($kategoriPesertaId) {
                $query->where('kategori_peserta_id', $kategoriPesertaId);
            })
            ->when($jenisPrestasi && $jenisPrestasi !== 'all', function ($query) use ($jenisPrestasi) {
                $query->where('jenis_prestasi', $jenisPrestasi);
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
                $kategoriPeserta = $prestasi->kategoriPeserta?->nama ?? '-';
                
                return [
                    'id' => $prestasi->id,
                    'prestasi_group_id' => $prestasi->prestasi_group_id,
                    'jenis_prestasi' => $prestasi->jenis_prestasi,
                    'peserta_type' => 'pelatih',
                    'peserta_id' => $pelatih->id,
                    'nama' => $pelatih->nama ?? '-',
                    'cabor' => $cabor?->nama ?? '-',
                    'nomor_posisi' => $caborKategoriPelatih?->posisi_atlet ?? '-',
                    'juara' => $prestasi->juara ?? '-',
                    'medali' => $prestasi->medali ?? '-',
                    'kategori_peserta' => $kategoriPeserta,
                    'kategori_peserta_id' => $prestasi->kategori_peserta_id,
                    'bonus' => $prestasi->bonus ?? 0,
                    'nama_event' => $prestasi->nama_event ?: 'Event Tanpa Nama',
                    'tanggal' => $prestasi->tanggal,
                    'tingkat' => $prestasi->tingkat?->nama ?? '-',
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
            ->get()
            ->filter(function ($prestasi) {
                return $prestasi->tenaga_pendukung !== null && $prestasi->tenaga_pendukung->deleted_at === null;
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
                    'juara' => '-',
                    'medali' => '-',
                    'kategori_peserta' => !empty($kategoriPeserta) ? implode(', ', $kategoriPeserta) : '-',
                    'bonus' => $prestasi->bonus ?? 0,
                    'nama_event' => $prestasi->nama_event ?: 'Event Tanpa Nama',
                    'tanggal' => $prestasi->tanggal,
                    'tingkat' => '-',
                ];
            })
            ->filter(); // Remove null values

        // Combine all prestasi
        $allPrestasi = $atletPrestasi->concat($pelatihPrestasi)->concat($tenagaPendukungPrestasi);

        // Handle beregu: Group by prestasi_group_id and create unified rows
        $processedPrestasi = [];
        $processedGroupIds = [];
        
        // Convert to collection untuk memudahkan filtering
        $allPrestasiCollection = collect($allPrestasi);
        
        // Get all unique prestasi_group_id untuk beregu
        $bereguGroupIds = $allPrestasiCollection
            ->where('jenis_prestasi', 'ganda/mixed/beregu/double')
            ->whereNotNull('prestasi_group_id')
            ->pluck('prestasi_group_id')
            ->unique()
            ->values();
        
        // Process beregu groups
        foreach ($bereguGroupIds as $groupId) {
            // Ambil semua prestasi dengan prestasi_group_id ini
            $anggotaBeregu = $allPrestasiCollection->filter(function ($p) use ($groupId) {
                return isset($p['prestasi_group_id']) && $p['prestasi_group_id'] == $groupId;
            });
            
            if ($anggotaBeregu->isEmpty()) {
                continue;
            }
            
            // Ambil prestasi utama (yang id == prestasi_group_id)
            $prestasiUtama = $anggotaBeregu->firstWhere('id', $groupId);
            if (!$prestasiUtama) {
                continue;
            }
            
            // Buat row unified untuk beregu
            // Ambil data anggota dengan id dan nama untuk redirect
            $anggotaBereguData = $anggotaBeregu->map(function ($p) {
                return [
                    'id' => $p['peserta_id'],
                    'nama' => $p['nama'],
                    'peserta_type' => $p['peserta_type'],
                ];
            })->unique('id')->values()->toArray();
            
            $processedPrestasi[] = [
                'id' => $prestasiUtama['id'],
                'prestasi_group_id' => $prestasiUtama['prestasi_group_id'],
                'jenis_prestasi' => 'ganda/mixed/beregu/double',
                'peserta_type' => $prestasiUtama['peserta_type'],
                'peserta_id' => $prestasiUtama['peserta_id'],
                'nama' => $prestasiUtama['nama'], // Nama peserta utama saja
                'cabor' => $prestasiUtama['cabor'],
                'nomor_posisi' => '-',
                'juara' => $prestasiUtama['juara'],
                'medali' => $prestasiUtama['medali'], // Medali dihitung 1 per regu
                'kategori_peserta' => $prestasiUtama['kategori_peserta'],
                'kategori_peserta_id' => $prestasiUtama['kategori_peserta_id'],
                'bonus' => $prestasiUtama['bonus'],
                'nama_event' => $prestasiUtama['nama_event'],
                'tanggal' => $prestasiUtama['tanggal'],
                'tingkat' => $prestasiUtama['tingkat'],
                'is_beregu' => true,
                'jumlah_anggota' => $anggotaBeregu->count(),
                'anggota_beregu' => $anggotaBereguData, // Array dengan id, nama, dan peserta_type untuk modal
                'disabilitas' => $prestasiUtama['disabilitas'] ?? null,
                'klasifikasi' => $prestasiUtama['klasifikasi'] ?? null,
                'iq' => $prestasiUtama['iq'] ?? null,
            ];
            
            $processedGroupIds[] = $groupId;
        }
        
        // Process prestasi individu (yang tidak beregu atau belum diproses)
        foreach ($allPrestasiCollection as $prestasi) {
            // Skip jika sudah diproses sebagai beregu
            if ($prestasi['prestasi_group_id'] && in_array($prestasi['prestasi_group_id'], $processedGroupIds)) {
                continue;
            }
            
            // Skip anggota beregu
            if ($prestasi['prestasi_group_id'] && $prestasi['id'] != $prestasi['prestasi_group_id']) {
                continue;
            }
            
            // Prestasi individu
            $prestasi['is_beregu'] = false;
            $prestasi['jumlah_anggota'] = 1;
            $processedPrestasi[] = $prestasi;
        }

        // Group by kategori peserta
        $groupedByKategori = collect($processedPrestasi)->groupBy('kategori_peserta_id')->map(function ($prestasiGroup, $kategoriId) {
            // Hitung total medali (untuk beregu, medali dihitung 1 per regu)
            $totalMedali = [
                'Emas' => 0,
                'Perak' => 0,
                'Perunggu' => 0,
            ];
            
            foreach ($prestasiGroup as $prestasi) {
                if ($prestasi['medali'] && $prestasi['medali'] !== '-') {
                    $medali = $prestasi['medali'];
                    if (isset($totalMedali[$medali])) {
                        $totalMedali[$medali]++;
                    }
                }
            }
            
            return [
                'kategori_peserta_id' => $kategoriId,
                'kategori_peserta_nama' => $prestasiGroup->first()['kategori_peserta'] ?? '-',
                'count' => $prestasiGroup->count(),
                'total_bonus' => $prestasiGroup->sum('bonus'),
                'total_medali' => $totalMedali,
                'prestasi' => $prestasiGroup->values(),
            ];
        })->values();

        // Get unique kategori peserta for tabs
        $kategoriPesertaList = MstKategoriPeserta::whereNull('deleted_at')
            ->orderBy('nama')
            ->get()
            ->map(function ($kategori) {
                return [
                    'id' => $kategori->id,
                    'nama' => $kategori->nama,
                ];
            });

        // If limit is set, limit the prestasi per kategori
        if ($limit > 0 && (!$kategoriPesertaId || $kategoriPesertaId === 'all')) {
            $groupedByKategori = $groupedByKategori->map(function ($kategori) use ($limit) {
                $kategori['prestasi'] = collect($kategori['prestasi'])->take($limit);
                $kategori['has_more'] = $kategori['count'] > $limit;
                return $kategori;
            });
        }

        return response()->json([
            'data' => $groupedByKategori,
            'kategori_peserta_list' => $kategoriPesertaList,
            'total_bonus' => collect($processedPrestasi)->sum('bonus'),
            'meta' => [
                'total' => count($processedPrestasi),
                'limit' => $limit,
            ],
        ]);
    }

    public function apiSummary(Request $request)
    {
        try {
            // Gunakan logika yang sama dengan apiIndex untuk menghitung bonus dan medali
            // Ini penting untuk menghindari double counting pada prestasi beregu
            
            // Get all prestasi from atlet, pelatih, and tenaga pendukung
            $atletPrestasi = AtletPrestasi::with([
                'atlet' => function ($query) {
                    $query->withTrashed();
                },
            ])
                ->whereNull('deleted_at')
                ->get()
                ->filter(function ($prestasi) {
                    return $prestasi->atlet !== null && $prestasi->atlet->deleted_at === null;
                })
                ->map(function ($prestasi) {
                    return [
                        'id' => $prestasi->id,
                        'prestasi_group_id' => $prestasi->prestasi_group_id,
                        'jenis_prestasi' => $prestasi->jenis_prestasi,
                        'medali' => $prestasi->medali ?? '-',
                        'bonus' => $prestasi->bonus ?? 0,
                    ];
                });
            
            $pelatihPrestasi = PelatihPrestasi::with([
                'pelatih' => function ($query) {
                    $query->withTrashed();
                },
            ])
                ->whereNull('deleted_at')
                ->get()
                ->filter(function ($prestasi) {
                    return $prestasi->pelatih !== null && $prestasi->pelatih->deleted_at === null;
                })
                ->map(function ($prestasi) {
                    return [
                        'id' => $prestasi->id,
                        'prestasi_group_id' => $prestasi->prestasi_group_id,
                        'jenis_prestasi' => $prestasi->jenis_prestasi,
                        'medali' => $prestasi->medali ?? '-',
                        'bonus' => $prestasi->bonus ?? 0,
                    ];
                });
            
            $tenagaPendukungPrestasi = TenagaPendukungPrestasi::with([
                'tenaga_pendukung' => function ($query) {
                    $query->withTrashed();
                },
            ])
                ->whereNull('deleted_at')
                ->get()
                ->filter(function ($prestasi) {
                    return $prestasi->tenaga_pendukung !== null && $prestasi->tenaga_pendukung->deleted_at === null;
                })
                ->map(function ($prestasi) {
                    return [
                        'id' => $prestasi->id,
                        'prestasi_group_id' => $prestasi->prestasi_group_id,
                        'jenis_prestasi' => $prestasi->jenis_prestasi,
                        'medali' => $prestasi->medali ?? '-',
                        'bonus' => $prestasi->bonus ?? 0,
                    ];
                });
            
            // Combine all prestasi
            $allPrestasi = $atletPrestasi->concat($pelatihPrestasi)->concat($tenagaPendukungPrestasi);
            
            // Handle beregu: Group by prestasi_group_id and create unified rows (sama seperti apiIndex)
            $processedPrestasi = [];
            $processedGroupIds = [];
            
            $allPrestasiCollection = collect($allPrestasi);
            
            // Get all unique prestasi_group_id untuk beregu
            $bereguGroupIds = $allPrestasiCollection
                ->where('jenis_prestasi', 'ganda/mixed/beregu/double')
                ->whereNotNull('prestasi_group_id')
                ->pluck('prestasi_group_id')
                ->unique()
                ->values();
            
            // Process beregu groups
            foreach ($bereguGroupIds as $groupId) {
                $anggotaBeregu = $allPrestasiCollection->filter(function ($p) use ($groupId) {
                    return isset($p['prestasi_group_id']) && $p['prestasi_group_id'] == $groupId;
                });
                
                if ($anggotaBeregu->isEmpty()) {
                    continue;
                }
                
                // Ambil prestasi utama (yang id == prestasi_group_id)
                $prestasiUtama = $anggotaBeregu->firstWhere('id', $groupId);
                if (!$prestasiUtama) {
                    continue;
                }
                
                // Untuk beregu, hanya ambil bonus dan medali dari prestasi utama (1 per regu)
                $processedPrestasi[] = [
                    'id' => $prestasiUtama['id'],
                    'medali' => $prestasiUtama['medali'],
                    'bonus' => $prestasiUtama['bonus'],
                ];
                
                $processedGroupIds[] = $groupId;
            }
            
            // Process prestasi individu (yang tidak beregu atau belum diproses)
            foreach ($allPrestasiCollection as $prestasi) {
                // Skip jika sudah diproses sebagai beregu
                if ($prestasi['prestasi_group_id'] && in_array($prestasi['prestasi_group_id'], $processedGroupIds)) {
                    continue;
                }
                
                // Skip anggota beregu
                if ($prestasi['prestasi_group_id'] && $prestasi['id'] != $prestasi['prestasi_group_id']) {
                    continue;
                }
                
                // Prestasi individu
                $processedPrestasi[] = [
                    'id' => $prestasi['id'],
                    'medali' => $prestasi['medali'],
                    'bonus' => $prestasi['bonus'],
                ];
            }
            
            // Hitung total bonus dari processed prestasi (sudah handle beregu)
            $totalBonus = collect($processedPrestasi)->sum('bonus');
            
            // Hitung total medali dari processed prestasi (sudah handle beregu)
            $totalMedali = [
                'Emas' => 0,
                'Perak' => 0,
                'Perunggu' => 0,
            ];
            
            foreach ($processedPrestasi as $prestasi) {
                if ($prestasi['medali'] && $prestasi['medali'] !== '-') {
                    $medali = $prestasi['medali'];
                    if (isset($totalMedali[$medali])) {
                        $totalMedali[$medali]++;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_bonus' => $totalBonus,
                    'total_medali' => $totalMedali,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching prestasi summary: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
