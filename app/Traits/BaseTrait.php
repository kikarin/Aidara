<?php

namespace App\Traits;

use App\Exports\GeneralExport;
use App\Repositories\UsersMenuRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Yajra\DataTables\Facades\DataTables;

trait BaseTrait
{
    use SupportBaseTrait;

    private $controllerName;

    private $titlePage;

    private $route;

    private $kode_menu;

    private $kode_second_menu = '';

    private $permission_main;

    private $commonData = [];

    private $check_permission = true;

    public function initialize()
    {
        $usersMenuRepository   = app(UsersMenuRepository::class);
        $this->controllerName  = $this->getControllerName();
        $this->route           = $this->convertToDashSeparated($this->controllerName);
        $this->kode_menu       = strtoupper($this->convertToDashSeparated($this->controllerName));
        $this->permission_main = $this->convertToTitle($this->controllerName);
        $this->titlePage       = @$usersMenuRepository->getCacheByKode($this->kode_menu)->nama ?? $this->convertToTitle($this->controllerName);
        $this->commonData      = [
            'titlePage'        => $this->titlePage,
            'route'            => $this->route,
            'kode_first_menu'  => $this->kode_menu,
            'kode_second_menu' => $this->kode_second_menu,
            'permission_main'  => $this->permission_main,
        ];
    }

    public function index()
    {
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);

        return inertia('modules/'.$this->route.'/Index', $data);
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

        return inertia("modules/$this->route/Create", $data);
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

        return inertia("modules/$this->route/Edit", $data);
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

        return inertia("modules/$this->route/Show", $data);
    }

    public function store(Request $request)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__);
        $data   = $this->request->validate($this->getValidationRules());
        $data   = $this->request->all();
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        $model = $this->repository->create($data);
        if (! ($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
        }

        return redirect()->route($this->route.'.index')->with('success', trans('message.success_add'));
    }

    protected function getValidationRules()
    {
        if (method_exists($this->request, 'rules')) {
            return $this->request->rules();
        }

        return [];
    }

    public function update()
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $this->request->id]);
        $data   = $this->request->validate($this->request->rules());
        $data   = $this->request->all();
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        $model = $this->repository->update($this->request->id, $data);
        if (! ($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
        }

        return redirect()->route($this->route.'.index')->with('success', trans('message.success_update'));
    }

    public function destroy($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $model    = $this->repository->delete($id);
        $callback = $this->repository->callbackAfterDelete($model, $id);
        if (! ($callback instanceof \Illuminate\Database\Eloquent\Model)) {
            return $callback;
        }

        return redirect()->route($this->route.'.index')->with('success', trans('message.success_delete'));
    }

    public function destroy_selected(Request $request)
    {
        try {
            $model = $this->repository->delete_selected($request->ids);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil dihapus.',
                ]);
            }

            $callback = $this->repository->callbackAfterDeleteSelected($model, $request->id);
            if (! ($callback instanceof \Illuminate\Database\Eloquent\Model)) {
                return $callback;
            }

            return redirect()->route($this->route.'.index')->with('success', trans('message.success_delete'));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function table()
    {
        $request = [
            'orderDefault' => request()->input('order') ?? null,
        ];
        $route           = $this->route;
        $role            = getRole(Auth::user()->current_role_id);
        $permission_main = $this->permission_main;
        try {
            $permission_create = $role->hasPermissionTo($this->permission_main.' Add');
        } catch (PermissionDoesNotExist $e) {
            $permission_create = false;
        }
        try {
            $permission_detail = $role->hasPermissionTo($this->permission_main.' Detail');
        } catch (PermissionDoesNotExist $e) {
            $permission_detail = false;
        }
        try {
            $permission_edit = $role->hasPermissionTo($this->permission_main.' Edit');
        } catch (PermissionDoesNotExist $e) {
            $permission_edit = false;
        }
        try {
            $permission_delete = $role->hasPermissionTo($this->permission_main.' Delete');
        } catch (PermissionDoesNotExist $e) {
            $permission_delete = false;
        }
        $param = [
            'route' => $route,
        ]; // ini untuk parameter
        $data  = $this->repository->getDataTable($request);
        $table = DataTables::of($data)
            ->addColumn('options', function ($d) use ($route, $permission_main, $permission_create, $permission_detail, $permission_edit, $permission_delete) {
                return view($route.'.buttons', compact('d', 'route', 'permission_main', 'permission_create', 'permission_detail', 'permission_edit', 'permission_delete'));
            });
        $table = $this->repository->customTable($table, $param, $request);
        $table = $table->make(true);

        return $table;
    }

    public function export()
    {
        return Excel::download(new GeneralExport($this->repository->getInstanceModel(), request()->all()), $this->titlePage.'.xlsx');
    }

    private function getPermission()
    {
        $auth_user = Auth::user();

        return [
            'can' => [
                'Add'    => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($this->permission_main.' Add') : false,
                'Edit'   => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($this->permission_main.' Edit') : false,
                'Delete' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($this->permission_main.' Delete') : false,
                'Detail' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($this->permission_main.' Detail') : false,
            ],
        ];
    }
}
