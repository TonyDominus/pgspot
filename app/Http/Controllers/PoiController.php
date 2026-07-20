<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Poi;
use App\Support\Seo;
use App\Services\PoiListingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PoiController extends Controller
{
    public function __construct(private PoiListingService $listing) {}

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
            ->with(['categories:id,slug,name,color,icon', 'photos', 'tags:id,slug,name'])
            ->firstOrFail()
            ->append('primary_photo_url');

        $related = Poi::query()
            ->published()
            ->where('id', '!=', $poi->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('id', $poi->categories->pluck('id')))
            ->with(['categories:id,slug,name,color,icon', 'photos'])
            ->limit(4)
            ->get(['id', 'name', 'slug', 'description', 'latitude', 'longitude', 'rating'])
            ->each->append('primary_photo_url');

        $reviews = $poi->reviews()
            ->with('user:id,name')
            ->limit(20)
            ->get(['id', 'user_id', 'poi_id', 'rating', 'comment', 'created_at']);

        $userReview = $request->user()
            ? $poi->reviews()->where('user_id', $request->user()->id)->first()
            : null;

        return Inertia::render('Poi/Show', [
            'poi' => $poi,
            'related' => $related,
            'reviews' => $reviews,
            'userReview' => $userReview,
            'mapCenter' => AppSetting::getValue('app.default_center'),
            'seo' => Seo::forPoi($poi),
        ]);
    }
}
