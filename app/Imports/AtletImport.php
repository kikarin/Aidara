<?php

namespace App\Imports;

use App\Models\Atlet;
use App\Models\AtletKesehatan;
use App\Models\AtletOrangTua;
use App\Models\Cabor;
use App\Models\CaborKategoriAtlet;
use App\Models\MstKategoriPeserta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AtletImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $rowCount = 0;

    private $successCount = 0;

    private $errorCount = 0;

    private $errors = [];

    /**
     * @param  array  $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    /**
     * Convert Excel date serial number to YYYY-MM-DD format
     *
     * @param  mixed  $excelDate
     * @return string|null
     */
    private function convertExcelDate($excelDate)
    {
        // Kosong → null
        if (empty($excelDate)) {
            return null;
        }
    
        // Jika string → trim biar tidak ada trailing/hidden space
        if (is_string($excelDate)) {
            $excelDate = trim($excelDate);
    
            // Jika kosong setelah trim
            if ($excelDate === '') {
                return null;
            }
        }
    
        // Jika numeric → Excel serial number
        if (is_numeric($excelDate)) {
            return \Carbon\Carbon::createFromTimestamp(($excelDate - 25569) * 86400)
                ->format('Y-m-d');
        }
    
        // Jika format dd/mm/yyyy
        if (is_string($excelDate) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $excelDate)) {
            try {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $excelDate)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null; // Tidak error, aman
            }
        }
    
        // Jika format dd-mm-yyyy
        if (is_string($excelDate) && preg_match('/^\d{2}\-\d{2}\-\d{4}$/', $excelDate)) {
            try {
                return \Carbon\Carbon::createFromFormat('d-m-Y', $excelDate)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
    
        // Fallback parse otomatis
        $timestamp = strtotime($excelDate);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
    
        return null;
    }
    

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->rowCount++;

            DB::beginTransaction();

            try {
                // Jika nik kosong, gunakan nisn untuk mencari atlet yang sudah ada
                $nik  = !empty($row['nik']) ? $row['nik'] : null;
                $nisn = !empty($row['nisn']) ? $row['nisn'] : null;

                // Cari atlet berdasarkan nik atau nisn
                $atlet = null;
                if ($nik) {
                    $atlet = Atlet::withTrashed()->where('nik', $nik)->first();
                }

                // Jika tidak ditemukan berdasarkan nik dan ada nisn, cari berdasarkan nisn
                if (!$atlet && $nisn) {
                    $atlet = Atlet::withTrashed()->where('nisn', $nisn)->first();
                }

                // Jika nik kosong, tetap biarkan kosong (tidak diisi dengan nisn)
                // Untuk atlet baru: nik akan null jika kosong
                // Untuk atlet yang sudah ada: jika nik dari Excel kosong, jangan update field nik

                $data = [
                    'nisn'              => $nisn,
                    'nama'              => $row['nama']          ?? null,
                    'jenis_kelamin'     => $row['jenis_kelamin'] ?? null,
                    'tempat_lahir'      => $row['tempat_lahir']  ?? null,
                    'tanggal_lahir'     => $this->convertExcelDate($row['tanggal_lahir'] ?? null),
                    'agama'             => $row['agama']             ?? null,
                    'alamat'            => $row['alamat']            ?? null,
                    'sekolah'           => $row['sekolah']           ?? null,
                    'kelas_sekolah'     => $row['kelas_sekolah']     ?? null,
                    'ukuran_baju'       => $row['ukuran_baju']       ?? null,
                    'ukuran_celana'     => $row['ukuran_celana']     ?? null,
                    'ukuran_sepatu'     => $row['ukuran_sepatu']     ?? null,
                    'kecamatan_id'      => $row['kecamatan_id']      ?? null,
                    'kelurahan_id'      => $row['kelurahan_id']      ?? null,
                    'kategori_atlet_id' => $row['kategori_atlet_id'] ?? null,
                    'no_hp'             => $row['no_hp']             ?? null,
                    'email'             => $row['email']             ?? null,
                    'is_active'         => $row['is_active']         ?? 1,
                ];

                if ($atlet) {
                    // Jika atlet sudah ada
                    if ($atlet->trashed()) {
                        $atlet->restore();
                    }

                    // Jika nik dari Excel ada, update nik
                    // Jika nik dari Excel kosong, jangan update field nik (biarkan seperti yang sudah ada)
                    if ($nik) {
                        $data['nik'] = $nik;
                    }
                    // Jika nik kosong, tidak masukkan ke $data, sehingga nik di database tidak berubah

                    unset($data['id']);
                    $atlet->update($data);
                    $atletId = $atlet->id;
                } else {
                    // Jika atlet baru
                    // Jika nik kosong, set null (tidak diisi dengan nisn)
                    $data['nik'] = $nik; // akan null jika kosong
                    $atlet       = new Atlet($data);
                    $atlet->save();
                    $atletId = $atlet->id;
                }
                $this->successCount++;

                $orangTuaData = [
                    'atlet_id'           => $atletId,
                    'nama_ibu_kandung'   => $row['nama_ibu_kandung'] ?? null,
                    'tempat_lahir_ibu'   => $row['tempat_lahir_ibu'] ?? null,
                    'tanggal_lahir_ibu'  => $this->convertExcelDate($row['tanggal_lahir_ibu'] ?? null),
                    'alamat_ibu'         => $row['alamat_ibu']        ?? null,
                    'no_hp_ibu'          => $row['no_hp_ibu']         ?? null,
                    'pekerjaan_ibu'      => $row['pekerjaan_ibu']     ?? null,
                    'nama_ayah_kandung'  => $row['nama_ayah_kandung'] ?? null,
                    'tempat_lahir_ayah'  => $row['tempat_lahir_ayah'] ?? null,
                    'tanggal_lahir_ayah' => $this->convertExcelDate($row['tanggal_lahir_ayah'] ?? null),
                    'alamat_ayah'        => $row['alamat_ayah']       ?? null,
                    'no_hp_ayah'         => $row['no_hp_ayah']        ?? null,
                    'pekerjaan_ayah'     => $row['pekerjaan_ayah']    ?? null,
                    'nama_wali'          => $row['nama_wali']         ?? null,
                    'tempat_lahir_wali'  => $row['tempat_lahir_wali'] ?? null,
                    'tanggal_lahir_wali' => $this->convertExcelDate($row['tanggal_lahir_wali'] ?? null),
                    'alamat_wali'        => $row['alamat_wali']    ?? null,
                    'no_hp_wali'         => $row['no_hp_wali']     ?? null,
                    'pekerjaan_wali'     => $row['pekerjaan_wali'] ?? null,
                ];

                $orangTuaData = array_filter($orangTuaData, function ($value) {
                    return $value !== null;
                });

                // Log the data being saved for debugging
                Log::info('Saving orang tua data:', $orangTuaData);

                $orangTua = AtletOrangTua::withTrashed()->where('atlet_id', $atletId)->first();
                if ($orangTua) {
                    if ($orangTua->trashed()) {
                        $orangTua->restore();
                    }
                    unset($orangTuaData['id']);
                    $orangTua->update($orangTuaData);
                } else {
                    unset($orangTuaData['id']);
                    AtletOrangTua::create($orangTuaData);
                }

                $kesehatanData = [
                    'atlet_id'         => $atletId,
                    'tinggi_badan'     => $row['tinggi_badan']         ?? null,
                    'berat_badan'      => $row['berat_badan']          ?? null,
                    'penglihatan'      => $row['penglihatan']          ?? null,
                    'golongan_darah'   => $row['golongan_darah']       ?? null,
                    'riwayat_penyakit' => $row['riwayat_penyakit']     ?? null,
                    'alergi'           => $row['alergi']               ?? null,
                    'kelainan_jasmani' => $row['kelainan_jasmani']     ?? null,
                    'keterangan'       => $row['keterangan_kesehatan'] ?? null,
                ];

                $kesehatanData = array_filter($kesehatanData, function ($value) {
                    return $value !== null;
                });

                // Log the data being saved for debugging
                Log::info('Saving kesehatan data:', $kesehatanData);

                $kesehatan = AtletKesehatan::withTrashed()->where('atlet_id', $atletId)->first();
                if ($kesehatan) {
                    if ($kesehatan->trashed()) {
                        $kesehatan->restore();
                    }
                    unset($kesehatanData['id']);
                    $kesehatan->update($kesehatanData);
                } else {
                    unset($kesehatanData['id']);
                    AtletKesehatan::create($kesehatanData);
                }

                // Handle Kategori Peserta (PPOPM, KONI, NPCI) dari Excel
                // Baca kolom: kategori_peserta, jenis_peserta, atau kategori
                // Bisa berisi satu nilai atau multiple (dipisahkan koma)
                $kategoriPesertaInput = $row['kategori_peserta'] ?? $row['jenis_peserta'] ?? $row['kategori'] ?? $row['jenis'] ?? null;
                
                if ($kategoriPesertaInput && !empty(trim($kategoriPesertaInput))) {
                    $kategoriPesertaInput = trim($kategoriPesertaInput);
                    
                    // Split jika ada multiple kategori (dipisahkan koma, semicolon, atau slash)
                    $kategoriNames = preg_split('/[,;\/]+/', $kategoriPesertaInput);
                    $kategoriIds = [];
                    
                    foreach ($kategoriNames as $kategoriName) {
                        $kategoriName = strtoupper(trim($kategoriName));
                        if (empty($kategoriName)) {
                            continue;
                        }
                        
                        // Cari kategori peserta berdasarkan nama (case insensitive)
                        $kategoriPeserta = MstKategoriPeserta::whereRaw('UPPER(nama) = ?', [$kategoriName])->first();
                        
                        if ($kategoriPeserta) {
                            $kategoriIds[] = $kategoriPeserta->id;
                        } else {
                            // Log warning jika kategori tidak ditemukan
                            Log::warning('AtletImport: Kategori peserta tidak ditemukan', [
                                'atlet_id' => $atletId,
                                'kategori_name' => $kategoriName,
                                'available' => MstKategoriPeserta::pluck('nama')->toArray(),
                            ]);
                        }
                    }
                    
                    // Sync kategori peserta ke atlet (many-to-many)
                    if (!empty($kategoriIds)) {
                        $atlet->kategoriPesertas()->sync($kategoriIds);
                        Log::info('AtletImport: Synced kategori peserta', [
                            'atlet_id' => $atletId,
                            'kategori_ids' => $kategoriIds,
                        ]);
                    }
                }

                // Handle Cabang Olahraga dan Posisi dari Excel
                // Baca kolom: cabang_olahraga, cabor, atau cabang (untuk cabor)
                // Baca kolom: nomor_kelas_posisi, posisi, atau posisi_atlet (untuk posisi)
                $caborNama = $row['cabang_olahraga'] ?? $row['cabor'] ?? $row['cabang'] ?? null;
                $posisiAtlet = $row['nomor_kelas_posisi'] ?? $row['posisi'] ?? $row['posisi_atlet'] ?? $row['kelas'] ?? $row['nomor'] ?? null;

                if ($caborNama && !empty(trim($caborNama))) {
                    $caborNama = trim($caborNama);
                    
                    // Cari atau buat cabor berdasarkan nama
                    $cabor = Cabor::where('nama', $caborNama)->first();
                    if (!$cabor) {
                        // Buat cabor baru jika belum ada
                        $cabor = Cabor::create([
                            'nama' => $caborNama,
                            'deskripsi' => 'Dibuat otomatis dari import',
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                        Log::info('AtletImport: Created new cabor', ['cabor_id' => $cabor->id, 'nama' => $caborNama]);
                    }

                    // Cek apakah sudah ada relasi atlet ke cabor ini
                    $existingRelation = CaborKategoriAtlet::where('cabor_id', $cabor->id)
                        ->where('atlet_id', $atletId)
                        ->first();
                    
                    if ($existingRelation) {
                        // Update posisi jika sudah ada
                        $existingRelation->update([
                            'posisi_atlet' => $posisiAtlet ? trim($posisiAtlet) : null,
                            'updated_by' => Auth::id(),
                        ]);
                        Log::info('AtletImport: Updated cabor assignment', [
                            'atlet_id' => $atletId,
                            'cabor_id' => $cabor->id,
                            'posisi_atlet' => $posisiAtlet,
                        ]);
                    } else {
                        // Buat relasi baru tanpa kategori (cabor_kategori_id = null)
                        CaborKategoriAtlet::create([
                            'cabor_id' => $cabor->id,
                            'cabor_kategori_id' => null, // Langsung ke cabor tanpa kategori
                            'atlet_id' => $atletId,
                            'posisi_atlet' => $posisiAtlet ? trim($posisiAtlet) : null,
                            'is_active' => 1,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                        Log::info('AtletImport: Created new cabor assignment', [
                            'atlet_id' => $atletId,
                            'cabor_id' => $cabor->id,
                            'posisi_atlet' => $posisiAtlet,
                        ]);
                    }
                }

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

        // Log the full error for debugging
        Log::error('Import Error: '.$message, [
            'exception' => get_class($e),
            'trace'     => $e->getTraceAsString(),
        ]);

        // Handle database constraint violations
        if (str_contains($message, 'Integrity constraint violation')) {
            if (str_contains($message, 'Duplicate entry') && str_contains($message, 'atlets_nik_unique')) {
                return 'NIK sudah terdaftar (duplikat)';
            }
            if (str_contains($message, 'Column \'nik\' cannot be null')) {
                return 'NIK tidak boleh kosong';
            }
            if (str_contains($message, 'Column \'nama\' cannot be null')) {
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

        // Handle validation errors
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

        // Default error message with more details for debugging
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
