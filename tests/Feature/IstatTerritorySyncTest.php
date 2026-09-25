<?php

namespace Tests\Feature;

use App\Enums\PoiStatus;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Services\IstatTerritorySyncService;
use App\Support\MunicipalityResolver;
use App\Support\TerritoryDefaults;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IstatTerritorySyncTest extends TestCase
{
    public function test_sync_is_idempotent_and_keeps_perugia_poi_and_editorial_fields(): void
    {
        $perugia = $this->perugia();
        $perugia->update([
            'intro' => 'Testo da conservare',
            'is_indexable' => true,
            'latitude' => 43.112,
            'longitude' => 12.388,
        ]);
        $poi = $this->createPublishedPoi([
            'municipality_id' => $perugia->id,
            'slug' => 'poi-perugia-stabile',
            'latitude' => 43.111,
            'longitude' => 12.389,
            'address' => 'Corso Vannucci',
            'status' => PoiStatus::Published,
        ]);
        $before = DB::table('pois')->where('id', $poi->id)->first();
        $defaults = TerritoryDefaults::get();

        $first = $this->sync($this->sample());
        $second = $this->sync($this->sample());

        $perugia->refresh();
        $poi->refresh();
        $after = DB::table('pois')->where('id', $poi->id)->first();

        $this->assertSame('054039', $perugia->istat_code);
        $this->assertSame('Perugia', $perugia->name);
        $this->assertTrue($perugia->is_active);
        $this->assertSame('perugia', $perugia->slug);
        $this->assertSame('Testo da conservare', $perugia->intro);
        $this->assertTrue($perugia->is_indexable);
        $this->assertEqualsWithDelta(43.112, (float) $perugia->latitude, 0.000001);
        $this->assertEquals($before, $after);
        $this->assertSame($perugia->id, $poi->municipality_id);
        $this->assertSame(0, $second['regions_created']);
        $this->assertSame(0, $second['provinces_created']);
        $this->assertSame(0, $second['municipalities_created']);
        $this->assertSame(0, $second['provinces_updated']);
        $this->assertSame(0, $second['municipalities_updated']);
        $this->assertSame(0, $second['municipalities_deactivated']);
        $this->assertGreaterThan(0, $first['municipalities_created']);

        $resolved = TerritoryDefaults::get();
        $this->assertSame(
            Region::query()->where('istat_code', '10')->value('id'),
            $resolved['region_id'],
        );
        $this->assertSame(
            Province::query()->where('istat_code', '054')->value('id'),
            $resolved['province_id'],
        );
        $this->assertSame($perugia->id, $resolved['municipality_id']);
        $this->assertSame('Umbria', Region::query()->find($resolved['region_id'])?->name);
        $this->assertNotSame(0, $defaults['municipality_id']);
    }

    public function test_rename_code_change_and_suppressed_municipality_are_handled(): void
    {
        $perugiaProvince = Province::query()->where('code', 'PG')->firstOrFail();
        $old = Municipality::query()->create([
            'province_id' => $perugiaProvince->id,
            'name' => 'Vecchio Nome',
            'slug' => 'vecchio-nome',
            'istat_code' => '054050',
            'intro' => 'Scheda locale',
            'is_indexable' => true,
            'is_active' => true,
        ]);
        $suppressed = Municipality::query()->create([
            'province_id' => $perugiaProvince->id,
            'name' => 'Abetone',
            'slug' => 'abetone',
            'istat_code' => '047099',
            'is_indexable' => false,
            'is_active' => true,
        ]);
        $referenced = Municipality::query()->create([
            'province_id' => $perugiaProvince->id,
            'name' => 'Comune Agganciato',
            'slug' => 'comune-agganciato',
            'istat_code' => '054077',
            'is_indexable' => false,
            'is_active' => true,
        ]);
        $poi = $this->createPublishedPoi([
            'municipality_id' => $referenced->id,
            'slug' => 'poi-sul-comune-vecchio',
        ]);
        $lecco = Municipality::query()->create([
            'province_id' => $perugiaProvince->id,
            'name' => 'Torre de\' Busi',
            'slug' => 'torre-de-busi',
            'istat_code' => '016080',
            'is_indexable' => false,
            'is_active' => true,
        ]);

        $rows = $this->sample();
        $rows[] = $this->row('10', 'Umbria', '054', '054', 'Perugia', 'province', 'PG', '054050', '054050', 'Nome Aggiornato');
        $rows[] = $this->row('03', 'Lombardia', '097', '097', 'Lecco', 'province', 'LC', '097080', '016080', 'Torre de\' Busi');

        $result = $this->sync($rows);

        $old->refresh();
        $suppressed->refresh();
        $referenced->refresh();
        $poi->refresh();
        $lecco->refresh();
        $this->assertSame('097080', $lecco->istat_code);
        $this->assertSame('097', $lecco->province->istat_code);
        $this->assertSame('Lecco', $lecco->province->name);
        $this->assertSame('Nome Aggiornato', $old->name);
        $this->assertSame('vecchio-nome', $old->slug);
        $this->assertSame('Scheda locale', $old->intro);
        $this->assertTrue($old->is_indexable);
        $this->assertTrue($old->is_active);
        $this->assertFalse($suppressed->is_active);
        $this->assertFalse($referenced->is_active);
        $this->assertNotNull(Municipality::query()->find($referenced->id));
        $this->assertSame($referenced->id, $poi->municipality_id);
        $this->assertContains('Comune Agganciato (054077)', $result['reallocation']);
    }

    public function test_supracommunal_types_and_active_lookup(): void
    {
        $this->sync($this->sample());

        $this->assertSame('metropolitan_city', Province::query()->where('istat_code', '258')->value('type'));
        $this->assertSame('free_municipal_consortium', Province::query()->where('istat_code', '081')->value('type'));
        $this->assertSame('autonomous_province', Province::query()->where('istat_code', '021')->value('type'));
        $this->assertSame('non_administrative_unit', Province::query()->where('istat_code', '032')->value('type'));
        $this->assertTrue((bool) Province::query()->where('istat_code', '032')->value('is_active'));

        $admin = $this->createAdmin();
        $perugiaProvince = Province::query()->where('istat_code', '054')->firstOrFail();
        Municipality::query()->create([
            'province_id' => $perugiaProvince->id,
            'name' => 'Abetone',
            'slug' => 'abetone',
            'istat_code' => '047099',
            'is_indexable' => false,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->getJson(route('territories.municipalities', ['province_id' => $perugiaProvince->id]))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'perugia'])
            ->assertJsonMissing(['slug' => 'abetone']);

        $this->actingAs($admin)
            ->getJson(route('territories.provinces', ['region_id' => Region::query()->where('istat_code', '10')->value('id')]))
            ->assertOk()
            ->assertJsonMissing(['slug' => 'terni']);

        try {
            MunicipalityResolver::find('istat:047099');
            $this->fail('Un codice non più attivo non deve risolvere un altro comune.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('non è più attivo', $e->getMessage());
        }

        $this->assertSame('Perugia', MunicipalityResolver::find('istat:054039')->name);
    }

    public function test_sardinia_replacement_deactivates_the_old_unit(): void
    {
        $sardegna = Region::query()->create(['name' => 'Sardegna', 'slug' => 'sardegna', 'istat_code' => '20']);
        $old = Province::query()->create([
            'region_id' => $sardegna->id,
            'name' => 'Sassari',
            'slug' => 'sassari',
            'code' => 'SS',
            'type' => 'province',
            'istat_code' => '090',
            'is_active' => true,
        ]);
        $olbia = Municipality::query()->create([
            'province_id' => $old->id,
            'name' => 'Olbia',
            'slug' => 'olbia',
            'istat_code' => '104017',
            'is_indexable' => false,
            'is_active' => true,
        ]);

        $rows = $this->sample();
        $rows[] = $this->row('20', 'Sardegna', '312', '112', 'Sassari', 'metropolitan_city', 'SS', '112050', '090064', 'Sassari');
        $rows[] = $this->row('20', 'Sardegna', '113', '113', 'Gallura Nord-Est Sardegna', 'province', 'OT', '113017', '104017', 'Olbia');

        $this->sync($rows);
        $old->refresh();
        $olbia->refresh();

        $sassari = Province::query()->where('istat_code', '312')->firstOrFail();
        $gallura = Province::query()->where('istat_code', '113')->firstOrFail();

        $this->assertSame('metropolitan_city', $sassari->type);
        $this->assertTrue($sassari->is_active);
        $this->assertFalse($old->is_active);
        $this->assertNull($old->code);
        $this->assertSame('SS', $sassari->code);
        $this->assertTrue($gallura->is_active);
        $this->assertSame($gallura->id, $olbia->province_id);
        $this->assertSame('113017', $olbia->istat_code);
        $this->assertTrue($olbia->is_active);
        $this->assertSame(1, Province::query()->where('istat_code', '312')->count());
    }

    public function test_official_workbook_declares_the_expected_municipality_count(): void
    {
        $path = database_path('data/territories/istat/Elenco-comuni-italiani.xlsx');
        $rows = app(IstatTerritorySyncService::class)->rowsFromWorkbook($path);
        $codes = collect($rows)->pluck('municipality_code')->unique();

        $this->assertCount(7894, $rows);
        $this->assertCount(7894, $codes);
        $this->assertTrue($codes->contains('054039'));
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array<string, mixed>
     */
    private function sync(array $rows): array
    {
        return app(IstatTerritorySyncService::class)->syncOfficial($rows);
    }

    /** @return list<array<string, string>> */
    private function sample(): array
    {
        return [
            $this->row('10', 'Umbria', '054', '054', 'Perugia', 'province', 'PG', '054039', '054039', 'Perugia'),
            $this->row('12', 'Lazio', '258', '058', 'Roma', 'metropolitan_city', 'RM', '058091', '058091', 'Roma'),
            $this->row('19', 'Sicilia', '081', '081', 'Trapani', 'free_municipal_consortium', 'TP', '081001', '081001', 'Trapani'),
            $this->row('04', 'Trentino-Alto Adige/Südtirol', '021', '021', 'Bolzano/Bozen', 'autonomous_province', 'BZ', '021008', '021008', 'Bolzano/Bozen'),
            $this->row('06', 'Friuli-Venezia Giulia', '032', '032', 'Trieste', 'non_administrative_unit', 'TS', '032006', '032006', 'Trieste'),
            $this->row('09', 'Toscana', '047', '047', 'Pistoia', 'province', 'PT', '047001', '047001', 'Abetone Cutigliano'),
        ];
    }

    /** @return array<string, string> */
    private function row(
        string $regionCode,
        string $regionName,
        string $utsCode,
        string $historic,
        string $utsName,
        string $type,
        string $sigla,
        string $municipalityCode,
        string $previousCode,
        string $municipalityName,
    ): array {
        return [
            'region_code' => $regionCode,
            'region_name' => $regionName,
            'uts_code' => $utsCode,
            'historic_province_code' => $historic,
            'uts_name' => $utsName,
            'uts_type' => $type,
            'sigla' => $sigla,
            'municipality_code' => $municipalityCode,
            'previous_code' => $previousCode,
            'municipality_name' => $municipalityName,
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
