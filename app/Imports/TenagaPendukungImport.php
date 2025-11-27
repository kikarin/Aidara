<?php

namespace App\Imports;

use App\Models\TenagaPendukung;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TenagaPendukungImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $rowCount = 0;

    private $successCount = 0;

    private $errorCount = 0;

    private $errors = [];

    private function convertExcelDate($excelDate)
    {
        if (empty($excelDate)) {
            return null;
        }
        if (is_string($excelDate) && strtotime($excelDate) !== false) {
            return date('Y-m-d', strtotime($excelDate));
        }
        if (is_numeric($excelDate)) {
            $unixTimestamp = ($excelDate - 25569) * 86400;

            return date('Y-m-d', $unixTimestamp);
        }

        return null;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->rowCount++;
            DB::beginTransaction();
            try {
                $tp   = TenagaPendukung::withTrashed()->where('nik', $row['nik'])->first();
                $data = [
                    'nik'           => $row['nik']           ?? null,
                    'nama'          => $row['nama']          ?? null,
                    'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                    'tempat_lahir'  => $row['tempat_lahir']  ?? null,
                    'tanggal_lahir' => $this->convertExcelDate($row['tanggal_lahir'] ?? null),
                    'alamat'        => $row['alamat']       ?? null,
                    'kecamatan_id'  => $row['kecamatan_id'] ?? null,
                    'kelurahan_id'  => $row['kelurahan_id'] ?? null,
                    'no_hp'         => $row['no_hp']        ?? null,
                    'email'         => $row['email']        ?? null,
                    'is_active'     => $row['is_active']    ?? 1,
                ];
                if ($tp) {
                    if ($tp->trashed()) {
                        $tp->restore();
                    }
                    unset($data['id']);
                    $tp->update($data);
                } else {
                    $tp = new TenagaPendukung($data);
                    $tp->save();
                }
                $this->successCount++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->errorCount++;
                $errorMessage   = $this->getUserFriendlyErrorMessage($e);
                $this->errors[] = [
                    'row'   => $this->rowCount,
                    'error' => $errorMessage,
                    'data'  => $row,
                ];
                Log::error('Error importing row '.$this->rowCount.': '.$e->getMessage(), [
                    'row'       => $row,
                    'exception' => $e,
                ]);

                continue;
            }
        }

        return null;
    }

    private function getUserFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        Log::error('Import Error: '.$message, [
            'exception' => get_class($e),
            'trace'     => $e->getTraceAsString(),
        ]);
        if (str_contains($message, 'Integrity constraint violation')) {
            if (str_contains($message, 'Duplicate entry') && str_contains($message, 'tenaga_pendukungs_nik_unique')) {
                return 'NIK sudah terdaftar (duplikat)';
            }
            if (str_contains($message, "Column 'nik' cannot be null")) {
                return 'NIK tidak boleh kosong';
            }
            if (str_contains($message, "Column 'nama' cannot be null")) {
                return 'Nama tidak boleh kosong';
            }
            if (str_contains($message, 'foreign key constraint fails')) {
                if (str_contains($message, 'kecamatan_id')) {
                    return 'Kecamatan tidak ditemukan';
                }
                if (str_contains($message, 'kelurahan_id')) {
                    return 'Kelurahan tidak ditemukan';
                }

                return 'Data referensi tidak ditemukan';
            }
            if (str_contains($message, 'Incorrect date value')) {
                return 'Format tanggal tidak valid. Pastikan format tanggal adalah YYYY-MM-DD';
            }
        }
        if (str_contains($message, 'validation')) {
            if (str_contains($message, 'date_format')) {
                return 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD';
            }
            if (str_contains($message, 'email')) {
                return 'Format email tidak valid';
            }
            if (str_contains($message, 'numeric')) {
                return 'Nilai harus berupa angka';
            }

            return 'Data tidak valid: '.$message;
        }

        return 'Data tidak dapat disimpan: '.$e->getMessage();
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
