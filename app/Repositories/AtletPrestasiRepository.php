<?php

namespace App\Repositories;

use App\Models\AtletPrestasi;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AtletPrestasiRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(AtletPrestasi $model)
    {
        $this->model = $model;
        $this->with  = [
            'created_by_user',
            'updated_by_user',
            'tingkat',
        ];
    }

    public function create(array $data)
    {
        Log::info('AtletPrestasiRepository: create', $data);
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
        // Buat prestasi untuk atlet utama
        $mainPrestasi = $this->model->create($data);
        $prestasiGroupId = $mainPrestasi->id;

        // Update prestasi utama dengan prestasi_group_id
        $mainPrestasi->update(['prestasi_group_id' => $prestasiGroupId]);

        // Buat prestasi untuk setiap anggota beregu
        $anggotaPrestasiIds = [$mainPrestasi->id];
        foreach ($anggotaBeregu as $atletId) {
            $anggotaData = $data;
            $anggotaData['atlet_id'] = $atletId;
            $anggotaData['prestasi_group_id'] = $prestasiGroupId;
            // Ambil kategori_peserta_id dari atlet anggota
            $anggotaData = $this->customDataCreateUpdate($anggotaData);
            $anggotaPrestasi = $this->model->create($anggotaData);
            $anggotaPrestasiIds[] = $anggotaPrestasi->id;

            // Simpan ke pivot table
            \DB::table('atlet_prestasi_beregu')->insert([
                'prestasi_group_id' => $prestasiGroupId,
                'atlet_id' => $atletId,
                'atlet_prestasi_id' => $anggotaPrestasi->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Simpan atlet utama ke pivot table juga
        \DB::table('atlet_prestasi_beregu')->insert([
            'prestasi_group_id' => $prestasiGroupId,
            'atlet_id' => $data['atlet_id'],
            'atlet_prestasi_id' => $mainPrestasi->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $mainPrestasi;
    }

    public function update($id, array $data)
    {
        Log::info('AtletPrestasiRepository: update', ['id' => $id, 'data' => $data]);
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

            Log::info('AtletPrestasiRepository: updated', $record->toArray());
            return $record;
        }
        Log::warning('AtletPrestasiRepository: not found for update', ['id' => $id]);

        return null;
    }

    protected function updateBereguPrestasi($mainPrestasi, array $data, array $anggotaBeregu)
    {
        $prestasiGroupId = $mainPrestasi->prestasi_group_id ?? $mainPrestasi->id;
        
        // Update prestasi utama
        $data['prestasi_group_id'] = $prestasiGroupId;
        $mainPrestasi->update($data);

        // Hapus anggota lama
        \DB::table('atlet_prestasi_beregu')
            ->where('prestasi_group_id', $prestasiGroupId)
            ->where('atlet_id', '!=', $mainPrestasi->atlet_id)
            ->delete();

        // Hapus prestasi anggota lama (kecuali yang masih dipilih)
        $existingAnggotaIds = \DB::table('atlet_prestasi_beregu')
            ->where('prestasi_group_id', $prestasiGroupId)
            ->where('atlet_id', '!=', $mainPrestasi->atlet_id)
            ->pluck('atlet_prestasi_id')
            ->toArray();
        
        $this->model->whereIn('id', $existingAnggotaIds)
            ->where('atlet_id', '!=', $mainPrestasi->atlet_id)
            ->whereNotIn('atlet_id', $anggotaBeregu)
            ->delete();

        // Update atau buat prestasi untuk anggota baru
        foreach ($anggotaBeregu as $atletId) {
            $anggotaData = $data;
            $anggotaData['atlet_id'] = $atletId;
            $anggotaData['prestasi_group_id'] = $prestasiGroupId;

            // Cek apakah sudah ada prestasi untuk atlet ini
            $existingAnggotaPrestasi = $this->model->where('prestasi_group_id', $prestasiGroupId)
                ->where('atlet_id', $atletId)
                ->first();

            if ($existingAnggotaPrestasi) {
                // Ambil kategori_peserta_id dari atlet anggota
                $anggotaData = $this->customDataCreateUpdate($anggotaData);
                $existingAnggotaPrestasi->update($anggotaData);
                $anggotaPrestasiId = $existingAnggotaPrestasi->id;
            } else {
                // Ambil kategori_peserta_id dari atlet anggota
                $anggotaData = $this->customDataCreateUpdate($anggotaData);
                $anggotaPrestasi = $this->model->create($anggotaData);
                $anggotaPrestasiId = $anggotaPrestasi->id;
            }

            // Simpan ke pivot table
            \DB::table('atlet_prestasi_beregu')->updateOrInsert(
                [
                    'prestasi_group_id' => $prestasiGroupId,
                    'atlet_id' => $atletId,
                ],
                [
                    'atlet_prestasi_id' => $anggotaPrestasiId,
                    'updated_at' => now(),
                ]
            );
        }
    }

    protected function deleteBereguPrestasi($prestasiGroupId)
    {
        // Hapus dari pivot table
        \DB::table('atlet_prestasi_beregu')
            ->where('prestasi_group_id', $prestasiGroupId)
            ->delete();

        // Hapus prestasi anggota (kecuali yang utama)
        $anggotaPrestasiIds = \DB::table('atlet_prestasi_beregu')
            ->where('prestasi_group_id', $prestasiGroupId)
            ->pluck('atlet_prestasi_id')
            ->toArray();

        $this->model->whereIn('id', $anggotaPrestasiIds)
            ->where('id', '!=', $prestasiGroupId)
            ->delete();
    }

    public function delete($id)
    {
        Log::info('AtletPrestasiRepository: delete', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);
        if ($record) {
            $record->forceDelete();
            Log::info('AtletPrestasiRepository: deleted', ['id' => $id]);

            return true;
        }
        Log::warning('AtletPrestasiRepository: not found for delete', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        // Ambil kategori_peserta_id dari atlet jika tidak ada
        if (!isset($data['kategori_peserta_id']) && isset($data['atlet_id'])) {
            $atlet = \App\Models\Atlet::with('kategoriPesertas')->find($data['atlet_id']);
            if ($atlet && $atlet->kategoriPesertas->isNotEmpty()) {
                // Ambil kategori peserta pertama
                $data['kategori_peserta_id'] = $atlet->kategoriPesertas->first()->id;
            }
        }

        return $data;
    }

    public function getByAtletId($atletId)
    {
        return $this->model->where('atlet_id', $atletId)->get();
    }

    public function getById($id)
    {
        return $this->model->with($this->with)->with('kategoriPeserta')->find($id);
    }

    public function apiIndex($atletId)
    {
        $query = $this->model->where('atlet_id', $atletId);

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_event', 'like', "%$search%")
                    ->orWhere('juara', 'like', "%$search%")
                    ->orWhere('medali', 'like', "%$search%")
                    ->orWhere('keterangan', 'like', "%$search%")
                    ->orWhere('tanggal', 'like', "%$search%");
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
                    'juara'      => $item->juara,
                    'medali'     => $item->medali,
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
                'juara'      => $item->juara,
                'medali'     => $item->medali,
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

    public function handleCreate($atletId)
    {
        return Inertia::render('modules/atlet/prestasi/Create', [
            'atletId' => (int) $atletId,
        ]);
    }

    public function handleEdit($atletId, $id)
    {
        $prestasi = $this->getById($id);
        if (! $prestasi) {
            return redirect()->back()->with('error', 'Prestasi tidak ditemukan');
        }

        return Inertia::render('modules/atlet/prestasi/Edit', [
            'atletId' => (int) $atletId,
            'item'    => $prestasi,
        ]);
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->forceDelete();
    }
}
