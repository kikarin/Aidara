<?php

namespace App\Repositories;

use App\Models\AtletOrangTua;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AtletOrangTuaRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(AtletOrangTua $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        Log::info('AtletOrangTuaRepository: create method called with data', $data);
        $data = $this->customDataCreateUpdate($data);

        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        Log::info('AtletOrangTuaRepository: update method called with data', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);

        if ($record) {
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            Log::info('AtletOrangTuaRepository: update method - record updated', $record->toArray());

            return $record;
        }

        Log::warning('AtletOrangTuaRepository: update method - record not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('AtletOrangTuaRepository: delete method called (hard delete)', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            $record->forceDelete();
            Log::info('AtletOrangTuaRepository: record successfully hard-deleted', ['id' => $id]);

            return true;
        }

        Log::warning('AtletOrangTuaRepository: record not found for deletion', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        Log::info('AtletOrangTuaRepository: customDataCreateUpdate method called', ['data_before_processing' => $data]);
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        Log::info('AtletOrangTuaRepository: customDataCreateUpdate method - data after processing', $data);

        return $data;
    }

    public function getByAtletId($atletId)
    {
        Log::info('AtletOrangTuaRepository: getByAtletId method called', ['atlet_id' => $atletId]);
        $data = $this->model->with(['created_by_user', 'updated_by_user'])->where('atlet_id', $atletId)->first();
        if ($data) {
            Log::info('AtletOrangTuaRepository: getByAtletId method - data found', $data->toArray());
        } else {
            Log::info('AtletOrangTuaRepository: getByAtletId method - no data found');
        }

        return $data;
    }

    public function getById($id)
    {
        return $this->model->with(['created_by_user', 'updated_by_user'])->find($id);
    }
}
