<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Municipality;
use App\Models\Tag;
use App\Services\LegacyCategoryRemap;
use App\Support\PoiSheet;
use Tests\TestCase;

class PoiSheetTest extends TestCase
{
    public function test_sheet_uses_municipality_region_primary_category_and_tags(): void
    {
        $perugia = $this->perugia();
        $category = $this->category('panorami', 'Panorami');
        $tag = Tag::query()->create(['name' => 'Tramonto', 'slug' => 'tramonto', 'is_active' => true]);
        $inactive = Tag::query()->create(['name' => 'Nascosto', 'slug' => 'nascosto', 'is_active' => false]);
        $poi = $this->createPublishedPoi([
            'name' => 'Belvedere test',
            'slug' => 'belvedere-test',
            'description' => 'Vista sulla valle.',
            'address' => 'Via del Cassero',
            'municipality_id' => $perugia->id,
            'primary_category_id' => $category->id,
            'is_free' => true,
            'accessibility' => 'yes',
            'parking' => 'nearby',
            'website' => 'comune.perugia.it',
            'phone' => '075 123456',
        ]);
        $poi->categories()->sync([$category->id]);
        $poi->tags()->sync([$tag->id, $inactive->id]);

        $this->get('/luoghi/belvedere-test')
            ->assertOk()
            ->assertDontSee('Sempre aperto', false)
            ->assertInertia(fn ($page) => $page
                ->component('Poi/Show')
                ->where('poi.slug', 'belvedere-test')
                ->where('placeLine', 'Perugia, Umbria')
                ->where('primaryCategory.slug', 'panorami')
                ->where('poi.tags', fn ($tags) => collect($tags)->pluck('slug')->all() === ['tramonto'])
                ->where('facts', fn ($facts) => collect($facts)->pluck('id')->all() === ['is_free', 'accessibility', 'parking', 'website', 'phone'])
                ->where('seo.title', 'Belvedere test a Perugia — PG Spot')
                ->where('seo.url', route('poi.show', 'belvedere-test', absolute: true))
                ->where('seo.json_ld.@type', 'TouristAttraction')
                ->where('seo.json_ld.address.addressLocality', 'Perugia')
                ->where('seo.json_ld.address.addressRegion', 'Umbria')
                ->where('seo.json_ld.address.addressCountry', 'IT')
                ->where('verifiedLabel', null));
    }

    public function test_missing_free_access_parking_and_hours_are_omitted(): void
    {
        $poi = $this->createPublishedPoi([
            'slug' => 'senza-dati',
            'is_free' => null,
            'accessibility' => 'unknown',
            'parking' => 'unknown',
            'opening_hours' => null,
        ]);

        $this->assertSame([], PoiSheet::facts($poi));

        $paid = $this->createPublishedPoi([
            'slug' => 'a-pagamento',
            'is_free' => false,
            'accessibility' => 'partial',
            'parking' => 'none',
            'primary_category_id' => $this->category('parcheggi', 'Parcheggi')->id,
        ]);

        $labels = collect(PoiSheet::facts($paid))->pluck('label')->all();
        $this->assertSame(['A pagamento', 'Accessibilità parziale', 'Nessun parcheggio'], $labels);
        $this->assertNotContains('Parcheggio sconosciuto', $labels);
    }

    public function test_related_skips_the_poi_itself_and_unpublished_records(): void
    {
        $perugia = $this->perugia();
        $category = $this->category('fontanelle', 'Fontanelle');
        $poi = $this->createPublishedPoi([
            'slug' => 'fontana-centro',
            'municipality_id' => $perugia->id,
            'primary_category_id' => $category->id,
        ]);
        $other = $this->createPublishedPoi([
            'name' => 'Altra fontana',
            'slug' => 'altra-fontana',
            'municipality_id' => $perugia->id,
            'primary_category_id' => $category->id,
        ]);
        $this->createPublishedPoi([
            'name' => 'Bozza',
            'slug' => 'fontana-bozza',
            'municipality_id' => $perugia->id,
            'primary_category_id' => $category->id,
            'status' => 'draft',
        ]);

        $this->get('/luoghi/fontana-centro')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('related', function ($related) use ($poi, $other) {
                    $ids = collect($related)->pluck('id');

                    return $ids->contains($other->id) && ! $ids->contains($poi->id) && $ids->count() === 1;
                }));
    }

    public function test_report_link_uses_the_existing_contribution_form(): void
    {
        $user = $this->createUser();
        $poi = $this->createPublishedPoi(['slug' => 'da-segnalare', 'name' => 'Da segnalare']);

        $this->get(route('contribute.create', ['poi' => 'da-segnalare', 'intent' => 'report']))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('contribute.create', ['poi' => 'da-segnalare', 'intent' => 'report']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Contribute/Create')
                ->where('intent', 'report')
                ->where('reportPoi.id', $poi->id));

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Da segnalare',
            'type' => 'report',
            'poi_id' => $poi->id,
            'latitude' => 43.11,
            'longitude' => 12.39,
            'notes' => 'Chiuso',
        ])->assertRedirect();

        $this->assertDatabaseHas('contributions', [
            'poi_id' => $poi->id,
            'type' => 'report',
        ]);
    }

    public function test_favorite_route_is_unchanged_for_a_verified_user(): void
    {
        $user = $this->createUser();
        $poi = $this->createPublishedPoi(['slug' => 'da-salvare']);

        $this->actingAs($user)
            ->post(route('poi.favorites.store', $poi->slug))
            ->assertRedirect();

        $this->assertTrue($user->favoritePois()->where('pois.id', $poi->id)->exists());
    }

    public function test_legacy_panorama_moves_to_panorami_without_deleting_the_old_category(): void
    {
        $legacy = $this->category('panorama', 'Panorami', false);
        $current = $this->category('panorami', 'Panorami');
        $poi = $this->createPublishedPoi([
            'slug' => 'porta-sole-test',
            'primary_category_id' => $legacy->id,
        ]);
        $poi->categories()->sync([$legacy->id]);

        $result = app(LegacyCategoryRemap::class)->remapPanorama();

        $poi->refresh();
        $this->assertFalse($result['skipped']);
        $this->assertSame($current->id, $poi->primary_category_id);
        $this->assertTrue($poi->categories()->where('categories.id', $current->id)->exists());
        $this->assertFalse($poi->categories()->where('categories.id', $legacy->id)->exists());
        $this->assertDatabaseHas('categories', ['id' => $legacy->id, 'slug' => 'panorama']);
    }

    private function category(string $slug, string $name, bool $active = true): Category
    {
        return Category::query()->create([
            'slug' => $slug,
            'name' => $name,
            'color' => '#2E7D32',
            'is_active' => $active,
        ]);
    }

    private function perugia(): Municipality
    {
        return Municipality::query()
            ->where('slug', 'perugia')
            ->whereHas('province', fn ($query) => $query->where('code', 'PG'))
            ->firstOrFail();
    }
}
