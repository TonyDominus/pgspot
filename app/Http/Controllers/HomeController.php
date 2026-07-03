<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\PoiStatus;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Poi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $categorySlug = $request->string('cat')->toString() ?: null;

        $categories = Category::query()->active()->get(['id', 'slug', 'name', 'icon', 'color']);

        $poisQuery = Poi::query()
            ->published()
            ->with(['categories:id,slug,name,color', 'photos' => fn ($q) => $q->where('is_primary', true)->limit(1)]);

        if ($categorySlug) {
            $poisQuery->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        $pois = $poisQuery
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'latitude', 'longitude', 'address', 'rating']);

        $featuredEvents = Event::query()
            ->published()
            ->featured()
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->limit(6)
            ->get(['id', 'title', 'slug', 'description', 'starts_at', 'ends_at', 'image', 'is_featured']);

        return Inertia::render('Home', [
            'categories' => $categories,
            'pois' => $pois,
            'featuredEvents' => $featuredEvents,
            'activeCategory' => $categorySlug,
            'canContribute' => (bool) $request->user(),
        ]);
    }
}
