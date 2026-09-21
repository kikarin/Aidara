<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingTarif;
use App\Support\Booking\BookingSatuan;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    /**
     * @param  array{
     *   tarif_id: int,
     *   kategori_tarif: string,
     *   starts_at: string|\DateTimeInterface,
     *   ends_at: string|\DateTimeInterface,
     *   qty?: int|null,
     *   luas_m2?: float|null,
     *   duration_value?: int|null,
     *   addon_ids?: array<int, int>|array<int, array{id: int, qty?: int}>
     * }  $input
     * @return array{
     *   subtotal: int,
     *   addon_total: int,
     *   grand_total: int,
     *   lines: array<int, array<string, mixed>>,
     *   addons: array<int, array<string, mixed>>,
     *   tarif: array<string, mixed>,
     *   duration: array<string, mixed>
     * }
     */
    public function quote(array $input): array
    {
        $tarif = BookingTarif::query()
            ->with(['venue:id,code,name', 'area:id,code,name'])
            ->where('is_active', true)
            ->find($input['tarif_id'] ?? null);

        if (! $tarif) {
            throw new InvalidArgumentException('Tarif tidak ditemukan atau tidak aktif.');
        }

        $kategori = $input['kategori_tarif'] ?? null;
        if (! in_array($kategori, ['pemerintah', 'non_pemerintah'], true)) {
            throw new InvalidArgumentException('kategori_tarif harus pemerintah atau non_pemerintah.');
        }

        $unitPrice = $kategori === 'pemerintah'
            ? $tarif->tarif_pemerintah
            : $tarif->tarif_non_pemerintah;

        if ($unitPrice === null) {
            throw new InvalidArgumentException(
                $kategori === 'pemerintah'
                    ? 'Tarif pemerintah tidak tersedia untuk item ini. Hubungi admin.'
                    : 'Tarif non pemerintah tidak tersedia untuk item ini.'
            );
        }

        $startsAt = Carbon::parse($input['starts_at']);
        $endsAt = Carbon::parse($input['ends_at']);

        if ($endsAt->lte($startsAt)) {
            throw new InvalidArgumentException('ends_at harus setelah starts_at.');
        }

        $qty = max(1, (int) ($input['qty'] ?? 1));
        $luas = isset($input['luas_m2']) ? (float) $input['luas_m2'] : null;

        if (in_array($tarif->satuan, [BookingSatuan::PER_M2_DAY, BookingSatuan::PER_M2_MONTH], true)
            && ($luas === null || $luas <= 0)
        ) {
            throw new InvalidArgumentException('luas_m2 wajib untuk satuan M².');
        }

        $duration = $this->resolveDuration($tarif->satuan, $startsAt, $endsAt, $input['duration_value'] ?? null);
        $lineTotal = $this->calculateLineTotal($tarif->satuan, (int) $unitPrice, $qty, $luas, $duration['value']);

        $line = [
            'tarif_id' => $tarif->id,
            'uraian' => $tarif->uraian,
            'satuan' => $tarif->satuan,
            'qty' => $qty,
            'luas_m2' => $luas,
            'duration_value' => $duration['value'],
            'duration_label' => $duration['label'],
            'unit_price' => (int) $unitPrice,
            'line_total' => $lineTotal,
            'snapshot' => [
                'tarif_code' => $tarif->code,
                'kategori_tarif' => $kategori,
                'venue_id' => $tarif->venue_id,
                'area_id' => $tarif->area_id,
                'time_slot' => $tarif->time_slot,
                'audience_type' => $tarif->audience_type,
                'day_type' => $tarif->day_type,
                'vehicle_class' => $tarif->vehicle_class,
                'event_level' => $tarif->event_level,
                'category' => $tarif->category,
            ],
        ];

        $addons = $this->quoteAddons($input['addon_ids'] ?? []);
        $addonTotal = array_sum(array_column($addons, 'line_total'));
        $subtotal = $lineTotal;
        $grandTotal = $subtotal + $addonTotal;

        return [
            'subtotal' => $subtotal,
            'addon_total' => $addonTotal,
            'grand_total' => $grandTotal,
            'lines' => [$line],
            'addons' => $addons,
            'tarif' => [
                'id' => $tarif->id,
                'code' => $tarif->code,
                'uraian' => $tarif->uraian,
                'satuan' => $tarif->satuan,
                'venue' => $tarif->venue,
                'area' => $tarif->area,
            ],
            'duration' => $duration,
            'kategori_tarif' => $kategori,
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
        ];
    }

    /**
     * @return array{value: int, label: string, hours: float, days: int, months: int, blocks_3hour: int}
     */
    public function resolveDuration(string $satuan, Carbon $startsAt, Carbon $endsAt, ?int $override = null): array
    {
        $minutes = max(1, $startsAt->diffInMinutes($endsAt));
        $hours = $minutes / 60;
        $days = max(1, $startsAt->copy()->startOfDay()->diffInDays($endsAt->copy()->startOfDay()) + 1);
        $months = max(1, (int) ceil($days / 30));
        $blocks = max(1, (int) ceil($hours / 3));
        $hoursCeil = max(1, (int) ceil($hours));

        $value = match ($satuan) {
            BookingSatuan::PER_HOUR, BookingSatuan::PER_COURT_HOUR => $override ?? $hoursCeil,
            BookingSatuan::PER_DAY, BookingSatuan::PER_ACTIVITY_DAY, BookingSatuan::PER_M2_DAY => $override ?? $days,
            BookingSatuan::PER_M2_MONTH => $override ?? $months,
            BookingSatuan::PER_UNIT_3HOUR => $override ?? $blocks,
            BookingSatuan::PER_MATCH, BookingSatuan::PER_PERSON => $override ?? 1,
            default => $override ?? $hoursCeil,
        };

        $label = match ($satuan) {
            BookingSatuan::PER_HOUR, BookingSatuan::PER_COURT_HOUR => "{$value} jam",
            BookingSatuan::PER_DAY, BookingSatuan::PER_ACTIVITY_DAY, BookingSatuan::PER_M2_DAY => "{$value} hari",
            BookingSatuan::PER_M2_MONTH => "{$value} bulan",
            BookingSatuan::PER_UNIT_3HOUR => "{$value} blok (3 jam)",
            BookingSatuan::PER_MATCH => "{$value} pertandingan",
            BookingSatuan::PER_PERSON => 'per orang',
            default => (string) $value,
        };

        return [
            'value' => $value,
            'label' => $label,
            'hours' => round($hours, 2),
            'days' => $days,
            'months' => $months,
            'blocks_3hour' => $blocks,
        ];
    }

    public function calculateLineTotal(string $satuan, int $unitPrice, int $qty, ?float $luas, int $durationValue): int
    {
        return (int) match ($satuan) {
            BookingSatuan::PER_MATCH => $unitPrice * $qty,
            BookingSatuan::PER_PERSON => $unitPrice * $qty,
            BookingSatuan::PER_HOUR => $unitPrice * $durationValue,
            BookingSatuan::PER_COURT_HOUR => $unitPrice * $qty * $durationValue,
            BookingSatuan::PER_DAY, BookingSatuan::PER_ACTIVITY_DAY => $unitPrice * $durationValue,
            BookingSatuan::PER_UNIT_3HOUR => $unitPrice * $qty * $durationValue,
            BookingSatuan::PER_M2_DAY, BookingSatuan::PER_M2_MONTH => (int) round($unitPrice * (float) $luas * $durationValue),
            default => $unitPrice * $qty * $durationValue,
        };
    }

    /**
     * @param  array<int, int>|array<int, array{id: int, qty?: int}>  $addonIds
     * @return array<int, array<string, mixed>>
     */
    private function quoteAddons(array $addonIds): array
    {
        if ($addonIds === []) {
            return [];
        }

        $normalized = [];
        foreach ($addonIds as $item) {
            if (is_array($item)) {
                $normalized[] = [
                    'id' => (int) ($item['id'] ?? 0),
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                ];
            } else {
                $normalized[] = ['id' => (int) $item, 'qty' => 1];
            }
        }

        $ids = array_values(array_unique(array_column($normalized, 'id')));
        $addons = BookingAddon::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $lines = [];
        foreach ($normalized as $row) {
            $addon = $addons->get($row['id']);
            if (! $addon) {
                throw new InvalidArgumentException("Add-on #{$row['id']} tidak ditemukan atau tidak aktif.");
            }
            if ($addon->harga === null) {
                throw new InvalidArgumentException("Harga add-on {$addon->name} belum ditentukan admin.");
            }

            $lineTotal = (int) $addon->harga * $row['qty'];
            $lines[] = [
                'addon_id' => $addon->id,
                'name' => $addon->name,
                'qty' => $row['qty'],
                'unit_price' => (int) $addon->harga,
                'line_total' => $lineTotal,
                'snapshot' => [
                    'code' => $addon->code,
                ],
            ];
        }

        return $lines;
    }
}
