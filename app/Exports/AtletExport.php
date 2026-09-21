<?php

namespace App\Exports;

use App\Repositories\AtletRepository;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AtletExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $repository;
    protected $request;

    public function __construct(AtletRepository $repository, $request = [])
    {
        $this->repository = $repository;
        $this->request = $request;
    }

    public function collection()
    {
        // Simpan request asli untuk restore nanti
        $originalRequest = request()->all();
        
        // Apply request parameters untuk filter/search/sort
        foreach ($this->request as $key => $value) {
            if (!in_array($key, ['per_page', 'page'])) {
                request()->merge([$key => $value]);
            }
        }
        
        // Set per_page ke -1 untuk mengambil semua data yang sesuai filter
        request()->merge(['per_page' => -1]);
        
        // Gunakan customIndex untuk mendapatkan data yang sama seperti di datatable
        $data = $this->repository->customIndex([]);
        
        // Restore original request
        request()->merge($originalRequest);
        
        return collect($data['atlets']);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Jenis Kelamin',
            'Usia',
            'Lama Bergabung',
            'Kategori Peserta',
            'Cabor',
        ];
    }

    public function map($row): array
    {
        // Format jenis kelamin
        $jenisKelamin = '-';
        if (isset($row['jenis_kelamin'])) {
            $jenisKelamin = $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($row['jenis_kelamin'] === 'P' ? 'Perempuan' : '-');
        }

        // Hitung usia
        $usia = '-';
        if (isset($row['tanggal_lahir']) && $row['tanggal_lahir']) {
            $today = new \DateTime();
            $birthDate = new \DateTime($row['tanggal_lahir']);
            $age = $today->diff($birthDate)->y;
            $usia = $age;
        }

        // Format lama bergabung
        $lamaBergabung = '-';
        if (isset($row['tanggal_bergabung']) && $row['tanggal_bergabung']) {
            $start = new \DateTime($row['tanggal_bergabung']);
            $now = new \DateTime();
            $interval = $now->diff($start);
            
            $tahun = $interval->y;
            $bulan = $interval->m;
            
            $result = '';
            if ($tahun > 0) {
                $result .= $tahun . ' tahun ';
            }
            if ($bulan > 0) {
                $result .= $bulan . ' bulan';
            }
            if (!$result) {
                $result = 'Kurang dari 1 bulan';
            }
            $lamaBergabung = trim($result);
        }

        // Format kategori peserta
        $kategoriPeserta = '-';
        if (isset($row['kategori_pesertas']) && is_array($row['kategori_pesertas']) && count($row['kategori_pesertas']) > 0) {
            $kategoriList = array_map(function ($item) {
                return is_array($item) ? ($item['nama'] ?? '-') : $item;
            }, $row['kategori_pesertas']);
            $kategoriPeserta = implode(', ', $kategoriList);
        }

        // Format cabor
        $cabor = '-';
        if (isset($row['cabor_kategori_atlet']) && is_array($row['cabor_kategori_atlet']) && count($row['cabor_kategori_atlet']) > 0) {
            // Filter untuk menghindari duplikasi berdasarkan cabor_id
            $uniqueCaborMap = [];
            foreach ($row['cabor_kategori_atlet'] as $item) {
                if (isset($item['cabor']['id'])) {
                    $caborId = $item['cabor']['id'];
                    if (!isset($uniqueCaborMap[$caborId])) {
                        $caborName = $item['cabor']['nama'] ?? '-';
                        $posisi = isset($item['posisi_atlet']) && $item['posisi_atlet'] ? " ({$item['posisi_atlet']})" : '';
                        $uniqueCaborMap[$caborId] = $caborName . $posisi;
                    }
                }
            }
            $cabor = implode(', ', array_values($uniqueCaborMap));
        }


        static $rowNumber = 0;
        $rowNumber++;
        
        return [
            $rowNumber, // No urut
            $row['nama'] ?? '-',
            $jenisKelamin,
            $usia,
            $lamaBergabung,
            $kategoriPeserta,
            $cabor,
        ];
    }

    public function title(): string
    {
        return 'Data Atlet';
    }
}

