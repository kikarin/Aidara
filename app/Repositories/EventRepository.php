<?php

namespace App\Repositories;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;

class EventRepository
{
    use RepositoryTrait;

    protected $model;

    protected $request;

    public function __construct(Event $model)
    {
        $this->model   = $model;
        $this->request = EventRequest::createFromBase(request());
        $this->with    = ['created_by_user', 'updated_by_user', 'kategoriEvent.cabor', 'tingkatEvent'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama_event', 'foto', 'kategori_event_id', 'tingkat_event_id', 'lokasi', 'tanggal_mulai', 'tanggal_selesai', 'status', 'created_at', 'updated_at');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_event', 'like', '%'.$search.'%')
                    ->orWhere('lokasi', 'like', '%'.$search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$search.'%');
            });
        }

        if (request('sort')) {
            $order        = request('order', 'asc');
            $sortField    = request('sort');
            $validColumns = ['id', 'nama_event', 'lokasi', 'tanggal_mulai', 'tanggal_selesai', 'status', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $query->orderBy($sortField, $order);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        $page    = (int) request('page', 1);

        if ($perPage === -1) {
            $allData         = $query->with($this->with)->get();
            $transformedData = $allData->map(function ($item) {
                return [
                    'id'                  => $item->id,
                    'nama_event'         => $item->nama_event,
                    'foto'               => $item->foto,
                    'foto_url'            => $item->foto ? url('storage/' . $item->foto) : null,
                    'kategori_event_id'   => $item->kategori_event_id,
                    'kategori_event_nama' => $item->kategoriEvent ? $item->kategoriEvent->cabor->nama . ' - ' . $item->kategoriEvent->nama : '-',
                    'tingkat_event_id'    => $item->tingkat_event_id,
                    'tingkat_event_nama'  => $item->tingkatEvent ? $item->tingkatEvent->nama : '-',
                    'lokasi'              => $item->lokasi,
                    'tanggal_mulai'       => $item->tanggal_mulai,
                    'tanggal_selesai'     => $item->tanggal_selesai,
                    'status'              => $item->status,
                ];
            });
            $data += [
                'events'     => $transformedData,
                'total'      => $transformedData->count(),
                'currentPage' => 1,
                'perPage'    => -1,
                'search'      => request('search', ''),
                'sort'        => request('sort', ''),
                'order'       => request('order', 'asc'),
            ];

            return $data;
        }

        $pageForPaginate = $page < 1 ? 1 : $page;
        $items           = $query->with($this->with)->paginate($perPage, ['*'], 'page', $pageForPaginate)->withQueryString();

        $transformedData = collect($items->items())->map(function ($item) {
            return [
                'id'                  => $item->id,
                'nama_event'         => $item->nama_event,
                'foto'               => $item->foto,
                'foto_url'            => $item->foto ? url('storage/' . $item->foto) : null,
                'kategori_event_id'   => $item->kategori_event_id,
                'kategori_event_nama' => $item->kategoriEvent ? $item->kategoriEvent->cabor->nama . ' - ' . $item->kategoriEvent->nama : '-',
                'tingkat_event_id'    => $item->tingkat_event_id,
                'tingkat_event_nama'  => $item->tingkatEvent ? $item->tingkatEvent->nama : '-',
                'lokasi'              => $item->lokasi,
                'tanggal_mulai'       => $item->tanggal_mulai,
                'tanggal_selesai'     => $item->tanggal_selesai,
                'status'              => $item->status,
            ];
        });

        $data += [
            'events'     => $transformedData,
            'total'      => $items->total(),
            'currentPage' => $items->currentPage(),
            'perPage'    => $items->perPage(),
            'search'      => request('search', ''),
            'sort'        => request('sort', ''),
            'order'       => request('order', 'asc'),
        ];

        return $data;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        $userId = Auth::id();

        if (is_null($record)) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        if ($item) {
            // Convert item ke array dan tambahkan foto_url
            $itemArray = $item->toArray();
            $itemArray['foto_url'] = $item->foto ? url('storage/' . $item->foto) : null;
            $data['item'] = $itemArray;
        }

        return $data;
    }

    public function delete_selected(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function getDetailWithUserTrack($id)
    {
        return $this->model
            ->with($this->with)
            ->where('id', $id)
            ->first();
    }

    public function validateRequest($request)
    {
        $rules    = method_exists($request, 'rules') ? $request->rules() : [];
        $messages = method_exists($request, 'messages') ? $request->messages() : [];

        return $request->validate($rules, $messages);
    }
}

