<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtletPrestasi;
use App\Models\PelatihPrestasi;
use App\Models\TenagaPendukungPrestasi;
use App\Models\MstKategoriPeserta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PrestasiController extends Controller
{
    /**
     * Get list prestasi
     * Untuk dashboard: limit 5 terbaru
     * Untuk lihat semua: semua prestasi
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            
            // Check permission
            if (!Gate::allows('Dashboard Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat prestasi.',
                ], 403);
            }

            $kategoriPesertaId = $request->input('kategori_peserta_id', '');
            $jenisPrestasi = $request->input('jenis_prestasi', '');
            $limit = (int) $request->input('limit', 0); // 0 means no limit, 5 for dashboard preview

            // Get all prestasi from atlet, pelatih, and tenaga pendukung
            $atletPrestasi = AtletPrestasi::with([
                'atlet' => function ($query) {
                    $query->withTrashed()->with(['kategoriPesertas', 'caborKategoriAtlet' => function ($q) {
                        $q->where('is_active', 1)->whereNull('deleted_at')->with('cabor');
                    }]);
                },
                'kategoriPeserta',
                'tingkat',
                'anggotaBeregu.atlet',
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
                    return $prestasi->atlet !== null && $prestasi->atlet->deleted_at === null;
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
                        // NPCI/SOIna fields
                        'disabilitas' => ($isNPCI || $isSOIna) ? ($atlet->disabilitas ?? '-') : null,
                        'klasifikasi' => $isNPCI ? ($atlet->klasifikasi ?? '-') : null,
                        'iq' => $isSOIna ? ($atlet->iq ?? '-') : null,
                    ];
                })
                ->filter();

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
                    return $prestasi->pelatih !== null && $prestasi->pelatih->deleted_at === null;
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
                ->filter();

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
                ->filter();

            // Combine all prestasi
            $allPrestasi = $atletPrestasi->concat($pelatihPrestasi)->concat($tenagaPendukungPrestasi);

            // Handle beregu: Group by prestasi_group_id and create unified rows
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
                
                $prestasiUtama = $anggotaBeregu->firstWhere('id', $groupId);
                if (!$prestasiUtama) {
                    continue;
                }
                
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
                    'nama' => $prestasiUtama['nama'],
                    'cabor' => $prestasiUtama['cabor'],
                    'nomor_posisi' => '-',
                    'juara' => $prestasiUtama['juara'],
                    'medali' => $prestasiUtama['medali'],
                    'kategori_peserta' => $prestasiUtama['kategori_peserta'],
                    'kategori_peserta_id' => $prestasiUtama['kategori_peserta_id'],
                    'bonus' => $prestasiUtama['bonus'],
                    'nama_event' => $prestasiUtama['nama_event'],
                    'tanggal' => $prestasiUtama['tanggal'],
                    'tingkat' => $prestasiUtama['tingkat'],
                    'is_beregu' => true,
                    'jumlah_anggota' => $anggotaBeregu->count(),
                    'anggota_beregu' => $anggotaBereguData,
                    'disabilitas' => $prestasiUtama['disabilitas'] ?? null,
                    'klasifikasi' => $prestasiUtama['klasifikasi'] ?? null,
                    'iq' => $prestasiUtama['iq'] ?? null,
                ];
                
                $processedGroupIds[] = $groupId;
            }
            
            // Process prestasi individu
            foreach ($allPrestasiCollection as $prestasi) {
                if ($prestasi['prestasi_group_id'] && in_array($prestasi['prestasi_group_id'], $processedGroupIds)) {
                    continue;
                }
                
                if ($prestasi['prestasi_group_id'] && $prestasi['id'] != $prestasi['prestasi_group_id']) {
                    continue;
                }
                
                $prestasi['is_beregu'] = false;
                $prestasi['jumlah_anggota'] = 1;
                $processedPrestasi[] = $prestasi;
            }

            // Group by kategori peserta
            $groupedByKategori = collect($processedPrestasi)->groupBy('kategori_peserta_id')->map(function ($prestasiGroup, $kategoriId) {
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

            // Calculate total bonus and medali
            $totalBonus = collect($processedPrestasi)->sum('bonus');
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
                'status' => 'success',
                'data' => $groupedByKategori,
                'kategori_peserta_list' => $kategoriPesertaList,
                'total_bonus' => $totalBonus,
                'total_medali' => $totalMedali,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Prestasi error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data prestasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

