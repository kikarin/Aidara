<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /** @var list<string> */
    private array $editableKeys = [
        'payment_mode',
        'rekening_transfer',
        'kontak_klarifikasi',
        'branding_name',
        'payment_expire_hours',
        'pengajuan_sla_hari_kerja',
        'surat_kop',
    ];

    public function edit(): Response
    {
        $settings = BookingSetting::query()
            ->whereIn('key', $this->editableKeys)
            ->orderBy('key')
            ->get()
            ->keyBy('key');

        $form = [];
        foreach ($this->editableKeys as $key) {
            $row = $settings->get($key);
            $form[$key] = [
                'value' => $row?->value,
                'description' => $row?->description,
            ];
        }

        return Inertia::render('modules/e-booking/admin/Settings', [
            'settings' => $form,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branding_name' => ['nullable', 'string', 'max:120'],
            'kontak_klarifikasi' => ['nullable', 'string', 'max:64'],
            'payment_mode' => ['nullable', 'in:manual,bjb'],
            'payment_expire_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'pengajuan_sla_hari_kerja' => ['nullable', 'integer', 'min:1', 'max:90'],
            'rekening_bank' => ['nullable', 'string', 'max:150'],
            'rekening_nomor' => ['nullable', 'string', 'max:64'],
            'rekening_atas_nama' => ['nullable', 'string', 'max:150'],
            'surat_instansi' => ['nullable', 'string', 'max:150'],
            'surat_alamat' => ['nullable', 'string', 'max:255'],
            'surat_email' => ['nullable', 'string', 'max:120'],
            'surat_telp' => ['nullable', 'string', 'max:64'],
            'surat_penandatangan_nama' => ['nullable', 'string', 'max:150'],
            'surat_penandatangan_jabatan' => ['nullable', 'string', 'max:150'],
        ]);

        if (array_key_exists('branding_name', $data) && $data['branding_name'] !== null) {
            BookingSetting::setValue('branding_name', $data['branding_name']);
        }
        if (array_key_exists('kontak_klarifikasi', $data) && $data['kontak_klarifikasi'] !== null) {
            BookingSetting::setValue('kontak_klarifikasi', $data['kontak_klarifikasi']);
        }
        if (! empty($data['payment_mode'])) {
            BookingSetting::setValue('payment_mode', $data['payment_mode']);
        }
        if (isset($data['payment_expire_hours'])) {
            BookingSetting::setValue('payment_expire_hours', (int) $data['payment_expire_hours']);
        }
        if (isset($data['pengajuan_sla_hari_kerja'])) {
            BookingSetting::setValue('pengajuan_sla_hari_kerja', (int) $data['pengajuan_sla_hari_kerja']);
        }

        $currentRek = BookingSetting::getValue('rekening_transfer');
        $rek = is_array($currentRek) ? $currentRek : [];
        if (isset($data['rekening_bank'])) {
            $rek['bank'] = $data['rekening_bank'];
        }
        if (isset($data['rekening_nomor'])) {
            $rek['rekening'] = $data['rekening_nomor'];
        }
        if (isset($data['rekening_atas_nama'])) {
            $rek['atas_nama'] = $data['rekening_atas_nama'];
        }
        if ($rek !== []) {
            BookingSetting::setValue('rekening_transfer', $rek);
        }

        $currentKop = BookingSetting::getValue('surat_kop');
        $kop = is_array($currentKop) ? $currentKop : [];
        foreach (['surat_instansi' => 'instansi', 'surat_alamat' => 'alamat', 'surat_email' => 'email', 'surat_telp' => 'telp', 'surat_penandatangan_nama' => 'penandatangan_nama', 'surat_penandatangan_jabatan' => 'penandatangan_jabatan'] as $field => $kopKey) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $kop[$kopKey] = $data[$field];
            }
        }
        if ($kop !== []) {
            BookingSetting::setValue('surat_kop', $kop);
        }

        return redirect()
            ->route('e-booking.admin.settings')
            ->with('success', 'Pengaturan disimpan.');
    }
}
