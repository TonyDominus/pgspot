<?php

namespace Tests\Feature;

use App\Enums\PoiStatus;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Municipality;
use App\Models\Poi;
use App\Services\MvpPrepareService;
use App\Services\OsmImportService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MvpPrepareTest extends TestCase
{
    public function test_legal_page_falls_back_when_setting_body_empty(): void
    {
        AppSetting::setValue('legal.privacy', ['body' => '']);

        $this->get('/legal/privacy')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Legal/Show')
                ->where('content', fn ($content) => is_string($content) && str_contains($content, 'Informativa privacy')));
    }

    public function test_prepare_mvp_fills_legal_and_archives_junk(): void
    {
        AppSetting::setValue('legal.privacy', ['body' => '']);
        $junk = $this->createPublishedPoi([
            'name' => 'Casa mia',
            'slug' => 'casa-mia-test',
        ]);

        $result = app(MvpPrepareService::class)->run(dryRun: false, skipImport: true);

        $this->assertContains('legal.privacy', $result['legal']['filled']);
        $this->assertNotEmpty(AppSetting::getValue('legal.privacy')['body'] ?? '');
        $this->assertSame(PoiStatus::Archived, $junk->fresh()->status);
    }

    public function test_osm_import_creates_pois_from_overpass_payload(): void
    {
        Category::query()->updateOrCreate(
            ['slug' => 'bagni'],
            ['name' => 'Bagni', 'icon' => 'restroom', 'color' => '#00ACC1', 'sort_order' => 2, 'is_active' => true],
        );

        Http::fake([
            '*' => Http::response([
                'elements' => [
                    [
                        'type' => 'node',
                        'id' => 12345,
                        'lat' => 43.112,
                        'lon' => 12.389,
                        'tags' => [
                            'amenity' => 'toilets',
                            'name' => 'WC Test OSM',
                            'addr:street' => 'Via Baglioni',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $municipality = Municipality::query()
            ->where('slug', 'perugia')
            ->whereHas('province', fn ($q) => $q->where('code', 'PG'))
            ->firstOrFail();

        $result = app(OsmImportService::class)->import([
            'south' => 43.05,
            'west' => 12.30,
            'north' => 43.16,
            'east' => 12.45,
        ], $municipality);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('pois', ['name' => 'WC Test OSM']);
        $poi = Poi::query()->where('name', 'WC Test OSM')->first();
        $this->assertSame('node/12345', $poi->attributes['osm_id'] ?? null);
        $this->assertTrue($poi->attributes['needs_photo'] ?? false);
        $this->assertSame($municipality->id, $poi->municipality_id);
        $this->assertSame('Via Baglioni', $poi->address);
    }

    public function test_ongoing_event_started_weeks_ago_is_listed(): void
    {
        $this->setEventsPublic(true);
        $this->createPublishedEvent([
            'title' => 'Evento lungo',
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->addMonth(),
            'is_featured' => true,
        ]);

        $this->get('/eventi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('events', 1));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('featuredEvents', 1));
    }
}
