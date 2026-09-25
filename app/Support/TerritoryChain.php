<?php

namespace App\Support;

use App\Models\Municipality;
use App\Models\Province;

class TerritoryChain
{
    /**
     * @return array<string, string>|null
     */
    public static function message(int $regionId, int $provinceId, int $municipalityId, bool $requireActive = true): ?array
    {
        $province = Province::query()->find($provinceId);
        if (! $province || (int) $province->region_id !== $regionId) {
            return ['province_id' => 'La provincia non appartiene alla regione selezionata.'];
        }

        if ($requireActive && ! $province->is_active) {
            return ['province_id' => 'La provincia selezionata non è più attiva.'];
        }

        $municipality = Municipality::query()->find($municipalityId);
        if (! $municipality || (int) $municipality->province_id !== $provinceId) {
            return ['municipality_id' => 'Il comune non appartiene alla provincia selezionata.'];
        }

        if ($requireActive && ! $municipality->is_active) {
            return ['municipality_id' => 'Il comune selezionato non è più attivo.'];
        }

        return null;
    }
}
