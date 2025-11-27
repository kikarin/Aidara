<?php

namespace App\Repositories;

use App\Models\ProgramLatihan;
use App\Models\Pemeriksaan;
use App\Models\Turnamen;
use Illuminate\Support\Facades\Auth;

class HomeRepository
{
    /**
     * Get home data for mobile (latest 5 from each module)
     */
    public function getHomeData($request)
    {
        $user = Auth::user();

        // Get latest 5 program latihan
        $programLatihan = $this->getLatestProgramLatihan($user);

        // Get latest 5 pemeriksaan
        $pemeriksaan = $this->getLatestPemeriksaan($user);

        // Get latest 5 turnamen
        $turnamen = $this->getLatestTurnamen($user);

        return [
            'programLatihan' => $programLatihan,
            'pemeriksaan'    => $pemeriksaan,
            'turnamen'       => $turnamen,
        ];
    }

    /**
     * Get latest 5 program latihan with role-based filtering
     */
    protected function getLatestProgramLatihan($user)
    {
        $query = ProgramLatihan::with(['caborKategori.cabor'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        // Apply role-based filtering
        $this->applyRoleFilterProgramLatihan($query, $user);

        return $query->get()->map(function ($item) {
            return [
                'id'         => $item->id,
                'nama'       => $item->nama_program,
                'cabor'      => $item->caborKategori->cabor->nama ?? '-',
                'kategori'   => $item->caborKategori->nama        ?? '-',
                'periode'    => $this->formatPeriodeForMobile($item->periode_mulai, $item->periode_selesai),
                'created_at' => $item->created_at,
            ];
        });
    }

    /**
     * Get latest 5 pemeriksaan with role-based filtering
     */
    protected function getLatestPemeriksaan($user)
    {
        $query = Pemeriksaan::with(['caborKategori.cabor', 'tenagaPendukung'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        // Apply role-based filtering
        $this->applyRoleFilterPemeriksaan($query, $user);

        return $query->get()->map(function ($item) {
            return [
                'id'         => $item->id,
                'nama'       => $item->nama_pemeriksaan,
                'cabor'      => $item->caborKategori->cabor->nama ?? '-',
                'kategori'   => $item->caborKategori->nama        ?? '-',
                'tanggal'    => $item->tanggal_pemeriksaan,
                'status'     => $this->mapStatus($item->status),
                'created_at' => $item->created_at,
            ];
        });
    }

    /**
     * Get latest 5 turnamen with role-based filtering
     */
    protected function getLatestTurnamen($user)
    {
        $query = Turnamen::with(['caborKategori.cabor', 'tingkat', 'juara'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        // Apply role-based filtering
        $this->applyRoleFilterTurnamen($query, $user);

        return $query->get()->map(function ($item) {
            return [
                'id'         => $item->id,
                'nama'       => $item->nama,
                'cabor'      => $item->caborKategori->cabor->nama ?? '-',
                'kategori'   => $item->caborKategori->nama        ?? '-',
                'periode'    => $this->formatPeriodeForMobile($item->tanggal_mulai, $item->tanggal_selesai),
                'tingkat'    => $item->tingkat->nama ?? '-',
                'lokasi'     => $item->lokasi,
                'created_at' => $item->created_at,
            ];
        });
    }

    /**
     * Apply role-based filtering for program latihan
     */
    protected function applyRoleFilterProgramLatihan($query, $user)
    {
        if ($user->current_role_id == 35) { // Atlet
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('caborKategoriAtlet', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('atlet_id', $user->atlet->id);
                });
            });
        } elseif ($user->current_role_id == 36) { // Pelatih
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('caborKategoriPelatih', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('pelatih_id', $user->pelatih->id);
                });
            });
        } elseif ($user->current_role_id == 37) { // Tenaga Pendukung
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('tenagaPendukung', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('tenaga_pendukung_id', $user->tenagaPendukung->id);
                });
            });
        }
    }

    /**
     * Apply role-based filtering for pemeriksaan
     */
    protected function applyRoleFilterPemeriksaan($query, $user)
    {
        if ($user->current_role_id == 35) { // Atlet
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('caborKategoriAtlet', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('atlet_id', $user->atlet->id);
                });
            });
        } elseif ($user->current_role_id == 36) { // Pelatih
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('caborKategoriPelatih', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('pelatih_id', $user->pelatih->id);
                });
            });
        } elseif ($user->current_role_id == 37) { // Tenaga Pendukung
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('tenagaPendukung', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('tenaga_pendukung_id', $user->tenagaPendukung->id);
                });
            });
        }
    }

    /**
     * Apply role-based filtering for turnamen
     */
    protected function applyRoleFilterTurnamen($query, $user)
    {
        if ($user->current_role_id == 35) { // Atlet
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('caborKategoriAtlet', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('atlet_id', $user->atlet->id);
                });
            });
        } elseif ($user->current_role_id == 36) { // Pelatih
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('caborKategoriPelatih', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('pelatih_id', $user->pelatih->id);
                });
            });
        } elseif ($user->current_role_id == 37) { // Tenaga Pendukung
            $query->whereHas('caborKategori', function ($sub_query) use ($user) {
                $sub_query->whereHas('tenagaPendukung', function ($sub_sub_query) use ($user) {
                    $sub_sub_query->where('tenaga_pendukung_id', $user->tenagaPendukung->id);
                });
            });
        }
    }

    /**
     * Format periode for mobile
     */
    protected function formatPeriodeForMobile($startDate, $endDate): string
    {
        if (!$startDate || !$endDate) {
            return '-';
        }

        return $startDate . ' s/d ' . $endDate;
    }

    /**
     * Map status to Indonesian
     */
    protected function mapStatus($status)
    {
        return match ($status) {
            'selesai'  => 'Sudah',
            'sebagian' => 'Sebagian',
            'belum'    => 'Belum',
            default    => $status,
        };
    }
}
