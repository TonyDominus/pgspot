<?php

namespace App\Services;

use App\Models\Poi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContributionDuplicateService
{
    /**
     * Luoghi pubblicati nello stesso comune, entro circa 200 metri o con nome simile.
     * Non blocca l'invio.
     *
     * @return Collection<int, Poi>
     */
    public function find(string $name, float $latitude, float $longitude, ?int $municipalityId): Collection
    {
        $box = 0.002;
        $candidates = Poi::query()
            ->published()
            ->when($municipalityId, fn ($query) => $query->where('municipality_id', $municipalityId))
            ->whereBetween('latitude', [$latitude - $box, $latitude + $box])
            ->whereBetween('longitude', [$longitude - $box, $longitude + $box])
            ->limit(20)
            ->get(['id', 'name', 'slug', 'latitude', 'longitude']);

        $needle = Str::lower($name);

        return $candidates->filter(function (Poi $poi) use ($needle, $latitude, $longitude) {
            $km = app(PoiCatalogQuery::class)->kilometers($latitude, $longitude, (float) $poi->latitude, (float) $poi->longitude);
            $haystack = Str::lower($poi->name);

            return $km <= 0.2 || str_contains($haystack, $needle) || str_contains($needle, $haystack);
        })->take(5)->values();
    }
}
