<?php

namespace Tests\Feature;

use App\Enums\ItineraryStatus;
use App\Enums\PoiStatus;
use App\Models\Itinerary;
use App\Services\ItineraryService;
use Tests\TestCase;

class ItineraryTest extends TestCase
{
    public function test_legacy_ids_keep_order_skip_missing_and_duplicates(): void
    {
        $first = $this->createPublishedPoi(['name' => 'Uno', 'slug' => 'uno']);
        $third = $this->createPublishedPoi(['name' => 'Tre', 'slug' => 'tre']);
        $itinerary = $this->itinerary();

        $missing = app(ItineraryService::class)->importLegacyIds($itinerary, [$first->id, 99999, $third->id, $first->id]);

        $this->assertSame([$first->id, $third->id], $itinerary->pois()->pluck('pois.id')->all());
        $this->assertSame([1, 2], $itinerary->pois()->pluck('itinerary_poi.position')->all());
        $this->assertSame([99999], collect($missing)->pluck('poi_id')->all());
    }

    public function test_poi_lists_only_public_itineraries_via_pivot(): void
    {
        $poi = $this->createPublishedPoi(['slug' => 'nella-passeggiata']);
        $other = $this->createPublishedPoi(['slug' => 'altra-tappa']);
        $visible = $this->itinerary(['title' => 'Visibile', 'slug' => 'visibile', 'status' => ItineraryStatus::Published]);
        $draft = $this->itinerary(['title' => 'Bozza', 'slug' => 'bozza-it', 'status' => ItineraryStatus::Draft]);
        app(ItineraryService::class)->syncStops($visible, [['poi_id' => $poi->id], ['poi_id' => $other->id]]);
        app(ItineraryService::class)->syncStops($draft, [['poi_id' => $poi->id], ['poi_id' => $other->id]]);

        $this->get('/luoghi/nella-passeggiata')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('itineraries', fn ($items) => collect($items)->pluck('slug')->all() === ['visibile']));
    }

    public function test_index_hides_drafts_and_itineraries_with_too_few_published_stops(): void
    {
        $one = $this->createPublishedPoi(['slug' => 'tappa-a']);
        $two = $this->createPublishedPoi(['slug' => 'tappa-b']);
        $draftPoi = $this->createPublishedPoi(['slug' => 'tappa-bozza', 'status' => PoiStatus::Draft]);

        $ok = $this->itinerary(['slug' => 'ok-path', 'status' => ItineraryStatus::Published]);
        $draft = $this->itinerary(['slug' => 'hidden-draft', 'status' => ItineraryStatus::Draft]);
        $thin = $this->itinerary(['slug' => 'thin-path', 'status' => ItineraryStatus::Published]);
        app(ItineraryService::class)->syncStops($ok, [['poi_id' => $one->id], ['poi_id' => $two->id]]);
        app(ItineraryService::class)->syncStops($draft, [['poi_id' => $one->id], ['poi_id' => $two->id]]);
        app(ItineraryService::class)->syncStops($thin, [['poi_id' => $one->id], ['poi_id' => $draftPoi->id]]);

        $this->get(route('routes'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Itineraries/Index')
                ->where('itineraries', fn ($items) => collect($items)->pluck('slug')->all() === ['ok-path']));
    }

    public function test_detail_is_ordered_and_draft_is_hidden(): void
    {
        $first = $this->createPublishedPoi(['name' => 'Porta', 'slug' => 'porta-it']);
        $second = $this->createPublishedPoi(['name' => 'Piazza', 'slug' => 'piazza-it']);
        $itinerary = $this->itinerary(['slug' => 'perugia-panoramica', 'status' => ItineraryStatus::Published]);
        app(ItineraryService::class)->syncStops($itinerary, [
            ['poi_id' => $second->id, 'note' => 'Arrivo'],
            ['poi_id' => $first->id, 'note' => 'Parti da qui'],
        ]);
        app(ItineraryService::class)->syncStops($itinerary, [
            ['poi_id' => $first->id, 'note' => 'Parti da qui'],
            ['poi_id' => $second->id, 'note' => 'Arrivo'],
        ]);

        $this->get(route('itineraries.show', 'perugia-panoramica'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Itineraries/Show')
                ->where('seo.title', 'Perugia panoramica — PG Spot')
                ->where('seo.url', route('itineraries.show', 'perugia-panoramica', absolute: true))
                ->where('stops.0.slug', 'porta-it')
                ->where('stops.0.note', 'Parti da qui')
                ->where('stops.1.slug', 'piazza-it'));

        $draft = $this->itinerary(['slug' => 'ancora-bozza', 'status' => ItineraryStatus::Draft]);
        app(ItineraryService::class)->syncStops($draft, [['poi_id' => $first->id], ['poi_id' => $second->id]]);
        $this->get(route('itineraries.show', 'ancora-bozza'))->assertNotFound();
    }

    public function test_admin_can_build_reorder_and_publish(): void
    {
        $admin = $this->createAdmin();
        $first = $this->createPublishedPoi(['name' => 'Arco', 'slug' => 'arco-it']);
        $second = $this->createPublishedPoi(['name' => 'Rocca', 'slug' => 'rocca-it']);
        $draftPoi = $this->createPublishedPoi(['name' => 'Bozza tappa', 'slug' => 'bozza-tappa', 'status' => PoiStatus::Draft]);

        $this->actingAs($admin)->post(route('admin.itineraries.store'), [
            'title' => 'Perugia panoramica',
            'slug' => 'perugia-panoramica',
            'excerpt' => 'Due ore in centro',
            'status' => 'draft',
            'stops' => [
                ['poi_id' => $first->id, 'note' => 'Inizio'],
                ['poi_id' => $second->id],
            ],
        ])->assertRedirect(route('admin.itineraries.index'));

        $itinerary = Itinerary::query()->where('slug', 'perugia-panoramica')->firstOrFail();
        $this->assertSame(ItineraryStatus::Draft, $itinerary->status);
        $this->assertSame([$first->id, $second->id], $itinerary->pois()->pluck('pois.id')->all());

        $this->actingAs($admin)->put(route('admin.itineraries.update', $itinerary), [
            'title' => 'Perugia panoramica',
            'slug' => 'perugia-panoramica',
            'excerpt' => 'Due ore in centro',
            'status' => 'published',
            'stops' => [
                ['poi_id' => $second->id, 'note' => 'Ora prima'],
                ['poi_id' => $first->id],
                ['poi_id' => $first->id],
            ],
        ])->assertRedirect(route('admin.itineraries.index'));

        $itinerary->refresh();
        $this->assertSame(ItineraryStatus::Published, $itinerary->status);
        $this->assertSame([$second->id, $first->id], $itinerary->pois()->pluck('pois.id')->all());
        $this->assertSame('Ora prima', $itinerary->pois()->first()?->pivot->note);

        $this->actingAs($admin)->put(route('admin.itineraries.update', $itinerary), [
            'title' => 'Perugia panoramica',
            'slug' => 'perugia-panoramica',
            'excerpt' => 'Due ore in centro',
            'status' => 'published',
            'stops' => [['poi_id' => $first->id]],
        ])->assertSessionHasErrors('status');

        $this->actingAs($admin)->put(route('admin.itineraries.update', $itinerary), [
            'title' => 'Perugia panoramica',
            'slug' => 'perugia-panoramica',
            'excerpt' => 'Due ore in centro',
            'status' => 'published',
            'stops' => [
                ['poi_id' => $first->id],
                ['poi_id' => $draftPoi->id],
            ],
        ])->assertSessionHasErrors('status');
    }

    public function test_cover_falls_back_to_the_first_stop_photo(): void
    {
        $poi = $this->createPublishedPoi(['slug' => 'con-foto-it']);
        $other = $this->createPublishedPoi(['slug' => 'senza-foto-it']);
        $poi->photos()->create(['path' => 'pois/cover.jpg', 'is_primary' => true, 'sort_order' => 1]);
        $itinerary = $this->itinerary(['slug' => 'con-copertina', 'status' => ItineraryStatus::Published]);
        app(ItineraryService::class)->syncStops($itinerary, [['poi_id' => $poi->id], ['poi_id' => $other->id]]);

        $this->get(route('itineraries.show', 'con-copertina'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('itinerary.cover_url', fn ($url) => str_contains((string) $url, 'cover.jpg')));
    }

    public function test_home_and_map_expose_itinerary_stops_in_order(): void
    {
        $first = $this->createPublishedPoi(['name' => 'Prima', 'slug' => 'prima-mappa', 'latitude' => 43.11, 'longitude' => 12.39]);
        $second = $this->createPublishedPoi(['name' => 'Seconda', 'slug' => 'seconda-mappa', 'latitude' => 43.12, 'longitude' => 12.40]);
        $itinerary = $this->itinerary(['slug' => 'perugia-panoramica', 'status' => ItineraryStatus::Published]);
        app(ItineraryService::class)->syncStops($itinerary, [['poi_id' => $first->id], ['poi_id' => $second->id]]);

        $this->get('/?itinerary=perugia-panoramica')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('itinerary.slug', 'perugia-panoramica')
                ->where('itinerary.stops.0.slug', 'prima-mappa')
                ->where('itinerary.stops.1.slug', 'seconda-mappa'));

        $this->get('/?itinerary=inesistente')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('itinerary', null));

        $this->getJson('/map/pois?itinerary=perugia-panoramica')
            ->assertOk()
            ->assertJsonPath('markers.0.slug', 'prima-mappa')
            ->assertJsonPath('markers.1.slug', 'seconda-mappa');

        $this->getJson('/map/pois?itinerary=inesistente')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_sitemap_includes_only_public_itineraries(): void
    {
        $first = $this->createPublishedPoi(['slug' => 'sm-a']);
        $second = $this->createPublishedPoi(['slug' => 'sm-b']);
        $public = $this->itinerary(['slug' => 'in-sitemap', 'status' => ItineraryStatus::Published]);
        $draft = $this->itinerary(['slug' => 'fuori-sitemap', 'status' => ItineraryStatus::Draft]);
        app(ItineraryService::class)->syncStops($public, [['poi_id' => $first->id], ['poi_id' => $second->id]]);
        app(ItineraryService::class)->syncStops($draft, [['poi_id' => $first->id], ['poi_id' => $second->id]]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('in-sitemap', false)
            ->assertDontSee('fuori-sitemap', false);
    }

    public function test_admin_poi_search_is_limited_and_includes_drafts(): void
    {
        $admin = $this->createAdmin();
        $this->createPublishedPoi(['name' => 'Belvedere pubblicato', 'slug' => 'bel-pub']);
        $this->createPublishedPoi(['name' => 'Belvedere bozza', 'slug' => 'bel-draft', 'status' => PoiStatus::Draft]);

        $this->actingAs($admin)
            ->getJson(route('admin.itineraries.pois', ['q' => 'bel']))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'bel-pub'])
            ->assertJsonFragment(['slug' => 'bel-draft']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function itinerary(array $overrides = []): Itinerary
    {
        return Itinerary::query()->create(array_merge([
            'title' => 'Perugia panoramica',
            'slug' => 'itinerario-'.uniqid(),
            'excerpt' => 'Due ore a piedi',
            'description' => 'Un percorso breve.',
            'status' => ItineraryStatus::Draft,
            'sort_order' => 0,
        ], $overrides));
    }
}
