<?php

namespace App\Repositories;

use App\Models\Identity;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class IdentityRepository
{
    use RepositoryTrait;

    private $cacheKeyApi = 'Identity_cache';

    protected $model;

    protected $categoryIdentityRepository;

    public function __construct(Identity $model, CategoryIdentityRepository $categoryIdentityRepository)
    {
        $this->model                      = $model;
        $this->categoryIdentityRepository = $categoryIdentityRepository;

        $this->route = 'identity';
    }

    public function listType()
    {
        return $this->model->listType();
    }

    public function getByKode($kode, $category_identity_id = null)
    {
        $record = $this->model::where('kode', $kode)->when(
            $category_identity_id != null,
            function ($query) use ($category_identity_id) {
                return $query->where('category_identity_id', $category_identity_id);
            }
        )->first();

        return $record;
    }

    public function save($data)
    {
        Artisan::call('cache:clear');

        foreach ($data['kode'] as $key => $value) {
            $get = $this->getByKode($value, $data['category_identity_id']);
            if ($get) {
                if ($get->type == $this->model::TYPE_FILE) {
                    if (@$data[$value]) {
                        $file_lama  = $get->file;
                        $uploadFile = $this->uploadFileCustom($data[$value], 'identity/photo');
                        $filename   = @$uploadFile['filename'];
                        $type_file  = $uploadFile['type_file'];
                        $this->update($get->id, ['value' => $filename, 'type_file' => $type_file]);
                        if ($get->file != '') {
                            $this->deleteFileCustom($file_lama, 'identity/photo');
                        }
                    }
                } else {
                    $this->update($get->id, ['value' => $data[$value]]);
                }
            }
        }
        $this->updateCache();

        return true;
    }

    public function getCache()
    {
        $data = Cache::remember($this->cacheKeyApi, 60, function () {
            $getAll  = $this->getAll();
            $getData = collect($getAll);

            return $getData;
        });

        return $data;
    }

    public function getCacheByKode($kode)
    {
        $getCache = $this->getCache();
        $getCache = $getCache->where('kode', $kode)->first();

        return $getCache;
    }

    public function forgetCache()
    {
        Cache::forget($this->cacheKeyApi);
    }

    public function updateCache()
    {
        $this->forgetCache();
        $this->getCache();
    }

    public function customIndex($data)
    {
        $data += [
            'request'  => request()->all(),
            'listType' => $this->listType(),
            'data'     => $this->categoryIdentityRepository->getAll(),
        ];

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $data += [
            'category_identity_id' => request()->input('category_identity_id'),
            'listType'             => $this->listType(),
        ];

        return $data;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        return redirect()->route($this->route.'.index', ['category_identity_id' => $model->category_identity_id])->with('success', ($method == 'store') ? trans('message.success_add') : trans('message.success_update'));
    }
}
