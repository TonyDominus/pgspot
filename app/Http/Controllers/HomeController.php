<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Itinerary;
use App\Models\Poi;
use App\Services\ItineraryService;
use App\Services\PoiCatalogQuery;
use App\Services\SponsorshipService;
use App\Support\Seo;
use App\Support\SiteFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private SponsorshipService $sponsorships,
        private ItineraryService $itineraryService,
    ) {}

    public function index(Request $request): Response
    {
        $featuredEvents = SiteFeatures::eventsPublicEnabled()
            ? Event::query()
                ->listed()
                ->featured()
                ->orderBy('starts_at')
                ->limit(3)
                ->get(['id', 'title', 'slug', 'description', 'starts_at', 'image'])
            : collect();

        $filters = PoiCatalogQuery::filtersFromRequest($request);
        $municipality = PoiCatalogQuery::municipalityByIstat($filters['municipality']);
        $center = AppSetting::getValue('app.default_center', ['lat' => 43.1107, 'lng' => 12.3908, 'zoom' => 14]);
        if ($municipality?->latitude && $municipality?->longitude) {
            $center = [
                'lat' => (float) $municipality->latitude,
                'lng' => (float) $municipality->longitude,
                'zoom' => 13,
            ];
        }

        $itinerary = $this->homeItinerary($request);
        if ($itinerary && $itinerary['stops'] !== []) {
            $first = $itinerary['stops'][0];
            $center = ['lat' => $first['latitude'], 'lng' => $first['longitude'], 'zoom' => 14];
        }

        $focus = null;
        $focusSlug = $request->string('focus')->toString();
        if ($focusSlug !== '') {
            $focusPoi = Poi::query()->published()->where('slug', $focusSlug)->with([
                'primaryCategory:id,slug,name,color,icon',
                'municipality:id,name',
                'photos' => fn ($photos) => $photos->where('is_primary', true),
            ])->first(['id', 'slug', 'name', 'latitude', 'longitude', 'rating', 'review_count', 'primary_category_id', 'municipality_id']);
            if ($focusPoi) {
                $focus = [
                    'id' => $focusPoi->id,
                    'slug' => $focusPoi->slug,
                    'name' => $focusPoi->name,
                    'latitude' => (float) $focusPoi->latitude,
                    'longitude' => (float) $focusPoi->longitude,
                    'rating' => $focusPoi->rating,
                    'review_count' => $focusPoi->review_count,
                    'primary_category' => $focusPoi->primaryCategory,
                    'municipality' => $focusPoi->municipality?->name,
                    'primary_photo_url' => $focusPoi->append('primary_photo_url')->primary_photo_url,
                ];
                if (! $itinerary) {
                    $center = ['lat' => $focus['latitude'], 'lng' => $focus['longitude'], 'zoom' => 16];
                }
            }
        }

        return Inertia::render('Home', [
            'categories' => PoiCatalogQuery::categoryOptions(),
            'filterTags' => PoiCatalogQuery::tagOptions(),
            'filters' => [
                'category' => $filters['category'],
                'municipality' => $municipality ? [
                    'istat_code' => $municipality->istat_code,
                    'name' => $municipality->name,
                    'latitude' => $municipality->latitude,
                    'longitude' => $municipality->longitude,
                ] : null,
                'tags' => $filters['tags'],
                'free' => $filters['free'],
                'access' => $filters['access'],
                'parking' => $filters['parking'],
                'rating' => $filters['min_rating'] ?: null,
            ],
            'focus' => $focus,
            'itinerary' => $itinerary,
            'mapCenter' => $center,
            'featuredEvents' => $featuredEvents,
            'sponsorships' => $this->sponsorships->activeForPlacement('home_sheet'),
            'featuredSponsorships' => $this->sponsorships->activeForPlacement('home_list'),
            'canContribute' => (bool) $request->user(),
            'seo' => Seo::forHome(),
        ]);
    }

    public function filters(Request $request): RedirectResponse
    {
        return redirect()->route('home', $request->query());
    }

    /**
     * @return array{slug: string, title: string, stops: list<array<string, mixed>>}|null
     */
    private function homeItinerary(Request $request): ?array
    {
        $slug = $request->string('itinerary')->toString();
        if ($slug === '') {
            return null;
        }

        $itinerary = Itinerary::query()->where('slug', $slug)->first();
        if (! $itinerary || ! $this->itineraryService->isPubliclyVisible($itinerary)) {
            return null;
        }

        $stops = $this->itineraryService->publicStops($itinerary);

        return [
            'slug' => $itinerary->slug,
            'title' => $itinerary->title,
            'stops' => $stops->map(fn (Poi $poi) => $this->itineraryService->stopPayload($poi))->values()->all(),
        ];
    }

    public function favorites(Request $request): Response
    {
        $user = $request->user();

        $pois = $user
            ? $user->favoritePois()->with('categories:id,slug,name,color,icon')->get()
            : collect();

        return Inertia::render('Favorites', [
            'pois' => $pois,
            'mapCenter' => AppSetting::getValue('app.default_center'),
        ]);
    }
}
