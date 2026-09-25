<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\Poi;
use App\Services\ItineraryService;
use App\Services\PoiCatalogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapPoiController extends Controller
{
    public const LIMIT = 500;

    public function __invoke(Request $request, PoiCatalogQuery $catalog, ItineraryService $itineraries): JsonResponse
    {
        if ($request->filled('itinerary')) {
            return $this->itineraryMarkers($request, $itineraries);
        }

        $validated = $request->validate([
            'south' => 'required|numeric|between:-90,90',
            'north' => 'required|numeric|between:-90,90',
            'west' => 'required|numeric|between:-180,180',
            'east' => 'required|numeric|between:-180,180',
        ]);

        $filters = PoiCatalogQuery::filtersFromRequest($request);
        $query = $catalog->apply($catalog->base(), $filters)
            ->whereBetween('latitude', [min($validated['south'], $validated['north']), max($validated['south'], $validated['north'])])
            ->whereBetween('longitude', [min($validated['west'], $validated['east']), max($validated['west'], $validated['east'])])
            ->with([
                'primaryCategory:id,slug,name,color,icon',
                'municipality:id,name',
                'photos' => fn ($photos) => $photos->where('is_primary', true),
            ])
            ->limit(self::LIMIT + 1);

        $rows = $query->get([
            'id', 'slug', 'name', 'latitude', 'longitude', 'rating', 'review_count',
            'primary_category_id', 'municipality_id',
        ]);

        if ($catalog->hasDistance($filters)) {
            $rows = $rows->filter(function (Poi $poi) use ($catalog, $filters) {
                return $catalog->kilometers(
                    (float) $filters['latitude'],
                    (float) $filters['longitude'],
                    (float) $poi->latitude,
                    (float) $poi->longitude,
                ) <= (int) $filters['radius'];
            })->sortBy(fn (Poi $poi) => $catalog->kilometers(
                (float) $filters['latitude'],
                (float) $filters['longitude'],
                (float) $poi->latitude,
                (float) $poi->longitude,
            ))->values();
        }

        $truncated = $rows->count() > self::LIMIT;
        $markers = $rows->take(self::LIMIT)->map(fn (Poi $poi) => [
            'id' => $poi->id,
            'slug' => $poi->slug,
            'name' => $poi->name,
            'latitude' => (float) $poi->latitude,
            'longitude' => (float) $poi->longitude,
            'rating' => $poi->rating,
            'review_count' => $poi->review_count,
            'primary_category' => $poi->primaryCategory ? [
                'slug' => $poi->primaryCategory->slug,
                'name' => $poi->primaryCategory->name,
                'color' => $poi->primaryCategory->color,
                'icon' => $poi->primaryCategory->icon,
            ] : null,
            'municipality' => $poi->municipality?->name,
            'primary_photo_url' => $poi->append('primary_photo_url')->primary_photo_url,
            'sponsored' => false,
        ])->values();

        return response()->json([
            'markers' => $markers,
            'truncated' => $truncated,
            'count' => $markers->count(),
        ]);
    }

    private function itineraryMarkers(Request $request, ItineraryService $itineraries): JsonResponse
    {
        $slug = $request->string('itinerary')->toString();
        $itinerary = Itinerary::query()->where('slug', $slug)->first();
        if (! $itinerary || ! $itineraries->isPubliclyVisible($itinerary)) {
            return response()->json([
                'markers' => [],
                'truncated' => false,
                'count' => 0,
                'itinerary' => null,
            ]);
        }

        $markers = $itineraries->publicStops($itinerary)
            ->map(fn (Poi $poi) => $itineraries->stopPayload($poi) + ['sponsored' => false])
            ->values();

        return response()->json([
            'markers' => $markers,
            'truncated' => false,
            'count' => $markers->count(),
            'itinerary' => ['slug' => $itinerary->slug, 'title' => $itinerary->title],
        ]);
    }
}
