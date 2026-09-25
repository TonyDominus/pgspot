<?php

namespace App\Support;

use App\Models\AppSetting;

class TerritoryDefaults
{
    public const KEY = 'territory.defaults';

    /**
     * @return array{region_id: ?int, province_id: ?int, municipality_id: ?int}
     */
    public static function get(): array
    {
        $value = AppSetting::getValue(self::KEY, []);
        if (! is_array($value)) {
            $value = [];
        }

        return [
            'region_id' => isset($value['region_id']) ? (int) $value['region_id'] : null,
            'province_id' => isset($value['province_id']) ? (int) $value['province_id'] : null,
            'municipality_id' => isset($value['municipality_id']) ? (int) $value['municipality_id'] : null,
        ];
    }

    public static function put(int $regionId, int $provinceId, int $municipalityId): void
    {
        AppSetting::setValue(self::KEY, [
            'region_id' => $regionId,
            'province_id' => $provinceId,
            'municipality_id' => $municipalityId,
        ]);
    }
}
