<?php

namespace App\Repositories;

use App\Models\SeleksiPendaftar;
use App\Support\SeleksiPpopm;

class SeleksiPendaftarRepository
{
    public function paginate(array $filters = []): array
    {
        $query = SeleksiPendaftar::query()
            ->with(['caborSyarat', 'periode'])
            ->when($filters['periode_id'] ?? null, fn ($q, $id) => $q->where('periode_id', $id))
            ->when($filters['cabor_syarat_id'] ?? null, fn ($q, $id) => $q->where('cabor_syarat_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('nama', 'like', '%'.$search.'%')
                        ->orWhere('nomor_tes', 'like', '%'.$search.'%')
                        ->orWhere('nik', 'like', '%'.$search.'%')
                        ->orWhere('sekolah', 'like', '%'.$search.'%');
                });
            });

        $sort  = $filters['sort'] ?? 'id';
        $order = $filters['order'] ?? 'desc';
        $valid = ['id', 'nama', 'status', 'created_at', 'nilai_akhir', 'nomor_tes', 'tanggal_lahir'];
        $query->orderBy(in_array($sort, $valid, true) ? $sort : 'id', $order === 'asc' ? 'asc' : 'desc');

        $perPage = (int) ($filters['per_page'] ?? 10);
        $page    = (int) ($filters['page'] ?? 1);

        if ($perPage === -1) {
            $items = $query->get()->map(fn (SeleksiPendaftar $item) => $this->transform($item));

            return [
                'data' => $items,
                'meta' => [
                    'total'        => $items->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => $filters['search'] ?? '',
                    'sort'         => $sort,
                    'order'        => $order,
                ],
            ];
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn (SeleksiPendaftar $item) => $this->transform($item)),
            'meta' => [
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'search'       => $filters['search'] ?? '',
                'sort'         => $sort,
                'order'        => $order,
            ],
        ];
    }

    public function transform(SeleksiPendaftar $item): array
    {
        $syarat = $item->caborSyarat;

        return [
            'id'                   => $item->id,
            'nomor_tes'            => $item->nomor_tes,
            'nama'                 => $item->nama,
            'jenis_kelamin'        => $item->jenis_kelamin,
            'jenis_kelamin_label'  => $item->jenis_kelamin === 'L' ? 'Putra' : 'Putri',
            'tanggal_lahir'        => $item->tanggal_lahir?->format('Y-m-d'),
            'tahun_lahir'          => $item->tahunLahir(),
            'sekolah'              => $item->sekolah,
            'tinggi_badan'         => $item->tinggi_badan,
            'posisi'               => $item->posisi,
            'nomor_kelas'          => $item->nomor_kelas,
            'asal_kabupaten_bogor' => $item->asal_kabupaten_bogor,
            'cabor'                => $syarat?->nama_cabor,
            'cabor_syarat_id'      => $item->cabor_syarat_id,
            'kelompok_map'         => $syarat?->kelompok_map,
            'map_label'            => $syarat?->mapLabel(),
            'status'               => $item->status,
            'status_label'         => $item->statusLabel(),
            'nilai_akhir'          => $item->nilai_akhir,
            'ranking'              => $item->ranking,
            'atlet_id'             => $item->atlet_id,
            'created_at'           => $item->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function transformDetail(SeleksiPendaftar $item): array
    {
        $item->load(['caborSyarat.cabor', 'periode', 'berkas', 'atlet']);

        $base = $this->transform($item);

        return $base + [
            'nik'                      => $item->nik,
            'nisn'                     => $item->nisn,
            'tempat_lahir'             => $item->tempat_lahir,
            'alamat'                   => $item->alamat,
            'no_hp'                    => $item->no_hp,
            'email'                    => $item->email,
            'kelas_sekolah'            => $item->kelas_sekolah,
            'berat_badan'              => $item->berat_badan,
            'vertical_jump'            => $item->vertical_jump,
            'bisa_dua_posisi'          => $item->bisa_dua_posisi,
            'bisa_berenang'            => $item->bisa_berenang,
            'kuasai_poomsae'           => $item->kuasai_poomsae,
            'bersedia_pindah_domisili' => $item->bersedia_pindah_domisili,
            'setuju_perjanjian'        => $item->setuju_perjanjian,
            'alasan_tolak'             => $item->alasan_tolak,
            'skor_kecabangan_mentah'   => $item->skor_kecabangan_mentah,
            'skor_kecabangan'          => $item->skor_kecabangan,
            'skor_fisik'               => $item->skor_fisik,
            'skor_akademik'            => $item->skor_akademik,
            'skor_psikologi'           => $item->skor_psikologi,
            'psikologi_rekomendasi'    => $item->psikologi_rekomendasi,
            'kesehatan_layak'          => $item->kesehatan_layak,
            'antropometri_layak'       => $item->antropometri_layak,
            'periode'                  => $item->periode?->nama,
            'syarat'                   => $item->caborSyarat,
            'berkas'                   => $item->berkas->map(fn ($berkas) => [
                'id'        => $berkas->id,
                'jenis'     => $berkas->jenis,
                'label'     => $berkas->label(),
                'url'       => $berkas->url(),
                'file_nama' => $berkas->file_nama,
            ]),
            'atlet'                    => $item->atlet ? [
                'id'   => $item->atlet->id,
                'nama' => $item->atlet->nama,
            ] : null,
            'status_labels'            => SeleksiPpopm::STATUS_LABELS,
        ];
    }

    public function nextNomorTes(int $periodeId, int $tahun): string
    {
        $last = SeleksiPendaftar::withTrashed()
            ->where('periode_id', $periodeId)
            ->whereNotNull('nomor_tes')
            ->orderByDesc('id')
            ->value('nomor_tes');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return sprintf('PPOPM-%d-%04d', $tahun, $seq);
    }
}
