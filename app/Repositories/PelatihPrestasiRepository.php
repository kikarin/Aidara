<?php

namespace App\Repositories;

use App\Models\PelatihPrestasi;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PelatihPrestasiRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PelatihPrestasi $model)
    {
        $this->model = $model;
        $this->with  = [
            'created_by_user',
            'updated_by_user',
            'tingkat',
            'kategoriPrestasiPelatih',
            'kategoriAtlet',
            'kategoriPeserta',
        ];
    }

    public function create(array $data)
    {
        Log::info('PelatihPrestasiRepository: create', $data);
        $anggotaBeregu = $data['anggota_beregu'] ?? [];
        unset($data['anggota_beregu']);

        $data = $this->customDataCreateUpdate($data);
        
        // Jika beregu, buat prestasi untuk semua anggota
        if (($data['jenis_prestasi'] ?? 'individu') === 'ganda/mixed/beregu/double' && !empty($anggotaBeregu)) {
            return $this->createBereguPrestasi($data, $anggotaBeregu);
        }

        $model = $this->model->create($data);
        return $model;
    }

    protected function createBereguPrestasi(array $data, array $anggotaBeregu)
    {
        // Buat prestasi untuk pelatih utama
        $mainPrestasi = $this->model->create($data);
        $prestasiGroupId = $mainPrestasi->id;

        // Update prestasi utama dengan prestasi_group_id
        $mainPrestasi->update(['prestasi_group_id' => $prestasiGroupId]);

        // Buat prestasi untuk setiap anggota beregu
        foreach ($anggotaBeregu as $pelatihId) {
            $anggotaData = $data;
            $anggotaData['pelatih_id'] = $pelatihId;
            $anggotaData['prestasi_group_id'] = $prestasiGroupId;
            // Ambil kategori_peserta_id dari pelatih anggota
            $anggotaData = $this->customDataCreateUpdate($anggotaData);
            $this->model->create($anggotaData);
        }

        return $mainPrestasi;
    }

    public function update($id, array $data)
    {
        Log::info('PelatihPrestasiRepository: update', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);
        if ($record) {
            $anggotaBeregu = $data['anggota_beregu'] ?? [];
            unset($data['anggota_beregu']);

            $processedData = $this->customDataCreateUpdate($data, $record);
            
            // Jika berubah dari individu ke beregu atau sebaliknya, perlu handle khusus
            $isBeregu = ($processedData['jenis_prestasi'] ?? $record->jenis_prestasi) === 'ganda/mixed/beregu/double';
            $wasBeregu = $record->jenis_prestasi === 'ganda/mixed/beregu/double';

            if ($isBeregu && !empty($anggotaBeregu)) {
                // Update beregu prestasi
                $this->updateBereguPrestasi($record, $processedData, $anggotaBeregu);
            } elseif ($wasBeregu && !$isBeregu) {
                // Hapus anggota beregu jika berubah ke individu
                $this->deleteBereguPrestasi($record->prestasi_group_id ?? $record->id);
            } else {
                // Update normal
                $record->update($processedData);
            }

            Log::info('PelatihPrestasiRepository: updated', $record->toArray());
            return $record;
        }
        Log::warning('PelatihPrestasiRepository: not found for update', ['id' => $id]);

        return null;
    }

    protected function updateBereguPrestasi($mainPrestasi, array $data, array $anggotaBeregu)
    {
        $prestasiGroupId = $mainPrestasi->prestasi_group_id ?? $mainPrestasi->id;
        
        // Update prestasi utama
        $data['prestasi_group_id'] = $prestasiGroupId;
        $mainPrestasi->update($data);

        // Hapus prestasi anggota lama (kecuali yang masih dipilih)
        $existingAnggotaIds = $this->model->where('prestasi_group_id', $prestasiGroupId)
            ->where('pelatih_id', '!=', $mainPrestasi->pelatih_id)
            ->whereNotIn('pelatih_id', $anggotaBeregu)
            ->pluck('id')
            ->toArray();
        
        $this->model->whereIn('id', $existingAnggotaIds)->delete();

        // Update atau buat prestasi untuk anggota baru
        foreach ($anggotaBeregu as $pelatihId) {
            $anggotaData = $data;
            $anggotaData['pelatih_id'] = $pelatihId;
            $anggotaData['prestasi_group_id'] = $prestasiGroupId;

            // Cek apakah sudah ada prestasi untuk pelatih ini
            $existingAnggotaPrestasi = $this->model->where('prestasi_group_id', $prestasiGroupId)
                ->where('pelatih_id', $pelatihId)
                ->first();

            if ($existingAnggotaPrestasi) {
                // Ambil kategori_peserta_id dari pelatih anggota
                $anggotaData = $this->customDataCreateUpdate($anggotaData);
                $existingAnggotaPrestasi->update($anggotaData);
            } else {
                // Ambil kategori_peserta_id dari pelatih anggota
                $anggotaData = $this->customDataCreateUpdate($anggotaData);
                $this->model->create($anggotaData);
            }
        }
    }

    protected function deleteBereguPrestasi($prestasiGroupId)
    {
        // Hapus prestasi anggota (kecuali yang utama)
        $this->model->where('prestasi_group_id', $prestasiGroupId)
            ->where('id', '!=', $prestasiGroupId)
            ->delete();
    }

    public function delete($id)
    {
        Log::info('PelatihPrestasiRepository: delete', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);
        if ($record) {
            $record->forceDelete();
            Log::info('PelatihPrestasiRepository: deleted', ['id' => $id]);

            return true;
        }
        Log::warning('PelatihPrestasiRepository: not found for delete', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        // Ambil kategori_peserta_id dari pelatih jika tidak ada
        if (!isset($data['kategori_peserta_id']) && isset($data['pelatih_id'])) {
            $pelatih = \App\Models\Pelatih::with('kategoriPesertas')->find($data['pelatih_id']);
            if ($pelatih && $pelatih->kategoriPesertas->isNotEmpty()) {
                // Ambil kategori peserta pertama
                $data['kategori_peserta_id'] = $pelatih->kategoriPesertas->first()->id;
            }
        }

        return $data;
    }

    public function getByPelatihId($pelatihId)
    {
        return $this->model->where('pelatih_id', $pelatihId)->get();
    }

    public function getById($id)
    {
        return $this->model->with($this->with)->with('kategoriPeserta')->find($id);
    }

    public function apiIndex($pelatihId)
    {
        $query = $this->model->where('pelatih_id', $pelatihId);

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_event', 'like', '%'.$search.'%')
                    ->orWhere('peringkat', 'like', '%'.$search.'%')
                    ->orWhere('keterangan', 'like', '%'.$search.'%')
                    ->orWhere('tanggal', 'like', '%'.$search.'%');
            });
        }
        // Sort
        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama_event', 'tingkat_id', 'tanggal', 'peringkat', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $order);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }
        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);
        if ($perPage === -1) {
            $all         = $query->with($this->with)->get();
            $transformed = collect($all)->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'nama_event' => $item->nama_event,
                    'tingkat'    => $item->tingkat ? ['id' => $item->tingkat->id, 'nama' => $item->tingkat->nama] : null,
                    'tanggal'    => $item->tanggal,
                    'peringkat'  => $item->peringkat,
                    'keterangan' => $item->keterangan,
                ];
            });

            return [
                'data' => $transformed,
                'meta' => [
                    'total'        => $transformed->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
        }
        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->with($this->with)->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();
        $transformed     = collect($items->items())->map(function ($item) {
            return [
                'id'         => $item->id,
                'nama_event' => $item->nama_event,
                'tingkat'    => $item->tingkat ? ['id' => $item->tingkat->id, 'nama' => $item->tingkat->nama] : null,
                'tanggal'    => $item->tanggal,
                'peringkat'  => $item->peringkat,
                'keterangan' => $item->keterangan,
            ];
        });

        return [
            'data' => $transformed,
            'meta' => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];
    }

    public function handleCreate($pelatihId)
    {
        return Inertia::render('modules/pelatih/prestasi/Create', [
            'pelatihId' => (int) $pelatihId,
        ]);
    }

    public function handleEdit($pelatihId, $id)
    {
        $prestasi = $this->getById($id);
        if (! $prestasi) {
            return redirect()->back()->with('error', 'Prestasi tidak ditemukan');
        }

        return Inertia::render('modules/pelatih/prestasi/Edit', [
            'pelatihId' => (int) $pelatihId,
            'item'      => $prestasi,
        ]);
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->forceDelete();
    }
}
