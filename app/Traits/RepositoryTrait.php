<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait RepositoryTrait
{
    public $with = [];

    public $withCount = [];

    public $orderDefault = 'created_at';

    public $orderDefaultSort = 'desc';

    public $orderByColumnsArray = [];

    public $selectColumn = [];

    public $whereHas = [];

    public $limit = null;

    public function getInstanceModel()
    {
        return $this->model;
    }

    public function getAll($data = [], $is_pagination = false, $is_count = false)
    {
        $record = $this->model::with($this->with)->withCount($this->withCount);
        if (count($this->selectColumn) > 0) {
            $record = $record->select($this->selectColumn);
        }
        if (count($this->orderByColumnsArray) > 0) {
            foreach ($this->orderByColumnsArray as $column => $direction) {
                $record = $record->orderBy($column, $direction);
            }
        } else {
            $record->orderBy($this->orderDefault, $this->orderDefaultSort);
        }
        if (count($this->whereHas) > 0) {
            foreach ($this->whereHas as $key => $value) {
                $record->whereHas($value);
            }
        }
        if (method_exists($this->model::class, 'scopeFilter')) {
            $record = $record->filter($data);
        }
        if (method_exists($this->model::class, 'scopeIdInNotIn')) {
            $record = $record->idInNotIn($data);
        }
        if ($this->limit != null) {
            $record = $record->limit($this->limit);
        }
        $record = $this->customGetAll($record, $data);
        if ($is_count == true) {
            $record = $record->count();
        } else {
            if ($is_pagination == true) {
                $record = $record->paginate(@$data['per_page'])->withQueryString();
            } else {
                $record = $record->get();
            }
        }

        return $record;
    }

    public function getCountAll()
    {
        return $this->model::count();
    }

    public function getById($id)
    {
        $record = $this->model::with($this->with)->withCount($this->withCount);
        $record = $this->customGetById($record);
        $record = $record->findOrFail($id);

        return $record;
    }

    public function getBySlug($slug)
    {
        $record = $this->model::with($this->with)->withCount($this->withCount)->where('slug', $slug)->first();

        return $record;
    }

    public function getFind($id)
    {
        $record = $this->model::with($this->with)->withCount($this->withCount);
        $record = $this->customGetById($record);
        $record = $record->find($id);

        return $record;
    }

    public function create($data)
    {
        try {
            DB::beginTransaction();
            $data = $this->customDataCreateUpdate($data);

            // $data = array_map(function($value) {
            //     return is_string($value) ? trim($value) : $value;
            // }, $data);

            $record = $this->model::create($data);
            $record = $this->callbackAfterStoreOrUpdate($record, $data);
            DB::commit();

            return $record;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function update($id, $data)
    {
        try {
            DB::beginTransaction();
            $record            = $this->getById($id);
            $record_sebelumnya = clone $record;
            $data              = $this->customDataCreateUpdate($data, $record);

            // $data = array_map(function($value) {
            //     return is_string($value) ? trim($value) : $value;
            // }, $data);

            $record->update($data);
            $record = $this->callbackAfterStoreOrUpdate($record, $data, 'update', $record_sebelumnya);
            DB::commit();

            return $record;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $record = $this->getById($id);
            $model  = $record;
            $model->delete();
            DB::commit();

            return $record;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function delete_selected($array_id)
    {
        if (! is_array($array_id)) {
            $array_id = [];
        }

        if (empty($array_id)) {
            return;
        }

        $record = $this->model->whereIn('id', $array_id)->get();
        foreach ($record as $item) {
            $this->callbackAfterDelete($item, request());
        }
        $this->model->whereIn('id', $array_id)->delete();
        $this->callbackAfterDeleteSelected($record, request());
    }

    public function getDataTable($data = [])
    {
        $data   = $this->customDataDatatable($data);
        $record = $this->model::select([$this->model->getTable().'.*'])->with($this->with)->withCount($this->withCount);
        if (@$data['orderDefault'] == null) {
            if (count($this->orderByColumnsArray) > 0) {
                foreach ($this->orderByColumnsArray as $column => $direction) {
                    $record = $record->orderBy($column, $direction);
                }
            } else {
                $record->orderBy($this->orderDefault, $this->orderDefaultSort);
            }
        }
        if (count($this->whereHas) > 0) {
            foreach ($this->whereHas as $key => $value) {
                $record->whereHas($value);
            }
        }
        if (method_exists($this->model::class, 'scopeFilter')) {
            $record = $record->filter($data);
        }
        $record = $this->customRecordDatatable($record, $data);

        return $record;
    }

    public function getPaginate($request_get)
    {
        $record = $this->model::query();
        $record = $record->with($this->with)->withCount($this->withCount);
        $record = $record->filter($request_get)->paginate($request_get['per_page'])->withQueryString();

        return $record;
    }

    public function customGetAll($record)
    {
        return $record;
    }

    public function customGetById($record)
    {
        return $record;
    }

    public function customIndex($data)
    {
        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        return $data;
    }

    public function customProperty($function = null, $data = [])
    {
        return true;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        return $data;
    }

    public function callbackBeforeStoreOrUpdate($data, $method)
    {
        return [
            'error' => 0,
            'data'  => $data,
        ];
    }

    public function customShow($data, $item = null)
    {
        return $data;
    }

    public function customDataDatatable($data)
    {
        $data += request()->all();

        return $data;
    }

    public function customTable($table, $param, $request)
    {
        return $table;
    }

    public function customRecordDatatable($record, $data)
    {
        return $record;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        return $model;
    }

    public function callbackAfterDelete($model, $request)
    {
        return $model;
    }

    public function callbackAfterDeleteSelected($model, $request)
    {
        return $model;
    }

    public function uploadFileCustom($file, $direktori)
    {
        if ($files = $file) {
            $destinationPath = "public/$direktori";
            $profileImage    = date('YmdHis').'-'.RandomString(20).'.'.$files->getClientOriginalExtension();
            $file->storeAs($destinationPath, $profileImage);
            $data_file['filename']  = $profileImage;
            $data_file['type_file'] = $files->getClientOriginalExtension();
            $data_file['size']      = Storage::size($destinationPath.'/'.$profileImage);

            return $data_file;
        } else {
            return [];
        }
    }

    public function deleteFileCustom($file, $direktori)
    {
        $file_path = "{$direktori}/".$file;
        if (Storage::disk('public')->exists($file_path)) {
            Storage::disk('public')->delete($file_path);
        } else {
            return false;
        }
    }

    public function export($data)
    {
        return $this->getAll($data);
    }

    public function dropdown($data = [])
    {
        return $this->getAll($data)->pluck('nama', 'id')->toArray();
    }
}
