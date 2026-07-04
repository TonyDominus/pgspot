<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\Poi;
use Inertia\Inertia;
use Inertia\Response;

class ItineraryController extends Controller
{
    public function index(): Response
    {
        $itineraries = Itinerary::query()
            ->published()
            ->get();

        $allPoiIds = $itineraries->flatMap(fn ($i) => $i->poi_ids ?? [])->unique()->filter();

        $pois = Poi::query()
            ->published()
            ->whereIn('id', $allPoiIds)
            ->with('categories:id,name,color')
            ->get()
            ->keyBy('id');

        $itineraries->transform(function ($itinerary) use ($pois) {
            $itinerary->stops = collect($itinerary->poi_ids ?? [])
                ->map(fn ($id) => $pois->get($id))
                ->filter()
                ->values();

            return $itinerary;
        });

        return Inertia::render('Itineraries/Index', [
            'itineraries' => $itineraries,
        ]);
    }

    public function show(string $slug): Response
    {
        $itinerary = Itinerary::query()->published()->where('slug', $slug)->firstOrFail();

        $stops = Poi::query()
            ->published()
            ->whereIn('id', $itinerary->poi_ids ?? [])
            ->with('categories:id,name,color')
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $itinerary->poi_ids ?? [], true))
            ->values();

        return Inertia::render('Itineraries/Show', [
            'itinerary' => $itinerary,
            'stops' => $stops,
        ]);
    }
}
