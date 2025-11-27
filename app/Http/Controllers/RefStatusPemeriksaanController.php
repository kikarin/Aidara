<?php

namespace App\Http\Controllers;

use App\Models\RefStatusPemeriksaan;

class RefStatusPemeriksaanController extends Controller
{
    public function index()
    {
        $statuses = RefStatusPemeriksaan::all(['id', 'nama']);

        return response()->json($statuses);
    }
}
