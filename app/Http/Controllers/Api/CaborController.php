<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabor;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Models\PemeriksaanKhusus;
use App\Models\PemeriksaanKhususAspek;
use App\Models\PemeriksaanKhususPeserta;
use App\Models\PemeriksaanKhususPesertaAspek;
use App\Models\PemeriksaanKhususPesertaKeseluruhan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CaborController extends Controller
{
    /**
     * Get list cabor
     * Untuk beranda: limit 5 terbaru
     * Untuk lihat semua: semua cabor
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            // Pastikan relasi ter-load dengan benar
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);
            
            // Debug: Log role dan peserta info (hapus setelah testing)
            // Log::info('Cabor Index - User Info', [
            //     'user_id' => $user->id,
            //     'current_role_id' => $user->current_role_id,
            //     'has_atlet' => $user->atlet ? true : false,
            //     'has_pelatih' => $user->pelatih ? true : false,
            //     'has_tenaga_pendukung' => $user->tenagaPendukung ? true : false,
            //     'atlet_id' => $user->atlet->id ?? null,
            //     'pelatih_id' => $user->pelatih->id ?? null,
            //     'tenaga_pendukung_id' => $user->tenagaPendukung->id ?? null,
            // ]);

            $query = Cabor::withoutGlobalScopes()
                ->whereNull('cabor.deleted_at')
                ->with('kategoriPeserta')
                ->select('cabor.id', 'cabor.nama', 'cabor.deskripsi', 'cabor.kategori_peserta_id', 'cabor.icon', 'cabor.created_at');

            // Role-based filtering
            // Non-peserta (Superadmin role_id=1, Admin role_id=11) - lihat semua cabor (tidak perlu filter)
            // Peserta (Atlet role_id=35, Pelatih role_id=36, Tenaga Pendukung role_id=37) - hanya cabor mereka
            $roleId = (int) ($user->current_role_id ?? 0);
            
            // Cek apakah user adalah peserta (bukan superadmin/admin)
            $isPeserta = in_array($roleId, [35, 36, 37]);
            
            if ($isPeserta) {
                $caborIds = [];
                $pesertaId = null;
                
                // Coba ambil peserta_id dari relasi atau dari user->peserta_id
                if ($roleId == 35) { // Atlet
                    $pesertaId = $user->atlet->id ?? $user->peserta_id ?? null;
                    if ($pesertaId) {
                        $caborIds = DB::table('cabor_kategori_atlet')
                            ->where('atlet_id', $pesertaId)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_atlet.deleted_at')
                            ->distinct()
                            ->pluck('cabor_id')
                            ->toArray();
                    }
                } elseif ($roleId == 36) { // Pelatih
                    $pesertaId = $user->pelatih->id ?? $user->peserta_id ?? null;
                    if ($pesertaId) {
                        $caborIds = DB::table('cabor_kategori_pelatih')
                            ->where('pelatih_id', $pesertaId)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_pelatih.deleted_at')
                            ->distinct()
                            ->pluck('cabor_id')
                            ->toArray();
                    }
                } elseif ($roleId == 37) { // Tenaga Pendukung
                    $pesertaId = $user->tenagaPendukung->id ?? $user->peserta_id ?? null;
                    if ($pesertaId) {
                        $caborIds = DB::table('cabor_kategori_tenaga_pendukung')
                            ->where('tenaga_pendukung_id', $pesertaId)
                            ->where('is_active', 1)
                            ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
                            ->distinct()
                            ->pluck('cabor_id')
                            ->toArray();
                    }
                }
                
                // Jika peserta tidak punya cabor, return empty
                if (empty($caborIds)) {
                    return response()->json([
                        'status' => 'success',
                        'data' => [],
                    ]);
                }
                
                // Filter cabor berdasarkan cabor_ids yang dimiliki peserta
                $query->whereIn('cabor.id', $caborIds);
            }
            // Jika bukan peserta (superadmin/admin), tidak perlu filter (lihat semua)

            // Filter by kategori_peserta_id jika ada
            if ($request->has('kategori_peserta_id') && $request->kategori_peserta_id && $request->kategori_peserta_id !== 'all') {
                $query->where('cabor.kategori_peserta_id', $request->kategori_peserta_id);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('cabor.nama', 'like', "%{$search}%")
                        ->orWhere('cabor.deskripsi', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortField = $request->get('sort', 'id');
            $sortOrder = $request->get('order', 'desc');
            $validColumns = ['id', 'nama', 'deskripsi', 'created_at', 'updated_at'];

            if (in_array($sortField, $validColumns)) {
                $query->orderBy("cabor.{$sortField}", $sortOrder);
            } else {
                $query->orderBy('cabor.id', 'desc');
            }

            // Limit untuk beranda (5 terbaru)
            $limit = $request->get('limit');
            if ($limit && $limit == 5) {
                $query->limit(5);
            }

            $items = $query->get();

            $formattedData = $items->map(function ($item) {
                // Hitung jumlah peserta unik per cabor (aktif dan tidak soft deleted)
                // Gunakan subquery untuk menghindari duplikasi
                $atletIds = DB::table('cabor_kategori_atlet')
                    ->where('cabor_id', $item->id)
                    ->where('is_active', 1)
                    ->whereNull('cabor_kategori_atlet.deleted_at')
                    ->distinct()
                    ->pluck('atlet_id')
                    ->toArray();

                $jumlahAtlet = 0;
                if (!empty($atletIds)) {
                    $jumlahAtlet = DB::table('atlets')
                        ->whereIn('id', $atletIds)
                        ->whereNull('atlets.deleted_at')
                        ->count();
                }

                $pelatihIds = DB::table('cabor_kategori_pelatih')
                    ->where('cabor_id', $item->id)
                    ->where('is_active', 1)
                    ->whereNull('cabor_kategori_pelatih.deleted_at')
                    ->distinct()
                    ->pluck('pelatih_id')
                    ->toArray();

                $jumlahPelatih = 0;
                if (!empty($pelatihIds)) {
                    $jumlahPelatih = DB::table('pelatihs')
                        ->whereIn('id', $pelatihIds)
                        ->whereNull('pelatihs.deleted_at')
                        ->count();
                }

                $tenagaIds = DB::table('cabor_kategori_tenaga_pendukung')
                    ->where('cabor_id', $item->id)
                    ->where('is_active', 1)
                    ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
                    ->distinct()
                    ->pluck('tenaga_pendukung_id')
                    ->toArray();

                $jumlahTenagaPendukung = 0;
                if (!empty($tenagaIds)) {
                    $jumlahTenagaPendukung = DB::table('tenaga_pendukungs')
                        ->whereIn('id', $tenagaIds)
                        ->whereNull('tenaga_pendukungs.deleted_at')
                        ->count();
                }

                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'deskripsi' => $item->deskripsi,
                    'icon' => $item->icon,
                    'kategori_peserta' => $item->kategoriPeserta ? [
                        'id' => $item->kategoriPeserta->id,
                        'nama' => $item->kategoriPeserta->nama,
                    ] : null,
                    'jumlah_atlet' => $jumlahAtlet,
                    'jumlah_pelatih' => $jumlahPelatih,
                    'jumlah_tenaga_pendukung' => $jumlahTenagaPendukung,
                    'created_at' => $item->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Cabor List error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data cabor.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get peserta cabor (atlet, pelatih, tenaga pendukung)
     */
    public function getPeserta(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();

            // Check permission
            if (!Gate::allows('Cabor Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat peserta cabor.',
                ], 403);
            }

            $cabor = Cabor::findOrFail($id);

            // Get jenis peserta dari query parameter (optional, default: all)
            $jenisPeserta = $request->query('jenis_peserta', 'all'); // all, atlet, pelatih, tenaga-pendukung

            $result = [
                'atlet' => [],
                'pelatih' => [],
                'tenaga_pendukung' => [],
            ];

            // Get Atlet
            if ($jenisPeserta === 'all' || $jenisPeserta === 'atlet') {
                // Get unique atlet IDs dari cabor ini (aktif dan tidak soft deleted)
                $atletIds = DB::table('cabor_kategori_atlet')
                    ->where('cabor_kategori_atlet.cabor_id', $id)
                    ->where('cabor_kategori_atlet.is_active', 1)
                    ->whereNull('cabor_kategori_atlet.deleted_at')
                    ->distinct()
                    ->pluck('cabor_kategori_atlet.atlet_id')
                    ->toArray();

                if (!empty($atletIds)) {
                    // Get atlet yang tidak soft deleted
                    $atletList = DB::table('atlets')
                        ->whereIn('atlets.id', $atletIds)
                        ->whereNull('atlets.deleted_at')
                        ->select(
                            'atlets.id',
                            'atlets.nama',
                            'atlets.jenis_kelamin',
                            'atlets.tanggal_lahir'
                        )
                        ->orderBy('atlets.nama')
                        ->get();

                    // Get posisi untuk setiap atlet (ambil yang pertama jika ada multiple)
                    $posisiMap = DB::table('cabor_kategori_atlet')
                        ->where('cabor_kategori_atlet.cabor_id', $id)
                        ->whereIn('cabor_kategori_atlet.atlet_id', $atletIds)
                        ->where('cabor_kategori_atlet.is_active', 1)
                        ->whereNull('cabor_kategori_atlet.deleted_at')
                        ->select('cabor_kategori_atlet.atlet_id', 'cabor_kategori_atlet.posisi_atlet')
                        ->get()
                        ->groupBy('atlet_id')
                        ->map(function ($items) {
                            return $items->first()->posisi_atlet ?? '-';
                        })
                        ->toArray();
                } else {
                    $atletList = collect();
                    $posisiMap = [];
                }

                foreach ($atletList as $atlet) {
                    $usia = null;
                    if ($atlet->tanggal_lahir) {
                        try {
                            $usia = Carbon::parse($atlet->tanggal_lahir)->age;
                        } catch (\Exception $e) {
                            $usia = null;
                        }
                    }

                    // Get foto
                    $foto = null;
                    $fotoThumbnail = null;
                    try {
                        $atletModel = \App\Models\Atlet::find($atlet->id);
                        if ($atletModel) {
                            $foto = $atletModel->foto;
                            $fotoThumbnail = $atletModel->foto_thumbnail;
                        }
                    } catch (\Exception $e) {
                        // Skip jika error
                    }

                    $result['atlet'][] = [
                        'id' => $atlet->id,
                        'nama' => $atlet->nama,
                        'foto' => $foto,
                        'foto_thumbnail' => $fotoThumbnail,
                        'jenis_kelamin' => $atlet->jenis_kelamin,
                        'usia' => $usia,
                        'posisi' => $posisiMap[$atlet->id] ?? '-',
                    ];
                }
            }

            // Get Pelatih
            if ($jenisPeserta === 'all' || $jenisPeserta === 'pelatih') {
                // Get unique pelatih IDs dari cabor ini (aktif dan tidak soft deleted)
                $pelatihIds = DB::table('cabor_kategori_pelatih')
                    ->where('cabor_kategori_pelatih.cabor_id', $id)
                    ->where('cabor_kategori_pelatih.is_active', 1)
                    ->whereNull('cabor_kategori_pelatih.deleted_at')
                    ->distinct()
                    ->pluck('cabor_kategori_pelatih.pelatih_id')
                    ->toArray();

                if (!empty($pelatihIds)) {
                    // Get pelatih yang tidak soft deleted
                    $pelatihList = DB::table('pelatihs')
                        ->whereIn('pelatihs.id', $pelatihIds)
                        ->whereNull('pelatihs.deleted_at')
                        ->select(
                            'pelatihs.id',
                            'pelatihs.nama',
                            'pelatihs.jenis_kelamin',
                            'pelatihs.tanggal_lahir'
                        )
                        ->orderBy('pelatihs.nama')
                        ->get();

                    // Get jenis untuk setiap pelatih (ambil yang pertama jika ada multiple)
                    $jenisMap = DB::table('cabor_kategori_pelatih')
                        ->where('cabor_kategori_pelatih.cabor_id', $id)
                        ->whereIn('cabor_kategori_pelatih.pelatih_id', $pelatihIds)
                        ->where('cabor_kategori_pelatih.is_active', 1)
                        ->whereNull('cabor_kategori_pelatih.deleted_at')
                        ->select('cabor_kategori_pelatih.pelatih_id', 'cabor_kategori_pelatih.jenis_pelatih')
                        ->get()
                        ->groupBy('pelatih_id')
                        ->map(function ($items) {
                            return $items->first()->jenis_pelatih ?? '-';
                        })
                        ->toArray();
                } else {
                    $pelatihList = collect();
                    $jenisMap = [];
                }

                foreach ($pelatihList as $pelatih) {
                    $usia = null;
                    if ($pelatih->tanggal_lahir) {
                        try {
                            $usia = Carbon::parse($pelatih->tanggal_lahir)->age;
                        } catch (\Exception $e) {
                            $usia = null;
                        }
                    }

                    // Get foto
                    $foto = null;
                    $fotoThumbnail = null;
                    try {
                        $pelatihModel = \App\Models\Pelatih::find($pelatih->id);
                        if ($pelatihModel) {
                            $foto = $pelatihModel->foto;
                            $fotoThumbnail = $pelatihModel->foto_thumbnail;
                        }
                    } catch (\Exception $e) {
                        // Skip jika error
                    }

                    $result['pelatih'][] = [
                        'id' => $pelatih->id,
                        'nama' => $pelatih->nama,
                        'foto' => $foto,
                        'foto_thumbnail' => $fotoThumbnail,
                        'jenis_kelamin' => $pelatih->jenis_kelamin,
                        'usia' => $usia,
                        'jenis' => $jenisMap[$pelatih->id] ?? '-',
                    ];
                }
            }

            // Get Tenaga Pendukung
            if ($jenisPeserta === 'all' || $jenisPeserta === 'tenaga-pendukung') {
                // Get unique tenaga pendukung IDs dari cabor ini (aktif dan tidak soft deleted)
                $tenagaIds = DB::table('cabor_kategori_tenaga_pendukung')
                    ->where('cabor_kategori_tenaga_pendukung.cabor_id', $id)
                    ->where('cabor_kategori_tenaga_pendukung.is_active', 1)
                    ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
                    ->distinct()
                    ->pluck('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                    ->toArray();

                if (!empty($tenagaIds)) {
                    // Get tenaga pendukung yang tidak soft deleted
                    $tenagaList = DB::table('tenaga_pendukungs')
                        ->whereIn('tenaga_pendukungs.id', $tenagaIds)
                        ->whereNull('tenaga_pendukungs.deleted_at')
                        ->select(
                            'tenaga_pendukungs.id',
                            'tenaga_pendukungs.nama',
                            'tenaga_pendukungs.jenis_kelamin',
                            'tenaga_pendukungs.tanggal_lahir'
                        )
                        ->orderBy('tenaga_pendukungs.nama')
                        ->get();

                    // Get jenis untuk setiap tenaga pendukung (ambil yang pertama jika ada multiple)
                    $jenisMap = DB::table('cabor_kategori_tenaga_pendukung')
                        ->where('cabor_kategori_tenaga_pendukung.cabor_id', $id)
                        ->whereIn('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', $tenagaIds)
                        ->where('cabor_kategori_tenaga_pendukung.is_active', 1)
                        ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at')
                        ->select('cabor_kategori_tenaga_pendukung.tenaga_pendukung_id', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung')
                        ->get()
                        ->groupBy('tenaga_pendukung_id')
                        ->map(function ($items) {
                            return $items->first()->jenis_tenaga_pendukung ?? '-';
                        })
                        ->toArray();
                } else {
                    $tenagaList = collect();
                    $jenisMap = [];
                }

                foreach ($tenagaList as $tenaga) {
                    $usia = null;
                    if ($tenaga->tanggal_lahir) {
                        try {
                            $usia = Carbon::parse($tenaga->tanggal_lahir)->age;
                        } catch (\Exception $e) {
                            $usia = null;
                        }
                    }

                    // Get foto
                    $foto = null;
                    $fotoThumbnail = null;
                    try {
                        $tenagaModel = \App\Models\TenagaPendukung::find($tenaga->id);
                        if ($tenagaModel) {
                            $foto = $tenagaModel->foto;
                            $fotoThumbnail = $tenagaModel->foto_thumbnail;
                        }
                    } catch (\Exception $e) {
                        // Skip jika error
                    }

                    $result['tenaga_pendukung'][] = [
                        'id' => $tenaga->id,
                        'nama' => $tenaga->nama,
                        'foto' => $foto,
                        'foto_thumbnail' => $fotoThumbnail,
                        'jenis_kelamin' => $tenaga->jenis_kelamin,
                        'usia' => $usia,
                        'jenis' => $jenisMap[$tenaga->id] ?? '-',
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Peserta Cabor error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'cabor_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil peserta cabor.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get perbandingan multi tes
     */
    public function getPerbandingan(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();

            // Check permission
            if (!Gate::allows('Cabor Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat perbandingan.',
                ], 403);
            }

            $cabor = Cabor::findOrFail($id);

            // Handle array dari query string (bisa berupa array atau string yang dipisah koma)
            $pemeriksaanIdsInput = $request->input('pemeriksaan_khusus_ids');
            
            // Convert string to array jika perlu
            if (is_string($pemeriksaanIdsInput)) {
                $pemeriksaanIds = array_map('intval', array_filter(explode(',', $pemeriksaanIdsInput)));
            } elseif (is_array($pemeriksaanIdsInput)) {
                $pemeriksaanIds = array_map('intval', array_filter($pemeriksaanIdsInput));
            } else {
                $pemeriksaanIds = [];
            }

            // Validasi setelah konversi
            if (empty($pemeriksaanIds) || count($pemeriksaanIds) < 2) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parameter pemeriksaan_khusus_ids wajib diisi minimal 2 pemeriksaan.',
                    'error' => 'pemeriksaan_khusus_ids harus berupa array dengan minimal 2 ID pemeriksaan. Contoh: ?pemeriksaan_khusus_ids[]=1&pemeriksaan_khusus_ids[]=2',
                ], 422);
            }

            // Validasi bahwa semua ID pemeriksaan ada di database
            $existingIds = PemeriksaanKhusus::whereIn('id', $pemeriksaanIds)
                ->where('cabor_id', $id)
                ->pluck('id')
                ->toArray();

            $invalidIds = array_diff($pemeriksaanIds, $existingIds);
            if (!empty($invalidIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Beberapa ID pemeriksaan tidak ditemukan atau tidak termasuk dalam cabor ini.',
                    'error' => 'ID pemeriksaan yang tidak valid: ' . implode(', ', $invalidIds),
                ], 422);
            }

            $caborKategoriId = $request->input('cabor_kategori_id');
            
            // Validasi cabor_kategori_id jika ada
            if ($caborKategoriId) {
                $exists = DB::table('cabor_kategori')
                    ->where('id', $caborKategoriId)
                    ->where('cabor_id', $id)
                    ->exists();
                
                if (!$exists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Kategori cabor tidak ditemukan atau tidak termasuk dalam cabor ini.',
                    ], 422);
                }
            }

            // Get pemeriksaan list
            $pemeriksaanList = PemeriksaanKhusus::whereIn('id', $pemeriksaanIds)
                ->where('cabor_id', $id)
                ->with(['caborKategori'])
                ->orderBy('tanggal_pemeriksaan')
                ->get();

            if ($pemeriksaanList->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemeriksaan tidak ditemukan',
                ], 404);
            }

            // Get semua aspek dari semua pemeriksaan (union berdasarkan nama)
            $allAspek = collect();
            foreach ($pemeriksaanList as $pemeriksaan) {
                $aspekList = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                    ->whereNull('deleted_at')
                    ->orderBy('urutan')
                    ->get();

                foreach ($aspekList as $aspek) {
                    // Cek apakah aspek dengan nama sama sudah ada
                    $existingAspek = $allAspek->firstWhere('nama', $aspek->nama);
                    if (!$existingAspek) {
                        $allAspek->push([
                            'id' => $aspek->id,
                            'nama' => $aspek->nama,
                            'urutan' => $aspek->urutan ?? 0,
                        ]);
                    }
                }
            }

            // Sort aspek by nama (atau urutan jika ada)
            $allAspek = $allAspek->sortBy('nama')->values();

            // Get semua peserta yang akan dibandingkan
            $pesertaQuery = PemeriksaanKhususPeserta::with(['peserta'])
                ->whereIn('pemeriksaan_khusus_id', $pemeriksaanIds);

            // Filter by kategori jika dipilih
            if ($caborKategoriId) {
                $pesertaQuery->whereHas('pemeriksaanKhusus', function ($q) use ($caborKategoriId) {
                    $q->where('cabor_kategori_id', $caborKategoriId);
                });
            }

            $allPeserta = $pesertaQuery->get();

            // Group peserta by peserta_id dan peserta_type (unique peserta)
            $uniquePeserta = collect();
            foreach ($allPeserta as $p) {
                try {
                    // Skip jika peserta_id atau peserta_type null
                    if (!$p->peserta_id || !$p->peserta_type) {
                        continue;
                    }

                    $key = $p->peserta_id . '_' . $p->peserta_type;
                    if (!$uniquePeserta->has($key)) {
                        // Get cabor_kategori_id from pemeriksaan khusus yang sudah di-load
                        $caborKategoriIdFromPemeriksaan = null;
                        $pemeriksaan = $pemeriksaanList->firstWhere('id', $p->pemeriksaan_khusus_id);
                        if ($pemeriksaan) {
                            $caborKategoriIdFromPemeriksaan = $pemeriksaan->cabor_kategori_id ?? null;
                        }
                        if (!$caborKategoriIdFromPemeriksaan && $caborKategoriId) {
                            $caborKategoriIdFromPemeriksaan = $caborKategoriId;
                        }
                        
                        // Get posisi and usia
                        $posisi = '-';
                        $usia = '-';
                        
                        try {
                            if ($p->peserta_type === 'App\\Models\\Atlet' && $caborKategoriIdFromPemeriksaan) {
                                $posisi = $this->getAtletPosisi($p->peserta_id, $caborKategoriIdFromPemeriksaan);
                                if ($p->peserta && isset($p->peserta->tanggal_lahir) && $p->peserta->tanggal_lahir) {
                                    $usia = $this->calculateAge($p->peserta->tanggal_lahir);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error getting posisi/usia for peserta in perbandingan', [
                                'peserta_id' => $p->peserta_id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        
                        $uniquePeserta->put($key, [
                            'peserta_id' => $p->peserta_id,
                            'peserta_type' => $p->peserta_type,
                            'nama' => ($p->peserta && isset($p->peserta->nama)) ? $p->peserta->nama : '-',
                            'jenis_kelamin' => ($p->peserta && isset($p->peserta->jenis_kelamin)) ? $p->peserta->jenis_kelamin : null,
                            'posisi' => $posisi,
                            'usia' => $usia,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Error processing peserta in perbandingan', [
                        'peserta_id' => $p->peserta_id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Build data perbandingan
            $data = [];
            foreach ($uniquePeserta as $pesertaData) {
                $pesertaItem = [
                    'peserta_id' => $pesertaData['peserta_id'],
                    'peserta_type' => $pesertaData['peserta_type'],
                    'nama' => $pesertaData['nama'],
                    'jenis_kelamin' => $pesertaData['jenis_kelamin'],
                    'posisi' => $pesertaData['posisi'],
                    'usia' => $pesertaData['usia'],
                    'aspek' => [],
                ];

                // Loop setiap aspek
                foreach ($allAspek as $aspek) {
                    $aspekItem = [
                        'aspek_id' => $aspek['id'],
                        'aspek_nama' => $aspek['nama'],
                        'nilai' => [],
                    ];

                    // Loop setiap pemeriksaan untuk mendapatkan nilai aspek
                    foreach ($pemeriksaanList as $pemeriksaan) {
                        // Cari aspek dengan nama sama di pemeriksaan ini
                        $aspekInPemeriksaan = PemeriksaanKhususAspek::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                            ->where('nama', $aspek['nama'])
                            ->whereNull('deleted_at')
                            ->first();

                        if ($aspekInPemeriksaan) {
                            // Get peserta di pemeriksaan ini
                            $pesertaInPemeriksaan = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                                ->where('peserta_id', $pesertaData['peserta_id'])
                                ->where('peserta_type', $pesertaData['peserta_type'])
                                ->first();

                            if ($pesertaInPemeriksaan) {
                                // Get nilai aspek
                                try {
                                    // Query langsung untuk mendapatkan hasil aspek
                                    $hasilAspek = PemeriksaanKhususPesertaAspek::where('pemeriksaan_khusus_peserta_id', $pesertaInPemeriksaan->id)
                                        ->where('pemeriksaan_khusus_aspek_id', $aspekInPemeriksaan->id)
                                        ->first();

                                    $nilaiPerforma = null;
                                    $predikat = null;
                                    
                                    if ($hasilAspek) {
                                        $nilaiPerforma = $hasilAspek->nilai_performa !== null ? (float) $hasilAspek->nilai_performa : null;
                                        $predikat = $hasilAspek->predikat ?? null;
                                    }

                                    $aspekItem['nilai'][] = [
                                        'pemeriksaan_id' => $pemeriksaan->id,
                                        'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan ?? '-',
                                        'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan ?? null,
                                        'nilai_performa' => $nilaiPerforma,
                                        'predikat' => $predikat,
                                    ];
                                } catch (\Exception $e) {
                                    Log::warning('Error getting hasil aspek in perbandingan', [
                                        'peserta_id' => $pesertaData['peserta_id'],
                                        'pemeriksaan_id' => $pemeriksaan->id,
                                        'aspek_id' => $aspekInPemeriksaan->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                    
                                    $aspekItem['nilai'][] = [
                                        'pemeriksaan_id' => $pemeriksaan->id,
                                        'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan ?? '-',
                                        'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan ?? null,
                                        'nilai_performa' => null,
                                        'predikat' => null,
                                    ];
                                }
                            } else {
                                // Peserta tidak ada di pemeriksaan ini
                                $aspekItem['nilai'][] = [
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan ?? '-',
                                    'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan ?? null,
                                    'nilai_performa' => null,
                                    'predikat' => null,
                                ];
                            }
                        } else {
                            // Aspek tidak ada di pemeriksaan ini
                            $aspekItem['nilai'][] = [
                                'pemeriksaan_id' => $pemeriksaan->id,
                                'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan ?? '-',
                                'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan ?? null,
                                'nilai_performa' => null,
                                'predikat' => null,
                            ];
                        }
                    }

                    // Hanya tambahkan aspek jika ada nilai (setidaknya 1 pemeriksaan)
                    if (!empty($aspekItem['nilai'])) {
                        $pesertaItem['aspek'][] = $aspekItem;
                    }
                }

                // Hanya tambahkan peserta jika ada aspek dengan nilai
                if (!empty($pesertaItem['aspek'])) {
                    $data[] = $pesertaItem;
                }
            }

            // Format pemeriksaan list dengan null safety
            $pemeriksaanListFormatted = $pemeriksaanList->map(function ($p) {
                return [
                    'id' => $p->id ?? null,
                    'nama_pemeriksaan' => $p->nama_pemeriksaan ?? '-',
                    'tanggal_pemeriksaan' => $p->tanggal_pemeriksaan ?? null,
                    'cabor_kategori' => $p->caborKategori ? [
                        'id' => $p->caborKategori->id ?? null,
                        'nama' => $p->caborKategori->nama ?? '-',
                    ] : null,
                ];
            })->toArray();

            // Format aspek list dengan null safety
            $aspekListFormatted = $allAspek->map(function ($aspek) {
                return [
                    'id' => $aspek['id'] ?? null,
                    'nama' => $aspek['nama'] ?? '-',
                    'urutan' => $aspek['urutan'] ?? 0,
                ];
            })->toArray();

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'pemeriksaan_list' => $pemeriksaanListFormatted,
                'aspek_list' => $aspekListFormatted,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabor tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Get Perbandingan Cabor error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'cabor_id' => $id,
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil perbandingan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get ranking
     */
    public function getRanking(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->fresh();

            // Check permission
            if (!Gate::allows('Cabor Show')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat ranking.',
                ], 403);
            }

            $cabor = Cabor::findOrFail($id);

            // Get semua pemeriksaan khusus untuk cabor ini
            $pemeriksaanList = PemeriksaanKhusus::where('cabor_id', $id)
                ->with(['caborKategori'])
                ->orderBy('tanggal_pemeriksaan')
                ->get();

            if ($pemeriksaanList->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'pemeriksaan_list' => [],
                        'ranking_total_rata_rata' => [],
                        'ranking_per_tes' => [],
                        'ranking_perbandingan_3_tes_terakhir' => [],
                    ],
                ]);
            }

            // Get semua peserta yang pernah mengikuti pemeriksaan khusus ini
            $allPeserta = PemeriksaanKhususPeserta::with(['peserta'])
                ->whereIn('pemeriksaan_khusus_id', $pemeriksaanList->pluck('id'))
                ->get();

            // Group peserta by peserta_id dan peserta_type (unique peserta)
            $uniquePeserta = collect();
            foreach ($allPeserta as $p) {
                $key = $p->peserta_id . '_' . $p->peserta_type;
                if (!$uniquePeserta->has($key)) {
                    // Get cabor_kategori_id from pemeriksaan khusus yang sudah di-load
                    $caborKategoriIdFromPemeriksaan = null;
                    $pemeriksaan = $pemeriksaanList->firstWhere('id', $p->pemeriksaan_khusus_id);
                    if ($pemeriksaan) {
                        $caborKategoriIdFromPemeriksaan = $pemeriksaan->cabor_kategori_id;
                    }
                    
                    // Get posisi and usia
                    $posisi = '-';
                    $usia = '-';
                    
                    try {
                        if ($p->peserta_type === 'App\\Models\\Atlet' && $caborKategoriIdFromPemeriksaan) {
                            $posisi = $this->getAtletPosisi($p->peserta_id, $caborKategoriIdFromPemeriksaan);
                            if ($p->peserta && isset($p->peserta->tanggal_lahir)) {
                                $usia = $this->calculateAge($p->peserta->tanggal_lahir);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error getting posisi/usia for peserta in ranking', [
                            'peserta_id' => $p->peserta_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    
                    $uniquePeserta->put($key, [
                        'peserta_id' => $p->peserta_id,
                        'peserta_type' => $p->peserta_type,
                        'nama' => $p->peserta ? ($p->peserta->nama ?? '-') : '-',
                        'jenis_kelamin' => $p->peserta ? ($p->peserta->jenis_kelamin ?? null) : null,
                        'posisi' => $posisi,
                        'usia' => $usia,
                    ]);
                }
            }

            // Ranking Total Rata-rata (rata-rata dari semua tes)
            $rankingTotalRataRata = [];
            foreach ($uniquePeserta as $pesertaData) {
                $nilaiList = [];

                // Loop setiap pemeriksaan untuk mendapatkan nilai keseluruhan
                foreach ($pemeriksaanList as $pemeriksaan) {
                    $pesertaInPemeriksaan = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                        ->where('peserta_id', $pesertaData['peserta_id'])
                        ->where('peserta_type', $pesertaData['peserta_type'])
                        ->first();

                    if ($pesertaInPemeriksaan) {
                        $hasilKeseluruhan = PemeriksaanKhususPesertaKeseluruhan::where('pemeriksaan_khusus_peserta_id', $pesertaInPemeriksaan->id)
                            ->first();

                        if ($hasilKeseluruhan && $hasilKeseluruhan->nilai_keseluruhan !== null) {
                            $nilaiList[] = (float) $hasilKeseluruhan->nilai_keseluruhan;
                        }
                    }
                }

                // Hitung rata-rata
                if (!empty($nilaiList)) {
                    $rataRata = array_sum($nilaiList) / count($nilaiList);
                    $predikat = $this->getPredikatFromPercentage($rataRata);

                    $rankingTotalRataRata[] = [
                        'peserta_id' => $pesertaData['peserta_id'],
                        'peserta_type' => $pesertaData['peserta_type'],
                        'nama' => $pesertaData['nama'],
                        'jenis_kelamin' => $pesertaData['jenis_kelamin'],
                        'posisi' => $pesertaData['posisi'] ?? '-',
                        'usia' => $pesertaData['usia'] ?? '-',
                        'nilai' => round($rataRata, 2),
                        'predikat' => $predikat,
                        'predikat_label' => \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($predikat),
                    ];
                }
            }

            // Sort by nilai descending
            usort($rankingTotalRataRata, fn($a, $b) => $b['nilai'] <=> $a['nilai']);

            // Ranking Per Tes
            $rankingPerTes = [];
            foreach ($pemeriksaanList as $pemeriksaan) {
                $rankingPerTesItem = [];

                // Get semua peserta di pemeriksaan ini
                $pesertaInPemeriksaan = PemeriksaanKhususPeserta::with(['peserta'])
                    ->where('pemeriksaan_khusus_id', $pemeriksaan->id)
                    ->get();

                foreach ($pesertaInPemeriksaan as $peserta) {
                    $hasilKeseluruhan = PemeriksaanKhususPesertaKeseluruhan::where('pemeriksaan_khusus_peserta_id', $peserta->id)
                        ->first();

                    if ($hasilKeseluruhan && $hasilKeseluruhan->nilai_keseluruhan !== null) {
                        $nilai = (float) $hasilKeseluruhan->nilai_keseluruhan;
                        $predikat = $hasilKeseluruhan->predikat;

                        // Get cabor_kategori_id from pemeriksaan khusus
                        $caborKategoriIdFromPemeriksaan = $pemeriksaan->cabor_kategori_id ?? null;
                        
                        // Get posisi and usia
                        $posisi = '-';
                        $usia = '-';
                        
                        try {
                            if ($peserta->peserta_type === 'App\\Models\\Atlet' && $caborKategoriIdFromPemeriksaan) {
                                $posisi = $this->getAtletPosisi($peserta->peserta_id, $caborKategoriIdFromPemeriksaan);
                                if ($peserta->peserta && isset($peserta->peserta->tanggal_lahir)) {
                                    $usia = $this->calculateAge($peserta->peserta->tanggal_lahir);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error getting posisi/usia for peserta in ranking per tes', [
                                'peserta_id' => $peserta->peserta_id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $rankingPerTesItem[] = [
                            'peserta_id' => $peserta->peserta_id,
                            'peserta_type' => $peserta->peserta_type,
                            'nama' => $peserta->peserta ? ($peserta->peserta->nama ?? '-') : '-',
                            'jenis_kelamin' => $peserta->peserta ? ($peserta->peserta->jenis_kelamin ?? null) : null,
                            'posisi' => $posisi,
                            'usia' => $usia,
                            'nilai' => $nilai,
                            'predikat' => $predikat,
                            'predikat_label' => $predikat ? \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($predikat) : null,
                        ];
                    }
                }

                // Sort by nilai descending
                usort($rankingPerTesItem, fn($a, $b) => $b['nilai'] <=> $a['nilai']);

                $rankingPerTes[] = [
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                    'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                    'ranking' => $rankingPerTesItem,
                ];
            }

            // Ranking Perbandingan 3 Tes Terakhir
            $rankingPerbandingan3TesTerakhir = [];
            $last3Pemeriksaan = $pemeriksaanList->sortByDesc('tanggal_pemeriksaan')->take(3)->values();
            
            if ($last3Pemeriksaan->count() >= 2) {
                foreach ($uniquePeserta as $pesertaData) {
                    $nilaiList = [];

                    // Loop 3 tes terakhir untuk mendapatkan nilai keseluruhan
                    foreach ($last3Pemeriksaan as $pemeriksaan) {
                        $pesertaInPemeriksaan = PemeriksaanKhususPeserta::where('pemeriksaan_khusus_id', $pemeriksaan->id)
                            ->where('peserta_id', $pesertaData['peserta_id'])
                            ->where('peserta_type', $pesertaData['peserta_type'])
                            ->first();

                        if ($pesertaInPemeriksaan) {
                            $hasilKeseluruhan = PemeriksaanKhususPesertaKeseluruhan::where('pemeriksaan_khusus_peserta_id', $pesertaInPemeriksaan->id)
                                ->first();

                            if ($hasilKeseluruhan && $hasilKeseluruhan->nilai_keseluruhan !== null) {
                                $nilaiList[] = [
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'pemeriksaan_nama' => $pemeriksaan->nama_pemeriksaan,
                                    'tanggal_pemeriksaan' => $pemeriksaan->tanggal_pemeriksaan,
                                    'nilai' => (float) $hasilKeseluruhan->nilai_keseluruhan,
                                ];
                            }
                        }
                    }

                    // Hitung rata-rata dari 3 tes terakhir
                    if (!empty($nilaiList)) {
                        $rataRata = array_sum(array_column($nilaiList, 'nilai')) / count($nilaiList);
                        $predikat = $this->getPredikatFromPercentage($rataRata);

                        $rankingPerbandingan3TesTerakhir[] = [
                            'peserta_id' => $pesertaData['peserta_id'],
                            'peserta_type' => $pesertaData['peserta_type'],
                            'nama' => $pesertaData['nama'],
                            'jenis_kelamin' => $pesertaData['jenis_kelamin'],
                            'posisi' => $pesertaData['posisi'] ?? '-',
                            'usia' => $pesertaData['usia'] ?? '-',
                            'nilai_rata_rata' => round($rataRata, 2),
                            'predikat' => $predikat,
                            'predikat_label' => \App\Services\PemeriksaanKhususCalculationService::getPredikatLabel($predikat),
                            'nilai_per_tes' => $nilaiList,
                        ];
                    }
                }

                // Sort by nilai_rata_rata descending
                usort($rankingPerbandingan3TesTerakhir, fn($a, $b) => $b['nilai_rata_rata'] <=> $a['nilai_rata_rata']);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'pemeriksaan_list' => $pemeriksaanList->map(function ($p) {
                        return [
                            'id' => $p->id,
                            'nama_pemeriksaan' => $p->nama_pemeriksaan,
                            'tanggal_pemeriksaan' => $p->tanggal_pemeriksaan,
                            'cabor_kategori' => $p->caborKategori ? [
                                'id' => $p->caborKategori->id,
                                'nama' => $p->caborKategori->nama,
                            ] : null,
                        ];
                    })->toArray(),
                    'ranking_total_rata_rata' => $rankingTotalRataRata,
                    'ranking_per_tes' => $rankingPerTes,
                    'ranking_perbandingan_3_tes_terakhir' => $rankingPerbandingan3TesTerakhir,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Ranking Cabor error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
                'cabor_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil ranking.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Helper function untuk menghitung umur
     */
    private function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $tanggalLahir = new Carbon($tanggalLahir);
            $today = Carbon::today();
            return (int) $tanggalLahir->diffInYears($today);
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Helper function untuk mendapatkan posisi atlet
     */
    private function getAtletPosisi($atletId, $caborKategoriId)
    {
        if (!$caborKategoriId || !$atletId) {
            return '-';
        }

        try {
            $posisi = DB::table('cabor_kategori_atlet')
                ->where('cabor_kategori_atlet.atlet_id', $atletId)
                ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                ->whereNull('cabor_kategori_atlet.deleted_at')
                ->value('cabor_kategori_atlet.posisi_atlet');

            return $posisi ?? '-';
        } catch (\Exception $e) {
            Log::warning('Error in getAtletPosisi', [
                'atlet_id' => $atletId,
                'cabor_kategori_id' => $caborKategoriId,
                'error' => $e->getMessage(),
            ]);

            return '-';
        }
    }

    /**
     * Helper function untuk mendapatkan predikat dari persentase
     */
    private function getPredikatFromPercentage($percentage)
    {
        if ($percentage === null) {
            return null;
        }

        if ($percentage >= 0 && $percentage < 20) {
            return 'sangat_kurang';
        } elseif ($percentage >= 20 && $percentage < 40) {
            return 'kurang';
        } elseif ($percentage >= 40 && $percentage < 60) {
            return 'sedang';
        } elseif ($percentage >= 60 && $percentage < 100) {
            return 'mendekati_target';
        } else { // >= 100
            return 'target';
        }
    }
}

