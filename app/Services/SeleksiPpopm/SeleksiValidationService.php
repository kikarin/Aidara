<?php

namespace App\Services\SeleksiPpopm;

use App\Models\SeleksiCaborSyarat;
use App\Support\SeleksiPpopm;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SeleksiValidationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function assertEligible(SeleksiCaborSyarat $syarat, array $data, bool $hasPiagam = false): void
    {
        $errors = $this->validate($syarat, $data, $hasPiagam);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function validate(SeleksiCaborSyarat $syarat, array $data, bool $hasPiagam = false): array
    {
        $errors = [];

        $jenisKelamin = (string) ($data['jenis_kelamin'] ?? '');
        $diizinkan    = $syarat->jenis_kelamin_diizinkan ?? ['L', 'P'];
        if ($jenisKelamin !== '' && ! in_array($jenisKelamin, $diizinkan, true)) {
            $errors['jenis_kelamin'] = 'Jenis kelamin tidak sesuai kuota cabor '.$syarat->nama_cabor.'.';
        }

        $tanggalLahir = $data['tanggal_lahir'] ?? null;
        if ($tanggalLahir) {
            $tahun = (int) Carbon::parse($tanggalLahir)->format('Y');
            if ($tahun < $syarat->tahun_lahir_min || $tahun > $syarat->tahun_lahir_max) {
                $errors['tanggal_lahir'] = "Tahun kelahiran harus {$syarat->tahun_lahir_min}–{$syarat->tahun_lahir_max} untuk {$syarat->nama_cabor}.";
            }
        }

        $tinggi = isset($data['tinggi_badan']) ? (float) $data['tinggi_badan'] : null;
        if ($tinggi !== null) {
            $tinggiError = $this->validateTinggi($syarat, $jenisKelamin, $tinggi, $data);
            if ($tinggiError) {
                $errors['tinggi_badan'] = $tinggiError;
            }
        }

        $posisiList = $syarat->posisi ?? [];
        if ($posisiList !== [] && empty($data['posisi'])) {
            $errors['posisi'] = 'Posisi wajib dipilih untuk '.$syarat->nama_cabor.'.';
        }

        $kuotaDetail = $syarat->kuota_detail ?? [];
        if ($kuotaDetail !== [] && empty($data['posisi']) && empty($data['nomor_kelas'])) {
            $errors['posisi'] = 'Posisi atau nomor/kelas wajib diisi sesuai kuota cabor.';
        }

        if ($syarat->wajib_dua_posisi && empty($data['bisa_dua_posisi'])) {
            $errors['bisa_dua_posisi'] = 'Calon atlet sepak bola wajib mampu bermain minimal 2 posisi.';
        }

        if ($syarat->wajib_berenang && empty($data['bisa_berenang'])) {
            $errors['bisa_berenang'] = 'Calon atlet dayung wajib bisa berenang.';
        }

        if ($syarat->wajib_piagam && ! $hasPiagam) {
            $errors['berkas_piagam'] = 'Piagam/sertifikat kejuaraan wajib untuk '.$syarat->nama_cabor.'.';
        }

        if ($syarat->kode === 'taekwondo-poomsae' && empty($data['kuasai_poomsae'])) {
            $errors['kuasai_poomsae'] = 'Poomsae wajib menguasai gerakan Taeguk 4 sampai Pyongwon.';
        }

        $asalBogor = filter_var($data['asal_kabupaten_bogor'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if (! $asalBogor && empty($data['bersedia_pindah_domisili'])) {
            $errors['bersedia_pindah_domisili'] = 'Pendaftar dari luar Kabupaten Bogor wajib bersedia pindah sekolah dan domisili.';
        }

        if (empty($data['setuju_perjanjian'])) {
            $errors['setuju_perjanjian'] = 'Calon atlet wajib menyetujui perjanjian peraturan UPT PPOPM.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateTinggi(SeleksiCaborSyarat $syarat, string $jenisKelamin, float $tinggi, array $data): ?string
    {
        $minPerPosisi = $syarat->tinggi_per_posisi ?? [];
        $posisi       = (string) ($data['posisi'] ?? '');
        if ($posisi !== '' && isset($minPerPosisi[$posisi])) {
            $min = (float) $minPerPosisi[$posisi];
            if ($tinggi < $min) {
                return "Tinggi badan minimal {$min} cm untuk posisi {$posisi}.";
            }

            return null;
        }

        $min = $jenisKelamin === 'L' ? $syarat->tinggi_min_putra : $syarat->tinggi_min_putri;
        if ($min === null) {
            return null;
        }

        $toleransi = $syarat->toleransi_tinggi ?? null;
        if (is_array($toleransi) && $tinggi >= ($toleransi['min'] ?? 0) && $tinggi <= ($toleransi['max'] ?? 0)) {
            $jumpNeed = (float) ($toleransi['vertical_jump'] ?? 0);
            $jump     = isset($data['vertical_jump']) ? (float) $data['vertical_jump'] : 0;
            if ($jumpNeed > 0 && $jump >= $jumpNeed) {
                return null;
            }

            return "Tinggi {$tinggi} cm berada di rentang toleransi. Wajib vertical jump minimal {$jumpNeed} cm.";
        }

        if ($tinggi < $min) {
            return "Tinggi badan minimal {$min} cm untuk {$syarat->nama_cabor}.";
        }

        return null;
    }

    public function berkasWajib(SeleksiCaborSyarat $syarat): array
    {
        $wajib = SeleksiPpopm::BERKAS_WAJIB;

        if ($syarat->wajib_piagam) {
            $wajib[] = 'piagam';
        }

        return $wajib;
    }
}
