<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Itinerary;
use App\Models\Poi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Seo
{
    public static function forPoi(Poi $poi): array
    {
        $poi->loadMissing('municipality.province.region');
        $poi->loadMissing('primaryCategory:id,slug,name,is_active');
        $description = Str::limit(strip_tags((string) ($poi->description ?? '')), 160);
        $locality = $poi->municipality?->name;
        $regionName = $poi->municipality?->province?->region?->name;
        $photo = $poi->primary_photo_url;
        $image = $photo
            ? (str_starts_with($photo, 'http') ? $photo : url($photo))
            : null;
        $url = route('poi.show', $poi->slug, absolute: true);
        $title = $locality
            ? $poi->name.' a '.$locality.' — PG Spot'
            : $poi->name.' — PG Spot';
        $fallback = $locality
            ? 'Scopri '.$poi->name.' a '.$locality.' su PG Spot.'
            : 'Scopri '.$poi->name.' su PG Spot.';

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => self::schemaType($poi),
            'name' => $poi->name,
            'description' => $description ?: null,
            'url' => $url,
            'image' => $image,
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $poi->latitude,
                'longitude' => (float) $poi->longitude,
            ],
            'address' => ($poi->address || $locality) ? array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $poi->address ?: null,
                'addressLocality' => $locality,
                'addressRegion' => $regionName,
                'addressCountry' => 'IT',
            ], fn ($value) => $value !== null && $value !== '') : null,
            'aggregateRating' => $poi->review_count > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $poi->rating,
                'reviewCount' => (int) $poi->review_count,
            ] : null,
        ], fn ($value) => $value !== null);

        return [
            'title' => $title,
            'description' => $description ?: $fallback,
            'url' => $url,
            'image' => $image ?: url('/favicon.svg'),
            'json_ld' => $jsonLd,
        ];
    }

    /**
     * @param  Collection<int, Poi>  $stops
     * @return array<string, mixed>
     */
    public static function forItinerary(Itinerary $itinerary, Collection $stops): array
    {
        $description = Str::limit(strip_tags((string) ($itinerary->excerpt ?: $itinerary->description ?: '')), 160);
        $url = route('itineraries.show', $itinerary->slug, absolute: true);
        $elements = $stops->values()->map(fn (Poi $poi, int $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $poi->name,
            'url' => route('poi.show', $poi->slug, absolute: true),
        ])->all();

        return [
            'title' => $itinerary->title.' — PG Spot',
            'description' => $description ?: 'Percorso editoriale su PG Spot.',
            'url' => $url,
            'image' => url('/favicon.svg'),
            'json_ld' => [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => $itinerary->title,
                'description' => $description ?: null,
                'url' => $url,
                'numberOfItems' => count($elements),
                'itemListElement' => $elements,
            ],
        ];
    }

    public static function forHome(): array
    {
        $tagline = AppSetting::getValue('app.tagline', 'La mappa collaborativa di Perugia');

        return [
            'title' => 'PG Spot — Esplora Perugia',
            'description' => $tagline.' Panorami, servizi, itinerari e luoghi da scoprire.',
            'url' => route('home', absolute: true),
            'image' => url('/favicon.svg'),
        ];
    }

    private static function schemaType(Poi $poi): string
    {
        return match ($poi->primaryCategory?->slug) {
            'panorami', 'panorama' => 'TouristAttraction',
            'parcheggi', 'parking' => 'ParkingFacility',
            default => 'Place',
        };
    }
}
