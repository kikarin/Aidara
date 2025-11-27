<?php

namespace App\Repositories;

use App\Models\AtletParameterUmum;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AtletParameterUmumRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(AtletParameterUmum $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        Log::info('AtletParameterUmumRepository: create method called with data', $data);
        $data = $this->customDataCreateUpdate($data);

        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        Log::info('AtletParameterUmumRepository: update method called with data', ['id' => $id, 'data' => $data]);
        $record = $this->model->find($id);

        if ($record) {
            $processedData = $this->customDataCreateUpdate($data, $record);
            $record->update($processedData);
            Log::info('AtletParameterUmumRepository: update method - record updated', $record->toArray());

            return $record;
        }

        Log::warning('AtletParameterUmumRepository: update method - record not found for update', ['id' => $id]);

        return null;
    }

    public function delete($id)
    {
        Log::info('AtletParameterUmumRepository: delete method called (hard delete)', ['id' => $id]);
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            $record->forceDelete();
            Log::info('AtletParameterUmumRepository: record successfully hard-deleted', ['id' => $id]);

            return true;
        }

        Log::warning('AtletParameterUmumRepository: record not found for deletion', ['id' => $id]);

        return false;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        Log::info('AtletParameterUmumRepository: customDataCreateUpdate method called', ['data_before_processing' => $data]);
        $userId = Auth::check() ? Auth::id() : null;

        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        Log::info('AtletParameterUmumRepository: customDataCreateUpdate method - data after processing', $data);

        return $data;
    }

    public function getByAtletId($atletId)
    {
        return $this->model->where('atlet_id', $atletId)->with('mstParameter')->get();
    }

    public function upsertByAtletId($atletId, array $parameterData)
    {
        $userId = Auth::check() ? Auth::id() : null;
        
        // Collect mst_parameter_id yang akan di-update untuk tracking
        $parameterIdsToKeep = [];
        
        foreach ($parameterData as $param) {
            if (isset($param['mst_parameter_id']) && isset($param['nilai']) && $param['nilai'] !== '') {
                $parameterIdsToKeep[] = $param['mst_parameter_id'];
                
                // Cek apakah record sudah ada (termasuk yang soft deleted)
                $existing = $this->model->withTrashed()
                    ->where('atlet_id', $atletId)
                    ->where('mst_parameter_id', $param['mst_parameter_id'])
                    ->first();
                
                if ($existing) {
                    // Jika sudah ada (termasuk soft deleted), restore jika perlu dan update
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update([
                        'nilai'      => $param['nilai'],
                        'updated_by' => $userId,
                    ]);
                } else {
                    // Jika belum ada, create baru
                $this->model->create([
                    'atlet_id'         => $atletId,
                    'mst_parameter_id' => $param['mst_parameter_id'],
                    'nilai'            => $param['nilai'],
                    'created_by'       => $userId,
                    'updated_by'       => $userId,
                ]);
            }
            }
        }
        
        // Soft delete parameter yang tidak ada di request (jika ada)
        // Hanya delete yang belum di-soft delete sebelumnya
        if (!empty($parameterIdsToKeep)) {
            $this->model->where('atlet_id', $atletId)
                ->whereNotIn('mst_parameter_id', $parameterIdsToKeep)
                ->whereNull('deleted_at') // Hanya yang belum di-soft delete
                ->delete();
        } else {
            // Jika semua parameter kosong, soft delete semua yang belum di-soft delete
            $this->model->where('atlet_id', $atletId)
                ->whereNull('deleted_at')
                ->delete();
        }
    }
}
