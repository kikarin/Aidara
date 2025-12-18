<?php

namespace App\Http\Controllers;

use App\Models\ProgramLatihan;
use App\Models\RekapAbsenProgramLatihan;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RekapAbsenProgramLatihanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    public function __construct()
    {
        $this->initialize();
        $this->route                          = 'program-latihan';
        $this->commonData['kode_first_menu']  = 'PROGRAM-LATIHAN';
        $this->commonData['kode_second_menu'] = 'REKAP-ABSEN';
    }

    public static function middleware(): array
    {
        return [
            new Middleware("can:Program Latihan Rekap Absen", only: ['index', 'show', 'store', 'update', 'deleteMedia']),
        ];
    }

    public function index($program_id)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        
        // Generate array tanggal dari periode_mulai sampai periode_selesai
        $tanggalList = $this->generateDateRange($programLatihan->periode_mulai, $programLatihan->periode_selesai);
        
        // Ambil data rekap absen yang sudah ada
        $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $program_id)
            ->with(['media', 'createdByUser.pelatih.caborKategoriPelatih', 'createdByUser.pelatih.kategoriPesertas'])
            ->get()
            ->keyBy('tanggal');
        
        // Merge dengan tanggal list
        $calendarData = collect($tanggalList)->map(function ($tanggal) use ($rekapAbsen) {
            $rekap = $rekapAbsen->get($tanggal);
            return [
                'tanggal' => $tanggal,
                'rekap_id' => $rekap?->id,
                'jenis_latihan' => $rekap?->jenis_latihan,
                'keterangan' => $rekap?->keterangan,
                'foto_absen' => $rekap ? $rekap->getMedia('foto_absen')->map(function ($media) use ($rekap) {
                    // Gunakan getPath() untuk mendapatkan full path, lalu extract relative path
                    $fullPath = $media->getPath();
                    $mediaRoot = storage_path('app/media');
                    $relativePath = str_replace($mediaRoot . '/', '', $fullPath);
                    // Gunakan Storage::disk('media')->url() untuk mendapatkan URL dengan APP_URL
                    $url = Storage::disk('media')->url($relativePath);
                    return [
                        'id' => $media->id,
                        'url' => $url,
                        'name' => $media->name,
                    ];
                })->toArray() : [],
                'file_nilai' => $rekap ? $rekap->getMedia('file_nilai')->map(function ($media) use ($rekap) {
                    // Gunakan getPath() untuk mendapatkan full path, lalu extract relative path
                    $fullPath = $media->getPath();
                    $mediaRoot = storage_path('app/media');
                    $relativePath = str_replace($mediaRoot . '/', '', $fullPath);
                    // Gunakan Storage::disk('media')->url() untuk mendapatkan URL dengan APP_URL
                    $url = Storage::disk('media')->url($relativePath);
                    return [
                        'id' => $media->id,
                        'url' => $url,
                        'name' => $media->name,
                    ];
                })->toArray() : [],
            ];
        });

        // Ambil data pelatih dari rekap absen pertama (biasanya sama untuk semua)
        $pelatihData = null;
        if ($rekapAbsen->isNotEmpty()) {
            $firstRekap = $rekapAbsen->first();
            $user = $firstRekap->createdByUser;
            if ($user && $user->pelatih) {
                $pelatih = $user->pelatih;
                // Cari cabor kategori pelatih yang sesuai dengan program latihan
                $caborKategoriPelatih = $pelatih->caborKategoriPelatih
                    ->where('cabor_id', $programLatihan->cabor_id)
                    ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                    ->first();
                
                $pelatihData = [
                    'nama' => $pelatih->nama,
                    'kategori_peserta' => $pelatih->kategoriPesertas->pluck('nama')->join(', ') ?: '-',
                    'cabor' => $programLatihan->cabor?->nama ?? '-',
                    'jenis_pelatih' => $caborKategoriPelatih?->jenis_pelatih ?? '-',
                ];
            }
        }

        $data = $this->commonData + [
            'program_latihan' => [
                'id' => $programLatihan->id,
                'nama_program' => $programLatihan->nama_program,
                'cabor_nama' => $programLatihan->cabor?->nama,
                'cabor_kategori_nama' => $programLatihan->caborKategori?->nama,
                'periode_mulai' => $programLatihan->periode_mulai,
                'periode_selesai' => $programLatihan->periode_selesai,
                'periode_hitung' => $programLatihan->periode_hitung,
            ],
            'calendar_data' => $calendarData->values()->toArray(),
            'pelatih_data' => $pelatihData,
        ];

        return Inertia::render('modules/program-latihan/RekapAbsen', $data);
    }

    public function show($program_id)
    {
        $programLatihan = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($program_id);
        
        // Ambil semua rekap absen yang sudah ada (hanya yang punya data)
        $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $program_id)
            ->with(['media', 'createdByUser.pelatih.caborKategoriPelatih', 'createdByUser.pelatih.kategoriPesertas'])
            ->orderBy('tanggal', 'asc')
            ->get();
        
        // Format data untuk view
        $rekapData = $rekapAbsen->map(function ($rekap) {
            return [
                'id' => $rekap->id,
                'tanggal' => $rekap->tanggal,
                'jenis_latihan' => $rekap->jenis_latihan,
                'keterangan' => $rekap->keterangan,
                'foto_absen' => $rekap->getMedia('foto_absen')->map(function ($media) {
                    $fullPath = $media->getPath();
                    $mediaRoot = storage_path('app/media');
                    $relativePath = str_replace($mediaRoot . '/', '', $fullPath);
                    $url = Storage::disk('media')->url($relativePath);
                    return [
                        'id' => $media->id,
                        'url' => $url,
                        'name' => $media->name,
                    ];
                })->toArray(),
                'file_nilai' => $rekap->getMedia('file_nilai')->map(function ($media) {
                    $fullPath = $media->getPath();
                    $mediaRoot = storage_path('app/media');
                    $relativePath = str_replace($mediaRoot . '/', '', $fullPath);
                    $url = Storage::disk('media')->url($relativePath);
                    return [
                        'id' => $media->id,
                        'url' => $url,
                        'name' => $media->name,
                    ];
                })->toArray(),
            ];
        });
        
        // Ambil data pelatih
        $pelatihData = null;
        if ($rekapAbsen->isNotEmpty()) {
            $firstRekap = $rekapAbsen->first();
            $user = $firstRekap->createdByUser;
            if ($user && $user->pelatih) {
                $pelatih = $user->pelatih;
                $caborKategoriPelatih = $pelatih->caborKategoriPelatih
                    ->where('cabor_id', $programLatihan->cabor_id)
                    ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                    ->first();
                
                $pelatihData = [
                    'nama' => $pelatih->nama,
                    'kategori_peserta' => $pelatih->kategoriPesertas->pluck('nama')->join(', ') ?: '-',
                    'cabor' => $programLatihan->cabor?->nama ?? '-',
                ];
            }
        }
        
        // Hitung statistik
        $stats = [
            'total' => $rekapData->count(),
            'fisik' => $rekapData->where('jenis_latihan', 'latihan_fisik')->count(),
            'strategi' => $rekapData->where('jenis_latihan', 'latihan_strategi')->count(),
            'teknik' => $rekapData->where('jenis_latihan', 'latihan_teknik')->count(),
            'mental' => $rekapData->where('jenis_latihan', 'latihan_mental')->count(),
            'pemulihan' => $rekapData->where('jenis_latihan', 'latihan_pemulihan')->count(),
        ];
        
        $data = $this->commonData + [
            'program_latihan' => [
                'id' => $programLatihan->id,
                'nama_program' => $programLatihan->nama_program,
                'cabor_nama' => $programLatihan->cabor?->nama,
                'cabor_kategori_nama' => $programLatihan->caborKategori?->nama,
                'periode_mulai' => $programLatihan->periode_mulai,
                'periode_selesai' => $programLatihan->periode_selesai,
                'periode_hitung' => $programLatihan->periode_hitung,
            ],
            'rekap_data' => $rekapData->values()->toArray(),
            'pelatih_data' => $pelatihData,
            'stats' => $stats,
        ];
        
        return Inertia::render('modules/program-latihan/RekapAbsenDetail', $data);
    }

    public function store(Request $request, $program_id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis_latihan' => 'required|in:latihan_fisik,latihan_strategi,latihan_teknik,latihan_mental,latihan_pemulihan',
            'keterangan' => 'nullable|string',
            'foto_absen.*' => 'nullable|image|mimes:jpeg,png,gif|max:5120',
            'file_nilai.*' => 'nullable|file|mimes:pdf,xls,xlsx|max:10240',
        ]);

        $programLatihan = ProgramLatihan::findOrFail($program_id);
        
        // Cek apakah tanggal dalam range periode
        if ($request->tanggal < $programLatihan->periode_mulai || $request->tanggal > $programLatihan->periode_selesai) {
            return back()->withErrors(['tanggal' => 'Tanggal harus dalam periode program latihan']);
        }

        // Validasi: Hanya bisa input untuk tanggal hari ini
        $today = now()->format('Y-m-d');
        if ($request->tanggal !== $today) {
            return back()->withErrors(['tanggal' => 'Hanya dapat input rekap absen untuk tanggal hari ini']);
        }

        // Cari atau buat rekap absen
        $rekapAbsen = RekapAbsenProgramLatihan::firstOrCreate(
            [
                'program_latihan_id' => $program_id,
                'tanggal' => $request->tanggal,
            ],
            [
                'jenis_latihan' => $request->jenis_latihan,
                'keterangan' => $request->keterangan,
                'created_by' => Auth::id(),
            ]
        );

        // Update jika sudah ada
        if ($rekapAbsen->wasRecentlyCreated === false) {
            $rekapAbsen->update([
                'jenis_latihan' => $request->jenis_latihan,
                'keterangan' => $request->keterangan,
                'updated_by' => Auth::id(),
            ]);
        }

        // Upload foto absen (multiple)
        if ($request->hasFile('foto_absen')) {
            foreach ($request->file('foto_absen') as $foto) {
                $rekapAbsen->addMedia($foto)
                    ->usingName('Foto Absen ' . $request->tanggal)
                    ->toMediaCollection('foto_absen');
            }
        }

        // Upload file nilai (multiple)
        if ($request->hasFile('file_nilai')) {
            foreach ($request->file('file_nilai') as $file) {
                $rekapAbsen->addMedia($file)
                    ->usingName('File Nilai ' . $request->tanggal)
                    ->toMediaCollection('file_nilai');
            }
        }

        return back()->with('success', 'Rekap absen berhasil disimpan!');
    }

    public function update(Request $request, $program_id, $rekap_id)
    {
        $request->validate([
            'jenis_latihan' => 'required|in:latihan_fisik,latihan_strategi,latihan_teknik,latihan_mental,latihan_pemulihan',
            'keterangan' => 'nullable|string',
            'foto_absen.*' => 'nullable|image|mimes:jpeg,png,gif|max:5120',
            'file_nilai.*' => 'nullable|file|mimes:pdf,xls,xlsx|max:10240',
            'deleted_media_ids' => 'nullable|array',
            'deleted_media_ids.*' => 'nullable|integer',
        ]);

        $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $program_id)
            ->findOrFail($rekap_id);

        // Validasi: Hanya bisa update untuk tanggal hari ini
        $today = now()->format('Y-m-d');
        if ($rekapAbsen->tanggal !== $today) {
            return back()->withErrors(['tanggal' => 'Hanya dapat mengupdate rekap absen untuk tanggal hari ini']);
        }

        // Delete media yang diminta SEBELUM update (seperti is_delete_foto di atlet)
        // FormData mengirim array sebagai deleted_media_ids[]
        $deletedMediaIds = $request->input('deleted_media_ids', []);
        if (!empty($deletedMediaIds) && is_array($deletedMediaIds)) {
            // Hapus media dari semua collection
            foreach ($deletedMediaIds as $mediaId) {
                if ($mediaId) {
                    // Cari di foto_absen collection
                    $media = $rekapAbsen->getMedia('foto_absen')->find($mediaId);
                    if ($media) {
                        $media->delete();
                        continue;
                    }
                    
                    // Cari di file_nilai collection
                    $media = $rekapAbsen->getMedia('file_nilai')->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
            }
            // Refresh model untuk memastikan media terhapus dari cache
            $rekapAbsen->refresh();
        }

        $rekapAbsen->update([
            'jenis_latihan' => $request->jenis_latihan,
            'keterangan' => $request->keterangan,
            'updated_by' => Auth::id(),
        ]);

        // Upload foto absen (multiple)
        if ($request->hasFile('foto_absen')) {
            foreach ($request->file('foto_absen') as $foto) {
                $rekapAbsen->addMedia($foto)
                    ->usingName('Foto Absen ' . $rekapAbsen->tanggal)
                    ->toMediaCollection('foto_absen');
            }
        }

        // Upload file nilai (multiple)
        if ($request->hasFile('file_nilai')) {
            foreach ($request->file('file_nilai') as $file) {
                $rekapAbsen->addMedia($file)
                    ->usingName('File Nilai ' . $rekapAbsen->tanggal)
                    ->toMediaCollection('file_nilai');
            }
        }

        return back()->with('success', 'Rekap absen berhasil diperbarui!');
    }

    public function deleteMedia($program_id, $rekap_id, $media_id)
    {
        $rekapAbsen = RekapAbsenProgramLatihan::where('program_latihan_id', $program_id)
            ->findOrFail($rekap_id);
        
        $media = $rekapAbsen->getMedia()->find($media_id);
        if ($media) {
            $media->delete();
            return response()->json(['message' => 'File berhasil dihapus!', 'success' => true]);
        }

        return response()->json(['message' => 'File tidak ditemukan', 'success' => false], 404);
    }

    private function generateDateRange($startDate, $endDate)
    {
        $dates = [];
        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        return $dates;
    }
}

