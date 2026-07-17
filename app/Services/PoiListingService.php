<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Poi;
use Illuminate\Http\Request;

class PoiListingService
{
    public function getPageData(Request $request): array
    {
        $categorySlug = $request->string('cat')->toString() ?: null;
        $search = $request->string('q')->toString() ?: null;

        $categories = Category::query()->active()->get(['id', 'slug', 'name', 'icon', 'color']);

        $poisQuery = Poi::query()
            ->published()
            ->with(['categories:id,slug,name,color,icon', 'photos']);

        if ($categorySlug) {
            $poisQuery->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search) {
            $poisQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $minRating = $request->integer('min_rating');
        if ($minRating > 0) {
            $poisQuery->where('rating', '>=', $minRating);
        }

        $pois = $poisQuery
            ->orderByDesc('rating')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'latitude', 'longitude', 'address', 'rating', 'attributes'])
            ->each->append('primary_photo_url');

        return [
            'categories' => $categories,
            'pois' => $pois,
            'activeCategory' => $categorySlug,
            'search' => $search,
            'filters' => [
                'tags' => $request->input('tags', []),
                'max_distance' => $request->integer('max_distance') ?: 5000,
                'min_rating' => $minRating,
            ],
        ];
    }
}
