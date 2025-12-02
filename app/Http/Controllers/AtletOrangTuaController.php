<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtletOrangTuaRequest;
use App\Repositories\AtletOrangTuaRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class AtletOrangTuaController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(AtletOrangTuaRepository $repository, Request $request)
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

    public function store(AtletOrangTuaRequest $request, $atlet_id)
    {
        Log::info('AtletOrangTuaController: START store method', ['atlet_id_route' => $atlet_id, 'request_all' => $request->all()]);

        $data             = $request->validated();
        $data['atlet_id'] = $atlet_id;

        Log::info('AtletOrangTuaController: store method - validated data', $data);

        $existingOrangTua = $this->repository->getByAtletId($atlet_id);

        if ($existingOrangTua) {
            Log::info('AtletOrangTuaController: Existing record found, updating.', ['id' => $existingOrangTua->id]);
            $model   = $this->repository->update($existingOrangTua->id, $data);
            $message = 'Data orang tua/wali berhasil diperbarui!';
        } else {
            Log::info('AtletOrangTuaController: No existing record, creating new.');
            $model   = $this->repository->create($data);
            $message = 'Data orang tua/wali berhasil ditambahkan!';
        }

        if ($model) {
            Log::info('AtletOrangTuaController: store method - model after save', $model->toArray());

            return redirect()->back()->with('success', $message)->with('orangTuaId', $model->id);
        } else {
            Log::error('AtletOrangTuaController: store method - Failed to save or update model.');

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan atau memperbarui data orang tua/wali.');
        }
    }

    public function show($id)
    {
        Log::info('AtletOrangTuaController: START show method', ['id' => $id]);
        $item = $this->repository->getById($id);
        if (! $item) {
            Log::info('AtletOrangTuaController: show method - Data not found.');

            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }
        Log::info('AtletOrangTuaController: show method - Data found.', $item->toArray());

        return response()->json($item);
    }

    public function update(AtletOrangTuaRequest $request, $atlet_id, $id) // $id here is atlet_orang_tua ID
    {
        Log::info('AtletOrangTuaController: START update method', ['atlet_id_route' => $atlet_id, 'id_orang_tua' => $id, 'request_all' => $request->all()]);

        try {
            $data             = $request->validated();
            $data['atlet_id'] = $atlet_id;

            Log::info('AtletOrangTuaController: update method - validated data', $data);

            $model = $this->repository->update($id, $data);

            if ($model) {
                Log::info('AtletOrangTuaController: update method - model after save', $model->toArray());

                return redirect()->back()->with('success', 'Data orang tua/wali berhasil diperbarui!')->with('orangTuaId', $model->id);
            } else {
                Log::error('AtletOrangTuaController: update method - Failed to find or update model.');

                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data orang tua/wali: Data tidak ditemukan.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating Atlet Orang Tua: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data orang tua/wali: '.$e->getMessage());
        }
    }

    public function getByAtletId($atletId)
    {
        $atletOrangTua = $this->repository->getByAtletId($atletId);

        if (! $atletOrangTua) {
            return response()->json(null, 200);
        }

        return response()->json($atletOrangTua);
    }

    public function destroy($atlet_id, $id)
    {
        Log::info('AtletOrangTuaController: START destroy method', ['atlet_id_route' => $atlet_id, 'id_orang_tua' => $id]);

        try {
            $this->repository->delete($id);
            Log::info('AtletOrangTuaController: destroy method - Data successfully deleted.', ['id' => $id]);

            return redirect()->back()->with('success', 'Data orang tua/wali berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Error deleting Atlet Orang Tua: '.$e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Gagal menghapus data orang tua/wali: '.$e->getMessage());
        }
    }
}
