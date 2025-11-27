<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenagaPendukungKesehatanRequest;
use App\Repositories\TenagaPendukungKesehatanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class TenagaPendukungKesehatanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(TenagaPendukungKesehatanRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->commonData['kode_first_menu']  = $this->kode_menu;
        $this->commonData['kode_second_menu'] = null;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Add", only: ['store']),
            new Middleware("can:$permission Detail", only: ['show', 'getByTenagaPendukungId']),
            new Middleware("can:$permission Edit", only: ['update']),
            new Middleware("can:$permission Delete", only: ['destroy']),
        ];
    }

    public function store(TenagaPendukungKesehatanRequest $request, $tenaga_pendukung_id)
    {
        Log::info('TenagaPendukungKesehatanController: START store method', ['tenaga_pendukung_id_route' => $tenaga_pendukung_id, 'request_all' => $request->all()]);
        $data                        = $request->validated();
        $data['tenaga_pendukung_id'] = $tenaga_pendukung_id;
        Log::info('TenagaPendukungKesehatanController: store method - validated data', $data);
        $existingKesehatan = $this->repository->getByTenagaPendukungId($tenaga_pendukung_id);
        if ($existingKesehatan) {
            Log::info('TenagaPendukungKesehatanController: Existing record found, updating.', ['id' => $existingKesehatan->id]);
            $model   = $this->repository->update($existingKesehatan->id, $data);
            $message = 'Data kesehatan tenaga pendukung berhasil diperbarui!';
        } else {
            Log::info('TenagaPendukungKesehatanController: No existing record, creating new.');
            $model   = $this->repository->create($data);
            $message = 'Data kesehatan tenaga pendukung berhasil ditambahkan!';
        }
        if ($model) {
            Log::info('TenagaPendukungKesehatanController: store method - model after save', $model->toArray());

            return redirect()->back()->with('success', $message)->with('kesehatanId', $model->id);
        } else {
            Log::error('TenagaPendukungKesehatanController: store method - Failed to save or update model.');

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan atau memperbarui data kesehatan tenaga pendukung.');
        }
    }

    public function show($id)
    {
        Log::info('TenagaPendukungKesehatanController: START show method', ['id' => $id]);
        $item = $this->repository->getById($id);
        if (! $item) {
            Log::info('TenagaPendukungKesehatanController: show method - Data not found.');

            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }
        Log::info('TenagaPendukungKesehatanController: show method - Data found.', $item->toArray());

        return response()->json($item);
    }

    public function update(TenagaPendukungKesehatanRequest $request, $tenaga_pendukung_id, $id)
    {
        Log::info('TenagaPendukungKesehatanController: START update method', ['tenaga_pendukung_id_route' => $tenaga_pendukung_id, 'id_kesehatan' => $id, 'request_all' => $request->all()]);
        try {
            $data                        = $request->validated();
            $data['tenaga_pendukung_id'] = $tenaga_pendukung_id;
            Log::info('TenagaPendukungKesehatanController: update method - validated data', $data);
            $model = $this->repository->update($id, $data);
            if ($model) {
                Log::info('TenagaPendukungKesehatanController: update method - model after save', $model->toArray());

                return redirect()->back()->with('success', 'Data kesehatan tenaga pendukung berhasil diperbarui!')->with('kesehatanId', $model->id);
            } else {
                Log::error('TenagaPendukungKesehatanController: update method - Failed to find or update model.');

                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data kesehatan tenaga pendukung: Data tidak ditemukan.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating Tenaga Pendukung Kesehatan: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data kesehatan tenaga pendukung: '.$e->getMessage());
        }
    }

    public function getByTenagaPendukungId($tenagaPendukungId)
    {
        $kesehatan = $this->repository->getByTenagaPendukungId($tenagaPendukungId);
        if (! $kesehatan) {
            return response()->json(null, 200);
        }

        return response()->json($kesehatan);
    }

    public function destroy($tenaga_pendukung_id, $id)
    {
        Log::info('TenagaPendukungKesehatanController: START destroy method', ['tenaga_pendukung_id_route' => $tenaga_pendukung_id, 'id_kesehatan' => $id]);
        try {
            $this->repository->delete($id);
            Log::info('TenagaPendukungKesehatanController: destroy method - Data successfully deleted.', ['id' => $id]);

            return redirect()->back()->with('success', 'Data kesehatan tenaga pendukung berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Error deleting Tenaga Pendukung Kesehatan: '.$e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Gagal menghapus data kesehatan tenaga pendukung: '.$e->getMessage());
        }
    }
}
