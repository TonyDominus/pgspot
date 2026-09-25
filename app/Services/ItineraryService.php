<?php

namespace App\Services;

use App\Enums\ItineraryStatus;
use App\Enums\PoiStatus;
use App\Models\Itinerary;
use App\Models\Municipality;
use App\Models\Poi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItineraryService
{
    /**
     * @param  list<array{poi_id:int, note?:?string}>  $stops
     */
    public function syncStops(Itinerary $itinerary, array $stops): void
    {
        $payload = [];
        $position = 1;
        $seen = [];

        foreach ($stops as $stop) {
            $poiId = (int) ($stop['poi_id'] ?? 0);
            if ($poiId < 1 || isset($seen[$poiId]) || ! Poi::query()->whereKey($poiId)->exists()) {
                continue;
            }
            $note = isset($stop['note']) ? Str::limit(trim((string) $stop['note']), 255, '') : null;
            $payload[$poiId] = [
                'position' => $position,
                'note' => $note !== '' ? $note : null,
            ];
            $seen[$poiId] = true;
            $position++;
        }

        $itinerary->pois()->sync($payload);
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<array{itinerary_id:int, poi_id:int}>
     */
    public function importLegacyIds(Itinerary $itinerary, array $ids): array
    {
        $missing = [];
        $stops = [];
        foreach ($ids as $poiId) {
            $poiId = (int) $poiId;
            if ($poiId < 1) {
                continue;
            }
            if (! Poi::query()->whereKey($poiId)->exists()) {
                $missing[] = ['itinerary_id' => $itinerary->id, 'poi_id' => $poiId];

                continue;
            }
            $stops[] = ['poi_id' => $poiId];
        }
        $this->syncStops($itinerary, $stops);

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function publishErrors(Itinerary $itinerary): array
    {
        $itinerary->loadMissing('pois');
        $errors = [];

        if (trim((string) $itinerary->title) === '') {
            $errors[] = 'Serve un titolo.';
        }
        if (trim((string) $itinerary->slug) === '') {
            $errors[] = 'Serve uno slug.';
        }
        if (trim((string) ($itinerary->excerpt ?: $itinerary->description)) === '') {
            $errors[] = 'Serve una descrizione o un estratto.';
        }

        $published = $itinerary->pois->where('status', PoiStatus::Published);
        if ($published->count() < 2) {
            $errors[] = 'Servono almeno due tappe pubblicate.';
        }
        if ($itinerary->pois->count() !== $published->count()) {
            $errors[] = 'Tutte le tappe devono essere pubblicate.';
        }

        return $errors;
    }

    public function isPubliclyVisible(Itinerary $itinerary): bool
    {
        if ($itinerary->status !== ItineraryStatus::Published) {
            return false;
        }

        return $itinerary->pois()->published()->count() >= 2;
    }

    public function territoryLabel(Itinerary $itinerary): ?string
    {
        $itinerary->loadMissing('pois.municipality.province.region');
        $municipalities = $itinerary->pois
            ->pluck('municipality')
            ->filter()
            ->unique('id')
            ->values();

        if ($municipalities->count() === 1) {
            $municipality = $municipalities->first();

            return $municipality->province?->region
                ? $municipality->name.', '.$municipality->province->region->name
                : $municipality->name;
        }

        if ($municipalities->count() > 1) {
            $region = $municipalities
                ->map(fn (Municipality $municipality) => $municipality->province?->region?->name)
                ->filter()
                ->unique()
                ->count() === 1
                ? $municipalities->first()?->province?->region?->name
                : null;

            return trim(($region ? $region.' · ' : '').$municipalities->count().' comuni');
        }

        return $itinerary->municipality?->name;
    }

    public function coverUrl(Itinerary $itinerary): ?string
    {
        if ($itinerary->cover_path) {
            return Storage::disk('public')->url($itinerary->cover_path);
        }

        $itinerary->loadMissing(['pois.photos' => fn ($photos) => $photos->where('is_primary', true)]);

        foreach ($itinerary->pois as $poi) {
            $url = $poi->append('primary_photo_url')->primary_photo_url;
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    public function durationLabel(Itinerary $itinerary): ?string
    {
        if ($itinerary->estimated_duration_minutes) {
            $hours = intdiv((int) $itinerary->estimated_duration_minutes, 60);
            $minutes = (int) $itinerary->estimated_duration_minutes % 60;
            if ($hours && $minutes) {
                return $hours.'h '.$minutes.'m';
            }

            return $hours ? $hours.'h' : $minutes.' min';
        }

        return $itinerary->duration ?: null;
    }

    /**
     * @return Collection<int, Poi>
     */
    public function publicStops(Itinerary $itinerary): Collection
    {
        return $itinerary->pois()->published()
            ->with([
                'primaryCategory:id,slug,name,color,icon',
                'municipality:id,name',
                'photos' => fn ($photos) => $photos->where('is_primary', true),
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function stopPayload(Poi $poi): array
    {
        return [
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
            'note' => $poi->pivot?->note,
            'position' => (int) ($poi->pivot?->position ?? 0),
            'stop_number' => (int) ($poi->pivot?->position ?? 0),
        ];
    }
}
