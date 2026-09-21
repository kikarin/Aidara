<?php

namespace App\Services;

use App\Models\CaborKategoriAtlet;
use App\Models\ProgramLatihan;
use App\Models\ProgramLatihanAbsenAtlet;
use App\Models\RekapAbsenProgramLatihan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProgramLatihanKehadiranService
{
    /**
     * Alias untuk getRingkasanProgram.
     *
     * @return array<string, mixed>
     */
    public function getRekapPerAtlet(int $programLatihanId, ?string $bulan = null): array
    {
        return $this->getRingkasanProgram($programLatihanId, $bulan);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRekapBulanan(int $programLatihanId): array
    {
        $program = ProgramLatihan::findOrFail($programLatihanId);
        $start = Carbon::parse($program->periode_mulai)->startOfMonth();
        $end = Carbon::parse($program->periode_selesai)->endOfMonth();
        $months = [];

        while ($start->lte($end)) {
            $bulan = $start->format('Y-m');
            $summary = $this->getRingkasanProgram($programLatihanId, $bulan);
            $months[] = [
                'bulan' => $bulan,
                'label' => $start->translatedFormat('F Y'),
                'total_sesi' => $summary['total_sesi'],
                'total_atlet' => $summary['total_atlet'],
                'atlets' => $summary['atlets'],
            ];
            $start->addMonth();
        }

        return $months;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRingkasanAtlet(int $atletId): array
    {
        $records = ProgramLatihanAbsenAtlet::query()
            ->with(['programLatihan.cabor', 'programLatihan.caborKategori'])
            ->where('atlet_id', $atletId)
            ->orderByDesc('tanggal')
            ->get()
            ->groupBy('program_latihan_id');

        return $records->map(function ($group, $programId) {
            /** @var \Illuminate\Support\Collection<int, ProgramLatihanAbsenAtlet> $group */
            $program = $group->first()?->programLatihan;
            $hadir = $group->where('status', 'hadir')->count();
            $total = $group->count();

            return [
                'program_latihan_id' => (int) $programId,
                'nama_program' => $program?->nama_program,
                'cabor_nama' => $program?->cabor?->nama,
                'cabor_kategori_nama' => $program?->caborKategori?->nama,
                'periode_mulai' => $program?->periode_mulai,
                'periode_selesai' => $program?->periode_selesai,
                'hadir' => $hadir,
                'izin' => $group->where('status', 'izin')->count(),
                'sakit' => $group->where('status', 'sakit')->count(),
                'alpha' => $group->where('status', 'alpha')->count(),
                'total_absen' => $total,
                'persentase' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAtletAbsenSummary(int $programLatihanId, int $atletId): array
    {
        $bulan = Carbon::now('Asia/Jakarta')->format('Y-m');
        $program = ProgramLatihan::findOrFail($programLatihanId);
        [$rangeStart, $rangeEnd] = $this->resolveDateRange($program, $bulan);

        $records = ProgramLatihanAbsenAtlet::query()
            ->where('program_latihan_id', $programLatihanId)
            ->where('atlet_id', $atletId)
            ->whereBetween('tanggal', [$rangeStart, $rangeEnd])
            ->get();

        $todayRecord = ProgramLatihanAbsenAtlet::query()
            ->where('program_latihan_id', $programLatihanId)
            ->where('atlet_id', $atletId)
            ->whereDate('tanggal', $today)
            ->first();

        return [
            'hari_ini' => $todayRecord ? $this->formatAbsenRecord($todayRecord) : null,
            'bulan_berjalan' => [
                'bulan' => $bulan,
                'hadir' => $records->where('status', 'hadir')->count(),
                'izin' => $records->where('status', 'izin')->count(),
                'sakit' => $records->where('status', 'sakit')->count(),
                'alpha' => $records->where('status', 'alpha')->count(),
                'total' => $records->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRingkasanProgram(int $programLatihanId, ?string $bulan = null): array
    {
        $program = ProgramLatihan::with(['cabor', 'caborKategori'])->findOrFail($programLatihanId);

        [$rangeStart, $rangeEnd] = $this->resolveDateRange($program, $bulan);

        $totalSesi = RekapAbsenProgramLatihan::query()
            ->where('program_latihan_id', $programLatihanId)
            ->when($rangeStart && $rangeEnd, fn ($q) => $q->whereBetween('tanggal', [$rangeStart, $rangeEnd]))
            ->count();

        $atletIds = CaborKategoriAtlet::query()
            ->where('cabor_id', $program->cabor_id)
            ->where('cabor_kategori_id', $program->cabor_kategori_id)
            ->whereNull('deleted_at')
            ->pluck('atlet_id');

        $absenRecords = ProgramLatihanAbsenAtlet::query()
            ->with(['atlet', 'media'])
            ->where('program_latihan_id', $programLatihanId)
            ->whereIn('atlet_id', $atletIds)
            ->when($rangeStart && $rangeEnd, fn ($q) => $q->whereBetween('tanggal', [$rangeStart, $rangeEnd]))
            ->get()
            ->groupBy('atlet_id');

        $atlets = \App\Models\Atlet::query()
            ->whereIn('id', $atletIds)
            ->orderBy('nama')
            ->get();

        $rows = $atlets->map(function ($atlet) use ($absenRecords, $totalSesi) {
            /** @var Collection<int, ProgramLatihanAbsenAtlet> $records */
            $records = $absenRecords->get($atlet->id, collect());

            $hadir = $records->where('status', 'hadir')->count();
            $izin = $records->where('status', 'izin')->count();
            $sakit = $records->where('status', 'sakit')->count();
            $alpha = $records->where('status', 'alpha')->count();

            $denominator = max(1, $totalSesi);
            $persentase = $totalSesi > 0 ? round(($hadir / $denominator) * 100, 1) : 0;

            return [
                'atlet_id' => $atlet->id,
                'nama' => $atlet->nama,
                'nik' => $atlet->nik,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'total_sesi' => $totalSesi,
                'persentase' => $persentase,
                'kategori_kehadiran' => $this->kategoriKehadiran($persentase),
            ];
        })->values()->all();

        return [
            'program' => [
                'id' => $program->id,
                'nama_program' => $program->nama_program,
                'cabor_nama' => $program->cabor?->nama,
                'cabor_kategori_nama' => $program->caborKategori?->nama,
                'periode_mulai' => $program->periode_mulai,
                'periode_selesai' => $program->periode_selesai,
            ],
            'filter_bulan' => $bulan,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'total_sesi' => $totalSesi,
            'total_atlet' => count($rows),
            'atlets' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRiwayatAtlet(int $programLatihanId, int $atletId): array
    {
        return ProgramLatihanAbsenAtlet::query()
            ->with('media')
            ->where('program_latihan_id', $programLatihanId)
            ->where('atlet_id', $atletId)
            ->orderByDesc('tanggal')
            ->get()
            ->map(fn (ProgramLatihanAbsenAtlet $row) => $this->formatAbsenRecord($row))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAbsenRecord(ProgramLatihanAbsenAtlet $row): array
    {
        $media = $row->getFirstMedia('foto_absen_atlet');

        return [
            'id' => $row->id,
            'tanggal' => $row->tanggal?->format('Y-m-d'),
            'status' => $row->status,
            'status_label' => $this->statusLabel($row->status),
            'waktu_foto' => $row->waktu_foto,
            'lokasi' => $row->lokasi,
            'latitude' => $row->latitude,
            'longitude' => $row->longitude,
            'catatan' => $row->catatan,
            'foto' => $media ? [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ] : null,
        ];
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha' => 'Alpha',
            default => ucfirst($status),
        };
    }

    private function kategoriKehadiran(float $persentase): string
    {
        if ($persentase >= 80) {
            return 'baik';
        }

        if ($persentase >= 50) {
            return 'cukup';
        }

        return 'kurang';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveDateRange(ProgramLatihan $program, ?string $bulan): array
    {
        if ($bulan === null || $bulan === '' || $bulan === 'all') {
            return [$program->periode_mulai, $program->periode_selesai];
        }

        try {
            $start = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $programStart = Carbon::parse($program->periode_mulai);
            $programEnd = Carbon::parse($program->periode_selesai);

            if ($start->lt($programStart)) {
                $start = $programStart->copy();
            }

            if ($end->gt($programEnd)) {
                $end = $programEnd->copy();
            }

            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        } catch (\Throwable) {
            return [$program->periode_mulai, $program->periode_selesai];
        }
    }
}
