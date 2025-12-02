<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtletKesehatanRequest;
use App\Repositories\AtletKesehatanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class AtletKesehatanController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(AtletKesehatanRepository $repository, Request $request)
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
            new Middleware("can:$permission Detail", only: ['show', 'getByAtletId']),
            new Middleware("can:$permission Edit", only: ['update']),
            new Middleware("can:$permission Delete", only: ['destroy']),
        ];
    }

    public function store(AtletKesehatanRequest $request, $atlet_id)
    {
        Log::info('AtletKesehatanController: START store method', ['atlet_id_route' => $atlet_id, 'request_all' => $request->all()]);

        $data             = $request->validated();
        $data['atlet_id'] = $atlet_id;

        Log::info('AtletKesehatanController: store method - validated data', $data);

        $existingKesehatan = $this->repository->getByAtletId($atlet_id);

        if ($existingKesehatan) {
            Log::info('AtletKesehatanController: Existing record found, updating.', ['id' => $existingKesehatan->id]);
            $model   = $this->repository->update($existingKesehatan->id, $data);
            $message = 'Data kesehatan atlet berhasil diperbarui!';
        } else {
            Log::info('AtletKesehatanController: No existing record, creating new.');
            $model   = $this->repository->create($data);
            $message = 'Data kesehatan atlet berhasil ditambahkan!';
        }

        if ($model) {
            Log::info('AtletKesehatanController: store method - model after save', $model->toArray());

            return redirect()->back()->with('success', $message)->with('kesehatanId', $model->id);
        } else {
            Log::error('AtletKesehatanController: store method - Failed to save or update model.');

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan atau memperbarui data kesehatan atlet.');
        }
    }

    public function show($id)
    {
        Log::info('AtletKesehatanController: START show method', ['id' => $id]);
        $item = $this->repository->getById($id);
        if (! $item) {
            Log::info('AtletKesehatanController: show method - Data not found.');

            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }
        Log::info('AtletKesehatanController: show method - Data found.', $item->toArray());

        return response()->json($item);
    }

    public function update(AtletKesehatanRequest $request, $atlet_id, $id) // $id here is atlet_kesehatan ID
    {
        Log::info('AtletKesehatanController: START update method', ['atlet_id_route' => $atlet_id, 'id_kesehatan' => $id, 'request_all' => $request->all()]);

        try {
            $data             = $request->validated();
            $data['atlet_id'] = $atlet_id;

            Log::info('AtletKesehatanController: update method - validated data', $data);

            $model = $this->repository->update($id, $data);

            if ($model) {
                Log::info('AtletKesehatanController: update method - model after save', $model->toArray());

                return redirect()->back()->with('success', 'Data kesehatan atlet berhasil diperbarui!')->with('kesehatanId', $model->id);
            } else {
                Log::error('AtletKesehatanController: update method - Failed to find or update model.');

                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data kesehatan atlet: Data tidak ditemukan.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating Atlet Kesehatan: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data kesehatan atlet: '.$e->getMessage());
        }
    }

    public function getByAtletId($atletId)
    {
        $atletKesehatan = $this->repository->getByAtletId($atletId);

        if (! $atletKesehatan) {
            return response()->json(null, 200);
        }

        return response()->json($atletKesehatan);
    }

    public function destroy($atlet_id, $id)
    {
        Log::info('AtletKesehatanController: START destroy method', ['atlet_id_route' => $atlet_id, 'id_kesehatan' => $id]);

        try {
            $this->repository->delete($id);
            Log::info('AtletKesehatanController: destroy method - Data successfully deleted.', ['id' => $id]);

            return redirect()->back()->with('success', 'Data kesehatan atlet berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Error deleting Atlet Kesehatan: '.$e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Gagal menghapus data kesehatan atlet: '.$e->getMessage());
        }
    }
}
