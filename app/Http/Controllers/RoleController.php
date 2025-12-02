<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\CategoryPermissionRepository;
use App\Repositories\RoleRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $categoryPermissionRepository;

    private $request;

    public function __construct(RoleRepository $repository, CategoryPermissionRepository $categoryPermissionRepository, Request $request)
    {
        $this->repository                   = $repository;
        $this->categoryPermissionRepository = $categoryPermissionRepository;
        $this->request                      = RoleRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'roles';
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

    public function set_permission($id)
    {
        $item = $this->repository->getById($id);
        // Ambil semua permission dan group by category
        $permissions        = Permission::with('category_permission')->get();
        $groupedPermissions = $permissions->groupBy(function ($perm) {
            return optional($perm->category_permission)->name ?? 'Other';
        });
        // Ambil permission id yang sudah dimiliki role
        $rolePermissionIds = $item->permissions->pluck('id')->toArray();
        $this->commonData['titlePage'] .= ' Set Permission';
        $data = $this->commonData + [
            'item'             => $item,
            'permissionGroups' => $groupedPermissions->map(function ($perms, $cat) {
                return [
                    'label'    => $cat,
                    'children' => $perms->map(function ($p) {
                        return ['id' => $p->id, 'label' => $p->name];
                    })->values(),
                ];
            })->values(),
            'selectedPermissions' => $rolePermissionIds,
        ];

        return inertia('modules/roles/SetPermissions', $data);
    }

    public function set_permission_action(Request $request)
    {
        $request->validate([
            'id' => 'required|digits_between:1,20|numeric',
        ]);
        $this->repository->setPermission($request->id, $request->permission_id);

        return redirect()->route($this->route.'.set-permission', $request->id)->with('success', trans('message.success_save'));
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['roles'],
            'meta' => [
                'total'        => $data['meta']['total'],
                'current_page' => $data['meta']['current_page'],
                'per_page'     => $data['meta']['per_page'],
                'search'       => $data['meta']['search'],
                'sort'         => $data['meta']['sort'],
                'order'        => $data['meta']['order'],
            ],
        ]);
    }

    public function store(RoleRequest $request)
    {
        $data = $this->repository->validateRoleRequest($request);
        Role::create($data);

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan!');
    }

    public function update(RoleRequest $request, $id)
    {
        $data = $this->repository->validateRoleRequest($request);
        $role = Role::findOrFail($id);
        $role->update($data);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui!');
    }
}
