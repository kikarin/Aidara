<?php

namespace App\Services\SeleksiPpopm;

use App\Models\SeleksiCaborSyarat;
use App\Models\SeleksiPendaftar;
use App\Support\SeleksiPpopm;
use Illuminate\Support\Collection;

class SeleksiScoringService
{
    public function normalizeKecabangan(?float $mentah): ?float
    {
        if ($mentah === null) {
            return null;
        }

        return round(($mentah / 5) * 100, 2);
    }

    public function applyTes(SeleksiPendaftar $pendaftar, array $scores): SeleksiPendaftar
    {
        if (array_key_exists('skor_kecabangan_mentah', $scores) && $scores['skor_kecabangan_mentah'] !== null) {
            $pendaftar->skor_kecabangan_mentah = (float) $scores['skor_kecabangan_mentah'];
            $pendaftar->skor_kecabangan        = $this->normalizeKecabangan((float) $scores['skor_kecabangan_mentah']);
        }

        foreach (['skor_fisik', 'skor_akademik', 'skor_psikologi'] as $field) {
            if (array_key_exists($field, $scores) && $scores[$field] !== null && $scores[$field] !== '') {
                $pendaftar->{$field} = (float) $scores[$field];
            }
        }

        foreach (['psikologi_rekomendasi', 'kesehatan_layak', 'antropometri_layak'] as $field) {
            if (array_key_exists($field, $scores) && $scores[$field] !== null && $scores[$field] !== '') {
                $pendaftar->{$field} = filter_var($scores[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $pendaftar->status      = $this->resolveStatusAfterTes($pendaftar);
        $pendaftar->nilai_akhir = $this->hitungNilaiAkhir($pendaftar);
        $pendaftar->save();

        return $pendaftar;
    }

    public function resolveStatusAfterTes(SeleksiPendaftar $pendaftar): string
    {
        if ($pendaftar->kesehatan_layak === false) {
            return SeleksiPpopm::STATUS_GUGUR_KESEHATAN;
        }

        if ($pendaftar->antropometri_layak === false) {
            return SeleksiPpopm::STATUS_GUGUR_ANTROPOMETRI;
        }

        if ($pendaftar->skor_kecabangan !== null && $pendaftar->skor_kecabangan < SeleksiPpopm::AMBANG_KECABANGAN) {
            return SeleksiPpopm::STATUS_GUGUR_KECABANGAN;
        }

        if (in_array($pendaftar->status, [
            SeleksiPpopm::STATUS_LULUS,
            SeleksiPpopm::STATUS_OBSERVASI,
            SeleksiPpopm::STATUS_TIDAK_LULUS,
        ], true)) {
            return $pendaftar->status;
        }

        return SeleksiPpopm::STATUS_LULUS_ADMINISTRASI;
    }

    public function hitungNilaiAkhir(SeleksiPendaftar $pendaftar): ?float
    {
        if (! $pendaftar->hasCompleteScores()) {
            return null;
        }

        return round(
            ($pendaftar->skor_kecabangan * SeleksiPpopm::BOBOT_KECABANGAN)
            + ($pendaftar->skor_fisik * SeleksiPpopm::BOBOT_FISIK)
            + ($pendaftar->skor_akademik * SeleksiPpopm::BOBOT_AKADEMIK)
            + ($pendaftar->skor_psikologi * SeleksiPpopm::BOBOT_PSIKOLOGI),
            2
        );
    }

    public function runPleno(int $periodeId): Collection
    {
        $syaratList = SeleksiCaborSyarat::query()
            ->where('periode_id', $periodeId)
            ->get();

        $hasil = collect();

        foreach ($syaratList as $syarat) {
            $hasil = $hasil->concat($this->rankCabor($syarat));
        }

        return $hasil;
    }

    private function rankCabor(SeleksiCaborSyarat $syarat): Collection
    {
        $kandidat = SeleksiPendaftar::query()
            ->where('cabor_syarat_id', $syarat->id)
            ->whereNotIn('status', [
                SeleksiPpopm::STATUS_DRAFT,
                SeleksiPpopm::STATUS_SUBMITTED,
                SeleksiPpopm::STATUS_DITOLAK_ADMIN,
                SeleksiPpopm::STATUS_GUGUR_KESEHATAN,
                SeleksiPpopm::STATUS_GUGUR_ANTROPOMETRI,
                SeleksiPpopm::STATUS_GUGUR_KECABANGAN,
            ])
            ->get()
            ->filter(fn (SeleksiPendaftar $item) => $item->hasCompleteScores())
            ->map(function (SeleksiPendaftar $item) {
                $item->nilai_akhir = $this->hitungNilaiAkhir($item);

                return $item;
            })
            ->sort(fn (SeleksiPendaftar $a, SeleksiPendaftar $b) => $this->compareTieBreak($a, $b))
            ->values();

        $kuotaDetail = $syarat->kuota_detail ?? [];
        $positionKeys = collect($kuotaDetail)->keys()->reject(fn ($key) => $key === 'all');
        $groups       = $positionKeys->isNotEmpty()
            ? $kandidat->groupBy(fn (SeleksiPendaftar $item) => $item->posisi ?: $item->nomor_kelas ?: '_')
            : collect(['_' => $kandidat]);

        $ranked = collect();

        foreach ($groups as $key => $group) {
            $ranked = $ranked->concat(
                $this->applyQuota(
                    $group->values(),
                    $this->quotaForGroup($syarat, (string) $key)
                )
            );
        }

        return $ranked;
    }

    /**
     * @param  array<string, int>|null  $kuotaPerJk
     */
    private function quotaForGroup(SeleksiCaborSyarat $syarat, string $groupKey): array
    {
        $detail = $syarat->kuota_detail ?? [];
        if ($groupKey !== '_' && isset($detail[$groupKey])) {
            $value = $detail[$groupKey];
            if (is_array($value)) {
                return [
                    'L' => (int) ($value['L'] ?? $value['putra'] ?? 0),
                    'P' => (int) ($value['P'] ?? $value['putri'] ?? 0),
                ];
            }

            return ['all' => (int) $value];
        }

        if (isset($detail['all'])) {
            return ['all' => (int) $detail['all']];
        }

        return [
            'L' => (int) $syarat->kuota_putra,
            'P' => (int) $syarat->kuota_putri,
        ];
    }

    private function applyQuota(Collection $sorted, array $quota): Collection
    {
        $acceptedL = 0;
        $acceptedP = 0;
        $acceptedAll = 0;
        $acceptedLuar = 0;
        $rank = 1;

        $totalQuota = ($quota['all'] ?? 0) + ($quota['L'] ?? 0) + ($quota['P'] ?? 0);
        $maxLuar    = max(1, (int) ceil($totalQuota * 0.10));
        if ($totalQuota <= 1) {
            $maxLuar = $totalQuota > 0 ? 0 : 0;
        }

        return $sorted->map(function (SeleksiPendaftar $item) use (&$rank, &$acceptedL, &$acceptedP, &$acceptedAll, &$acceptedLuar, $quota, $maxLuar, $totalQuota) {
            $item->ranking     = $rank;
            $item->nilai_akhir = $this->hitungNilaiAkhir($item);
            $rank++;

            if ($item->nilai_akhir === null) {
                return $item;
            }

            if ($item->nilai_akhir < SeleksiPpopm::AMBANG_OBSERVASI) {
                $item->status      = SeleksiPpopm::STATUS_TIDAK_LULUS;
                $item->alasan_tolak = 'Nilai akhir di bawah 50.';
                $item->save();

                return $item;
            }

            if ($item->nilai_akhir < SeleksiPpopm::AMBANG_LULUS) {
                $item->status       = SeleksiPpopm::STATUS_OBSERVASI;
                $item->alasan_tolak = 'Nilai akhir 50–69, masuk program observasi/perbaikan.';
                $item->save();

                return $item;
            }

            $slotAvailable = $this->hasQuotaSlot($item, $quota, $acceptedL, $acceptedP, $acceptedAll);
            $luarOk        = $item->asal_kabupaten_bogor || $acceptedLuar < $maxLuar || $totalQuota <= 1;

            if ($slotAvailable && $luarOk) {
                $item->status       = SeleksiPpopm::STATUS_LULUS;
                $item->alasan_tolak = null;
                $this->incrementQuota($item, $quota, $acceptedL, $acceptedP, $acceptedAll);
                if (! $item->asal_kabupaten_bogor) {
                    $acceptedLuar++;
                }
            } else {
                $item->status       = SeleksiPpopm::STATUS_TIDAK_LULUS;
                $item->alasan_tolak = $slotAvailable
                    ? 'Kuota 10% luar Kabupaten Bogor sudah terpenuhi.'
                    : 'Kuota cabor/posisi sudah terpenuhi.';
            }

            $item->save();

            return $item;
        });
    }

    private function hasQuotaSlot(SeleksiPendaftar $item, array $quota, int $acceptedL, int $acceptedP, int $acceptedAll): bool
    {
        if (isset($quota['all'])) {
            return $acceptedAll < $quota['all'];
        }

        if ($item->jenis_kelamin === 'L') {
            return $acceptedL < ($quota['L'] ?? 0);
        }

        return $acceptedP < ($quota['P'] ?? 0);
    }

    private function incrementQuota(SeleksiPendaftar $item, array $quota, int &$acceptedL, int &$acceptedP, int &$acceptedAll): void
    {
        if (isset($quota['all'])) {
            $acceptedAll++;

            return;
        }

        if ($item->jenis_kelamin === 'L') {
            $acceptedL++;

            return;
        }

        $acceptedP++;
    }

    private function compareTieBreak(SeleksiPendaftar $a, SeleksiPendaftar $b): int
    {
        return [$b->nilai_akhir, $b->skor_kecabangan, $b->skor_fisik, $b->skor_psikologi, $b->skor_akademik, $a->tanggal_lahir?->timestamp]
            <=> [$a->nilai_akhir, $a->skor_kecabangan, $a->skor_fisik, $a->skor_psikologi, $a->skor_akademik, $b->tanggal_lahir?->timestamp];
    }
}
