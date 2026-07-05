<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Inertia\Inertia;
use Inertia\Response;

class PublicEventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('event/PublicIndex', [
            'events' => $this->eventService->getPublicList(),
        ]);
    }

    public function show(int $id): Response
    {
        $event = $this->eventService->getPublicDetail($id);

        abort_if($event === null, 404);

        return Inertia::render('event/PublicShow', [
            'event' => $event,
        ]);
    }
}
