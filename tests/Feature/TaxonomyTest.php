<?php

namespace Tests\Feature;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Tag;
use App\Services\OsmImportService;
use App\Services\TaxonomyBackfillService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaxonomyTest extends TestCase
{
    public function test_primary_category_is_added_to_the_pivot(): void
    {
        $category = $this->category('panorami', 'Panorami');

        $poi = $this->createPublishedPoi([
            'primary_category_id' => $category->id,
        ]);

        $this->assertTrue($poi->categories()->where('categories.id', $category->id)->exists());
    }

    public function test_single_category_becomes_primary(): void
    {
        $category = $this->category('bagni', 'Bagni');
        $poi = $this->createPublishedPoi();
        $poi->categories()->sync([$category->id]);

        app(TaxonomyBackfillService::class)->run();

        $this->assertSame($category->id, $poi->fresh()->primary_category_id);
    }

    public function test_panorama_plus_instagram_spot_becomes_panorama_and_fotografico(): void
    {
        $panorama = $this->category('panorami', 'Panorami');
        $instagram = $this->category('instagram-spot', 'Instagram Spot');
        $poi = $this->createPublishedPoi();
        $poi->categories()->sync([$panorama->id, $instagram->id]);

        app(TaxonomyBackfillService::class)->run();

        $poi->refresh();
        $this->assertSame($panorama->id, $poi->primary_category_id);
        $this->assertTrue($poi->tags()->where('slug', 'fotografico')->exists());
        $this->assertFalse($poi->categories()->where('categories.slug', 'instagram-spot')->exists());
    }

    public function test_instagram_spot_only_is_reported_and_not_invented(): void
    {
        $instagram = $this->category('instagram-spot', 'Instagram Spot');
        $poi = $this->createPublishedPoi(['name' => 'Solo Instagram', 'slug' => 'solo-instagram']);
        $poi->categories()->sync([$instagram->id]);

        $report = app(TaxonomyBackfillService::class)->run();

        $poi->refresh();
        $this->assertNull($poi->primary_category_id);
        $this->assertTrue($poi->categories()->where('categories.id', $instagram->id)->exists());
        $this->assertTrue($instagram->fresh()->is_active);
        $this->assertTrue(collect($report['ambiguous'])->contains(
            fn (array $row) => $row['slug'] === 'solo-instagram',
        ));
    }

    public function test_legacy_attribute_tags_are_normalized_into_relations(): void
    {
        $poi = $this->createPublishedPoi([
            'attributes' => ['tags' => ['Vista città', 'vista città', 'Romantico']],
        ]);

        app(TaxonomyBackfillService::class)->run();

        $poi->refresh();
        $this->assertEqualsCanonicalizing(
            ['vista-citta', 'romantico'],
            $poi->tags()->pluck('slug')->all(),
        );
        $this->assertArrayNotHasKey('tags', $poi->attributes ?? []);
    }

    public function test_sunset_and_city_view_booleans_become_tags(): void
    {
        $poi = $this->createPublishedPoi([
            'attributes' => ['sunset' => true, 'city_view' => true, 'tags' => ['Tramonto', 'Vista città']],
        ]);

        app(TaxonomyBackfillService::class)->run();

        $poi->refresh();
        $this->assertEqualsCanonicalizing(['tramonto', 'vista-citta'], $poi->tags()->pluck('slug')->all());
        $this->assertArrayNotHasKey('sunset', $poi->attributes ?? []);
        $this->assertArrayNotHasKey('city_view', $poi->attributes ?? []);
    }

    public function test_free_is_migrated_only_when_explicit(): void
    {
        $free = $this->createPublishedPoi(['slug' => 'gratis', 'attributes' => ['free' => true]]);
        $paid = $this->createPublishedPoi(['slug' => 'pagamento', 'attributes' => ['free' => false]]);
        $unknown = $this->createPublishedPoi(['slug' => 'ignoto', 'attributes' => ['tags' => ['Foto']]]);

        app(TaxonomyBackfillService::class)->run();

        $this->assertTrue($free->fresh()->is_free);
        $this->assertFalse($paid->fresh()->is_free);
        $this->assertNull($unknown->fresh()->is_free);
    }

    public function test_accessibile_string_becomes_accessibility_yes(): void
    {
        $poi = $this->createPublishedPoi([
            'attributes' => ['tags' => ['Accessibile'], 'accessible' => true],
        ]);

        app(TaxonomyBackfillService::class)->run();

        $poi->refresh();
        $this->assertSame('yes', $poi->accessibility);
        $this->assertFalse($poi->tags()->where('slug', 'accessibile')->exists());
        $this->assertArrayNotHasKey('accessible', $poi->attributes ?? []);
    }

    public function test_generic_parking_string_is_not_invented_as_an_enum(): void
    {
        $parking = $this->category('parcheggi', 'Parcheggi');
        $poi = $this->createPublishedPoi([
            'attributes' => ['tags' => ['Con parcheggio'], 'free' => false],
        ]);
        $poi->categories()->sync([$parking->id]);

        $report = app(TaxonomyBackfillService::class)->run();

        $poi->refresh();
        $this->assertSame('unknown', $poi->parking);
        $this->assertSame($parking->id, $poi->primary_category_id);
        $this->assertFalse($poi->tags()->exists());
        $this->assertContains('Con parcheggio', $poi->attributes['legacy_unmapped_tags'] ?? []);
        $this->assertTrue(collect($report['unmapped_tags'])->contains(
            fn (array $row) => $row['value'] === 'Con parcheggio',
        ));
    }

    public function test_admin_can_manage_categories_without_deleting_them(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Belvedere',
            'slug' => 'belvedere',
            'icon' => 'panorama',
            'color' => '#2E7D32',
            'description' => 'Un punto di vista.',
            'is_active' => true,
            'is_indexable' => false,
            'sort_order' => 3,
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('slug', 'belvedere')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Belvedere',
            'slug' => 'belvedere',
            'is_active' => false,
            'is_indexable' => false,
            'sort_order' => 3,
        ])->assertRedirect();

        $this->assertFalse($category->fresh()->is_active);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_admin_can_manage_tags_without_deleting_them(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Tramonto',
            'slug' => 'tramonto',
            'is_active' => true,
            'is_filterable' => true,
            'is_indexable' => false,
            'sort_order' => 1,
        ])->assertRedirect(route('admin.tags.index'));

        $tag = Tag::query()->where('slug', 'tramonto')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.tags.update', $tag), [
            'name' => 'Tramonto',
            'slug' => 'tramonto',
            'is_active' => false,
            'is_filterable' => true,
            'is_indexable' => false,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertFalse($tag->fresh()->is_active);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_inactive_tag_is_not_selectable_for_a_poi(): void
    {
        $admin = $this->createAdmin();
        $tag = Tag::query()->create([
            'name' => 'Alba',
            'slug' => 'alba',
            'is_active' => false,
            'is_filterable' => true,
            'is_indexable' => false,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pois.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'tags',
                fn ($tags) => collect($tags)->pluck('slug')->doesntContain('alba'),
            ));

        $this->actingAs($admin)
            ->post(route('admin.pois.store'), $this->poiPayload([
                'tag_ids' => [$tag->id],
            ]))
            ->assertSessionHasErrors('tag_ids');
    }

    public function test_inactive_category_is_not_offered_for_a_new_poi(): void
    {
        $admin = $this->createAdmin();
        $inactive = $this->category('vecchia', 'Vecchia', false);
        $this->category('panorami', 'Panorami');

        $this->actingAs($admin)
            ->get(route('admin.pois.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'categories',
                function ($categories) {
                    $slugs = collect($categories)->pluck('slug');

                    return $slugs->doesntContain('vecchia') && $slugs->contains('panorami');
                },
            ));

        $this->actingAs($admin)
            ->post(route('admin.pois.store'), $this->poiPayload([
                'primary_category_id' => $inactive->id,
            ]))
            ->assertSessionHasErrors('primary_category_id');
    }

    public function test_admin_can_create_and_edit_a_poi_with_a_primary_category(): void
    {
        $admin = $this->createAdmin();
        $primary = $this->category('panorami', 'Panorami');
        $secondary = $this->category('fontanelle', 'Fontanelle');
        $tag = Tag::query()->create([
            'name' => 'Fotografico',
            'slug' => 'fotografico',
            'is_active' => true,
            'is_filterable' => true,
            'is_indexable' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pois.store'), $this->poiPayload([
                'name' => 'Belvedere nuovo',
                'primary_category_id' => $primary->id,
                'tag_ids' => [$tag->id],
                'is_free' => '1',
                'parking' => 'nearby',
            ]))
            ->assertRedirect();

        $poi = Poi::query()->where('name', 'Belvedere nuovo')->firstOrFail();
        $this->assertSame($primary->id, $poi->primary_category_id);
        $this->assertTrue($poi->categories()->where('categories.id', $primary->id)->exists());
        $this->assertTrue($poi->is_free);
        $this->assertSame('nearby', $poi->parking);

        $this->actingAs($admin)
            ->put(route('admin.pois.update', $poi), $this->poiPayload([
                'name' => 'Belvedere nuovo',
                'primary_category_id' => $primary->id,
                'secondary_category_ids' => [$secondary->id],
                'tag_ids' => [$tag->id],
                'is_free' => '0',
            ]))
            ->assertRedirect(route('admin.pois.index'));

        $poi->refresh();
        $this->assertFalse($poi->is_free);
        $this->assertEqualsCanonicalizing(
            [$primary->id, $secondary->id],
            $poi->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_approved_contribution_sets_the_primary_category(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $category = $this->category('panorami', 'Panorami');
        $municipality = $this->perugia();

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Nuovo belvedere',
            'category_id' => $category->id,
            'latitude' => 43.11,
            'longitude' => 12.39,
            'type' => 'new_poi',
            'municipality_id' => $municipality->id,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect();

        $contribution = Contribution::query()->firstOrFail();
        $this->assertSame(ContributionType::NewPoi, $contribution->type);

        $this->actingAs($admin)
            ->post(route('admin.contributions.approve', $contribution))
            ->assertRedirect();

        $poi = Poi::query()->where('name', 'Nuovo belvedere')->firstOrFail();
        $this->assertSame($category->id, $poi->primary_category_id);
        $this->assertTrue($poi->categories()->where('categories.id', $category->id)->exists());
        $this->assertSame('community', $poi->source_type);
        $this->assertSame(ContributionStatus::Approved, $contribution->fresh()->status);
    }

    public function test_osm_import_sets_primary_category_and_source(): void
    {
        $category = $this->category('bagni', 'Bagni');

        Http::fake([
            '*' => Http::response([
                'elements' => [[
                    'type' => 'node',
                    'id' => 555,
                    'lat' => 43.11,
                    'lon' => 12.39,
                    'tags' => ['amenity' => 'toilets', 'name' => 'WC OSM'],
                ]],
            ], 200),
        ]);

        app(OsmImportService::class)->import([
            'south' => 43.0,
            'west' => 12.2,
            'north' => 43.2,
            'east' => 12.5,
        ], $this->perugia());

        $poi = Poi::query()->where('name', 'WC OSM')->firstOrFail();
        $this->assertSame($category->id, $poi->primary_category_id);
        $this->assertTrue($poi->categories()->where('categories.id', $category->id)->exists());
        $this->assertSame('osm', $poi->source_type);
        $this->assertSame('node/555', $poi->source_ref);
        $this->assertNull($poi->is_free);
        $this->assertFalse($poi->tags()->exists());
    }

    private function category(string $slug, string $name, bool $active = true): Category
    {
        return Category::query()->create([
            'slug' => $slug,
            'name' => $name,
            'color' => '#2E7D32',
            'sort_order' => 1,
            'is_active' => $active,
        ]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function poiPayload(array $overrides = []): array
    {
        $municipality = $this->perugia();

        return array_merge([
            'name' => 'Luogo test',
            'latitude' => 43.11,
            'longitude' => 12.39,
            'status' => 'published',
            'region_id' => $municipality->province->region_id,
            'province_id' => $municipality->province_id,
            'municipality_id' => $municipality->id,
        ], $overrides);
    }

    private function perugia(): Municipality
    {
        return Municipality::query()
            ->where('slug', 'perugia')
            ->whereHas('province', fn ($query) => $query->where('code', 'PG'))
            ->firstOrFail();
    }
}
