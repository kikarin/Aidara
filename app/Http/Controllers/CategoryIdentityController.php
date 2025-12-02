<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryIdentityRequest;
use App\Repositories\CategoryIdentityRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CategoryIdentityController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(CategoryIdentityRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = CategoryIdentityRequest::createFromBase($request);
        $this->initialize();
        $this->commonData['kode_first_menu']  = 'MASTER';
        $this->commonData['kode_second_menu'] = 'IDENTITY';
    }

    public static function middleware(): array
    {
        $permission = 'Identity';

        return [
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }
}
