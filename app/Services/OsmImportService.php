<?php

namespace App\Services;

use App\Enums\PoiStatus;
use App\Models\Category;
use App\Models\Poi;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OsmImportService
{
    /** @var array<string, string> osm amenity => category slug */
    private const AMENITY_MAP = [
        'toilets' => 'bagni',
        'drinking_water' => 'fontanelle',
        'parking' => 'parcheggi',
    ];

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @return array{created: int, updated: int, skipped: int, total: int, items: list<array{action: string, name: string, osm_id: string}>}
     */
    public function import(array $bbox, bool $dryRun = false, int $limit = 250, ?User $actor = null): array
    {
        $elements = $this->fetchElements($bbox);
        $categories = Category::query()
            ->whereIn('slug', array_values(self::AMENITY_MAP))
            ->get()
            ->keyBy('slug');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        foreach ($elements as $element) {
            if (count($items) >= $limit) {
                break;
            }

            $amenity = $element['tags']['amenity'] ?? null;
            if (! is_string($amenity) || ! isset(self::AMENITY_MAP[$amenity])) {
                $skipped++;

                continue;
            }

            $categorySlug = self::AMENITY_MAP[$amenity];
            $category = $categories->get($categorySlug);
            if (! $category) {
                $skipped++;

                continue;
            }

            $coords = $this->coordinates($element);
            if ($coords === null) {
                $skipped++;

                continue;
            }

            $osmKey = ($element['type'] ?? 'node').'/'.$element['id'];
            $name = $this->displayName($element['tags'] ?? [], $amenity);
            $address = $this->addressFromTags($element['tags'] ?? []);
            $attributes = [
                'source' => 'openstreetmap',
                'osm_id' => $osmKey,
                'osm_amenity' => $amenity,
                'free' => true,
                'tags' => $this->defaultTags($amenity),
                'needs_photo' => true,
            ];

            $existing = Poi::query()
                ->where('attributes->osm_id', $osmKey)
                ->first();

            if ($dryRun) {
                $items[] = [
                    'action' => $existing ? 'update' : 'create',
                    'name' => $name,
                    'osm_id' => $osmKey,
                ];
                $existing ? $updated++ : $created++;

                continue;
            }

            if ($existing) {
                $existing->fill([
                    'name' => $name,
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                    'address' => $address ?: $existing->address,
                    'attributes' => array_merge($existing->attributes ?? [], $attributes),
                ]);
                $existing->save();
                $existing->categories()->syncWithoutDetaching([$category->id]);
                $updated++;
                $items[] = ['action' => 'update', 'name' => $name, 'osm_id' => $osmKey];

                continue;
            }

            $poi = Poi::query()->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name, $osmKey),
                'description' => $this->description($amenity),
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'address' => $address,
                'status' => PoiStatus::Published,
                'attributes' => $attributes,
                'created_by' => $actor?->id,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
                'rating' => 0,
                'review_count' => 0,
            ]);
            $poi->categories()->sync([$category->id]);
            $created++;
            $items[] = ['action' => 'create', 'name' => $name, 'osm_id' => $osmKey];
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @param  array{south: float, west: float, north: float, east: float}  $bbox
     * @return list<array<string, mixed>>
     */
    public function fetchElements(array $bbox): array
    {
        $s = $bbox['south'];
        $w = $bbox['west'];
        $n = $bbox['north'];
        $e = $bbox['east'];

        $amenities = implode('|', array_keys(self::AMENITY_MAP));
        $query = <<<QL
[out:json][timeout:90];
(
  node["amenity"~"{$amenities}"]({$s},{$w},{$n},{$e});
  way["amenity"~"{$amenities}"]({$s},{$w},{$n},{$e});
);
out center tags;
QL;

        $endpoints = $this->endpoints();
        $errors = [];

        foreach ($endpoints as $url) {
            try {
                $response = Http::timeout(100)
                    ->withHeaders([
                        'User-Agent' => (string) config('services.overpass.user_agent'),
                        'Accept' => 'application/json',
                    ])
                    ->withOptions(['verify' => (bool) config('services.overpass.verify', true)])
                    ->asForm()
                    ->post($url, ['data' => $query]);

                if ($response->status() === 406 || $response->serverError() || $response->status() === 429) {
                    $errors[] = "{$url} → HTTP {$response->status()}";

                    continue;
                }

                $response->throw();

                /** @var list<array<string, mixed>> $elements */
                $elements = $response->json('elements') ?? [];

                return $elements;
            } catch (\Throwable $e) {
                $errors[] = "{$url} → ".$e->getMessage();
            }
        }

        throw new \RuntimeException(
            'Overpass non raggiungibile (406 spesso = User-Agent/mirror). Tentativi: '.implode(' | ', $errors)
        );
    }

    /** @return list<string> */
    private function endpoints(): array
    {
        $primary = (string) config('services.overpass.url');
        $mirrors = config('services.overpass.mirrors', []);
        if (! is_array($mirrors)) {
            $mirrors = [];
        }

        return array_values(array_unique(array_filter([
            $primary,
            ...$mirrors,
        ])));
    }

    /** @param  array<string, mixed>  $element */
    private function coordinates(array $element): ?array
    {
        if (isset($element['lat'], $element['lon'])) {
            return ['lat' => (float) $element['lat'], 'lng' => (float) $element['lon']];
        }

        if (isset($element['center']['lat'], $element['center']['lon'])) {
            return [
                'lat' => (float) $element['center']['lat'],
                'lng' => (float) $element['center']['lon'],
            ];
        }

        return null;
    }

    /** @param  array<string, string>  $tags */
    private function displayName(array $tags, string $amenity): string
    {
        if (! empty($tags['name'])) {
            return $tags['name'];
        }

        $street = $tags['addr:street'] ?? null;
        $base = match ($amenity) {
            'toilets' => 'Bagno pubblico',
            'drinking_water' => 'Fontanella',
            'parking' => 'Parcheggio',
            default => 'Luogo',
        };

        return $street ? "{$base} — {$street}" : $base;
    }

    /** @param  array<string, string>  $tags */
    private function addressFromTags(array $tags): ?string
    {
        $parts = array_filter([
            trim(($tags['addr:street'] ?? '').' '.($tags['addr:housenumber'] ?? '')),
            $tags['addr:city'] ?? 'Perugia',
        ]);

        $address = trim(implode(', ', $parts));

        return $address !== '' ? $address : null;
    }

    private function description(string $amenity): string
    {
        return match ($amenity) {
            'toilets' => 'Servizi igienici pubblici (dato OpenStreetMap). Verifica apertura e accessibilità sul posto.',
            'drinking_water' => 'Punto di acqua potabile (dato OpenStreetMap).',
            'parking' => 'Area di sosta (dato OpenStreetMap). Controlla tariffe e orari in loco.',
            default => 'Punto importato da OpenStreetMap.',
        };
    }

    /** @return list<string> */
    private function defaultTags(string $amenity): array
    {
        return match ($amenity) {
            'toilets' => ['Accessibile'],
            'drinking_water' => ['Gratuito'],
            'parking' => ['Con parcheggio'],
            default => [],
        };
    }

    private function uniqueSlug(string $name, string $osmKey): string
    {
        $base = Str::slug(Str::limit($name, 60, '')) ?: 'poi-osm';
        $suffix = Str::lower(Str::after($osmKey, '/'));
        $slug = Str::limit($base.'-osm-'.$suffix, 100, '');

        if (! Poi::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug.'-'.Str::lower(Str::random(4));
    }
}
