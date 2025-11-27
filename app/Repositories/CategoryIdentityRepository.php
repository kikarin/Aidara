<?php

namespace App\Repositories;

use App\Models\CategoryIdentity;
use App\Traits\RepositoryTrait;

class CategoryIdentityRepository
{
    use RepositoryTrait;

    public function __construct(CategoryIdentity $model)
    {
        $this->model = $model;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        if (@$data['file']) {
            $uploadFile   = $this->uploadFileCustom($data['file'], 'category-identity/photo');
            $data['file'] = @$uploadFile['filename'];
        }

        return $data;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        if ($method == 'update') {
            if (! empty($data['file']) and $record_sebelumnya->file != '') {
                $this->deleteFileCustom($record_sebelumnya->file, 'category-identity/photo');
            }
        }

        return redirect()->route('identity.index', ['category_identity_id' => $model->id])->with('success', ($method == 'store') ? trans('message.success_add') : trans('message.success_update'));
    }

    public function callbackAfterDelete($model, $request)
    {
        return redirect()->route('identity.index')->with('success', trans('message.success_delete'));
    }
}
