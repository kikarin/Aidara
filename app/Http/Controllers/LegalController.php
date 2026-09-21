<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function show(string $slug): Response
    {
        $page = config("legal.pages.{$slug}");

        if (! $page) {
            abort(404);
        }

        return Inertia::render('legal/Show', [
            'page' => $page,
        ]);
    }
}
