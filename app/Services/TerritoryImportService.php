<?php

namespace App\Services;

use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TerritoryImportService
{
    /**
     * @return array{
     *     regions_created: int,
     *     regions_updated: int,
     *     provinces_created: int,
     *     provinces_updated: int,
     *     municipalities_created: int,
     *     municipalities_updated: int
     * }
     */
    public function importFile(string $path): array
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("File non trovato: {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => $this->importJson($path),
            'csv' => $this->importCsv($path),
            default => throw new \InvalidArgumentException('Formato non supportato. Usa un file .json o .csv.'),
        };
    }

    /**
     * Anagrafica nazionale in tre elenchi JSON (regioni, province, comuni).
     * La chiave di riconciliazione è il codice numerico del file, salvato con zeri iniziali.
     * Non modifica intro, is_indexable, slug già presenti, né i POI.
     *
     * @return array{
     *     regions_created: int,
     *     regions_updated: int,
     *     provinces_created: int,
     *     provinces_updated: int,
     *     municipalities_created: int,
     *     municipalities_updated: int,
     *     anomalies: list<array{entity: string, id: string, message: string}>,
     *     slug_collisions: list<array{entity: string, name: string, slug: string}>
     * }
     */
    public function importItalianFiles(string $regionsPath, string $provincesPath, string $municipalitiesPath): array
    {
        $regions = $this->readJsonList($this->resolvePath($regionsPath), ['id', 'nome']);
        $provinces = $this->readJsonList($this->resolvePath($provincesPath), ['id', 'id_regione', 'nome', 'sigla_automobilistica']);
        $municipalities = $this->readJsonList($this->resolvePath($municipalitiesPath), ['id', 'id_regione', 'id_provincia', 'nome']);

        return $this->importItalianDataset($regions, $provinces, $municipalities);
    }

    /**
     * @param  list<array<string, mixed>>  $regionRows
     * @param  list<array<string, mixed>>  $provinceRows
     * @param  list<array<string, mixed>>  $municipalityRows
     * @return array{
     *     regions_created: int,
     *     regions_updated: int,
     *     provinces_created: int,
     *     provinces_updated: int,
     *     municipalities_created: int,
     *     municipalities_updated: int,
     *     anomalies: list<array{entity: string, id: string, message: string}>,
     *     slug_collisions: list<array{entity: string, name: string, slug: string}>
     * }
     */
    public function importItalianDataset(array $regionRows, array $provinceRows, array $municipalityRows): array
    {
        $counts = $this->emptyCounts();
        $counts['anomalies'] = [];
        $counts['slug_collisions'] = [];

        $regionsByIstat = [];
        $regionsBySlug = [];
        foreach (Region::query()->get() as $region) {
            if ($region->istat_code) {
                $regionsByIstat[$region->istat_code] = $region;
            }
            $regionsBySlug[$region->slug] = $region;
        }

        $regionIdBySource = [];

        foreach ($regionRows as $row) {
            $sourceId = trim((string) ($row['id'] ?? ''));
            $name = trim((string) ($row['nome'] ?? ''));
            if ($sourceId === '' || ! ctype_digit($sourceId) || $name === '') {
                $counts['anomalies'][] = [
                    'entity' => 'region',
                    'id' => $sourceId,
                    'message' => 'Regione senza id numerico o senza nome.',
                ];

                continue;
            }

            $istat = str_pad($sourceId, 2, '0', STR_PAD_LEFT);
            $existing = $regionsByIstat[$istat] ?? null;
            $slug = Str::slug($name) ?: 'regione-'.$istat;
            if (! $existing && isset($regionsBySlug[$slug])) {
                $candidate = $regionsBySlug[$slug];
                if ($candidate->istat_code === null || $candidate->istat_code === $istat) {
                    $existing = $candidate;
                }
            }

            if ($existing) {
                $existing->name = $name;
                $existing->istat_code = $istat;
                $existing->save();
                $regionsByIstat[$istat] = $existing;
                $regionsBySlug[$existing->slug] = $existing;
                $regionIdBySource[$sourceId] = $existing->id;
                $counts['regions_updated']++;

                continue;
            }

            $slug = $this->availableSlug('regions', $slug, [], $istat, $counts['slug_collisions'], 'region', $name);
            $created = Region::query()->create([
                'name' => $name,
                'slug' => $slug,
                'istat_code' => $istat,
            ]);
            $regionsByIstat[$istat] = $created;
            $regionsBySlug[$slug] = $created;
            $regionIdBySource[$sourceId] = $created->id;
            $counts['regions_created']++;
        }

        $provincesByIstat = [];
        $provincesByCode = [];
        $provincesBySlug = [];
        foreach (Province::query()->get() as $province) {
            if ($province->istat_code) {
                $provincesByIstat[$province->istat_code] = $province;
            }
            if ($province->code) {
                $provincesByCode[$province->code] = $province;
            }
            $provincesBySlug[$province->region_id.'|'.$province->slug] = $province;
        }

        $provinceIdBySource = [];

        foreach ($provinceRows as $row) {
            $sourceId = trim((string) ($row['id'] ?? ''));
            $regionSource = trim((string) ($row['id_regione'] ?? ''));
            $name = trim((string) ($row['nome'] ?? ''));
            $sigla = $this->nullableCode($row['sigla_automobilistica'] ?? null);
            if ($sourceId === '' || ! ctype_digit($sourceId) || $name === '') {
                $counts['anomalies'][] = [
                    'entity' => 'province',
                    'id' => $sourceId,
                    'message' => 'Provincia senza id numerico o senza nome.',
                ];

                continue;
            }

            $regionId = $regionIdBySource[$regionSource] ?? null;
            if (! $regionId) {
                $counts['anomalies'][] = [
                    'entity' => 'province',
                    'id' => $sourceId,
                    'message' => "Provincia «{$name}» senza regione valida ({$regionSource}).",
                ];

                continue;
            }

            $istat = str_pad($sourceId, 3, '0', STR_PAD_LEFT);
            $type = $this->provinceType($row['codice_citta_metropolitana'] ?? null);
            $slug = Str::slug($name) ?: 'provincia-'.$istat;
            $existing = $provincesByIstat[$istat] ?? null;
            if (! $existing && $sigla && isset($provincesByCode[$sigla])) {
                $candidate = $provincesByCode[$sigla];
                if ($candidate->istat_code === null || $candidate->istat_code === $istat) {
                    $existing = $candidate;
                }
            }
            if (! $existing && isset($provincesBySlug[$regionId.'|'.$slug])) {
                $candidate = $provincesBySlug[$regionId.'|'.$slug];
                if ($candidate->istat_code === null || $candidate->istat_code === $istat) {
                    $existing = $candidate;
                }
            }

            if ($existing) {
                $existing->region_id = $regionId;
                $existing->name = $name;
                $existing->istat_code = $istat;
                $existing->type = $type;
                if ($sigla) {
                    $existing->code = $sigla;
                }
                $this->assignCoordinates($existing, $row['latitudine'] ?? null, $row['longitudine'] ?? null);
                $existing->save();
                $provincesByIstat[$istat] = $existing;
                if ($existing->code) {
                    $provincesByCode[$existing->code] = $existing;
                }
                $provincesBySlug[$existing->region_id.'|'.$existing->slug] = $existing;
                $provinceIdBySource[$sourceId] = $existing->id;
                $counts['provinces_updated']++;

                continue;
            }

            $slug = $this->availableSlug('provinces', $slug, ['region_id' => $regionId], $istat, $counts['slug_collisions'], 'province', $name);
            $created = Province::query()->create([
                'region_id' => $regionId,
                'name' => $name,
                'slug' => $slug,
                'code' => $sigla,
                'type' => $type,
                'istat_code' => $istat,
                'latitude' => $this->nullableFloat($row['latitudine'] ?? null),
                'longitude' => $this->nullableFloat($row['longitudine'] ?? null),
            ]);
            $provincesByIstat[$istat] = $created;
            if ($sigla) {
                $provincesByCode[$sigla] = $created;
            }
            $provincesBySlug[$regionId.'|'.$slug] = $created;
            $provinceIdBySource[$sourceId] = $created->id;
            $counts['provinces_created']++;
        }

        $municipalitiesByIstat = [];
        $municipalitiesBySlug = [];
        foreach (Municipality::query()->get(['id', 'province_id', 'name', 'slug', 'istat_code', 'latitude', 'longitude', 'intro', 'is_indexable']) as $municipality) {
            if ($municipality->istat_code) {
                $municipalitiesByIstat[$municipality->istat_code] = $municipality;
            }
            $municipalitiesBySlug[$municipality->province_id.'|'.$municipality->slug] = $municipality;
        }

        foreach ($municipalityRows as $row) {
            $sourceId = trim((string) ($row['id'] ?? ''));
            $provinceSource = trim((string) ($row['id_provincia'] ?? ''));
            $name = trim((string) ($row['nome'] ?? ''));
            if ($sourceId === '' || ! ctype_digit($sourceId) || $name === '') {
                $counts['anomalies'][] = [
                    'entity' => 'municipality',
                    'id' => $sourceId,
                    'message' => 'Comune senza id numerico o senza nome.',
                ];

                continue;
            }

            $provinceId = $provinceIdBySource[$provinceSource] ?? null;
            if (! $provinceId) {
                $counts['anomalies'][] = [
                    'entity' => 'municipality',
                    'id' => $sourceId,
                    'message' => "Comune «{$name}» senza provincia valida ({$provinceSource}).",
                ];

                continue;
            }

            if (! str_starts_with($sourceId, $provinceSource)) {
                $counts['anomalies'][] = [
                    'entity' => 'municipality',
                    'id' => $sourceId,
                    'message' => "L'id di «{$name}» non è coerente con la provincia {$provinceSource}.",
                ];
            }

            $istat = str_pad($sourceId, 6, '0', STR_PAD_LEFT);
            $slug = Str::slug($name) ?: 'comune-'.$istat;
            $existing = $municipalitiesByIstat[$istat] ?? null;
            if (! $existing && isset($municipalitiesBySlug[$provinceId.'|'.$slug])) {
                $candidate = $municipalitiesBySlug[$provinceId.'|'.$slug];
                if ($candidate->istat_code === null || $candidate->istat_code === $istat) {
                    $existing = $candidate;
                }
            }

            $latitude = $this->nullableFloat($row['latitudine'] ?? null);
            $longitude = $this->nullableFloat($row['longitudine'] ?? null);
            if ($latitude === null || $longitude === null) {
                $counts['anomalies'][] = [
                    'entity' => 'municipality',
                    'id' => $sourceId,
                    'message' => "Comune «{$name}» senza coordinate.",
                ];
            }

            if ($existing) {
                $existing->province_id = $provinceId;
                $existing->name = $name;
                $existing->istat_code = $istat;
                $this->assignCoordinates($existing, $row['latitudine'] ?? null, $row['longitudine'] ?? null);
                $existing->save();
                $municipalitiesByIstat[$istat] = $existing;
                $municipalitiesBySlug[$existing->province_id.'|'.$existing->slug] = $existing;
                $counts['municipalities_updated']++;

                continue;
            }

            $slug = $this->availableSlug('municipalities', $slug, ['province_id' => $provinceId], $istat, $counts['slug_collisions'], 'municipality', $name);
            $created = Municipality::query()->create([
                'province_id' => $provinceId,
                'name' => $name,
                'slug' => $slug,
                'istat_code' => $istat,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_indexable' => false,
            ]);
            $municipalitiesByIstat[$istat] = $created;
            $municipalitiesBySlug[$provinceId.'|'.$slug] = $created;
            $counts['municipalities_created']++;
        }

        return $counts;
    }

    /**
     * @return array{
     *     regions_created: int,
     *     regions_updated: int,
     *     provinces_created: int,
     *     provinces_updated: int,
     *     municipalities_created: int,
     *     municipalities_updated: int
     * }
     */
    private function importJson(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || ! isset($decoded['regions']) || ! is_array($decoded['regions'])) {
            throw new \InvalidArgumentException('Il JSON deve contenere la chiave "regions".');
        }

        $counts = $this->emptyCounts();

        foreach ($decoded['regions'] as $regionRow) {
            if (! is_array($regionRow)) {
                throw new \InvalidArgumentException('Ogni regione deve essere un oggetto.');
            }

            [$region, $created] = $this->upsertRegion($regionRow);
            $created ? $counts['regions_created']++ : $counts['regions_updated']++;

            foreach ($regionRow['provinces'] ?? [] as $provinceRow) {
                if (! is_array($provinceRow)) {
                    throw new \InvalidArgumentException('Ogni provincia deve essere un oggetto.');
                }

                [$province, $provinceCreated] = $this->upsertProvince($region, $provinceRow);
                $provinceCreated ? $counts['provinces_created']++ : $counts['provinces_updated']++;

                foreach ($provinceRow['municipalities'] ?? [] as $municipalityRow) {
                    if (! is_array($municipalityRow)) {
                        throw new \InvalidArgumentException('Ogni comune deve essere un oggetto.');
                    }

                    $municipalityCreated = $this->upsertMunicipality($province, $municipalityRow);
                    $municipalityCreated ? $counts['municipalities_created']++ : $counts['municipalities_updated']++;
                }
            }
        }

        return $counts;
    }

    /**
     * @return array{
     *     regions_created: int,
     *     regions_updated: int,
     *     provinces_created: int,
     *     provinces_updated: int,
     *     municipalities_created: int,
     *     municipalities_updated: int
     * }
     */
    private function importCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \InvalidArgumentException("Impossibile leggere {$path}.");
        }

        $delimiter = $this->detectDelimiter($handle);
        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header)) {
            fclose($handle);
            throw new \InvalidArgumentException('CSV senza intestazione.');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? $header[0];
        $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header);

        $required = ['region_name', 'region_slug', 'province_name', 'province_slug', 'municipality_name', 'municipality_slug'];
        $missing = array_values(array_diff($required, $header));
        if ($missing !== []) {
            fclose($handle);
            throw new \InvalidArgumentException('CSV: colonne richieste mancanti: '.implode(', ', $missing).'.');
        }

        $counts = $this->emptyCounts();
        $seenRegions = [];
        $seenProvinces = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            $data = [];
            foreach ($header as $index => $column) {
                $data[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            if ($data['region_slug'] === '' && $data['municipality_name'] === '') {
                continue;
            }

            [$region, $regionCreated] = $this->upsertRegion([
                'name' => $data['region_name'],
                'slug' => $data['region_slug'],
                'istat_code' => $data['region_istat_code'] ?? null,
            ]);
            if (! isset($seenRegions[$region->id])) {
                $seenRegions[$region->id] = true;
                $regionCreated ? $counts['regions_created']++ : $counts['regions_updated']++;
            }

            [$province, $provinceCreated] = $this->upsertProvince($region, [
                'name' => $data['province_name'],
                'slug' => $data['province_slug'],
                'code' => $data['province_code'] ?? null,
                'istat_code' => $data['province_istat_code'] ?? null,
            ]);
            if (! isset($seenProvinces[$province->id])) {
                $seenProvinces[$province->id] = true;
                $provinceCreated ? $counts['provinces_created']++ : $counts['provinces_updated']++;
            }

            $municipalityCreated = $this->upsertMunicipality($province, [
                'name' => $data['municipality_name'],
                'slug' => $data['municipality_slug'],
                'istat_code' => $data['istat_code'] ?? ($data['municipality_istat_code'] ?? null),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);
            $municipalityCreated ? $counts['municipalities_created']++ : $counts['municipalities_updated']++;
        }

        fclose($handle);

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: Region, 1: bool}
     */
    private function upsertRegion(array $row): array
    {
        $name = $this->requiredString($row, 'name', 'regione');
        $slug = $this->slugOrName($row['slug'] ?? null, $name);
        $existing = Region::query()->where('slug', $slug)->first();

        if ($existing) {
            $existing->name = $name;
            $this->fillIfEmpty($existing, 'istat_code', $this->nullableString($row['istat_code'] ?? null));
            $existing->save();

            return [$existing, false];
        }

        return [Region::query()->create([
            'name' => $name,
            'slug' => $slug,
            'istat_code' => $this->nullableString($row['istat_code'] ?? null),
        ]), true];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: Province, 1: bool}
     */
    private function upsertProvince(Region $region, array $row): array
    {
        $name = $this->requiredString($row, 'name', 'provincia');
        $slug = $this->slugOrName($row['slug'] ?? null, $name);
        $existing = Province::query()
            ->where('region_id', $region->id)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            $existing->name = $name;
            $this->fillIfEmpty($existing, 'code', $this->nullableCode($row['code'] ?? null));
            $this->fillIfEmpty($existing, 'istat_code', $this->nullableString($row['istat_code'] ?? null));
            $this->fillIfEmpty($existing, 'latitude', $this->nullableFloat($row['latitude'] ?? null));
            $this->fillIfEmpty($existing, 'longitude', $this->nullableFloat($row['longitude'] ?? null));
            $existing->save();

            return [$existing, false];
        }

        return [Province::query()->create([
            'region_id' => $region->id,
            'name' => $name,
            'slug' => $slug,
            'code' => $this->nullableCode($row['code'] ?? null),
            'istat_code' => $this->nullableString($row['istat_code'] ?? null),
            'latitude' => $this->nullableFloat($row['latitude'] ?? null),
            'longitude' => $this->nullableFloat($row['longitude'] ?? null),
        ]), true];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertMunicipality(Province $province, array $row): bool
    {
        $name = $this->requiredString($row, 'name', 'comune');
        $slug = $this->slugOrName($row['slug'] ?? null, $name);
        $existing = Municipality::query()
            ->where('province_id', $province->id)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            $existing->name = $name;
            $this->fillIfEmpty($existing, 'istat_code', $this->nullableString($row['istat_code'] ?? null));
            $this->fillIfEmpty($existing, 'latitude', $this->nullableFloat($row['latitude'] ?? null));
            $this->fillIfEmpty($existing, 'longitude', $this->nullableFloat($row['longitude'] ?? null));
            $existing->save();

            return false;
        }

        Municipality::query()->create([
            'province_id' => $province->id,
            'name' => $name,
            'slug' => $slug,
            'istat_code' => $this->nullableString($row['istat_code'] ?? null),
            'latitude' => $this->nullableFloat($row['latitude'] ?? null),
            'longitude' => $this->nullableFloat($row['longitude'] ?? null),
            'is_indexable' => false,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function requiredString(array $row, string $key, string $label): string
    {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value === '') {
            throw new \InvalidArgumentException("Manca il nome di una {$label}.");
        }

        return $value;
    }

    private function slugOrName(mixed $slug, string $name): string
    {
        $value = Str::slug(trim((string) ($slug ?? '')));

        return $value !== '' ? $value : (Str::slug($name) ?: 'territorio');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableCode(mixed $value): ?string
    {
        $text = $this->nullableString($value);

        return $text === null ? null : strtoupper($text);
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException("Coordinata non numerica: {$value}");
        }

        return (float) $value;
    }

    private function fillIfEmpty(Region|Province|Municipality $model, string $attribute, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($model->{$attribute} === null || $model->{$attribute} === '') {
            $model->{$attribute} = $value;
        }
    }

    /** @param  resource  $handle */
    private function detectDelimiter($handle): string
    {
        $line = fgets($handle);
        rewind($handle);

        if (! is_string($line)) {
            return ',';
        }

        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    /**
     * @param  list<string>  $requiredKeys
     * @return list<array<string, mixed>>
     */
    private function readJsonList(string $path, array $requiredKeys): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw new \InvalidArgumentException("{$path} deve essere un array JSON di record.");
        }
        if ($decoded === []) {
            throw new \InvalidArgumentException("{$path} è vuoto.");
        }

        $first = $decoded[0];
        if (! is_array($first)) {
            throw new \InvalidArgumentException("{$path} non contiene oggetti.");
        }

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $first)) {
                throw new \InvalidArgumentException("{$path} non ha il campo {$key}.");
            }
        }

        return $decoded;
    }

    private function resolvePath(string $path): string
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

    private function provinceType(mixed $metropolitanCode): string
    {
        if ($metropolitanCode === null || trim((string) $metropolitanCode) === '') {
            return 'province';
        }

        return 'metropolitan_city';
    }

    private function assignCoordinates(Province|Municipality $model, mixed $latitude, mixed $longitude): void
    {
        $lat = $this->nullableFloat($latitude);
        $lng = $this->nullableFloat($longitude);
        if ($lat !== null) {
            $model->latitude = $lat;
        }
        if ($lng !== null) {
            $model->longitude = $lng;
        }
    }

    /**
     * @param  array<string, int|string>  $scope
     * @param  list<array{entity: string, name: string, slug: string}>  $collisions
     */
    private function availableSlug(string $table, string $base, array $scope, string $istat, array &$collisions, string $entity, string $name): string
    {
        if (! $this->slugExists($table, $base, $scope)) {
            return $base;
        }

        $withCode = $base.'-'.$istat;
        $collisions[] = [
            'entity' => $entity,
            'name' => $name,
            'slug' => $withCode,
        ];

        if (! $this->slugExists($table, $withCode, $scope)) {
            return $withCode;
        }

        $index = 2;
        $candidate = $withCode.'-'.$index;
        while ($this->slugExists($table, $candidate, $scope)) {
            $index++;
            $candidate = $withCode.'-'.$index;
        }

        return $candidate;
    }

    /** @param  array<string, int|string>  $scope */
    private function slugExists(string $table, string $slug, array $scope): bool
    {
        return DB::table($table)->where('slug', $slug)->where($scope)->exists();
    }

    /**
     * @return array{
     *     regions_created: int,
     *     regions_updated: int,
     *     provinces_created: int,
     *     provinces_updated: int,
     *     municipalities_created: int,
     *     municipalities_updated: int
     * }
     */
    private function emptyCounts(): array
    {
        return [
            'regions_created' => 0,
            'regions_updated' => 0,
            'provinces_created' => 0,
            'provinces_updated' => 0,
            'municipalities_created' => 0,
            'municipalities_updated' => 0,
        ];
    }
}
