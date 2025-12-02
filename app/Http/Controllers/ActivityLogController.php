<?php

namespace App\Http\Controllers;

use App\Repositories\ActivityLogRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ActivityLogController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $repository;

    private $categoryPermissionRepository;

    private $request;

    public function __construct(ActivityLogRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->with       = ['causer', 'causer.role'];
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
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['logs'],
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

    public function show($id)
    {
        $item = $this->repository->getDetailWithUserTrack($id);
        if (! $item) {
            return redirect()->back()->with('error', 'Log not found');
        }

        // Format data sesuai kebutuhan
        $fields = [
            ['label' => 'Module', 'value' => $item->log_name ?? '-'],
            ['label' => 'Event', 'value' => $item->event ?? '-'],
            ['label' => 'Subject Type', 'value' => $item->subject_type ? class_basename($item->subject_type) : '-'],
            ['label' => 'Subject ID', 'value' => $item->subject_id ?? '-'],
            ['label' => 'Data', 'value' => $this->formatActivityData($item)],
        ];

        $actionFields = [
            ['label' => 'Created At', 'value' => $item->created_at ? date('Y-m-d H:i:s', strtotime($item->created_at)) : '-'],
            ['label' => 'Created By', 'value' => optional($item->causer)->name ?? '-'],
            ['label' => 'Role', 'value' => optional(optional($item->causer)->role)->name ?? '-'],
        ];

        return inertia('modules/activity-logs/Show', [
            'fields'       => $fields,
            'actionFields' => $actionFields,
            'backUrl'      => '/menu-permissions/logs',
        ]);
    }

    /**
     * Format activity data untuk display
     */
    private function formatActivityData($item)
    {
        $data = [];

        if ($item->properties && $item->properties instanceof \Illuminate\Support\Collection) {
            $data = $item->properties->toArray(); // ← fix di sini
        }

        if (empty($data)) {
            return '[]';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function destroy($id)
    {
        $log = $this->repository->getById($id);
        if ($log) {
            $log->delete();

            return redirect()->route('access-control.logs.index')->with('success', 'Log deleted successfully');
        }

        return redirect()->back()->with('error', 'Log not found');
    }
}
