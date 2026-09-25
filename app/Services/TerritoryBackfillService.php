<?php

namespace App\Services;

use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Support\TerritoryDefaults;
use Illuminate\Support\Facades\DB;

class TerritoryBackfillService
{
    /**
     * Centro mappa già usato da PGSpot (app.default_center). Serve solo a non assegnare
     * a Perugia i punti chiaramente fuori dall'area operativa storica.
     */
    public const PERUGIA_CENTER_LAT = 43.1107;

    public const PERUGIA_CENTER_LNG = 12.3908;

    public const PERUGIA_ASSIGN_RADIUS_KM = 8.0;

    /**
     * Record minimi e certi, necessari al backfill. Il resto dell'anagrafica arriva da import/seed.
     *
     * @return array{region: Region, province: Province, municipality: Municipality}
     */
    public function ensureCoreTerritory(): array
    {
        $region = Region::query()->firstOrCreate(
            ['slug' => 'umbria'],
            ['name' => 'Umbria'],
        );

        Province::query()->firstOrCreate(
            ['region_id' => $region->id, 'slug' => 'terni'],
            ['name' => 'Terni', 'code' => 'TR'],
        );

        $province = Province::query()->firstOrCreate(
            ['region_id' => $region->id, 'slug' => 'perugia'],
            ['name' => 'Perugia', 'code' => 'PG'],
        );

        $municipality = Municipality::query()->firstOrCreate(
            ['province_id' => $province->id, 'slug' => 'perugia'],
            [
                'name' => 'Perugia',
                'latitude' => self::PERUGIA_CENTER_LAT,
                'longitude' => self::PERUGIA_CENTER_LNG,
                'is_indexable' => false,
            ],
        );

        return [
            'region' => $region,
            'province' => $province,
            'municipality' => $municipality,
        ];
    }

    /**
     * Associa a Perugia solo i POI senza comune che risultano del progetto storico.
     * Non tocca coordinate, slug, indirizzo o status.
     *
     * Assegna se l'indirizzo contiene la parola «Perugia», oppure se l'indirizzo è vuoto
     * e il punto è entro 8 km dal centro mappa. Gli altri restano senza comune.
     *
     * @return array{assigned: int, skipped: list<array{id: int, name: string, slug: string, reason: string}>}
     */
    public function assignOrphanPoisToPerugia(): array
    {
        $core = $this->ensureCoreTerritory();
        $perugiaId = $core['municipality']->id;
        $assigned = 0;
        $skipped = [];

        $pois = DB::table('pois')
            ->whereNull('municipality_id')
            ->get(['id', 'name', 'slug', 'address', 'latitude', 'longitude']);

        foreach ($pois as $poi) {
            $reason = $this->skipReason($poi);
            if ($reason !== null) {
                $skipped[] = [
                    'id' => (int) $poi->id,
                    'name' => (string) $poi->name,
                    'slug' => (string) $poi->slug,
                    'reason' => $reason,
                ];

                continue;
            }

            DB::table('pois')->where('id', $poi->id)->update([
                'municipality_id' => $perugiaId,
                'updated_at' => now(),
            ]);
            $assigned++;
        }

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
        ];
    }

    public function ensureDefaultSettings(): void
    {
        $current = TerritoryDefaults::get();
        if ($current['municipality_id'] && Municipality::query()->whereKey($current['municipality_id'])->exists()) {
            return;
        }

        $core = $this->ensureCoreTerritory();

        TerritoryDefaults::put(
            $core['region']->id,
            $core['province']->id,
            $core['municipality']->id,
        );
    }

    private function skipReason(object $poi): ?string
    {
        $address = mb_strtolower(trim((string) ($poi->address ?? '')));
        $mentionsPerugia = $address !== '' && preg_match('/\bperugia\b/u', $address) === 1;

        if ($mentionsPerugia) {
            return null;
        }

        if ($address !== '') {
            return 'Indirizzo non riferito a Perugia: non assegnato in automatico.';
        }

        $distance = $this->distanceKm(
            self::PERUGIA_CENTER_LAT,
            self::PERUGIA_CENTER_LNG,
            (float) $poi->latitude,
            (float) $poi->longitude,
        );

        if ($distance <= self::PERUGIA_ASSIGN_RADIUS_KM) {
            return null;
        }

        return 'Senza indirizzo e a '.round($distance, 1).' km dal centro di Perugia.';
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
