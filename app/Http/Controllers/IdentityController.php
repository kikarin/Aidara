<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentityReqeust;
use App\Repositories\CategoryIdentityRepository;
use App\Repositories\IdentityRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class IdentityController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $categoryIdentityRepository;

    private $request;

    public function __construct(IdentityRepository $repository, CategoryIdentityRepository $categoryIdentityRepository, Request $request)
    {
        $this->repository                 = $repository;
        $this->categoryIdentityRepository = $categoryIdentityRepository;
        $this->request                    = IdentityReqeust::createFromBase($request);
        $this->initialize();
        $this->commonData['kode_first_menu']  = 'MASTER';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));

        return [
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function save(Request $request)
    {
        $this->repository->save($request->all());

        return back()->with('success', trans('message.success_save'));
    }
}
