<?php

namespace Tests\Feature;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Enums\PoiStatus;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Province;
use App\Models\Region;
use App\Services\TerritoryBackfillService;
use App\Services\TerritoryImportService;
use App\Support\TerritoryDefaults;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TerritoryTest extends TestCase
{
    public function test_region_province_municipality_and_poi_relations(): void
    {
        $region = Region::query()->create(['name' => 'Test Regione', 'slug' => 'test-regione']);
        $province = $region->provinces()->create([
            'name' => 'Test Provincia',
            'slug' => 'test-provincia',
        ]);
        $municipality = $province->municipalities()->create([
            'name' => 'Test Comune',
            'slug' => 'test-comune',
            'is_indexable' => false,
        ]);
        $poi = $this->createPublishedPoi(['municipality_id' => $municipality->id]);

        $this->assertTrue($region->provinces()->whereKey($province->id)->exists());
        $this->assertTrue($province->region->is($region));
        $this->assertTrue($province->municipalities()->whereKey($municipality->id)->exists());
        $this->assertTrue($municipality->province->is($province));
        $this->assertTrue($municipality->pois()->whereKey($poi->id)->exists());
        $this->assertTrue($poi->municipality->is($municipality));
    }

    public function test_municipality_slug_is_unique_per_province_not_globally(): void
    {
        $region = Region::query()->where('slug', 'umbria')->firstOrFail();
        $perugia = $region->provinces()->where('code', 'PG')->firstOrFail();
        $terni = $region->provinces()->where('code', 'TR')->firstOrFail();

        Municipality::query()->create([
            'province_id' => $perugia->id,
            'name' => 'Castelnuovo',
            'slug' => 'castelnuovo',
            'is_indexable' => false,
        ]);
        $sameName = Municipality::query()->create([
            'province_id' => $terni->id,
            'name' => 'Castelnuovo',
            'slug' => 'castelnuovo',
            'is_indexable' => false,
        ]);

        $this->assertSame('castelnuovo', $sameName->slug);
        $this->assertSame(2, Municipality::query()->where('slug', 'castelnuovo')->count());
    }

    public function test_existing_perugia_poi_is_backfilled_without_changing_slug_or_coordinates(): void
    {
        $perugia = $this->perugiaMunicipality();
        $poi = $this->createPublishedPoi([
            'name' => 'Belvedere di Porta Sole',
            'slug' => 'belvedere-invariato',
            'latitude' => 43.1120,
            'longitude' => 12.3888,
            'address' => 'Piazza IV Novembre, Perugia',
            'status' => PoiStatus::Published,
        ]);
        $rawLatitude = (string) $poi->getRawOriginal('latitude');
        $rawLongitude = (string) $poi->getRawOriginal('longitude');

        $result = app(TerritoryBackfillService::class)->assignOrphanPoisToPerugia();

        $fresh = $poi->fresh();
        $this->assertSame($perugia->id, $fresh->municipality_id);
        $this->assertSame('belvedere-invariato', $fresh->slug);
        $this->assertSame($rawLatitude, (string) $fresh->getRawOriginal('latitude'));
        $this->assertSame($rawLongitude, (string) $fresh->getRawOriginal('longitude'));
        $this->assertSame('Piazza IV Novembre, Perugia', $fresh->address);
        $this->assertSame(PoiStatus::Published, $fresh->status);
        $this->assertSame(1, $result['assigned']);

        $second = app(TerritoryBackfillService::class)->assignOrphanPoisToPerugia();
        $this->assertSame(0, $second['assigned']);
    }

    public function test_backfill_does_not_assign_a_poi_clearly_outside_perugia(): void
    {
        $outside = $this->createPublishedPoi([
            'name' => 'Piazza del Comune',
            'slug' => 'piazza-assisi',
            'address' => 'Piazza del Comune, Assisi',
            'latitude' => 43.0704,
            'longitude' => 12.6178,
        ]);

        $result = app(TerritoryBackfillService::class)->assignOrphanPoisToPerugia();

        $this->assertNull($outside->fresh()->municipality_id);
        $this->assertTrue(collect($result['skipped'])->contains(
            fn (array $row) => $row['slug'] === 'piazza-assisi',
        ));
    }

    public function test_admin_can_create_and_update_a_poi_in_any_municipality(): void
    {
        $admin = $this->createAdmin();
        $arezzo = $this->municipalityInNewProvince();

        $this->actingAs($admin)->post(route('admin.pois.store'), [
            'name' => 'Piazza Grande',
            'description' => 'Centro di Arezzo',
            'region_id' => $arezzo->province->region_id,
            'province_id' => $arezzo->province_id,
            'municipality_id' => $arezzo->id,
            'latitude' => 43.463,
            'longitude' => 11.879,
            'address' => 'Piazza Grande, Arezzo',
            'status' => 'published',
            'category_ids' => [],
        ])->assertRedirect();

        $poi = Poi::query()->where('name', 'Piazza Grande')->firstOrFail();
        $this->assertSame($arezzo->id, $poi->municipality_id);
        $this->assertSame('/luoghi/'.$poi->slug, route('poi.show', $poi->slug, absolute: false));

        $perugia = $this->perugiaMunicipality();
        $slug = $poi->slug;

        $this->actingAs($admin)->put(route('admin.pois.update', $poi), [
            'name' => 'Piazza Grande',
            'latitude' => 43.463,
            'longitude' => 11.879,
            'status' => 'published',
            'region_id' => $perugia->province->region_id,
            'province_id' => $perugia->province_id,
            'municipality_id' => $perugia->id,
            'category_ids' => [],
        ])->assertRedirect(route('admin.pois.index'));

        $fresh = $poi->fresh();
        $this->assertSame($perugia->id, $fresh->municipality_id);
        $this->assertSame($slug, $fresh->slug);
    }

    public function test_create_form_defaults_to_umbria_perugia(): void
    {
        $admin = $this->createAdmin();
        $perugia = $this->perugiaMunicipality();
        $defaults = TerritoryDefaults::get();

        $this->actingAs($admin)
            ->get(route('admin.pois.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Pois/Form')
                ->where('poi', null)
                ->where('territory.region_id', $defaults['region_id'])
                ->where('territory.province_id', $perugia->province_id)
                ->where('territory.municipality_id', $perugia->id));
    }

    public function test_regular_user_cannot_manage_territories_or_create_pois(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('admin.territories.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.pois.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.territories.municipalities.store'), [
            'province_id' => $this->perugiaMunicipality()->province_id,
            'name' => 'Assisi',
        ])->assertForbidden();
    }

    public function test_admin_can_create_and_update_a_municipality(): void
    {
        $admin = $this->createAdmin();
        $provinceId = $this->perugiaMunicipality()->province_id;

        $this->actingAs($admin)->post(route('admin.territories.municipalities.store'), [
            'province_id' => $provinceId,
            'name' => 'Assisi',
            'latitude' => 43.0707,
            'longitude' => 12.6196,
            'intro' => 'Intro iniziale',
            'is_indexable' => false,
        ])->assertRedirect(route('admin.territories.index', ['tab' => 'municipalities']));

        $assisi = Municipality::query()->where('slug', 'assisi')->firstOrFail();
        $this->assertFalse($assisi->is_indexable);
        $this->assertSame($provinceId, $assisi->province_id);

        $this->actingAs($admin)->put(route('admin.territories.municipalities.update', $assisi), [
            'province_id' => $provinceId,
            'name' => 'Assisi',
            'slug' => 'assisi',
            'latitude' => 43.071,
            'longitude' => 12.618,
            'intro' => 'Intro aggiornata',
            'is_indexable' => true,
        ])->assertRedirect();

        $assisi->refresh();
        $this->assertSame('Intro aggiornata', $assisi->intro);
        $this->assertTrue($assisi->is_indexable);
        $this->assertFalse(Route::has('admin.territories.municipalities.destroy'));
    }

    public function test_new_poi_contribution_requires_a_valid_municipality_and_approval_keeps_it(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $assisi = $this->assisi();
        $category = Category::query()->create([
            'slug' => 'panorami',
            'name' => 'Panorami',
            'icon' => 'panorama',
            'color' => '#2E7D32',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Eremo delle Celle',
            'category_id' => $category->id,
            'latitude' => 43.07,
            'longitude' => 12.62,
            'type' => 'new_poi',
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasErrors('municipality_id');

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Eremo delle Celle',
            'category_id' => $category->id,
            'latitude' => 43.07,
            'longitude' => 12.62,
            'type' => 'new_poi',
            'municipality_id' => 999999,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasErrors('municipality_id');

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Eremo delle Celle',
            'category_id' => $category->id,
            'description' => 'Eremo francescano',
            'latitude' => 43.07,
            'longitude' => 12.62,
            'type' => 'new_poi',
            'municipality_id' => $assisi->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect();

        $contribution = Contribution::query()->firstOrFail();
        $this->assertSame($assisi->id, (int) $contribution->payload['municipality_id']);

        $this->actingAs($admin)
            ->post(route('admin.contributions.approve', $contribution))
            ->assertRedirect();

        $poi = Poi::query()->where('name', 'Eremo delle Celle')->firstOrFail();
        $this->assertSame($assisi->id, $poi->municipality_id);
        $this->assertSame(ContributionStatus::Approved, $contribution->fresh()->status);
    }

    public function test_report_contribution_does_not_require_a_municipality(): void
    {
        $user = $this->createUser();
        $category = Category::query()->create([
            'slug' => 'bagni',
            'name' => 'Bagni',
            'color' => '#00ACC1',
            'sort_order' => 2,
        ]);

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Fontanella guasta',
            'category_id' => $category->id,
            'latitude' => 43.11,
            'longitude' => 12.39,
            'type' => 'report',
        ])->assertRedirect();

        $payload = Contribution::query()->firstOrFail()->payload;
        $this->assertArrayNotHasKey('municipality_id', $payload);
        $this->assertSame(ContributionType::Report->value, Contribution::query()->first()->type->value);
    }

    public function test_poi_url_stays_on_luoghi_slug_and_json_ld_uses_the_real_municipality(): void
    {
        $assisi = $this->assisi();
        $poi = $this->createPublishedPoi([
            'name' => 'Eremo delle Celle',
            'slug' => 'eremo-delle-celle',
            'description' => 'Eremo francescano sopra Assisi.',
            'address' => 'Eremo delle Celle',
            'latitude' => 43.070,
            'longitude' => 12.620,
            'municipality_id' => $assisi->id,
        ]);

        $this->assertSame('luoghi/{slug}', Route::getRoutes()->getByName('poi.show')->uri());

        $this->get('/luoghi/eremo-delle-celle')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Poi/Show')
                ->where('poi.slug', 'eremo-delle-celle')
                ->where('seo.json_ld.address.addressLocality', 'Assisi')
                ->where('seo.json_ld.address.addressRegion', 'Umbria')
                ->where('seo.json_ld.address.addressCountry', 'IT'));

        $this->assertNotSame('Perugia', $poi->municipality->name);
    }

    public function test_osm_import_command_fails_clearly_without_a_valid_municipality(): void
    {
        $this->artisan('pgspot:import-osm')
            ->expectsOutputToContain('Specifica il comune')
            ->assertFailed();

        $this->artisan('pgspot:import-osm', ['--municipality' => 'atlantide'])
            ->expectsOutputToContain('Comune non trovato')
            ->assertFailed();
    }

    public function test_osm_import_command_rejects_an_ambiguous_municipality_slug(): void
    {
        $terni = Province::query()->where('code', 'TR')->firstOrFail();
        Municipality::query()->create([
            'province_id' => $terni->id,
            'name' => 'Perugia',
            'slug' => 'perugia',
            'is_indexable' => false,
        ]);

        $this->artisan('pgspot:import-osm', ['--municipality' => 'perugia'])
            ->expectsOutputToContain('ambiguo')
            ->assertFailed();
    }

    public function test_territory_import_is_idempotent_and_leaves_editorial_fields_untouched(): void
    {
        $perugia = $this->perugiaMunicipality();
        $perugia->update([
            'intro' => 'Testo editoriale',
            'is_indexable' => true,
        ]);

        $path = database_path('data/territories/umbria-core.json');
        app(TerritoryImportService::class)->importFile($path);
        app(TerritoryImportService::class)->importFile($path);

        $perugia->refresh();
        $assisi = Municipality::query()->where('slug', 'assisi')->firstOrFail();

        $this->assertSame('Testo editoriale', $perugia->intro);
        $this->assertTrue($perugia->is_indexable);
        $this->assertFalse($assisi->is_indexable);
        $this->assertNull($assisi->istat_code);
        $this->assertSame(1, Region::query()->where('slug', 'umbria')->count());
        $this->assertSame(1, Municipality::query()->where('slug', 'assisi')->count());
    }

    public function test_import_command_reads_csv_and_rejects_a_missing_file(): void
    {
        $path = storage_path('framework/testing-territories.csv');
        file_put_contents($path, implode("\n", [
            'region_name,region_slug,province_name,province_slug,province_code,municipality_name,municipality_slug,latitude,longitude,istat_code',
            'Toscana,toscana,Arezzo,arezzo,AR,Arezzo,arezzo,,,,',
        ]));

        try {
            $this->artisan('pgspot:import-territories', ['file' => $path])->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $arezzo = Municipality::query()->where('slug', 'arezzo')->firstOrFail();
        $this->assertSame('Toscana', $arezzo->province->region->name);
        $this->assertSame('AR', $arezzo->province->code);
        $this->assertFalse($arezzo->is_indexable);

        $this->artisan('pgspot:import-territories', ['file' => storage_path('framework/missing-territories.json')])
            ->expectsOutputToContain('File non trovato')
            ->assertFailed();
    }

    public function test_lookup_endpoints_return_only_the_requested_children(): void
    {
        $admin = $this->createAdmin();
        $umbria = Region::query()->where('slug', 'umbria')->firstOrFail();
        $perugiaProvince = Province::query()->where('code', 'PG')->firstOrFail();

        $this->getJson(route('territories.provinces', ['region_id' => $umbria->id]))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->getJson(route('territories.provinces', ['region_id' => $umbria->id]))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'perugia'])
            ->assertJsonFragment(['slug' => 'terni']);

        $this->actingAs($admin)
            ->getJson(route('territories.municipalities', ['province_id' => $perugiaProvince->id]))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'perugia'])
            ->assertJsonMissing(['slug' => 'terni']);
    }

    private function perugiaMunicipality(): Municipality
    {
        return Municipality::query()
            ->where('slug', 'perugia')
            ->whereHas('province', fn ($query) => $query->where('code', 'PG'))
            ->firstOrFail();
    }

    private function assisi(): Municipality
    {
        $provinceId = $this->perugiaMunicipality()->province_id;

        return Municipality::query()->create([
            'province_id' => $provinceId,
            'name' => 'Assisi',
            'slug' => 'assisi',
            'is_indexable' => false,
        ]);
    }

    private function municipalityInNewProvince(): Municipality
    {
        $region = Region::query()->create(['name' => 'Toscana', 'slug' => 'toscana']);
        $province = $region->provinces()->create([
            'name' => 'Arezzo',
            'slug' => 'arezzo',
            'code' => 'AR',
        ]);

        return $province->municipalities()->create([
            'name' => 'Arezzo',
            'slug' => 'arezzo',
            'is_indexable' => false,
        ]);
    }
}
