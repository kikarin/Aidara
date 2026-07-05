<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Str;

class EventService
{
    /** @var list<string> */
    private const PUBLIC_STATUSES = ['publish', 'selesai'];

    /**
     * @return list<array<string, mixed>>
     */
    public function getLandingPreview(int $limit = 6): array
    {
        return $this->queryPublic()
            ->limit($limit)
            ->get()
            ->map(fn (Event $item) => $this->transformSummary($item))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getPublicList(): array
    {
        return $this->queryPublic()
            ->get()
            ->map(fn (Event $item) => $this->transformSummary($item))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPublicDetail(int $id): ?array
    {
        $item = $this->queryPublic()->where('id', $id)->first();

        if (!$item) {
            return null;
        }

        return $this->transformDetail($item);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Event>
     */
    private function queryPublic()
    {
        return Event::query()
            ->with(['kategoriEvent.cabor', 'tingkatEvent'])
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformSummary(Event $item): array
    {
        $deskripsi = $item->deskripsi ?? '';

        return [
            'id'                  => $item->id,
            'nama_event'          => $item->nama_event,
            'deskripsi'           => $deskripsi,
            'deskripsi_singkat'   => Str::limit(strip_tags($deskripsi), 140),
            'foto_url'            => $item->foto ? url('storage/' . $item->foto) : null,
            'kategori_event_nama' => $this->formatKategori($item),
            'tingkat_event_nama'  => $item->tingkatEvent?->nama ?? '-',
            'lokasi'              => $item->lokasi,
            'tanggal_mulai'       => $item->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai'     => $item->tanggal_selesai?->format('Y-m-d'),
            'status'              => $item->status,
            'status_label'        => $this->statusLabel($item->status),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDetail(Event $item): array
    {
        return [
            ...$this->transformSummary($item),
            'deskripsi_singkat' => null,
        ];
    }

    private function formatKategori(Event $item): string
    {
        if (!$item->kategoriEvent) {
            return '-';
        }

        $cabor = $item->kategoriEvent->cabor?->nama ?? '';
        $kategori = $item->kategoriEvent->nama ?? '';

        return trim($cabor . ($cabor && $kategori ? ' - ' : '') . $kategori) ?: '-';
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'publish'    => 'Publish',
            'selesai'    => 'Selesai',
            'draft'      => 'Draft',
            'dibatalkan' => 'Dibatalkan',
            default      => $status ?? '-',
        };
    }
}
