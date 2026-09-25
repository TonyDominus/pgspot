<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Poi;
use Illuminate\Support\Facades\DB;

class LegacyCategoryRemap
{
    /**
     * Sposta i POI dalla categoria legacy `panorama` a `panorami`
     * solo se i nomi coincidono. Non elimina la categoria legacy.
     *
     * @return array{moved: int, skipped: bool}
     */
    public function remapPanorama(): array
    {
        $legacy = Category::query()->where('slug', 'panorama')->first();
        $current = Category::query()->where('slug', 'panorami')->first();

        if (! $legacy || ! $current || mb_strtolower($legacy->name) !== mb_strtolower($current->name)) {
            return ['moved' => 0, 'skipped' => true];
        }

        $poiIds = DB::table('category_poi')->where('category_id', $legacy->id)->pluck('poi_id');
        foreach ($poiIds as $poiId) {
            $attached = DB::table('category_poi')
                ->where('category_id', $current->id)
                ->where('poi_id', $poiId)
                ->exists();
            if (! $attached) {
                DB::table('category_poi')->insert([
                    'category_id' => $current->id,
                    'poi_id' => $poiId,
                ]);
            }
        }

        $moved = Poi::query()->where('primary_category_id', $legacy->id)->update([
            'primary_category_id' => $current->id,
        ]);

        DB::table('category_poi')->where('category_id', $legacy->id)->delete();

        return ['moved' => $moved, 'skipped' => false];
    }
}
