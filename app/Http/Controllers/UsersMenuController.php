<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsersMenuRequest;
use App\Models\UsersMenu;
use App\Repositories\PermissionRepository;
use App\Repositories\UsersMenuRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UsersMenuController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $request;

    private $repository;

    private $permissionRepository;

    public function __construct(Request $request, UsersMenuRepository $repository, PermissionRepository $permissionRepository)
    {
        $this->repository           = $repository;
        $this->permissionRepository = $permissionRepository;
        $this->initialize();
        $this->request                        = UsersMenuRequest::createFromBase($request);
        $this->route                          = 'menus';
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

    /**
     * Get menus for API with permission filtering
     * This endpoint is used by the sidebar component
     */
    public function getMenus()
    {
        try {
            $menus = $this->repository->getMenus();

            return response()->json([
                'success' => true,
                'data'    => $menus,
                'message' => 'Menus retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => [],
                'message' => 'Failed to retrieve menus: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API endpoint for data table (admin interface)
     * This shows all menus regardless of permission for management purposes
     */
    public function apiIndex()
    {
        try {
            $query = UsersMenu::with(['rel_users_menu', 'permission'])
                ->orderBy('urutan')
                ->get();

            // Susun data secara hierarki
            $menus   = [];
            $counter = 1;

            // Ambil menu utama (rel = 0) terlebih dahulu
            foreach ($query->where('rel', 0)->sortBy('urutan') as $menu) {
                $prefix  = '';
                $menus[] = [
                    'no'         => $counter++,
                    'id'         => $menu->id,
                    'name'       => $prefix.$menu->nama,
                    'code'       => $menu->kode,
                    'icon'       => $menu->icon,
                    'url'        => $menu->url,
                    'order'      => $menu->urutan,
                    'permission' => optional($menu->permission)->name ?? '-',
                ];

                // Level 1 children
                foreach ($query->where('rel', $menu->id)->sortBy('urutan') as $child) {
                    $prefix  = '===';
                    $menus[] = [
                        'no'         => $counter++,
                        'id'         => $child->id,
                        'name'       => $prefix.$child->nama,
                        'code'       => $child->kode,
                        'icon'       => $child->icon,
                        'url'        => $child->url,
                        'order'      => $child->urutan,
                        'permission' => optional($child->permission)->name ?? '-',
                    ];

                    // Level 2 children
                    foreach ($query->where('rel', $child->id)->sortBy('urutan') as $grandChild) {
                        $prefix  = '======';
                        $menus[] = [
                            'no'         => $counter++,
                            'id'         => $grandChild->id,
                            'name'       => $prefix.$grandChild->nama,
                            'code'       => $grandChild->kode,
                            'icon'       => $grandChild->icon,
                            'url'        => $grandChild->url,
                            'order'      => $grandChild->urutan,
                            'permission' => optional($grandChild->permission)->name ?? '-',
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $menus,
                'meta'    => [
                    'total'        => count($menus),
                    'current_page' => 1,
                    'per_page'     => count($menus),
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
                'listUsersMenu' => $this->repository->listDropdown(),
                'permissions'   => $this->permissionRepository->getAll()->pluck('name', 'id'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menu data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(UsersMenuRequest $request)
    {
        $data = $this->repository->validateMenuRequest($request);
        $this->repository->create($data);

        return redirect()->route('menus.index')->with('success', 'Menu berhasil ditambahkan!');
    }

    public function update(UsersMenuRequest $request, $id)
    {
        $data = $this->repository->validateMenuRequest($request);
        $menu = $this->repository->getById($id);
        $menu->update($data);

        return redirect()->route('menus.index')->with('success', 'Menu berhasil diperbarui!');
    }
}
