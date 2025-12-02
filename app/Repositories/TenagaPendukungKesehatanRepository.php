<?php

namespace App\Repositories;

use App\Models\TenagaPendukungKesehatan;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TenagaPendukungKesehatanRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(TenagaPendukungKesehatan $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        Log::info('TenagaPendukungKesehatanRepository: create method called with data', $data);
        $data = $this->customDataCreateUpdate($data);

        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        Log::info('TenagaPendukungKesehatanRepository: update method called with data', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);
        if ($record) {
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            Log::info('TenagaPendukungKesehatanRepository: update method - record updated', $record->toArray());

            return $record;
        }
        Log::warning('TenagaPendukungKesehatanRepository: update method - record not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('TenagaPendukungKesehatanRepository: delete method called (hard delete)', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);
        if ($record) {
            $record->forceDelete();
            Log::info('TenagaPendukungKesehatanRepository: record successfully hard-deleted', ['id' => $id]);

            return true;
        }
        Log::warning('TenagaPendukungKesehatanRepository: record not found for deletion', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        Log::info('TenagaPendukungKesehatanRepository: customDataCreateUpdate method called', ['data_before_processing' => $data]);
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;
        Log::info('TenagaPendukungKesehatanRepository: customDataCreateUpdate method - data after processing', $data);

        return $data;
    }

    public function getByTenagaPendukungId($tenagaPendukungId)
    {
        Log::info('TenagaPendukungKesehatanRepository: getByTenagaPendukungId method called', ['tenaga_pendukung_id' => $tenagaPendukungId]);
        $data = $this->model->with(['created_by_user', 'updated_by_user'])->where('tenaga_pendukung_id', $tenagaPendukungId)->first();
        if ($data) {
            Log::info('TenagaPendukungKesehatanRepository: getByTenagaPendukungId method - data found', $data->toArray());
        } else {
            Log::info('TenagaPendukungKesehatanRepository: getByTenagaPendukungId method - no data found');
        }

        return $data;
    }

    public function getById($id)
    {
        return $this->model->with(['created_by_user', 'updated_by_user'])->find($id);
    }
}
