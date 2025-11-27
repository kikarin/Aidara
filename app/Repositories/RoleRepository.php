<?php

namespace App\Repositories;

use App\Models\Role;
use App\Traits\RepositoryTrait;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoleRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(Role $model)
    {
        $this->model = $model;
        $this->with  = ['created_by_user', 'updated_by_user'];

    }

    public function customCreateEdit($data, $item = null)
    {
        $data += [
            'listBg'       => $this->model->listBg(),
            'listInitPage' => $this->model->listInitPage(),
        ];

        return $data;
    }

    public function customTable($table, $data, $request)
    {
        return $table->editColumn('is_allow_login', function ($d) {
            return $d->is_allow_login_badge;
        })->editColumn('is_vertical_menu', function ($d) {
            return $d->is_vertical_menu_badge;
        })->editColumn('bg', function ($d) {
            return $d->bg_badge;
        })->rawColumns(['is_allow_login', 'is_vertical_menu', 'bg']);
    }

    public function setPermission($id, $permission_id_array = [])
    {
        $record = $this->getById($id);
        try {
            DB::beginTransaction();
            $properties['old']   = $record->permissions()->pluck('name')->toArray();
            $permission_id_array = array_map('intval', $permission_id_array);
            $record->syncPermissions($permission_id_array);
            $properties['attributes'] = $record->permissions()->pluck('name')->toArray();
            activity()->event('Set Permission')->performedOn($record)->withProperties($properties)->log('User');

            Cache::forget("menus_for_role_$id");
            Cache::put('menus_version', now()->timestamp);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function customIndex($data)
    {
        $query = $this->model
            ->select('id', 'name', 'bg', 'init_page_login', 'is_allow_login', 'is_vertical_menu');

        // Apply search
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('init_page_login', 'like', '%'.$searchTerm.'%')
                    ->orWhere(function ($subQ) use ($searchTerm) {
                        if (stripos('ya', $searchTerm) !== false) {
                            $subQ->orWhere('is_allow_login', 1);
                        }
                        if (stripos('tidak', $searchTerm) !== false) {
                            $subQ->orWhere('is_allow_login', 0);
                        }
                    });
            });
        }

        // Apply sorting
        if (request('sort')) {
            $order = request('order', 'asc');
            // Mapping nama kolom frontend ke nama kolom database
            $sortMapping = [
                'name'             => 'name',
                'init_page_login'  => 'init_page_login',
                'is_allow_login'   => 'is_allow_login',
                'is_vertical_menu' => 'is_vertical_menu',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'name';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('name'); // default sort
        }

        // Apply pagination
        $perPage = (int) request('per_page', 10);
        if ($perPage === -1) {
            $allRoles         = $query->get();
            $transformedRoles = $allRoles->map(function ($role) {
                return [
                    'id'               => $role->id,
                    'name'             => $role->name,
                    'bg'               => $role->bg,
                    'init_page_login'  => $role->init_page_login,
                    'is_allow_login'   => $role->is_allow_login ? 'Ya' : 'Tidak',
                    'is_vertical_menu' => $role->is_vertical_menu ? 'Vertical' : 'Horizontal',
                ];
            });
            $data += [
                'roles' => $transformedRoles,
                'meta'  => [
                    'total'        => $transformedRoles->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];

            return $data;
        }
        $page           = (int) request('page', 1);
        $pageForLaravel = $page < 1 ? 1 : $page;
        $roles          = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data
        $transformedRoles = $roles->getCollection()->map(function ($role) {
            return [
                'id'               => $role->id,
                'name'             => $role->name,
                'bg'               => $role->bg,
                'init_page_login'  => $role->init_page_login,
                'is_allow_login'   => $role->is_allow_login ? 'Ya' : 'Tidak',
                'is_vertical_menu' => $role->is_vertical_menu ? 'Vertical' : 'Horizontal',
            ];
        });

        $data += [
            'roles' => $transformedRoles,
            'meta'  => [
                'total'        => $roles->total(),
                'current_page' => $roles->currentPage(),
                'per_page'     => $roles->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    public function getDetailWithUserTrack($id)
    {
        $item = $this->getFind($id);
        if (! $item) {
            return null;
        }

        return $this->model->
            with(['created_by_user', 'updated_by_user'])
                ->find($id);
    }

    /**
     * Validasi request role (create/edit) pakai rules dari RoleRequest
     */
    public function validateRoleRequest($request)
    {
        $rules = method_exists($request, 'rules') ? $request->rules() : [];

        return $request->validate($rules);
    }
}
