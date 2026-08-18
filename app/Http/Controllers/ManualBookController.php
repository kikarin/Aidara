<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ManualBookController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:Dashboard Show'),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('modules/manual-book/Index', [
            'pdfUrl' => asset('pdf/MANUAL BOOK.pdf'),
        ]);
    }
}
