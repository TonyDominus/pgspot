<?php

namespace Tests\Feature;

use App\Enums\PoiStatus;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Province;
use App\Models\Region;
use App\Services\TerritoryImportService;
use App\Support\MunicipalityResolver;
use App\Support\TerritoryDefaults;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItalianTerritoryImportTest extends TestCase
{
    public function test_import_creates_region_province_and_municipality_and_keeps_the_chain(): void
    {
        $result = $this->importSample();

        $lazio = Region::query()->where('istat_code', '12')->firstOrFail();
        $roma = Province::query()->where('istat_code', '058')->firstOrFail();
        $comune = Municipality::query()->where('istat_code', '058091')->firstOrFail();

        $this->assertSame('Lazio', $lazio->name);
        $this->assertSame('Roma', $roma->name);
        $this->assertSame('RM', $roma->code);
        $this->assertSame('metropolitan_city', $roma->type);
        $this->assertEqualsWithDelta(41.89332, (float) $roma->latitude, 0.000001);
        $this->assertSame($lazio->id, $roma->region_id);
        $this->assertSame('Roma', $comune->name);
        $this->assertSame($roma->id, $comune->province_id);
        $this->assertSame($lazio->id, $comune->province->region_id);
        $this->assertFalse($comune->is_indexable);
        $this->assertGreaterThan(0, $result['regions_created']);
        $this->assertGreaterThan(0, $result['provinces_created']);
        $this->assertGreaterThan(0, $result['municipalities_created']);
    }

    public function test_second_import_does_not_duplicate_records(): void
    {
        $first = $this->importSample();
        $regions = Region::query()->count();
        $provinces = Province::query()->count();
        $municipalities = Municipality::query()->count();

        $second = $this->importSample();

        $this->assertSame(0, $second['regions_created']);
        $this->assertSame(0, $second['provinces_created']);
        $this->assertSame(0, $second['municipalities_created']);
        $this->assertSame($first['regions_created'] + $first['regions_updated'], $second['regions_updated']);
        $this->assertSame($regions, Region::query()->count());
        $this->assertSame($provinces, Province::query()->count());
        $this->assertSame($municipalities, Municipality::query()->count());
    }

    public function test_homonym_in_another_province_keeps_the_same_slug(): void
    {
        $this->importSample();

        $matches = Municipality::query()->where('slug', 'calliano')->with('province')->get();

        $this->assertCount(2, $matches);
        $this->assertCount(2, $matches->pluck('province_id')->unique());
        $this->assertEqualsCanonicalizing(['005019', '022050'], $matches->pluck('istat_code')->all());
    }

    public function test_slug_collision_in_the_same_province_uses_the_istat_code(): void
    {
        $result = $this->importSample();

        $plain = Municipality::query()->where('istat_code', '054098')->firstOrFail();
        $suffixed = Municipality::query()->where('istat_code', '054099')->firstOrFail();

        $this->assertSame('doppio', $plain->slug);
        $this->assertSame('doppio-054099', $suffixed->slug);
        $this->assertSame($plain->province_id, $suffixed->province_id);
        $this->assertNotEmpty($result['slug_collisions']);
    }

    public function test_existing_intro_and_indexable_flag_are_not_overwritten(): void
    {
        $perugia = $this->perugia();
        $perugia->update([
            'intro' => 'Testo che resta',
            'is_indexable' => true,
        ]);

        $this->importSample();
        $perugia->refresh();

        $this->assertSame('054039', $perugia->istat_code);
        $this->assertSame('perugia', $perugia->slug);
        $this->assertSame('Testo che resta', $perugia->intro);
        $this->assertTrue($perugia->is_indexable);
        $this->assertEqualsWithDelta(43.112, (float) $perugia->latitude, 0.000001);
        $this->assertFalse(Municipality::query()->where('istat_code', '054097')->firstOrFail()->is_indexable);
    }

    public function test_defaults_and_existing_poi_stay_unchanged(): void
    {
        $defaults = TerritoryDefaults::get();
        $perugia = $this->perugia();
        $poi = $this->createPublishedPoi([
            'municipality_id' => $perugia->id,
            'slug' => 'poi-invariato',
            'latitude' => 43.1122,
            'longitude' => 12.3881,
            'status' => PoiStatus::Published,
        ]);
        $before = DB::table('pois')->where('id', $poi->id)->first();

        $this->importSample();

        $after = DB::table('pois')->where('id', $poi->id)->first();
        $this->assertEquals($before, $after);
        $this->assertSame($defaults, TerritoryDefaults::get());
        $this->assertSame('Umbria', Region::query()->find($defaults['region_id'])?->name);
        $this->assertSame('Perugia', Province::query()->find($defaults['province_id'])?->name);
        $this->assertSame('Perugia', Municipality::query()->find($defaults['municipality_id'])?->name);
        $this->assertSame(1, Poi::query()->count());
    }

    public function test_missing_relation_is_reported_and_not_inserted(): void
    {
        $result = $this->importSample();

        $this->assertNull(Municipality::query()->where('name', 'Fantasma')->first());
        $this->assertNull(Region::query()->where('name', '')->first());
        $messages = collect($result['anomalies'])->pluck('message')->implode(' ');
        $this->assertStringContainsString('senza provincia valida', $messages);
        $this->assertStringContainsString('senza nome', $messages);
        $this->assertStringContainsString('senza coordinate', $messages);
        $this->assertNull(Municipality::query()->where('istat_code', '054097')->firstOrFail()->latitude);
    }

    public function test_admin_lookup_and_search_stay_on_the_requested_slice(): void
    {
        $this->importSample();
        $admin = $this->createAdmin();
        $perugiaProvince = Province::query()->where('code', 'PG')->firstOrFail();
        $expected = Municipality::query()->where('province_id', $perugiaProvince->id)->count();

        $this->actingAs($admin)
            ->getJson(route('territories.municipalities', ['province_id' => $perugiaProvince->id]))
            ->assertOk()
            ->assertJsonCount($expected)
            ->assertJsonFragment(['slug' => 'perugia'])
            ->assertJsonMissing(['slug' => 'calliano']);

        $this->actingAs($admin)
            ->get(route('admin.territories.index', ['tab' => 'municipalities', 'q' => 'Calliano']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('municipalities.data', 2)
                ->where('municipalities.total', 2));
    }

    public function test_resolver_uses_istat_prefix_and_does_not_guess_an_ambiguous_slug(): void
    {
        $this->importSample();

        $byCode = MunicipalityResolver::find('istat:054039');
        $byShortCode = MunicipalityResolver::find('istat:54039');

        $this->assertSame('054039', $byCode->istat_code);
        $this->assertTrue($byCode->is($byShortCode));
        $this->assertSame('Perugia', MunicipalityResolver::find('perugia')->name);

        try {
            MunicipalityResolver::find('054039');
            $this->fail('Un numero senza prefisso non deve essere letto come codice istat.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('istat:054039', $e->getMessage());
        }

        try {
            MunicipalityResolver::find('calliano');
            $this->fail('Uno slug ambiguo non deve scegliere un comune.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('ambiguo', $e->getMessage());
        }
    }

    public function test_command_imports_the_three_files_and_dry_run_rolls_back(): void
    {
        $dir = storage_path('framework/italian-territory-command');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir.'/regioni.json', json_encode([
            ['id' => '14', 'nome' => 'Molise', 'latitudine' => '41.5', 'longitudine' => '14.6'],
        ]));
        file_put_contents($dir.'/province.json', json_encode([
            ['id' => '70', 'id_regione' => '14', 'nome' => 'Campobasso', 'sigla_automobilistica' => 'CB', 'codice_citta_metropolitana' => null, 'latitudine' => '41.56', 'longitudine' => '14.66'],
        ]));
        file_put_contents($dir.'/comuni.json', json_encode([
            ['id' => '70006', 'id_regione' => '14', 'id_provincia' => '70', 'nome' => 'Campobasso', 'latitudine' => '41.56', 'longitudine' => '14.66'],
        ]));

        try {
            $this->artisan('pgspot:import-territories', ['--regioni' => $dir.'/regioni.json'])
                ->expectsOutputToContain('--regioni')
                ->assertFailed();

            $this->artisan('pgspot:import-territories', [
                '--regioni' => $dir.'/regioni.json',
                '--province' => $dir.'/province.json',
                '--comuni' => $dir.'/comuni.json',
                '--dry-run' => true,
            ])->assertSuccessful();

            $this->assertNull(Region::query()->where('istat_code', '14')->first());

            $this->artisan('pgspot:import-territories', [
                '--regioni' => $dir.'/regioni.json',
                '--province' => $dir.'/province.json',
                '--comuni' => $dir.'/comuni.json',
            ])->assertSuccessful();
        } finally {
            @unlink($dir.'/regioni.json');
            @unlink($dir.'/province.json');
            @unlink($dir.'/comuni.json');
        }

        $campobasso = Municipality::query()->where('istat_code', '070006')->firstOrFail();
        $this->assertSame('Campobasso', $campobasso->province->name);
        $this->assertSame('Molise', $campobasso->province->region->name);
        $this->assertFalse($campobasso->is_indexable);
    }

    /**
     * @return array<string, mixed>
     */
    private function importSample(): array
    {
        return app(TerritoryImportService::class)->importItalianDataset(
            $this->regions(),
            $this->provinces(),
            $this->municipalities(),
        );
    }

    /** @return list<array<string, mixed>> */
    private function regions(): array
    {
        return [
            ['id' => '1', 'nome' => 'Piemonte', 'latitudine' => '45.0', 'longitudine' => '8.0'],
            ['id' => '4', 'nome' => 'Trentino-Alto Adige/Südtirol', 'latitudine' => '46.4', 'longitudine' => '11.1'],
            ['id' => '10', 'nome' => 'Umbria', 'latitudine' => '42.9', 'longitudine' => '12.5'],
            ['id' => '12', 'nome' => 'Lazio', 'latitudine' => '41.8', 'longitudine' => '12.7'],
            ['id' => '', 'nome' => ''],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function provinces(): array
    {
        return [
            ['id' => '5', 'id_regione' => '1', 'nome' => 'Asti', 'sigla_automobilistica' => 'AT', 'codice_citta_metropolitana' => null, 'latitudine' => '44.9', 'longitudine' => '8.2'],
            ['id' => '22', 'id_regione' => '4', 'nome' => 'Trento', 'sigla_automobilistica' => 'TN', 'codice_citta_metropolitana' => null, 'latitudine' => '46.0', 'longitudine' => '11.1'],
            ['id' => '54', 'id_regione' => '10', 'nome' => 'Perugia', 'sigla_automobilistica' => 'PG', 'codice_citta_metropolitana' => null, 'latitudine' => '43.1', 'longitudine' => '12.3'],
            ['id' => '55', 'id_regione' => '10', 'nome' => 'Terni', 'sigla_automobilistica' => 'TR', 'codice_citta_metropolitana' => null, 'latitudine' => '42.5', 'longitudine' => '12.6'],
            ['id' => '58', 'id_regione' => '12', 'nome' => 'Roma', 'sigla_automobilistica' => 'RM', 'codice_citta_metropolitana' => '258', 'latitudine' => '41.89332', 'longitudine' => '12.48293'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function municipalities(): array
    {
        return [
            ['id' => '54039', 'id_regione' => '10', 'id_provincia' => '54', 'nome' => 'Perugia', 'latitudine' => '43.112', 'longitudine' => '12.388'],
            ['id' => '5019', 'id_regione' => '1', 'id_provincia' => '5', 'nome' => 'Calliano', 'latitudine' => '45.0', 'longitudine' => '8.2'],
            ['id' => '22050', 'id_regione' => '4', 'id_provincia' => '22', 'nome' => 'Calliano', 'latitudine' => '45.9', 'longitudine' => '11.1'],
            ['id' => '58091', 'id_regione' => '12', 'id_provincia' => '58', 'nome' => 'Roma', 'latitudine' => '41.89', 'longitudine' => '12.48'],
            ['id' => '54098', 'id_regione' => '10', 'id_provincia' => '54', 'nome' => 'Doppio', 'latitudine' => '43.0', 'longitudine' => '12.3'],
            ['id' => '54099', 'id_regione' => '10', 'id_provincia' => '54', 'nome' => 'Doppio', 'latitudine' => '43.1', 'longitudine' => '12.4'],
            ['id' => '54097', 'id_regione' => '10', 'id_provincia' => '54', 'nome' => 'Senzacoordinate', 'latitudine' => null, 'longitudine' => null],
            ['id' => '999001', 'id_regione' => '10', 'id_provincia' => '999', 'nome' => 'Fantasma', 'latitudine' => '43.0', 'longitudine' => '12.0'],
        ];
    }

    private function perugia(): Municipality
    {
        return Municipality::query()
            ->where('slug', 'perugia')
            ->whereHas('province', fn ($query) => $query->where('code', 'PG'))
            ->firstOrFail();
    }
}
