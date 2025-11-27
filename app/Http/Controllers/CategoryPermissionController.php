<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryPermissionRequest;
use App\Repositories\CategoryPermissionRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CategoryPermissionController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(CategoryPermissionRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = CategoryPermissionRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'permissions';
        $this->commonData['kode_first_menu']  = 'USERS-MANAGEMENT';
        $this->commonData['kode_second_menu'] = 'PERMISSION';
    }

    public static function middleware(): array
    {
        $permission = 'Permission';

        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function store(CategoryPermissionRequest $request)
    {
        $data = $this->repository->validateCategoryRequest($request);
        $this->repository->create($data);

        return redirect()->route('permissions.index')->with('success', 'Category Permission created successfully');
    }

    public function update(CategoryPermissionRequest $request, $id)
    {
        $data = $this->repository->validateCategoryRequest($request);
        $this->repository->update($id, $data);

        return redirect()->route('permissions.index')->with('success', 'Category Permission updated successfully');
    }
}
