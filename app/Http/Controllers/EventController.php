<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\SiteFeatures;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        abort_unless(SiteFeatures::eventsPublicEnabled(), 404);

        $events = Event::query()
            ->published()
            ->where('starts_at', '>=', now()->subMonths(1))
            ->orderBy('starts_at')
            ->get(['id', 'title', 'slug', 'description', 'starts_at', 'ends_at', 'image', 'external_url']);

        return Inertia::render('Events/Index', [
            'events' => $events,
        ]);
    }
}
