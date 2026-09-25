<?php

namespace App\Services;

use App\Models\Poi;
use Illuminate\Support\Collection;

class PoiRelatedService
{
    /**
     * Candidati pubblicati che condividono comune, categoria primaria attiva o tag attivi.
     * Punteggio: comune 100, stessa primaria 40, 15 per tag condiviso, fino a 20 entro 25 km.
     * Il limite SQL è 40, poi si tengono i primi risultati. Nessun POI non pubblicato e mai se stesso.
     *
     * @return Collection<int, Poi>
     */
    public function for(Poi $poi, int $limit = 4): Collection
    {
        $tagIds = $poi->tags->pluck('id');
        $primaryId = $poi->primaryCategory?->is_active ? $poi->primary_category_id : null;

        $candidates = Poi::query()
            ->published()
            ->where('id', '!=', $poi->id)
            ->where(function ($query) use ($poi, $primaryId, $tagIds) {
                $constrained = false;
                if ($poi->municipality_id) {
                    $query->orWhere('municipality_id', $poi->municipality_id);
                    $constrained = true;
                }
                if ($primaryId) {
                    $query->orWhere('primary_category_id', $primaryId);
                    $constrained = true;
                }
                if ($tagIds->isNotEmpty()) {
                    $query->orWhereHas('tags', fn ($tags) => $tags->whereIn('tags.id', $tagIds)->where('is_active', true));
                    $constrained = true;
                }
                if (! $constrained) {
                    $query->whereRaw('0 = 1');
                }
            })
            ->with([
                'primaryCategory:id,slug,name,color,icon,is_active',
                'categories:id,slug,name,color,icon',
                'photos',
                'tags' => fn ($tags) => $tags->where('is_active', true)->select('tags.id'),
            ])
            ->limit(40)
            ->get([
                'id', 'name', 'slug', 'description', 'latitude', 'longitude', 'rating',
                'municipality_id', 'primary_category_id',
            ]);

        return $candidates
            ->map(function (Poi $candidate) use ($poi, $primaryId, $tagIds) {
                $score = 0;
                if ($poi->municipality_id && (int) $candidate->municipality_id === (int) $poi->municipality_id) {
                    $score += 100;
                }
                if ($primaryId && (int) $candidate->primary_category_id === (int) $primaryId) {
                    $score += 40;
                }
                $score += $candidate->tags->pluck('id')->intersect($tagIds)->count() * 15;
                $km = $this->kilometers($poi, $candidate);
                if ($km !== null && $km <= 25) {
                    $score += (int) round(20 * (1 - ($km / 25)));
                }
                $candidate->setAttribute('related_score', $score);

                return $candidate;
            })
            ->filter(fn (Poi $candidate) => $candidate->related_score > 0)
            ->sortByDesc('related_score')
            ->take($limit)
            ->values()
            ->each(function (Poi $candidate) {
                $candidate->append('primary_photo_url');
                $candidate->makeHidden(['tags', 'related_score']);
            });
    }

    private function kilometers(Poi $from, Poi $to): ?float
    {
        if ($from->latitude === null || $from->longitude === null || $to->latitude === null || $to->longitude === null) {
            return null;
        }

        $earth = 6371;
        $latFrom = deg2rad((float) $from->latitude);
        $latTo = deg2rad((float) $to->latitude);
        $latDelta = deg2rad((float) $to->latitude - (float) $from->latitude);
        $lngDelta = deg2rad((float) $to->longitude - (float) $from->longitude);
        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
