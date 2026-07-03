<?php

namespace App\Enums;

enum SponsorshipPlacement: string
{
    case HomeSheet = 'home_sheet';
    case HomeList = 'home_list';
    case Map = 'map';
    case Detail = 'detail';

    public function label(): string
    {
        return match ($this) {
            self::HomeSheet => 'Vicino a te (home)',
            self::HomeList => 'Lista luoghi',
            self::Map => 'Mappa',
            self::Detail => 'Dettaglio',
        };
    }
}
