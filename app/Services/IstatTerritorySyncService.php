<?php

namespace App\Services;

use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Province;
use App\Models\Region;
use App\Support\SimpleXlsx;
use App\Support\TerritoryDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IstatTerritorySyncService
{
    public const REGION_UMBRIA = '10';

    public const PROVINCE_PERUGIA = '054';

    public const MUNICIPALITY_PERUGIA = '054039';

    /**
     * @return array<string, mixed>
     */
    public function syncFromOfficialFile(string $xlsxPath, ?string $crosswalkPath = null): array
    {
        $rows = $this->rowsFromWorkbook($this->resolve($xlsxPath));
        $aliases = $crosswalkPath ? $this->aliasesFromCrosswalk($this->resolve($crosswalkPath)) : [];

        return $this->syncOfficial($rows, $aliases);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string>  $extraAliases  previous istat => current istat
     * @return array<string, mixed>
     */
    public function syncOfficial(array $rows, array $extraAliases = []): array
    {
        $official = $this->normalizeRows($rows);
        $result = $this->emptyResult();
        $currentCodes = [];
        $aliasesByCurrent = [];

        foreach ($official as $row) {
            $currentCodes[$row['municipality_code']] = true;
            if ($row['previous_code'] !== '' && $row['previous_code'] !== $row['municipality_code']) {
                $aliasesByCurrent[$row['municipality_code']][] = $row['previous_code'];
            }
        }

        foreach ($extraAliases as $previous => $current) {
            $previous = $this->pad($previous, 6);
            $current = $this->pad($current, 6);
            if ($previous !== '' && $current !== '' && $previous !== $current && isset($currentCodes[$current])) {
                $aliasesByCurrent[$current][] = $previous;
            }
        }

        $regions = $this->upsertRegions($official, $result);
        $provinces = $this->upsertProvinces($official, $regions, $result);
        $this->upsertMunicipalities($official, $provinces, $aliasesByCurrent, $currentCodes, $result);
        $this->deactivateMissingProvinces($provinces['active_ids'], $result);
        $this->refreshPerugiaDefaults();

        return $result;
    }

    /**
     * @return list<array<string, string>>
     */
    public function rowsFromWorkbook(string $path): array
    {
        $sheet = SimpleXlsx::rows($path);
        $header = $sheet[0]['A'] ?? '';
        if (! str_contains($header, 'Codice Regione')) {
            throw new \InvalidArgumentException('Il file non ha l\'intestazione ISTAT attesa (Codice Regione).');
        }

        $rows = [];
        foreach (array_slice($sheet, 1) as $record) {
            $rows[] = [
                'region_code' => $this->pad($record['A'] ?? '', 2),
                'region_name' => trim($record['K'] ?? ''),
                'uts_code' => $this->pad($record['B'] ?? '', 3),
                'historic_province_code' => $this->pad($record['C'] ?? '', 3),
                'uts_name' => trim(preg_replace('/\s+/u', ' ', $record['L'] ?? '') ?? ''),
                'uts_type' => $this->provinceType($record['M'] ?? ''),
                'sigla' => strtoupper(trim($record['O'] ?? '')),
                'municipality_code' => $this->pad($record['E'] ?? '', 6),
                'previous_code' => $this->pad($record['R'] ?? '', 6),
                'municipality_name' => trim($record['F'] ?? '') !== '' ? trim($record['F'] ?? '') : trim($record['G'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public function aliasesFromCrosswalk(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \InvalidArgumentException("File non trovato: {$path}");
        }

        $aliases = [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $current = $this->pad($row[2] ?? '', 6);
            $previous = $this->pad($row[4] ?? '', 6);
            if (strlen($current) === 6 && strlen($previous) === 6 && $current !== $previous) {
                $aliases[$previous] = $current;
            }
        }
        fclose($handle);

        return $aliases;
    }

    /**
     * @param  list<array<string, string>>  $official
     * @param  array<string, mixed>  $result
     * @return array<string, Region>
     */
    private function upsertRegions(array $official, array &$result): array
    {
        $byIstat = [];
        foreach (Region::query()->get() as $region) {
            if ($region->istat_code) {
                $byIstat[$region->istat_code] = $region;
            }
        }

        $wanted = [];
        foreach ($official as $row) {
            if ($row['region_code'] !== '' && $row['region_name'] !== '') {
                $wanted[$row['region_code']] = $row['region_name'];
            }
        }

        $regions = [];
        foreach ($wanted as $code => $name) {
            $existing = $byIstat[$code] ?? null;
            if (! $existing) {
                $slug = Str::slug($name);
                $candidates = Region::query()
                    ->whereNull('istat_code')
                    ->where(function ($query) use ($name, $slug) {
                        $query->where('name', $name)->orWhere('slug', $slug);
                    })
                    ->get();
                if ($candidates->count() === 1) {
                    $existing = $candidates->first();
                }
            }

            if ($existing) {
                $existing->name = $name;
                $existing->istat_code = $code;
                if ($existing->isDirty()) {
                    $existing->save();
                    $result['regions_updated']++;
                }
                $byIstat[$code] = $existing;
                $regions[$code] = $existing;

                continue;
            }

            $regions[$code] = Region::query()->create([
                'name' => $name,
                'slug' => $this->availableSlug('regions', Str::slug($name) ?: 'regione-'.$code, []),
                'istat_code' => $code,
            ]);
            $result['regions_created']++;
        }

        return $regions;
    }

    /**
     * @param  list<array<string, string>>  $official
     * @param  array<string, Region>  $regions
     * @param  array<string, mixed>  $result
     * @return array{by_code: array<string, Province>, active_ids: array<int, true>}
     */
    private function upsertProvinces(array $official, array $regions, array &$result): array
    {
        $byIstat = [];
        $bySigla = [];
        foreach (Province::query()->get() as $province) {
            if ($province->istat_code) {
                $byIstat[$province->istat_code] = $province;
            }
            if ($province->code) {
                $bySigla[$province->region_id.'|'.$province->code] = $province;
            }
        }

        $wanted = [];
        foreach ($official as $row) {
            if ($row['uts_code'] !== '' && $row['uts_name'] !== '') {
                $wanted[$row['uts_code']] = $row;
            }
        }

        $matched = [];
        foreach ($wanted as $code => $row) {
            $region = $regions[$row['region_code']] ?? null;
            if (! $region) {
                $result['anomalies'][] = "Unità {$code} senza regione {$row['region_code']}.";

                continue;
            }

            $existing = $byIstat[$code] ?? null;
            if (! $existing && $row['historic_province_code'] !== '') {
                $candidate = $byIstat[$row['historic_province_code']] ?? null;
                if ($candidate && (int) $candidate->region_id === (int) $region->id && ($candidate->code === null || $candidate->code === $row['sigla'])) {
                    $existing = $candidate;
                }
            }
            if (! $existing && $row['sigla'] !== '') {
                $candidate = $bySigla[$region->id.'|'.$row['sigla']] ?? null;
                if ($candidate && ($candidate->istat_code === null || $candidate->istat_code === $code)) {
                    $existing = $candidate;
                }
            }

            $matched[$code] = ['row' => $row, 'region' => $region, 'province' => $existing];
        }

        foreach ($matched as $item) {
            $row = $item['row'];
            if ($row['sigla'] === '') {
                continue;
            }

            $query = Province::query()->where('code', $row['sigla']);
            if ($item['province']) {
                $query->where('id', '!=', $item['province']->id);
            }
            $query->update(['code' => null]);
        }

        $byCode = [];
        $activeIds = [];
        foreach ($matched as $code => $item) {
            $row = $item['row'];
            $region = $item['region'];
            $existing = $item['province'];
            if ($existing) {
                $existing->region_id = $region->id;
                $existing->name = $row['uts_name'];
                $existing->istat_code = $code;
                $existing->type = $row['uts_type'];
                $existing->is_active = true;
                if ($row['sigla'] !== '') {
                    $existing->code = $row['sigla'];
                }
                if ($existing->isDirty()) {
                    $existing->save();
                    $result['provinces_updated']++;
                }
                $byCode[$code] = $existing;
                $activeIds[$existing->id] = true;

                continue;
            }

            $created = Province::query()->create([
                'region_id' => $region->id,
                'name' => $row['uts_name'],
                'slug' => $this->availableSlug('provinces', Str::slug($row['uts_name']) ?: 'unita-'.$code, ['region_id' => $region->id]),
                'code' => $row['sigla'] !== '' ? $row['sigla'] : null,
                'type' => $row['uts_type'],
                'istat_code' => $code,
                'is_active' => true,
            ]);
            $byCode[$code] = $created;
            $activeIds[$created->id] = true;
            $result['provinces_created']++;
        }

        return ['by_code' => $byCode, 'active_ids' => $activeIds];
    }

    /**
     * @param  list<array<string, string>>  $official
     * @param  array{by_code: array<string, Province>, active_ids: array<int, true>}  $provinces
     * @param  array<string, list<string>>  $aliasesByCurrent
     * @param  array<string, true>  $currentCodes
     * @param  array<string, mixed>  $result
     */
    private function upsertMunicipalities(array $official, array $provinces, array $aliasesByCurrent, array $currentCodes, array &$result): void
    {
        $byIstat = [];
        $bySlug = [];
        foreach (Municipality::query()->get() as $municipality) {
            if ($municipality->istat_code) {
                $byIstat[$municipality->istat_code] = $municipality;
            } else {
                $bySlug[$municipality->province_id.'|'.$municipality->slug] = $municipality;
            }
        }

        $claimed = [];
        foreach ($official as $row) {
            $province = $provinces['by_code'][$row['uts_code']] ?? null;
            if (! $province || $row['municipality_code'] === '' || $row['municipality_name'] === '') {
                $result['anomalies'][] = 'Comune senza codice, nome o unità sovracomunale: '.($row['municipality_name'] ?: $row['municipality_code']);

                continue;
            }

            $existing = $byIstat[$row['municipality_code']] ?? null;
            if ($existing && isset($claimed[$existing->id])) {
                $existing = null;
            }

            if (! $existing) {
                $slug = Str::slug($row['municipality_name']);
                $candidate = $bySlug[$province->id.'|'.$slug] ?? null;
                if ($candidate && ! isset($claimed[$candidate->id])) {
                    $existing = $candidate;
                }
            }

            if (! $existing) {
                foreach ($aliasesByCurrent[$row['municipality_code']] ?? [] as $alias) {
                    $candidate = $byIstat[$alias] ?? null;
                    if ($candidate && ! isset($claimed[$candidate->id])) {
                        $existing = $candidate;
                        break;
                    }
                }
            }

            if ($existing) {
                $previousCode = $existing->istat_code;
                $previousProvince = (int) $existing->province_id;
                $existing->province_id = $province->id;
                $existing->name = $row['municipality_name'];
                $existing->istat_code = $row['municipality_code'];
                $existing->is_active = true;
                if ($existing->isDirty()) {
                    if ($previousCode !== $row['municipality_code']) {
                        $result['codes_changed']++;
                    }
                    if ($previousProvince !== (int) $province->id) {
                        $result['transferred']++;
                    }
                    $existing->save();
                    $result['municipalities_updated']++;
                }
                if ($previousCode && $previousCode !== $row['municipality_code']) {
                    unset($byIstat[$previousCode]);
                }
                $claimed[$existing->id] = true;
                $byIstat[$row['municipality_code']] = $existing;

                continue;
            }

            $created = Municipality::query()->create([
                'province_id' => $province->id,
                'name' => $row['municipality_name'],
                'slug' => $this->availableSlug('municipalities', Str::slug($row['municipality_name']) ?: 'comune-'.$row['municipality_code'], ['province_id' => $province->id]),
                'istat_code' => $row['municipality_code'],
                'is_indexable' => false,
                'is_active' => true,
            ]);
            $claimed[$created->id] = true;
            $byIstat[$row['municipality_code']] = $created;
            $result['municipalities_created']++;
        }

        $defaultId = TerritoryDefaults::get()['municipality_id'];
        $referenced = Poi::query()->whereNotNull('municipality_id')->pluck('municipality_id')->map(fn ($id) => (int) $id)->all();
        if ($defaultId) {
            $referenced[] = (int) $defaultId;
        }

        foreach (Municipality::query()->whereNotIn('id', array_keys($claimed))->get() as $municipality) {
            if ($municipality->istat_code && isset($currentCodes[$municipality->istat_code])) {
                continue;
            }
            if ($municipality->is_active) {
                $municipality->is_active = false;
                $municipality->save();
                $result['municipalities_deactivated']++;
            }
            if (in_array((int) $municipality->id, $referenced, true)) {
                $result['reallocation'][] = $municipality->name.' ('.$municipality->istat_code.')';
            }
        }
    }

    /**
     * @param  array<int, true>  $activeIds
     * @param  array<string, mixed>  $result
     */
    private function deactivateMissingProvinces(array $activeIds, array &$result): void
    {
        foreach (Province::query()->whereNotIn('id', array_keys($activeIds))->get() as $province) {
            $dirty = false;
            if ($province->is_active) {
                $province->is_active = false;
                $dirty = true;
                $result['provinces_deactivated']++;
            }
            if ($province->code && Province::query()->where('code', $province->code)->where('id', '!=', $province->id)->where('is_active', true)->exists()) {
                $province->code = null;
                $dirty = true;
            }
            if ($dirty) {
                $province->save();
            }
        }
    }

    private function refreshPerugiaDefaults(): void
    {
        $municipality = Municipality::query()->where('istat_code', self::MUNICIPALITY_PERUGIA)->where('is_active', true)->first();
        $province = Province::query()->where('istat_code', self::PROVINCE_PERUGIA)->where('is_active', true)->first();
        $region = Region::query()->where('istat_code', self::REGION_UMBRIA)->first();
        if (! $municipality || ! $province || ! $region) {
            return;
        }

        $current = TerritoryDefaults::get();
        $currentMunicipality = $current['municipality_id']
            ? Municipality::query()->find($current['municipality_id'])
            : null;

        $keepsPerugia = $currentMunicipality === null
            || ! $currentMunicipality->is_active
            || $currentMunicipality->istat_code === self::MUNICIPALITY_PERUGIA
            || ($currentMunicipality->slug === 'perugia' && $currentMunicipality->province?->code === 'PG');

        if (! $keepsPerugia) {
            return;
        }

        if ($current['region_id'] === $region->id && $current['province_id'] === $province->id && $current['municipality_id'] === $municipality->id) {
            return;
        }

        TerritoryDefaults::put($region->id, $province->id, $municipality->id);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return list<array<string, string>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $type = $row['uts_type'] ?? '';
            if (! in_array($type, ['province', 'autonomous_province', 'metropolitan_city', 'free_municipal_consortium', 'non_administrative_unit'], true)) {
                $type = $this->provinceType($type);
            }
            $normalized[] = [
                'region_code' => $this->pad($row['region_code'] ?? '', 2),
                'region_name' => trim($row['region_name'] ?? ''),
                'uts_code' => $this->pad($row['uts_code'] ?? '', 3),
                'historic_province_code' => $this->pad($row['historic_province_code'] ?? '', 3),
                'uts_name' => trim($row['uts_name'] ?? ''),
                'uts_type' => $type,
                'sigla' => strtoupper(trim($row['sigla'] ?? '')),
                'municipality_code' => $this->pad($row['municipality_code'] ?? '', 6),
                'previous_code' => $this->pad($row['previous_code'] ?? '', 6),
                'municipality_name' => trim($row['municipality_name'] ?? ''),
            ];
        }

        return $normalized;
    }

    private function provinceType(string $code): string
    {
        return match (trim($code)) {
            '1', 'province' => 'province',
            '2', 'autonomous_province' => 'autonomous_province',
            '3', 'metropolitan_city' => 'metropolitan_city',
            '4', 'free_municipal_consortium' => 'free_municipal_consortium',
            '5', 'non_administrative_unit' => 'non_administrative_unit',
            default => 'province',
        };
    }

    private function pad(string $value, int $length): string
    {
        $value = trim($value);
        if ($value === '' || ! ctype_digit($value)) {
            return $value;
        }

        return str_pad($value, $length, '0', STR_PAD_LEFT);
    }

    /** @param  array<string, int|string>  $scope */
    private function availableSlug(string $table, string $base, array $scope): string
    {
        $base = $base !== '' ? $base : 'territorio';
        if (! DB::table($table)->where('slug', $base)->where($scope)->exists()) {
            return $base;
        }

        $index = 2;
        while (DB::table($table)->where('slug', $base.'-'.$index)->where($scope)->exists()) {
            $index++;
        }

        return $base.'-'.$index;
    }

    private function resolve(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $fromBase = base_path($path);
        if (is_file($fromBase)) {
            return $fromBase;
        }

        throw new \InvalidArgumentException("File non trovato: {$path}");
    }

    /** @return array<string, mixed> */
    private function emptyResult(): array
    {
        return [
            'regions_created' => 0,
            'regions_updated' => 0,
            'provinces_created' => 0,
            'provinces_updated' => 0,
            'provinces_deactivated' => 0,
            'municipalities_created' => 0,
            'municipalities_updated' => 0,
            'municipalities_deactivated' => 0,
            'codes_changed' => 0,
            'transferred' => 0,
            'reallocation' => [],
            'anomalies' => [],
        ];
    }
}
