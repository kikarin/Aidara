<?php

namespace App\Http\Controllers;

use App\Http\Requests\PelatihKesehatanRequest;
use App\Repositories\PelatihKesehatanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class PelatihKesehatanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(PelatihKesehatanRepository $repository, Request $request)
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
            new Middleware("can:$permission Detail", only: ['show', 'getByPelatihId']),
            new Middleware("can:$permission Edit", only: ['update']),
            new Middleware("can:$permission Delete", only: ['destroy']),
        ];
    }

    public function store(PelatihKesehatanRequest $request, $pelatih_id)
    {
        Log::info('PelatihKesehatanController: START store method', ['pelatih_id_route' => $pelatih_id, 'request_all' => $request->all()]);

        $data               = $request->validated();
        $data['pelatih_id'] = $pelatih_id;

        Log::info('PelatihKesehatanController: store method - validated data', $data);

        $existingKesehatan = $this->repository->getByPelatihId($pelatih_id);

        if ($existingKesehatan) {
            Log::info('PelatihKesehatanController: Existing record found, updating.', ['id' => $existingKesehatan->id]);
            $model   = $this->repository->update($existingKesehatan->id, $data);
            $message = 'Data kesehatan pelatih berhasil diperbarui!';
        } else {
            Log::info('PelatihKesehatanController: No existing record, creating new.');
            $model   = $this->repository->create($data);
            $message = 'Data kesehatan pelatih berhasil ditambahkan!';
        }

        if ($model) {
            Log::info('PelatihKesehatanController: store method - model after save', $model->toArray());

            return redirect()->back()->with('success', $message)->with('kesehatanId', $model->id);
        } else {
            Log::error('PelatihKesehatanController: store method - Failed to save or update model.');

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan atau memperbarui data kesehatan pelatih.');
        }
    }

    public function show($id)
    {
        Log::info('PelatihKesehatanController: START show method', ['id' => $id]);
        $item = $this->repository->getById($id);
        if (! $item) {
            Log::info('PelatihKesehatanController: show method - Data not found.');

            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }
        Log::info('PelatihKesehatanController: show method - Data found.', $item->toArray());

        return response()->json($item);
    }

    public function update(PelatihKesehatanRequest $request, $pelatih_id, $id) // $id here is pelatih_kesehatan ID
    {
        Log::info('PelatihKesehatanController: START update method', ['pelatih_id_route' => $pelatih_id, 'id_kesehatan' => $id, 'request_all' => $request->all()]);

        try {
            $data               = $request->validated();
            $data['pelatih_id'] = $pelatih_id;

            Log::info('PelatihKesehatanController: update method - validated data', $data);

            $model = $this->repository->update($id, $data);

            if ($model) {
                Log::info('PelatihKesehatanController: update method - model after save', $model->toArray());

                return redirect()->back()->with('success', 'Data kesehatan pelatih berhasil diperbarui!')->with('kesehatanId', $model->id);
            } else {
                Log::error('PelatihKesehatanController: update method - Failed to find or update model.');

                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data kesehatan pelatih: Data tidak ditemukan.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating Pelatih Kesehatan: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data kesehatan pelatih: '.$e->getMessage());
        }
    }

    public function getByPelatihId($pelatihId)
    {
        $pelatihKesehatan = $this->repository->getByPelatihId($pelatihId);

        if (! $pelatihKesehatan) {
            return response()->json(null, 200);
        }

        return response()->json($pelatihKesehatan);
    }

    public function destroy($pelatih_id, $id)
    {
        Log::info('PelatihKesehatanController: START destroy method', ['pelatih_id_route' => $pelatih_id, 'id_kesehatan' => $id]);

        try {
            $this->repository->delete($id);
            Log::info('PelatihKesehatanController: destroy method - Data successfully deleted.', ['id' => $id]);

            return redirect()->back()->with('success', 'Data kesehatan pelatih berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Error deleting Pelatih Kesehatan: '.$e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Gagal menghapus data kesehatan pelatih: '.$e->getMessage());
        }
    }
}
