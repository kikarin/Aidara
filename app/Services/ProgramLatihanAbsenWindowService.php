<?php

namespace App\Services;

use App\Models\ProgramLatihan;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ProgramLatihanAbsenWindowService
{
    public function isWithinWindow(ProgramLatihan $program): bool
    {
        if (!$program->absen_jam_mulai || !$program->absen_jam_selesai) {
            return true;
        }

        $now = Carbon::now('Asia/Jakarta')->format('H:i:s');
        $start = $this->normalizeTime((string) $program->absen_jam_mulai);
        $end = $this->normalizeTime((string) $program->absen_jam_selesai);

        return $now >= $start && $now <= $end;
    }

    public function assertWithinWindow(ProgramLatihan $program): void
    {
        if ($this->isWithinWindow($program)) {
            return;
        }

        $start = substr($this->normalizeTime((string) $program->absen_jam_mulai), 0, 5);
        $end = substr($this->normalizeTime((string) $program->absen_jam_selesai), 0, 5);

        throw ValidationException::withMessages([
            'waktu' => "Absen hanya dapat dilakukan pukul {$start}–{$end} WIB.",
        ]);
    }

    public function windowLabel(ProgramLatihan $program): ?string
    {
        if (!$program->absen_jam_mulai || !$program->absen_jam_selesai) {
            return null;
        }

        $start = substr($this->normalizeTime((string) $program->absen_jam_mulai), 0, 5);
        $end = substr($this->normalizeTime((string) $program->absen_jam_selesai), 0, 5);

        return "{$start} – {$end} WIB";
    }

    private function normalizeTime(string $time): string
    {
        if (strlen($time) === 5) {
            return $time . ':00';
        }

        return $time;
    }
}
