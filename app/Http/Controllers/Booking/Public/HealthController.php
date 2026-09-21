<?php

namespace App\Http\Controllers\Booking\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'module' => 'e-booking',
            'message' => 'E-Booking API skeleton ready',
            'version' => 1,
        ]);
    }
}
