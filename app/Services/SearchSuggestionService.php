<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Municipality;
use App\Models\Poi;
use Illuminate\Support\Str;

/**
 * Ranking: nome esatto, poi prefisso, poi contenuto nel nome, poi gli altri campi.
 * Massimo 5 luoghi, 3 comuni attivi, 3 categorie attive. Nessuna fuzzy search.
 */
class SearchSuggestionService
{
    public function suggest(string $term): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return ['pois' => [], 'municipalities' => [], 'categories' => []];
        }

        $like = '%'.$term.'%';

        $pois = Poi::query()
            ->published()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('municipality', fn ($municipality) => $municipality->where('is_active', true)->where('name', 'like', $like));
            })
            ->where(function ($visible) {
                $visible->whereNull('municipality_id')
                    ->orWhereHas('municipality', fn ($municipality) => $municipality->where('is_active', true));
            })
            ->with([
                'primaryCategory:id,slug,name,color',
                'municipality:id,name',
                'photos' => fn ($photos) => $photos->where('is_primary', true),
            ])
            ->limit(25)
            ->get(['id', 'name', 'slug', 'address', 'description', 'primary_category_id', 'municipality_id'])
            ->sortBy(fn (Poi $poi) => $this->rank($poi->name, $term))
            ->take(5)
            ->values()
            ->map(fn (Poi $poi) => [
                'id' => $poi->id,
                'slug' => $poi->slug,
                'name' => $poi->name,
                'type' => 'poi',
                'primary_category' => $poi->primaryCategory ? [
                    'slug' => $poi->primaryCategory->slug,
                    'name' => $poi->primaryCategory->name,
                    'color' => $poi->primaryCategory->color,
                ] : null,
                'municipality' => $poi->municipality?->name,
                'primary_photo_url' => $poi->append('primary_photo_url')->primary_photo_url,
            ]);

        $municipalities = Municipality::query()
            ->active()
            ->where('name', 'like', $like)
            ->with('province.region')
            ->limit(20)
            ->get()
            ->sortBy(fn (Municipality $municipality) => $this->rank($municipality->name, $term))
            ->take(3)
            ->values()
            ->map(fn (Municipality $municipality) => [
                'id' => $municipality->id,
                'istat_code' => $municipality->istat_code,
                'name' => $municipality->name,
                'slug' => $municipality->slug,
                'province' => $municipality->province?->name,
                'region' => $municipality->province?->region?->name,
                'latitude' => $municipality->latitude,
                'longitude' => $municipality->longitude,
                'type' => 'municipality',
            ]);

        $categories = Category::query()
            ->active()
            ->where('slug', '!=', 'instagram-spot')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->limit(20)
            ->get(['id', 'name', 'slug'])
            ->sortBy(fn (Category $category) => $this->rank($category->name, $term))
            ->take(3)
            ->values()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => 'category',
            ]);

        return [
            'pois' => $pois,
            'municipalities' => $municipalities,
            'categories' => $categories,
        ];
    }

    private function rank(string $name, string $term): int
    {
        $name = Str::lower($name);
        $term = Str::lower($term);
        if ($name === $term) {
            return 0;
        }
        if (str_starts_with($name, $term)) {
            return 1;
        }
        if (str_contains($name, $term)) {
            return 2;
        }

        return 3;
    }
}
