<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtri del catalogo pubblicato.
 * Distanza: prima un riquadro in gradi (cross-database), poi Haversine in PHP sul risultato già limitato.
 * Parcheggio "disponibile" = nearby, on_site o paid. none e unknown restano fuori.
 */
class PoiCatalogQuery
{
    public const PARKING_AVAILABLE = ['nearby', 'on_site', 'paid'];

    /** @return array<string, mixed> */
    public static function filtersFromRequest(Request $request): array
    {
        $category = $request->string('category')->toString() ?: $request->string('cat')->toString();
        $tags = $request->input('tag', $request->input('tags', []));
        if (is_string($tags)) {
            $tags = array_filter(explode(',', $tags));
        }

        $access = $request->input('access', []);
        if (is_string($access)) {
            $access = array_filter(explode(',', $access));
        }

        return [
            'category' => $category ?: null,
            'municipality' => $request->string('municipality')->toString() ?: null,
            'tags' => array_values(array_filter((array) $tags)),
            'free' => $request->boolean('free'),
            'access' => array_values(array_intersect((array) $access, ['yes', 'partial'])),
            'parking' => $request->boolean('parking'),
            'min_rating' => $request->integer('rating') ?: $request->integer('min_rating'),
            'latitude' => $request->filled('lat') ? (float) $request->input('lat') : null,
            'longitude' => $request->filled('lng') ? (float) $request->input('lng') : null,
            'radius' => $request->integer('radius') ?: null,
        ];
    }

    /** @param  array<string, mixed>  $filters */
    public function apply(Builder $query, array $filters): Builder
    {
        $query->published()->where(function (Builder $visible) {
            $visible->whereNull('municipality_id')
                ->orWhereHas('municipality', fn (Builder $municipality) => $municipality->where('is_active', true));
        });

        if (! empty($filters['category'])) {
            $query->whereHas('primaryCategory', fn (Builder $category) => $category
                ->where('slug', $filters['category'])
                ->where('is_active', true)
                ->where('slug', '!=', 'instagram-spot'));
        }

        if (! empty($filters['municipality'])) {
            $code = $filters['municipality'];
            $query->whereHas('municipality', fn (Builder $municipality) => $municipality
                ->where('is_active', true)
                ->where('istat_code', $code));
        }

        if (! empty($filters['tags'])) {
            $query->whereHas('tags', fn (Builder $tags) => $tags
                ->whereIn('slug', $filters['tags'])
                ->where('is_active', true)
                ->where('is_filterable', true));
        }

        if (! empty($filters['free'])) {
            $query->where('is_free', true);
        }

        if (! empty($filters['access'])) {
            $query->whereIn('accessibility', $filters['access']);
        }

        if (! empty($filters['parking'])) {
            $query->whereIn('parking', self::PARKING_AVAILABLE);
        }

        if (! empty($filters['min_rating'])) {
            $query->where('rating', '>=', (int) $filters['min_rating']);
        }

        if ($this->hasDistance($filters)) {
            [$south, $north, $west, $east] = $this->radiusBox(
                (float) $filters['latitude'],
                (float) $filters['longitude'],
                (int) $filters['radius'],
            );
            $query->whereBetween('latitude', [$south, $north])
                ->whereBetween('longitude', [$west, $east]);
        }

        return $query;
    }

    /** @param  array<string, mixed>  $filters */
    public function hasDistance(array $filters): bool
    {
        return isset($filters['latitude'], $filters['longitude'], $filters['radius'])
            && $filters['latitude'] !== null
            && $filters['longitude'] !== null
            && (int) $filters['radius'] > 0;
    }

    public function kilometers(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function radiusBox(float $lat, float $lng, int $km): array
    {
        $latDelta = $km / 111;
        $lngDelta = $km / max(1, 111 * cos(deg2rad($lat)));

        return [$lat - $latDelta, $lat + $latDelta, $lng - $lngDelta, $lng + $lngDelta];
    }

    public function base(): Builder
    {
        return Poi::query();
    }

    public static function categoryOptions()
    {
        return Category::query()
            ->active()
            ->where('slug', '!=', 'instagram-spot')
            ->get(['id', 'slug', 'name', 'icon', 'color']);
    }

    public static function tagOptions()
    {
        return Tag::query()
            ->where('is_active', true)
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'name']);
    }

    public static function municipalityByIstat(?string $code): ?Municipality
    {
        if (! $code) {
            return null;
        }

        return Municipality::query()
            ->active()
            ->where('istat_code', $code)
            ->with('province.region')
            ->first();
    }
}
