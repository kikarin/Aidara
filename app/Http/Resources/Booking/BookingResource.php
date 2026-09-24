<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Booking\Booking */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor' => $this->nomor,
            'status' => $this->status,
            'priority_flag' => $this->priority_flag,
            'kategori_tarif' => $this->kategori_tarif,
            'tujuan' => $this->tujuan,
            'keterangan' => $this->keterangan,
            'starts_at' => optional($this->starts_at)->toDateTimeString(),
            'ends_at' => optional($this->ends_at)->toDateTimeString(),
            'buffer_before_days' => $this->buffer_before_days,
            'buffer_after_days' => $this->buffer_after_days,
            'luas_m2' => $this->luas_m2,
            'qty' => $this->qty,
            'subtotal' => $this->subtotal,
            'addon_total' => $this->addon_total,
            'grand_total' => $this->grand_total,
            'terms_accepted_at' => optional($this->terms_accepted_at)->toDateTimeString(),
            'submitted_at' => optional($this->submitted_at)->toDateTimeString(),
            'venue' => $this->whenLoaded('venue'),
            'area' => $this->whenLoaded('area'),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'penyewa_profile' => $this->whenLoaded('penyewaProfile', fn () => $this->penyewaProfile ? [
                'id' => $this->penyewaProfile->id,
                'nama' => $this->penyewaProfile->nama,
                'no_hp' => $this->penyewaProfile->no_hp,
                'instansi' => $this->penyewaProfile->instansi,
            ] : null),
            'items' => $this->whenLoaded('items'),
            'addons' => $this->whenLoaded('addonSelected'),
            'payments' => $this->whenLoaded('payments'),
            'status_logs' => $this->whenLoaded('statusLogs'),
            'incidents' => $this->whenLoaded('incidents'),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
