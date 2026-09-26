<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingTarif;
use App\Support\Booking\BookingSatuan;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    /**
     * Hitung harga booking. Dua bentuk input:
     *  - Seluruh venue: tarif_id (harus tarif venue-wide / tanpa area).
     *  - Area spesifik (bisa lebih dari satu): areas = [{area_id, tarif_id, qty?, luas_m2?}].
     * Bentuk lama (tarif_id + area_id) masih didukung.
     *
     * @param  array{
     *   tarif_id?: int|null,
     *   area_id?: int|null,
     *   areas?: list<array{area_id: mixed, tarif_id: mixed, qty?: mixed, luas_m2?: mixed}>,
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
     *   tarif: array<string, mixed>|null,
     *   duration: array<string, mixed>
     * }
     */
    public function quote(array $input): array
    {
        $kategori = $input['kategori_tarif'] ?? null;
        if (! in_array($kategori, ['pemerintah', 'non_pemerintah'], true)) {
            throw new InvalidArgumentException('kategori_tarif harus pemerintah atau non_pemerintah.');
        }

        $startsAt = Carbon::parse($input['starts_at']);
        $endsAt = Carbon::parse($input['ends_at']);

        if ($endsAt->lte($startsAt)) {
            throw new InvalidArgumentException('ends_at harus setelah starts_at.');
        }

        $rows = $this->normalizeQuoteRows($input);
        $lines = [];

        foreach ($rows as $row) {
            $lines[] = $this->buildQuoteLine($row, $kategori, $startsAt, $endsAt, $input['duration_value'] ?? null);
        }

        $subtotal = array_sum(array_column($lines, 'line_total'));
        $venueId = $rows[0]['venue_id'];
        $addons = $this->quoteAddons($input['addon_ids'] ?? [], $venueId);
        $addonTotal = array_sum(array_column($addons, 'line_total'));

        return [
            'subtotal' => (int) $subtotal,
            'addon_total' => $addonTotal,
            'grand_total' => (int) ($subtotal + $addonTotal),
            'lines' => $lines,
            'addons' => $addons,
            'tarif' => $lines[0]['tarif'] ?? null,
            'duration' => $lines[0]['duration'],
            'kategori_tarif' => $kategori,
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
        ];
    }

    /**
     * Normalisasi input ke daftar baris area.
     *
     * @param  array<string, mixed>  $input
     * @return list<array{area_id: ?int, tarif_id: int, qty: int, luas_m2: ?float, venue_id: int}>
     */
    private function normalizeQuoteRows(array $input): array
    {
        $rawRows = isset($input['areas']) && is_array($input['areas']) && $input['areas'] !== []
            ? array_values($input['areas'])
            : null;

        if ($rawRows === null) {
            $tarif = BookingTarif::query()->find($input['tarif_id'] ?? null);
            if (! $tarif) {
                throw new InvalidArgumentException('Tarif tidak ditemukan atau tidak aktif.');
            }

            $areaId = isset($input['area_id']) && $input['area_id'] !== '' && $input['area_id'] !== null
                ? (int) $input['area_id']
                : $tarif->area_id;

            $rawRows = [[
                'area_id' => $areaId,
                'tarif_id' => $tarif->id,
                'qty' => $input['qty'] ?? 1,
                'luas_m2' => $input['luas_m2'] ?? null,
            ]];
        }

        if ($rawRows === []) {
            throw new InvalidArgumentException('Pilih minimal satu area yang akan disewa.');
        }

        $rows = [];
        $seenAreas = [];

        foreach ($rawRows as $index => $raw) {
            $raw = is_array($raw) ? $raw : [];
            $tarifId = (int) ($raw['tarif_id'] ?? 0);
            $areaIdRaw = $raw['area_id'] ?? null;
            $areaId = ($areaIdRaw === null || $areaIdRaw === '' || (int) $areaIdRaw === 0) ? null : (int) $areaIdRaw;

            $tarif = BookingTarif::query()
                ->where('is_active', true)
                ->find($tarifId);

            if (! $tarif) {
                throw new InvalidArgumentException('Tarif tidak ditemukan atau tidak aktif.');
            }

            if ($areaId === null) {
                if ($tarif->area_id !== null) {
                    throw new InvalidArgumentException(
                        "{$tarif->uraian} adalah tarif area tertentu — pilih area terlebih dahulu."
                    );
                }
                $area = null;
            } else {
                $area = BookingArea::query()->where('is_active', true)->find($areaId);

                if (! $area || $area->venue_id !== $tarif->venue_id) {
                    throw new InvalidArgumentException('Area tidak ditemukan atau tidak aktif untuk venue ini.');
                }

                if ($tarif->area_id !== null && (int) $tarif->area_id !== $area->id) {
                    throw new InvalidArgumentException("Jenis sewa {$tarif->uraian} bukan untuk area {$area->name}.");
                }

                if (isset($seenAreas[$area->id])) {
                    throw new InvalidArgumentException("Area {$area->name} dipilih lebih dari sekali.");
                }
                $seenAreas[$area->id] = true;
            }

            $rows[] = [
                'area_id' => $area?->id,
                'tarif_id' => $tarif->id,
                'qty' => max(1, (int) ($raw['qty'] ?? 1)),
                'luas_m2' => isset($raw['luas_m2']) && $raw['luas_m2'] !== '' && $raw['luas_m2'] !== null
                    ? (float) $raw['luas_m2']
                    : null,
                'venue_id' => (int) $tarif->venue_id,
                'tarif_model' => $tarif,
                'area_model' => $area,
            ];
        }

        $venueIds = array_unique(array_column($rows, 'venue_id'));
        if (count($venueIds) > 1) {
            throw new InvalidArgumentException('Semua area yang dipilih harus berada di venue yang sama.');
        }

        return $rows;
    }

    /**
     * @param  array{area_id: ?int, tarif_id: int, qty: int, luas_m2: ?float, tarif_model: BookingTarif, area_model: ?BookingArea}  $row
     * @return array<string, mixed>
     */
    private function buildQuoteLine(array $row, string $kategori, Carbon $startsAt, Carbon $endsAt, mixed $durationOverride = null): array
    {
        $tarif = $row['tarif_model'];
        $area = $row['area_model'];

        $unitPrice = $kategori === 'pemerintah'
            ? $tarif->tarif_pemerintah
            : $tarif->tarif_non_pemerintah;

        if ($unitPrice === null) {
            throw new InvalidArgumentException(
                $kategori === 'pemerintah'
                    ? "Tarif pemerintah belum tersedia untuk {$tarif->uraian}. Hubungi admin."
                    : "Tarif non pemerintah belum tersedia untuk {$tarif->uraian}."
            );
        }

        $luas = $row['luas_m2'];

        if (in_array($tarif->satuan, [BookingSatuan::PER_M2_DAY, BookingSatuan::PER_M2_MONTH], true)
            && ($luas === null || $luas <= 0)
        ) {
            throw new InvalidArgumentException('luas_m2 wajib untuk satuan M².');
        }

        $duration = $this->resolveDuration($tarif->satuan, $startsAt, $endsAt, $durationOverride);
        $this->assertMetaHourConstraints($tarif->meta, $duration['hours']);
        $lineTotal = $this->calculateLineTotal($tarif->satuan, (int) $unitPrice, $row['qty'], $luas, $duration['value']);

        return [
            'tarif_id' => $tarif->id,
            'area_id' => $area?->id,
            'area' => $area ? [
                'id' => $area->id,
                'code' => $area->code,
                'name' => $area->name,
            ] : null,
            'uraian' => $tarif->uraian,
            'satuan' => $tarif->satuan,
            'qty' => $row['qty'],
            'luas_m2' => $luas,
            'duration_value' => $duration['value'],
            'duration_label' => $duration['label'],
            'unit_price' => (int) $unitPrice,
            'line_total' => $lineTotal,
            'tarif' => [
                'id' => $tarif->id,
                'code' => $tarif->code,
                'uraian' => $tarif->uraian,
                'satuan' => $tarif->satuan,
            ],
            'duration' => $duration,
            'snapshot' => [
                'tarif_code' => $tarif->code,
                'kategori_tarif' => $kategori,
                'venue_id' => $tarif->venue_id,
                'area_id' => $area?->id,
                'time_slot' => $tarif->time_slot,
                'audience_type' => $tarif->audience_type,
                'day_type' => $tarif->day_type,
                'vehicle_class' => $tarif->vehicle_class,
                'event_level' => $tarif->event_level,
                'category' => $tarif->category,
            ],
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
     * Enforce meta.min_hours / meta.max_hours (Perda sarana lainnya).
     *
     * @param  array<string, mixed>|null  $meta
     */
    private function assertMetaHourConstraints(?array $meta, float $hours): void
    {
        if ($meta === null || $meta === []) {
            return;
        }

        if (isset($meta['min_hours']) && is_numeric($meta['min_hours'])) {
            $min = (float) $meta['min_hours'];
            if ($hours + 1e-6 < $min) {
                throw new InvalidArgumentException(
                    "Durasi minimal untuk tarif ini adalah {$min} jam."
                );
            }
        }

        if (isset($meta['max_hours']) && is_numeric($meta['max_hours'])) {
            $max = (float) $meta['max_hours'];
            if ($hours - 1e-6 > $max) {
                throw new InvalidArgumentException(
                    "Durasi maksimal untuk tarif ini adalah {$max} jam."
                );
            }
        }
    }

    /**
     * @param  array<int, int>|array<int, array{id: int, qty?: int}>  $addonIds
     * @return array<int, array<string, mixed>>
     */
    private function quoteAddons(array $addonIds, ?int $venueId = null): array
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

        $query = BookingAddon::query()
            ->whereIn('id', $ids)
            ->where('is_active', true);

        if ($venueId) {
            $query->whereHas('venues', fn ($q) => $q->where('booking_venues.id', $venueId));
        }

        $addons = $query->get()->keyBy('id');

        $lines = [];
        foreach ($normalized as $row) {
            $addon = $addons->get($row['id']);
            if (! $addon) {
                throw new InvalidArgumentException("Add-on #{$row['id']} tidak tersedia untuk venue ini.");
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
