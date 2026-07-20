<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Poi;
use Illuminate\Support\Str;

class Seo
{
    public static function forPoi(Poi $poi): array
    {
        $description = Str::limit(strip_tags((string) ($poi->description ?? '')), 160);
        $image = $poi->primary_photo_url
            ? (str_starts_with($poi->primary_photo_url, 'http') ? $poi->primary_photo_url : url($poi->primary_photo_url))
            : url('/favicon.svg');

        return [
            'title' => $poi->name.' — PG Spot',
            'description' => $description ?: 'Scopri '.$poi->name.' su PG Spot, la mappa di Perugia.',
            'url' => route('poi.show', $poi->slug, absolute: true),
            'image' => $image,
            'json_ld' => [
                '@context' => 'https://schema.org',
                '@type' => 'Place',
                'name' => $poi->name,
                'description' => $description,
                'url' => route('poi.show', $poi->slug, absolute: true),
                'image' => $image,
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (float) $poi->latitude,
                    'longitude' => (float) $poi->longitude,
                ],
                'address' => $poi->address ? [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $poi->address,
                    'addressLocality' => 'Perugia',
                    'addressCountry' => 'IT',
                ] : null,
                'aggregateRating' => $poi->review_count > 0 ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (float) $poi->rating,
                    'reviewCount' => (int) $poi->review_count,
                ] : null,
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
}
