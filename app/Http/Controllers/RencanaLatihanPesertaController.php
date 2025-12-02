<?php

namespace App\Http\Controllers;

use App\Models\Atlet;
use App\Models\MstPosisiAtlet;
use App\Models\Pelatih;
use App\Models\RencanaLatihan;
use App\Models\TenagaPendukung;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RencanaLatihanPesertaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // Hanya izinkan pengguna dengan permission khusus melakukan set kehadiran
            new Middleware('can:Rencana Latihan Set Kehadiran', only: ['setKehadiranPage', 'updateKehadiran', 'setKehadiran']),
        ];
    }
    public function index(Request $request, $rencana_id, $jenis_peserta)
    {
        $rencana = RencanaLatihan::find($rencana_id);
        if (! $rencana) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total'        => 0,
                    'current_page' => 1,
                    'per_page'     => (int) $request->input('per_page', 10),
                ],
            ]);
        }

        $perPage = (int) $request->input('per_page', 10);
        $search  = $request->input('search', '');

        if ($jenis_peserta === 'atlet') {
            $caborKategoriId = $rencana->programLatihan->cabor_kategori_id;
            $query           = Atlet::query()
                ->join('rencana_latihan_atlet', function ($join) use ($rencana_id) {
                    $join->on('atlets.id', '=', 'rencana_latihan_atlet.atlet_id')
                        ->where('rencana_latihan_atlet.rencana_latihan_id', $rencana_id);
                })
                ->leftJoin('cabor_kategori_atlet', function ($join) use ($caborKategoriId) {
                    $join->on('atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
                        ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_atlet.deleted_at');
                })
                ->select(
                    'atlets.id',
                    'atlets.nama',
                    'atlets.foto',
                    'atlets.jenis_kelamin',
                    'atlets.tempat_lahir',
                    'atlets.tanggal_lahir',
                    'atlets.no_hp',
                    'cabor_kategori_atlet.is_active as kategori_is_active',
                    'cabor_kategori_atlet.posisi_atlet_id',
                    'rencana_latihan_atlet.kehadiran as kehadiran',
                    'rencana_latihan_atlet.keterangan as keterangan',
                    'rencana_latihan_atlet.foto as foto_kehadiran'
                );
        } elseif ($jenis_peserta === 'pelatih') {
            $caborKategoriId = $rencana->programLatihan->cabor_kategori_id;
            $query           = Pelatih::query()
                ->join('rencana_latihan_pelatih', function ($join) use ($rencana_id) {
                    $join->on('pelatihs.id', '=', 'rencana_latihan_pelatih.pelatih_id')
                        ->where('rencana_latihan_pelatih.rencana_latihan_id', $rencana_id);
                })
                ->leftJoin('cabor_kategori_pelatih', function ($join) use ($caborKategoriId) {
                    $join->on('pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
                        ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_pelatih.deleted_at');
                })
                ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
                ->select(
                    'pelatihs.id',
                    'pelatihs.nama',
                    'pelatihs.foto',
                    'pelatihs.jenis_kelamin',
                    'pelatihs.tempat_lahir',
                    'pelatihs.tanggal_lahir',
                    'pelatihs.no_hp',
                    'cabor_kategori_pelatih.is_active as kategori_is_active',
                    'mst_jenis_pelatih.nama as jenis_pelatih_nama',
                    'rencana_latihan_pelatih.kehadiran as kehadiran',
                    'rencana_latihan_pelatih.keterangan as keterangan',
                    'rencana_latihan_pelatih.foto as foto_kehadiran'
                );
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            $caborKategoriId = $rencana->programLatihan->cabor_kategori_id;
            $query           = TenagaPendukung::query()
                ->join('rencana_latihan_tenaga_pendukung', function ($join) use ($rencana_id) {
                    $join->on('tenaga_pendukungs.id', '=', 'rencana_latihan_tenaga_pendukung.tenaga_pendukung_id')
                        ->where('rencana_latihan_tenaga_pendukung.rencana_latihan_id', $rencana_id);
                })
                ->leftJoin('cabor_kategori_tenaga_pendukung', function ($join) use ($caborKategoriId) {
                    $join->on('tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
                        ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                })
                ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
                ->select(
                    'tenaga_pendukungs.id',
                    'tenaga_pendukungs.nama',
                    'tenaga_pendukungs.foto',
                    'tenaga_pendukungs.jenis_kelamin',
                    'tenaga_pendukungs.tempat_lahir',
                    'tenaga_pendukungs.tanggal_lahir',
                    'tenaga_pendukungs.no_hp',
                    'cabor_kategori_tenaga_pendukung.is_active as kategori_is_active',
                    'mst_jenis_tenaga_pendukung.nama as jenis_tenaga_pendukung_nama',
                    'rencana_latihan_tenaga_pendukung.kehadiran as kehadiran',
                    'rencana_latihan_tenaga_pendukung.keterangan as keterangan',
                    'rencana_latihan_tenaga_pendukung.foto as foto_kehadiran'
                );
        } else {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total'        => 0,
                    'current_page' => 1,
                    'per_page'     => $perPage,
                ],
            ]);
        }

        if ($search) {
            $query->where($jenis_peserta === 'atlet' ? 'atlets.nama' : ($jenis_peserta === 'pelatih' ? 'pelatihs.nama' : 'tenaga_pendukungs.nama'), 'like', "%$search%");
        }

        $result = $query->paginate($perPage)->appends($request->all());

        // Mapping posisi atlet dan foto kehadiran untuk response
        $data = $result->items();
        foreach ($data as &$row) {
            // Mapping posisi atlet
            if ($jenis_peserta === 'atlet') {
                $row->posisi_atlet_nama = '-';
                if (! empty($row->posisi_atlet_id)) {
                    $posisi                 = MstPosisiAtlet::find($row->posisi_atlet_id);
                    $row->posisi_atlet_nama = $posisi ? $posisi->nama : '-';
                }
            }

            // Mapping foto kehadiran dengan URL lengkap
            if (! empty($row->foto_kehadiran)) {
                $row->foto_kehadiran = url('storage/' . $row->foto_kehadiran);
            } else {
                $row->foto_kehadiran = null;
            }
        }
        $result->setCollection(collect($data));

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'total'        => $result->total(),
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'search'       => $search,
                'sort'         => $request->input('sort', ''),
                'order'        => $request->input('order', 'asc'),
            ],
        ]);
    }

    /**
     * Halaman daftar peserta rencana latihan (Inertia Page)
     */
    public function indexPage($program_id, $rencana_id, $jenis_peserta)
    {
        $rencana    = RencanaLatihan::with(['programLatihan.cabor', 'programLatihan.caborKategori'])->findOrFail($rencana_id);
        $program    = $rencana->programLatihan;
        $infoHeader = [
            'program_latihan_id'  => $program->id,
            'nama_program'        => $program->nama_program,
            'cabor_nama'          => $program->cabor?->nama,
            'cabor_kategori_nama' => $program->caborKategori?->nama,
            'cabor_kategori_id'   => $program->cabor_kategori_id,
            'periode_mulai'       => $program->periode_mulai,
            'periode_selesai'     => $program->periode_selesai,
        ];
        $infoRencana = [
            'tanggal'        => $rencana->tanggal,
            'materi'         => $rencana->materi,
            'lokasi_latihan' => $rencana->lokasi_latihan,
            'target_latihan' => $rencana->targetLatihan->pluck('deskripsi')->toArray(),
        ];

        return Inertia::render('modules/rencana-latihan/index/Index', [
            'program_id'    => $program_id,
            'rencana_id'    => $rencana_id,
            'jenis_peserta' => $jenis_peserta,
            'infoHeader'    => $infoHeader,
            'infoRencana'   => $infoRencana,
        ]);
    }

    /**
     * Hapus satu peserta dari rencana latihan
     */
    public function destroy(Request $request, $rencana_id, $jenis_peserta, $peserta_id)
    {
        $rencana = RencanaLatihan::findOrFail($rencana_id);
        if ($jenis_peserta === 'atlet') {
            $rencana->atlets()->detach($peserta_id);
        } elseif ($jenis_peserta === 'pelatih') {
            $rencana->pelatihs()->detach($peserta_id);
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            $rencana->tenagaPendukung()->detach($peserta_id);
        } else {
            return response()->json(['message' => 'Jenis peserta tidak valid'], 400);
        }

        return response()->json(['message' => 'Peserta berhasil dihapus']);
    }

    /**
     * Hapus banyak peserta dari rencana latihan
     */
    public function destroySelected(Request $request, $rencana_id, $jenis_peserta)
    {
        $ids     = $request->input('ids', []);
        $rencana = RencanaLatihan::findOrFail($rencana_id);
        if ($jenis_peserta === 'atlet') {
            $rencana->atlets()->detach($ids);
        } elseif ($jenis_peserta === 'pelatih') {
            $rencana->pelatihs()->detach($ids);
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            $rencana->tenagaPendukung()->detach($ids);
        } else {
            return response()->json(['message' => 'Jenis peserta tidak valid'], 400);
        }

        return response()->json(['message' => 'Peserta berhasil dihapus']);
    }

    /**
     * Set kehadiran massal untuk peserta
     */
    public function setKehadiran(Request $request, $rencana_id, $jenis_peserta)
    {
        $request->validate([
            'ids'        => 'required|array',
            'kehadiran'  => 'required|in:Hadir,Tidak Hadir,Izin,Sakit',
            'keterangan' => 'nullable|string|max:1000',
        ]);
        $ids        = $request->input('ids');
        $kehadiran  = $request->input('kehadiran');
        $keterangan = $request->input('keterangan');
        $rencana    = RencanaLatihan::findOrFail($rencana_id);

        if ($jenis_peserta === 'atlet') {
            foreach ($ids as $id) {
                $rencana->atlets()->updateExistingPivot($id, ['kehadiran' => $kehadiran, 'keterangan' => $keterangan ?? null]);
            }
        } elseif ($jenis_peserta === 'pelatih') {
            foreach ($ids as $id) {
                $rencana->pelatihs()->updateExistingPivot($id, ['kehadiran' => $kehadiran, 'keterangan' => $keterangan ?? null]);
            }
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            foreach ($ids as $id) {
                $rencana->tenagaPendukung()->updateExistingPivot($id, ['kehadiran' => $kehadiran, 'keterangan' => $keterangan ?? null]);
            }
        } else {
            return response()->json(['message' => 'Jenis peserta tidak valid'], 400);
        }

        return response()->json(['message' => 'Kehadiran berhasil diupdate']);
    }

    /**
     * Halaman set kehadiran individu
     */
    public function setKehadiranPage($program_id, $rencana_id, $jenis_peserta, $peserta_id)
    {
        $rencana = RencanaLatihan::with(['programLatihan.cabor', 'programLatihan.caborKategori'])->findOrFail($rencana_id);
        $program = $rencana->programLatihan;

        // Get peserta data
        $peserta       = null;
        $kehadiranData = null;

        if ($jenis_peserta === 'atlet') {
            $peserta       = Atlet::findOrFail($peserta_id);
            $kehadiranData = DB::table('rencana_latihan_atlet')
                ->where('rencana_latihan_id', $rencana_id)
                ->where('atlet_id', $peserta_id)
                ->first();
        } elseif ($jenis_peserta === 'pelatih') {
            $peserta       = Pelatih::findOrFail($peserta_id);
            $kehadiranData = DB::table('rencana_latihan_pelatih')
                ->where('rencana_latihan_id', $rencana_id)
                ->where('pelatih_id', $peserta_id)
                ->first();
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            $peserta       = TenagaPendukung::findOrFail($peserta_id);
            $kehadiranData = DB::table('rencana_latihan_tenaga_pendukung')
                ->where('rencana_latihan_id', $rencana_id)
                ->where('tenaga_pendukung_id', $peserta_id)
                ->first();
        }

        $infoHeader = [
            'program_latihan_id'  => $program->id,
            'nama_program'        => $program->nama_program,
            'cabor_nama'          => $program->cabor?->nama,
            'cabor_kategori_nama' => $program->caborKategori?->nama,
        ];

        $infoRencana = [
            'tanggal'        => $rencana->tanggal,
            'materi'         => $rencana->materi,
            'lokasi_latihan' => $rencana->lokasi_latihan,
        ];

        return Inertia::render('modules/rencana-latihan/index/SetKehadiran', [
            'program_id'     => $program_id,
            'rencana_id'     => $rencana_id,
            'jenis_peserta'  => $jenis_peserta,
            'peserta_id'     => $peserta_id,
            'peserta'        => $peserta,
            'kehadiran'      => $kehadiranData?->kehadiran,
            'keterangan'     => $kehadiranData?->keterangan,
            'foto_kehadiran' => $kehadiranData?->foto ? url('storage/' . $kehadiranData->foto) : null,
            'infoHeader'     => $infoHeader,
            'infoRencana'    => $infoRencana,
        ]);
    }

    /**
     * Update kehadiran individu dengan foto
     */
    public function updateKehadiran(Request $request, $rencana_id, $jenis_peserta, $peserta_id)
    {
        // Get existing foto untuk validasi
        $existingFoto = null;
        if ($jenis_peserta === 'atlet') {
            $existing = DB::table('rencana_latihan_atlet')
                ->where('rencana_latihan_id', $rencana_id)
                ->where('atlet_id', $peserta_id)
                ->first();
            $existingFoto = $existing?->foto;
        } elseif ($jenis_peserta === 'pelatih') {
            $existing = DB::table('rencana_latihan_pelatih')
                ->where('rencana_latihan_id', $rencana_id)
                ->where('pelatih_id', $peserta_id)
                ->first();
            $existingFoto = $existing?->foto;
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            $existing = DB::table('rencana_latihan_tenaga_pendukung')
                ->where('rencana_latihan_id', $rencana_id)
                ->where('tenaga_pendukung_id', $peserta_id)
                ->first();
            $existingFoto = $existing?->foto;
        }

        $rules = [
            'kehadiran'  => 'required|in:Hadir,Tidak Hadir,Izin,Sakit',
            'keterangan' => 'nullable|string|max:1000',
            'foto'       => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ];

        // Jika kehadiran adalah "Hadir" dan belum ada foto, foto wajib diisi
        if ($request->kehadiran === 'Hadir' && !$existingFoto) {
            $rules['foto'] = 'required|image|mimes:jpeg,png,jpg|max:5120';
        }

        $request->validate($rules, [
            'foto.required'  => 'Foto kehadiran wajib diupload jika status Hadir.',
            'foto.image'     => 'File harus berupa gambar.',
            'foto.mimes'     => 'Format foto harus JPG, JPEG, atau PNG.',
            'foto.max'       => 'Ukuran foto maksimal 5MB.',
        ]);

        $rencana    = RencanaLatihan::findOrFail($rencana_id);
        $kehadiran  = $request->input('kehadiran');
        $keterangan = $request->input('keterangan');
        $fotoPath   = null;

        // Handle foto upload jika hadir
        if ($request->hasFile('foto') && $request->kehadiran === 'Hadir') {
            $file     = $request->file('foto');
            $path     = 'kehadiran/' . $rencana_id . '/' . $jenis_peserta . '/' . $peserta_id;
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs($path, $fileName, 'public');
            $fotoPath = $path . '/' . $fileName;
        }

        // Update pivot table
        $updateData = [
            'kehadiran'  => $kehadiran,
            'keterangan' => $keterangan ?? null,
        ];

        if ($fotoPath) {
            $updateData['foto'] = $fotoPath;
            // Hapus foto lama jika ada
            if ($existingFoto && Storage::disk('public')->exists($existingFoto)) {
                Storage::disk('public')->delete($existingFoto);
            }
        } elseif ($kehadiran === 'Hadir' && $existingFoto) {
            // Jika status Hadir dan tidak ada foto baru, tetap pakai foto lama
            $updateData['foto'] = $existingFoto;
        } elseif ($kehadiran !== 'Hadir' && $existingFoto) {
            // Jika status bukan Hadir, hapus foto
            $updateData['foto'] = null;
            if (Storage::disk('public')->exists($existingFoto)) {
                Storage::disk('public')->delete($existingFoto);
            }
        }

        if ($jenis_peserta === 'atlet') {
            $rencana->atlets()->updateExistingPivot($peserta_id, $updateData);
        } elseif ($jenis_peserta === 'pelatih') {
            $rencana->pelatihs()->updateExistingPivot($peserta_id, $updateData);
        } elseif ($jenis_peserta === 'tenaga-pendukung') {
            $rencana->tenagaPendukung()->updateExistingPivot($peserta_id, $updateData);
        }

        return response()->json([
            'message'  => 'Kehadiran berhasil diupdate',
            'foto_url' => $fotoPath ? url('storage/' . $fotoPath) : null,
        ]);
    }
}
