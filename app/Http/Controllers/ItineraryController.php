<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Services\ItineraryService;
use App\Support\Seo;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ItineraryController extends Controller
{
    public function __construct(private ItineraryService $itineraries) {}

    public function index(): Response
    {
        $items = Itinerary::query()
            ->public()
            ->with(['pois' => fn ($pois) => $pois->published()->with([
                'municipality.province.region',
                'photos' => fn ($photos) => $photos->where('is_primary', true),
            ])])
            ->withCount(['pois as stop_count' => fn ($pois) => $pois->published()])
            ->get();

        return Inertia::render('Itineraries/Index', [
            'itineraries' => $items->map(fn (Itinerary $itinerary) => [
                'id' => $itinerary->id,
                'slug' => $itinerary->slug,
                'title' => $itinerary->title,
                'excerpt' => $itinerary->excerpt ?: $this->excerptFrom($itinerary->description),
                'cover_url' => $this->itineraries->coverUrl($itinerary),
                'territory' => $this->itineraries->territoryLabel($itinerary),
                'stop_count' => (int) $itinerary->stop_count,
                'duration' => $this->itineraries->durationLabel($itinerary),
                'difficulty' => $itinerary->difficulty,
            ]),
        ]);
    }

    public function show(string $slug): Response
    {
        $itinerary = Itinerary::query()->where('slug', $slug)->firstOrFail();
        abort_unless($this->itineraries->isPubliclyVisible($itinerary), 404);

        $stops = $this->itineraries->publicStops($itinerary);

        return Inertia::render('Itineraries/Show', [
            'itinerary' => [
                'id' => $itinerary->id,
                'slug' => $itinerary->slug,
                'title' => $itinerary->title,
                'excerpt' => $itinerary->excerpt,
                'description' => $itinerary->description,
                'cover_url' => $this->itineraries->coverUrl($itinerary),
                'territory' => $this->itineraries->territoryLabel($itinerary),
                'duration' => $this->itineraries->durationLabel($itinerary),
                'distance_km' => $itinerary->estimated_distance_km,
                'difficulty' => $itinerary->difficulty,
                'map_href' => route('home', ['itinerary' => $itinerary->slug]),
            ],
            'stops' => $stops->map(fn ($poi) => $this->itineraries->stopPayload($poi))->values(),
            'seo' => Seo::forItinerary($itinerary, $stops),
        ]);
    }

    private function excerptFrom(?string $text): ?string
    {
        $text = trim(strip_tags((string) $text));

        return $text === '' ? null : Str::limit($text, 160);
    }
}
