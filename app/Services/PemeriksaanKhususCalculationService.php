<?php

namespace App\Services;

class PemeriksaanKhususCalculationService
{
    /**
     * Parse number dengan support comma, dot, dan format waktu (mm:ss atau hh:mm:ss)
     */
    public static function parseNumber($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        $strValue = trim((string) $value);
        if (empty($strValue)) {
            return null;
        }

        // Deteksi format waktu (ada titik dua)
        if (strpos($strValue, ':') !== false) {
            return self::parseTimeToSeconds($strValue);
        }

        // Replace comma with dot (Indonesian format)
        $normalizedValue = str_replace(',', '.', $strValue);
        $parsed = (float) $normalizedValue;

        return is_nan($parsed) ? null : $parsed;
    }

    /**
     * Konversi format waktu ke detik
     * Support format: mm:ss, hh:mm:ss, mm:ss.mmm, atau hh:mm:ss.mmm
     * Contoh: 
     * - "00:45" = 45 detik
     * - "02:30" = 150 detik
     * - "01:02:30" = 3750 detik
     * - "00:12.94" = 12.94 detik (dengan milidetik)
     * - "00:12:34.567" = 754.567 detik (dengan milidetik)
     * 
     * @param string $timeString
     * @return float|null
     */
    private static function parseTimeToSeconds(string $timeString): ?float
    {
        $parts = explode(':', $timeString);
        
        if (count($parts) === 2) {
            // Format mm:ss atau mm:ss.mmm
            $minutes = (int) $parts[0];
            
            // Cek apakah ada milidetik di bagian detik
            $secondsPart = $parts[1];
            if (strpos($secondsPart, '.') !== false) {
                // Ada milidetik, parse sebagai float
                $seconds = (float) $secondsPart;
            } else {
                // Tidak ada milidetik, parse sebagai integer
                $seconds = (int) $secondsPart;
            }
            
            return (float) (($minutes * 60) + $seconds);
        } elseif (count($parts) === 3) {
            // Format hh:mm:ss atau hh:mm:ss.mmm
            $hours = (int) $parts[0];
            $minutes = (int) $parts[1];
            
            // Cek apakah ada milidetik di bagian detik
            $secondsPart = $parts[2];
            if (strpos($secondsPart, '.') !== false) {
                // Ada milidetik, parse sebagai float
                $seconds = (float) $secondsPart;
            } else {
                // Tidak ada milidetik, parse sebagai integer
                $seconds = (int) $secondsPart;
            }
            
            return (float) (($hours * 3600) + ($minutes * 60) + $seconds);
        }
        
        return null;
    }

    /**
     * Hitung persentase performa berdasarkan nilai aktual dan target
     * 
     * @param string|null $nilaiAktual
     * @param string|null $target
     * @param string $performaArah 'max' atau 'min'
     * @return array ['persentase_performa' => float (max 100), 'persentase_riil' => float (bisa > 100)]
     */
    public static function calculatePerforma($nilaiAktual, $target, $performaArah = 'max'): array
    {
        $aktual = self::parseNumber($nilaiAktual);
        $targetValue = self::parseNumber($target);

        if ($aktual === null || $targetValue === null || $targetValue <= 0) {
            return [
                'persentase_performa' => null,
                'persentase_riil' => null,
            ];
        }

        $persentaseRiil = null;
        
        if ($performaArah === 'min') {
            // Semakin kecil nilai semakin baik
            // Contoh: target 12 detik, aktual 14 detik = 85.71% (12/14 * 100)
            $persentaseRiil = ($targetValue / $aktual) * 100;
        } else {
            // Semakin besar nilai semakin baik (default)
            // Contoh: target 80, aktual 70 = 87.5% (70/80 * 100)
            $persentaseRiil = ($aktual / $targetValue) * 100;
        }

        // Persentase untuk perhitungan (capped at 100%)
        $persentasePerforma = min(100, max(0, $persentaseRiil));

        return [
            'persentase_performa' => round($persentasePerforma, 2),
            'persentase_riil' => round($persentaseRiil, 2),
        ];
    }

    /**
     * Tentukan predikat berdasarkan persentase performa
     * 
     * @param float|null $persentase
     * @return string|null
     */
    public static function getPredikat($persentase): ?string
    {
        if ($persentase === null) {
            return null;
        }

        if ($persentase >= 0 && $persentase < 30) {
            return 'sangat_kurang';
        } elseif ($persentase >= 30 && $persentase < 60) {
            return 'kurang';
        } elseif ($persentase >= 60 && $persentase < 85) {
            return 'sedang';
        } elseif ($persentase >= 85 && $persentase < 100) {
            return 'mendekati_target';
        } else { // >= 100
            return 'target';
        }
    }

    /**
     * Get label predikat dalam bahasa Indonesia
     */
    public static function getPredikatLabel($predikat): string
    {
        $labels = [
            'sangat_kurang' => 'Sangat Kurang',
            'kurang' => 'Kurang',
            'sedang' => 'Sedang',
            'mendekati_target' => 'Mendekati Target',
            'target' => 'Target',
        ];

        return $labels[$predikat] ?? '-';
    }

    /**
     * Get color class untuk predikat (untuk UI)
     */
    public static function getPredikatColorClass($predikat): string
    {
        $colors = [
            'sangat_kurang' => 'bg-red-500 text-white',
            'kurang' => 'bg-orange-500 text-white',
            'sedang' => 'bg-yellow-500 text-white',
            'mendekati_target' => 'bg-green-400 text-white',
            'target' => 'bg-green-600 text-white',
        ];

        return $colors[$predikat] ?? 'bg-gray-500 text-white';
    }

    /**
     * Hitung rata-rata array nilai (ignore null)
     */
    public static function calculateAverage(array $values): ?float
    {
        $filtered = array_filter($values, fn($v) => $v !== null);
        
        if (empty($filtered)) {
            return null;
        }

        return round(array_sum($filtered) / count($filtered), 2);
    }
}

