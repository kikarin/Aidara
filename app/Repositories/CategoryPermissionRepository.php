<?php

namespace App\Repositories;

use App\Models\CategoryPermission;
use App\Traits\RepositoryTrait;

class CategoryPermissionRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(CategoryPermission $model)
    {
        $this->model = $model;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        if ($record == null) {
            if (empty($data['sequence'])) {
                $last_sequence    = $this->getLastSequence();
                $last_sequence    = ($last_sequence == null) ? 1 : $last_sequence->sequence + 1;
                $data['sequence'] = $last_sequence;
            }
        }

        return $data;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        return redirect()->route('permissions.index')->with('success', ($method == 'store') ? trans('message.success_add') : trans('message.success_update'));
    }

    public function callbackAfterDelete($model, $request)
    {
        // Hapus semua permission anak sebelum hapus kategori
        if ($model) {
            $model->permission()->delete();
        }

        return redirect()->route('permissions.index')->with('success', trans('message.success_delete'));
    }

    public function callbackAfterDeleteSelected($model, $request)
    {
        return redirect()->route('permissions.index')->with('success', trans('message.success_delete'));
    }

    public function getLastSequence()
    {
        $record = $this->model::orderBy('sequence', 'desc')->first();

        return $record;
    }

    public function getAll_OrderSequence()
    {
        return $this->model::orderBy('sequence', 'asc')->get();
    }

    public function customIndex($data)
    {
        $data += [
            'categories' => $this->model
                ->with('permission')
                ->orderBy('sequence')
                ->get()
                ->map(function ($cat) {
                    return [
                        'id'          => $cat->id,
                        'name'        => $cat->name,
                        'sequence'    => $cat->sequence,
                        'permissions' => $cat->permission->map(function ($perm) {
                            return [
                                'id'   => $perm->id,
                                'name' => $perm->name,
                            ];
                        }),
                    ];
                }),
        ];

        return $data;
    }

    /**
     * Validasi request untuk create/edit
     */
    public function validateCategoryRequest($request)
    {
        $rules = method_exists($request, 'rules') ? $request->rules() : [];

        return $request->validate($rules);
    }
}
