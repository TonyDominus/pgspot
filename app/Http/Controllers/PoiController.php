<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Poi;
use App\Services\ItineraryService;
use App\Services\PoiListingService;
use App\Services\PoiRelatedService;
use App\Support\PoiSheet;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PoiController extends Controller
{
    public function __construct(
        private PoiListingService $listing,
        private PoiRelatedService $related,
        private ItineraryService $itineraryService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Poi/List', [
            ...$this->listing->getPageData($request),
            'mapCenter' => AppSetting::getValue('app.default_center', ['lat' => 43.1107, 'lng' => 12.3908, 'zoom' => 14]),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $poi = Poi::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'categories:id,slug,name,color,icon,is_active',
                'primaryCategory:id,slug,name,color,icon,is_active',
                'photos',
                'tags' => fn ($tags) => $tags->where('is_active', true)->select('tags.id', 'tags.slug', 'tags.name'),
                'municipality.province.region',
            ])
            ->firstOrFail()
            ->append('primary_photo_url');

        $secondary = $poi->categories
            ->where('is_active', true)
            ->where('slug', '!=', 'instagram-spot')
            ->where('id', '!=', $poi->primary_category_id)
            ->values();

        $itineraries = $poi->itineraries()
            ->public()
            ->get(['itineraries.id', 'itineraries.title', 'itineraries.slug', 'itineraries.duration', 'itineraries.estimated_duration_minutes'])
            ->map(fn ($itinerary) => [
                'id' => $itinerary->id,
                'title' => $itinerary->title,
                'slug' => $itinerary->slug,
                'duration' => $this->itineraryService->durationLabel($itinerary),
            ]);

        $events = Event::query()
            ->published()
            ->where('poi_id', $poi->id)
            ->orderBy('starts_at')
            ->get(['id', 'title', 'slug', 'starts_at']);

        $reviews = $poi->reviews()
            ->with('user:id,name')
            ->limit(20)
            ->get(['id', 'user_id', 'poi_id', 'rating', 'comment', 'created_at']);

        $userReview = $request->user()
            ? $poi->reviews()->where('user_id', $request->user()->id)->first()
            : null;

        $poi->makeHidden(['attributes']);

        return Inertia::render('Poi/Show', [
            'poi' => $poi,
            'primaryCategory' => $poi->primaryCategory?->slug === 'instagram-spot' ? null : $poi->primaryCategory,
            'secondaryCategories' => $secondary,
            'placeLine' => PoiSheet::placeLine($poi),
            'facts' => PoiSheet::facts($poi),
            'verifiedLabel' => PoiSheet::verifiedLabel($poi),
            'related' => $this->related->for($poi),
            'itineraries' => $itineraries,
            'events' => $events,
            'reviews' => $reviews,
            'userReview' => $userReview,
            'seo' => Seo::forPoi($poi),
        ]);
    }
}
