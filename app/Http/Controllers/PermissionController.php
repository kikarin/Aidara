<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Repositories\CategoryPermissionRepository;
use App\Repositories\PermissionRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $categoryPermissionRepository;

    private $request;

    public function __construct(PermissionRepository $repository, CategoryPermissionRepository $categoryPermissionRepository, Request $request)
    {
        $this->repository                   = $repository;
        $this->categoryPermissionRepository = $categoryPermissionRepository;
        $this->request                      = PermissionRequest::createFromBase($request);
        $this->initialize();
        $this->commonData['kode_first_menu']  = 'USERS-MANAGEMENT';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);
        if (! is_array($data)) {
            return $data;
        }

        return inertia('modules/permissions/PermissionCreate', $data);
    }

    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);
        if (! is_array($data)) {
            return $data;
        }

        return inertia('modules/permissions/PermissionEdit', $data);
    }

    public function show($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);

        return inertia('modules/permissions/PermissionDetail', $data);
    }

    public function store(PermissionRequest $request)
    {
        return $this->repository->customCreateEdit([], null);
    }

    public function update(PermissionRequest $request, $id)
    {
        return $this->repository->customCreateEdit([], $id);
    }
}
