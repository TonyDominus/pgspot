<?php

namespace Tests\Feature;

use App\Enums\PoiStatus;
use App\Models\Category;
use App\Models\Municipality;
use App\Models\Tag;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    public function test_search_returns_poi_municipality_and_category_within_limits(): void
    {
        $perugia = $this->perugia();
        $category = $this->category('panorami', 'Panorami');
        $this->createPublishedPoi([
            'name' => 'Belvedere unico',
            'slug' => 'belvedere-unico',
            'municipality_id' => $perugia->id,
            'primary_category_id' => $category->id,
        ]);
        $this->createPublishedPoi([
            'name' => 'Bozza belvedere',
            'slug' => 'bozza-belvedere',
            'status' => PoiStatus::Draft,
            'municipality_id' => $perugia->id,
        ]);

        $response = $this->getJson('/search/suggestions?q=bel');
        $response->assertOk();
        $names = collect($response->json('pois'))->pluck('name');
        $this->assertTrue($names->contains('Belvedere unico'));
        $this->assertFalse($names->contains('Bozza belvedere'));
        $this->assertLessThanOrEqual(5, count($response->json('pois')));

        $inactive = Municipality::query()->create([
            'province_id' => $perugia->province_id,
            'name' => 'Comune Spento',
            'slug' => 'comune-spento',
            'istat_code' => '054999',
            'is_active' => false,
        ]);
        $this->getJson('/search/suggestions?q=Comune')->assertOk()
            ->assertJsonMissing(['name' => $inactive->name]);

        $this->getJson('/search/suggestions?q=pano')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'panorami', 'type' => 'category']);
    }

    public function test_map_bbox_filters_payload_and_focus(): void
    {
        $perugia = $this->perugia();
        $category = $this->category('panorami', 'Panorami');
        $tag = Tag::query()->create(['name' => 'Tramonto', 'slug' => 'tramonto', 'is_active' => true, 'is_filterable' => true]);
        $inside = $this->createPublishedPoi([
            'name' => 'Dentro',
            'slug' => 'dentro-mappa',
            'latitude' => 43.11,
            'longitude' => 12.39,
            'municipality_id' => $perugia->id,
            'primary_category_id' => $category->id,
            'is_free' => true,
            'accessibility' => 'yes',
            'parking' => 'nearby',
            'rating' => 4.5,
        ]);
        $inside->tags()->sync([$tag->id]);
        $outside = $this->createPublishedPoi([
            'name' => 'Fuori',
            'slug' => 'fuori-mappa',
            'latitude' => 41.9,
            'longitude' => 12.5,
            'municipality_id' => $perugia->id,
        ]);
        $draft = $this->createPublishedPoi([
            'name' => 'Nascosto',
            'slug' => 'nascosto-mappa',
            'latitude' => 43.11,
            'longitude' => 12.39,
            'status' => PoiStatus::Draft,
        ]);
        $noPrimary = $this->createPublishedPoi([
            'name' => 'Scalinata',
            'slug' => 'scalinata-scoperta',
            'latitude' => 43.111,
            'longitude' => 12.391,
            'municipality_id' => $perugia->id,
        ]);

        $bbox = ['south' => 43.0, 'north' => 43.2, 'west' => 12.2, 'east' => 12.5];
        $response = $this->getJson('/map/pois?'.http_build_query($bbox));
        $response->assertOk();
        $slugs = collect($response->json('markers'))->pluck('slug');
        $this->assertTrue($slugs->contains($inside->slug));
        $this->assertTrue($slugs->contains($noPrimary->slug));
        $this->assertFalse($slugs->contains($outside->slug));
        $this->assertFalse($slugs->contains($draft->slug));
        $marker = collect($response->json('markers'))->firstWhere('slug', $inside->slug);
        $this->assertArrayNotHasKey('description', $marker);
        $this->assertArrayNotHasKey('attributes', $marker);
        $this->assertFalse($marker['sponsored']);

        $this->getJson('/map/pois?'.http_build_query($bbox + ['category' => 'panorami', 'free' => 1, 'access' => 'yes', 'parking' => 1, 'tag' => 'tramonto', 'rating' => 4]))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'dentro-mappa'])
            ->assertJsonMissing(['slug' => 'scalinata-scoperta']);

        $this->getJson('/map/pois?'.http_build_query($bbox + ['municipality' => '054039']))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'dentro-mappa']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('itinerary', null)->missing('pois'));

        $this->get('/?focus=dentro-mappa')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('focus.slug', 'dentro-mappa')->missing('pois'));

        $this->get('/?focus=inesistente')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('focus', null));
    }

    public function test_distance_keeps_only_nearby_pois(): void
    {
        $near = $this->createPublishedPoi([
            'name' => 'Vicino',
            'slug' => 'molto-vicino',
            'latitude' => 43.1108,
            'longitude' => 12.3909,
        ]);
        $this->createPublishedPoi([
            'name' => 'Lontano',
            'slug' => 'molto-lontano',
            'latitude' => 43.5,
            'longitude' => 12.9,
        ]);

        $this->getJson('/map/pois?'.http_build_query([
            'south' => 42.5, 'north' => 44, 'west' => 12, 'east' => 13,
            'lat' => 43.1107, 'lng' => 12.3908, 'radius' => 1,
        ]))->assertOk()
            ->assertJsonFragment(['slug' => $near->slug])
            ->assertJsonMissing(['slug' => 'molto-lontano']);
    }

    private function category(string $slug, string $name): Category
    {
        return Category::query()->create([
            'slug' => $slug,
            'name' => $name,
            'color' => '#2E7D32',
            'is_active' => true,
        ]);
    }

    private function perugia(): Municipality
    {
        $municipality = Municipality::query()
            ->where('slug', 'perugia')
            ->whereHas('province', fn ($query) => $query->where('code', 'PG'))
            ->firstOrFail();
        $municipality->update(['istat_code' => '054039', 'is_active' => true]);

        return $municipality;
    }
}
