<?php

namespace App\Support;

use App\Models\Poi;

class PoiSheet
{
    /** @return list<array{id: string, label: string, value?: string, href?: string}> */
    public static function facts(Poi $poi): array
    {
        $facts = [];

        if ($poi->is_free === true) {
            $facts[] = ['id' => 'is_free', 'label' => 'Gratuito'];
        } elseif ($poi->is_free === false) {
            $facts[] = ['id' => 'is_free', 'label' => 'A pagamento'];
        }

        $access = match ($poi->accessibility) {
            'yes' => 'Accessibile',
            'partial' => 'Accessibilità parziale',
            'no' => 'Non accessibile',
            default => null,
        };
        if ($access) {
            $facts[] = ['id' => 'accessibility', 'label' => $access];
        }

        $parking = match ($poi->parking) {
            'none' => 'Nessun parcheggio',
            'nearby' => 'Parcheggio nelle vicinanze',
            'on_site' => 'Parcheggio sul posto',
            'paid' => 'Parcheggio a pagamento',
            default => null,
        };
        if ($parking) {
            $facts[] = ['id' => 'parking', 'label' => $parking];
        }

        $hours = self::hours($poi);
        if ($hours) {
            $facts[] = ['id' => 'opening_hours', 'label' => 'Orari', 'value' => $hours];
        }

        if ($poi->price !== null && $poi->price !== '') {
            $facts[] = ['id' => 'price', 'label' => 'Prezzo', 'value' => number_format((float) $poi->price, 2, ',', '.').' €'];
        }

        $website = self::website($poi->website);
        if ($website) {
            $facts[] = ['id' => 'website', 'label' => 'Sito web', 'href' => $website];
        }

        $phone = trim((string) $poi->phone);
        if ($phone !== '') {
            $facts[] = ['id' => 'phone', 'label' => $phone, 'href' => 'tel:'.preg_replace('/\s+/', '', $phone)];
        }

        return $facts;
    }

    public static function placeLine(Poi $poi): ?string
    {
        $municipality = $poi->municipality?->name;
        $province = $poi->municipality?->province?->name;
        $region = $poi->municipality?->province?->region?->name;

        $parts = array_values(array_filter([
            $municipality,
            ($province && $province !== $municipality) ? $province : null,
            ($region && $region !== $municipality && $region !== $province) ? $region : null,
        ]));

        return $parts === [] ? null : implode(', ', $parts);
    }

    public static function verifiedLabel(Poi $poi): ?string
    {
        if (! $poi->last_verified_at) {
            return null;
        }

        return 'Informazioni verificate il '.$poi->last_verified_at->locale('it')->isoFormat('D MMMM YYYY');
    }

    private static function hours(Poi $poi): ?string
    {
        $hours = $poi->opening_hours;
        if (! is_array($hours)) {
            return null;
        }

        $text = trim((string) ($hours['text'] ?? ''));

        return $text !== '' ? $text : null;
    }

    private static function website(?string $website): ?string
    {
        $website = trim((string) $website);
        if ($website === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $website)) {
            $website = 'https://'.$website;
        }

        return $website;
    }
}
