<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\HomeRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    protected $repository;

    public function __construct(HomeRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get home data for mobile (latest 5 from each module)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Home Mobile API called', [
                'user'            => auth()->user(),
                'user_id'         => auth()->id(),
                'current_role_id' => auth()->user()->current_role_id ?? 'no role',
            ]);

            $data = $this->repository->getHomeData($request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data home berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data home: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => auth()->id(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data home: ' . $e->getMessage(),
            ], 500);
        }
    }
}
