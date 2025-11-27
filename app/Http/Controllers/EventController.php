<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Repositories\EventRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventController extends Controller implements HasMiddleware
{
    use BaseTrait;

    private $request;

    private $repository;

    public function __construct(Request $request, EventRepository $repository)
    {
        $this->repository = $repository;
        $this->request    = EventRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'event';
        $this->commonData['kode_first_menu']  = 'EVENT';
        $this->commonData['kode_second_menu'] = 'EVENT';
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

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['events'],
            'meta' => [
                'total'        => $data['total'],
                'current_page' => $data['currentPage'],
                'per_page'     => $data['perPage'],
                'search'       => $data['search'],
                'sort'         => $data['sort'],
                'order'        => $data['order'],
            ],
        ]);
    }

    public function index()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);

        return inertia('modules/event/Index', $data);
    }

    public function store(EventRequest $request)
    {
        // Handle foto upload SEBELUM validasi untuk memastikan file tidak hilang
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $fotoPath = $foto->store('event', 'public');
        }

        $data = $this->repository->validateRequest($request);

        // Set foto path ke data jika ada
        if ($fotoPath) {
            $data['foto'] = $fotoPath;
        }

        $this->repository->create($data);

        return redirect()->route('event.index')->with('success', 'Data event berhasil ditambahkan!');
    }

    public function update(EventRequest $request, $id)
    {
        // Handle foto upload SEBELUM validasi untuk memastikan file tidak hilang
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $event = $this->repository->getById($id);
            // Hapus foto lama jika ada
            if ($event && $event->foto) {
                Storage::disk('public')->delete($event->foto);
            }

            $foto = $request->file('foto');
            $fotoPath = $foto->store('event', 'public');
        }

        $data = $this->repository->validateRequest($request);

        // Set foto path ke data jika ada
        if ($fotoPath) {
            $data['foto'] = $fotoPath;
        }

        $this->repository->update($id, $data);

        return redirect()->route('event.index')->with('success', 'Data event berhasil diperbarui!');
    }

    public function show($id)
    {
        $item      = $this->repository->getDetailWithUserTrack($id);
        if (!$item) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }
        $itemArray = $item->toArray();

        return Inertia::render('modules/event/Show', [
            'item' => $itemArray,
        ]);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);

        return redirect()->route('event.index')->with('success', 'Data event berhasil dihapus!');
    }

    public function destroy_selected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|numeric|exists:event,id',
        ]);
        $this->repository->delete_selected($request->ids);

        return response()->json(['message' => 'Data event berhasil dihapus!']);
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

        return inertia('modules/event/Create', $data);
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

        return inertia('modules/event/Edit', $data);
    }
}

