<?php

namespace App\Enums;

enum SponsorshipType: string
{
    case Card = 'card';
    case FeaturedCarousel = 'featured_carousel';
    case MapMarker = 'map_marker';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Card sponsorizzata',
            self::FeaturedCarousel => 'Lista in evidenza',
            self::MapMarker => 'Marker mappa',
        };
    }
}
