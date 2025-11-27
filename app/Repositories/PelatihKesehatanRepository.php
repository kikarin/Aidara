<?php

namespace App\Repositories;

use App\Models\PelatihKesehatan;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PelatihKesehatanRepository
{
    use RepositoryTrait;

    protected $model;

    /**
     * Create a new class instance.
     */
    public function __construct(PelatihKesehatan $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        Log::info('PelatihKesehatanRepository: create method called with data', $data);
        $data = $this->customDataCreateUpdate($data);

        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        Log::info('PelatihKesehatanRepository: update method called with data', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);

        if ($record) {
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            Log::info('PelatihKesehatanRepository: update method - record updated', $record->toArray());

            return $record;
        }

        Log::warning('PelatihKesehatanRepository: update method - record not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('PelatihKesehatanRepository: delete method called (hard delete)', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            $record->forceDelete();
            Log::info('PelatihKesehatanRepository: record successfully hard-deleted', ['id' => $id]);

            return true;
        }

        Log::warning('PelatihKesehatanRepository: record not found for deletion', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        Log::info('PelatihKesehatanRepository: customDataCreateUpdate method called', ['data_before_processing' => $data]);
        $userId = Auth::check() ? Auth::id() : null;
        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        Log::info('PelatihKesehatanRepository: customDataCreateUpdate method - data after processing', $data);

        return $data;
    }

    public function getByPelatihId($pelatihId)
    {
        Log::info('PelatihKesehatanRepository: getByPelatihId method called', ['pelatih_id' => $pelatihId]);
        $data = $this->model->with(['created_by_user', 'updated_by_user'])->where('pelatih_id', $pelatihId)->first();
        if ($data) {
            Log::info('PelatihKesehatanRepository: getByPelatihId method - data found', $data->toArray());
        } else {
            Log::info('PelatihKesehatanRepository: getByPelatihId method - no data found');
        }

        return $data;
    }

    public function getById($id)
    {
        return $this->model->with(['created_by_user', 'updated_by_user'])->find($id);
    }
}
