<?php

namespace Tests\Feature;

use App\Enums\ContributionStatus;
use App\Enums\PoiStatus;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Municipality;
use App\Models\Poi;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContributionFlowTest extends TestCase
{
    public function test_new_poi_is_approved_as_a_draft_and_photo_is_optional(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $category = $this->category();
        $municipality = $this->perugia();

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Belvedere nuovo',
            'category_id' => $category->id,
            'municipality_id' => $municipality->id,
            'latitude' => 43.11,
            'longitude' => 12.39,
            'type' => 'new_poi',
            'description' => 'Vista breve',
        ])->assertRedirect(route('contribute.mine'));

        $contribution = Contribution::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.contributions.approve', $contribution))
            ->assertRedirect();

        $poi = Poi::query()->where('name', 'Belvedere nuovo')->firstOrFail();
        $this->assertSame(PoiStatus::Draft, $poi->status);
        $this->assertSame('community', $poi->source_type);
        $this->assertSame($category->id, $poi->primary_category_id);
        $this->assertSame(ContributionStatus::Approved, $contribution->fresh()->status);
        $this->assertSame($admin->id, $contribution->fresh()->reviewed_by);
    }

    public function test_edit_approval_changes_only_the_proposed_fields(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $poi = $this->createPublishedPoi([
            'name' => 'Fontana vecchia',
            'description' => 'Testo originale',
            'slug' => 'fontana-vecchia',
        ]);

        $this->actingAs($user)->post(route('contribute.store'), [
            'type' => 'edit',
            'poi_id' => $poi->id,
            'changes' => [
                'name' => ['from' => 'Fontana vecchia', 'to' => 'Fontana nuova'],
            ],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.contributions.approve', Contribution::query()->firstOrFail()));

        $poi->refresh();
        $this->assertSame('Fontana nuova', $poi->name);
        $this->assertSame('Testo originale', $poi->description);
    }

    public function test_photo_approval_does_not_replace_the_primary_photo(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $poi = $this->createPublishedPoi(['slug' => 'con-foto']);
        $poi->photos()->create([
            'path' => 'pois/existing.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->post(route('contribute.store'), [
            'type' => 'photo',
            'poi_id' => $poi->id,
            'caption' => 'Dal basso',
            'photo' => UploadedFile::fake()->image('nuova.jpg'),
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.contributions.approve', Contribution::query()->firstOrFail()));

        $added = $poi->photos()->where('caption', 'Dal basso')->firstOrFail();
        $this->assertFalse($added->is_primary);
        $this->assertTrue($poi->photos()->where('is_primary', true)->where('path', 'pois/existing.jpg')->exists());
    }

    public function test_report_approval_does_not_change_the_poi(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $poi = $this->createPublishedPoi(['slug' => 'da-segnalare-v2', 'name' => 'Ancora aperto']);

        $this->actingAs($user)->post(route('contribute.store'), [
            'type' => 'report',
            'poi_id' => $poi->id,
            'reason' => 'closed',
            'notes' => 'Sembra chiuso',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.contributions.approve', Contribution::query()->firstOrFail()));

        $this->assertSame(PoiStatus::Published, $poi->fresh()->status);
        $this->assertSame('Ancora aperto', $poi->fresh()->name);
    }

    public function test_other_report_requires_notes(): void
    {
        $user = $this->createUser();
        $poi = $this->createPublishedPoi();

        $this->actingAs($user)->post(route('contribute.store'), [
            'type' => 'report',
            'poi_id' => $poi->id,
            'reason' => 'other',
        ])->assertSessionHasErrors('notes');
    }

    public function test_nearby_poi_is_stored_as_a_possible_duplicate(): void
    {
        $user = $this->createUser();
        $category = $this->category();
        $municipality = $this->perugia();
        $existing = $this->createPublishedPoi([
            'name' => 'Belvedere gemello',
            'slug' => 'belvedere-gemello',
            'municipality_id' => $municipality->id,
            'latitude' => 43.1101,
            'longitude' => 12.3901,
        ]);

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Belvedere gemello',
            'category_id' => $category->id,
            'municipality_id' => $municipality->id,
            'latitude' => 43.1102,
            'longitude' => 12.3902,
            'type' => 'new_poi',
        ])->assertRedirect();

        $ids = Contribution::query()->firstOrFail()->payload['possible_duplicate_ids'];
        $this->assertContains($existing->id, $ids);
    }

    public function test_unverified_user_cannot_contribute(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('contribute.store'), ['type' => 'report'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_see_their_contributions(): void
    {
        $user = $this->createUser();
        $poi = $this->createPublishedPoi(['name' => 'Spot mio', 'slug' => 'spot-mio']);

        $this->actingAs($user)->post(route('contribute.store'), [
            'type' => 'report',
            'poi_id' => $poi->id,
            'reason' => 'wrong_info',
            'notes' => 'Nome diverso',
        ]);

        $this->actingAs($user)
            ->get(route('contribute.mine'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Contribute/Mine')
                ->where('contributions.0.spot', 'Spot mio')
                ->where('contributions.0.status', 'pending'));
    }

    public function test_active_filterable_tag_is_kept_on_the_draft(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();
        $category = $this->category();
        $municipality = $this->perugia();
        $tag = Tag::query()->create([
            'name' => 'Tramonto',
            'slug' => 'tramonto',
            'is_active' => true,
            'is_filterable' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->post(route('contribute.store'), [
            'name' => 'Terrazza',
            'category_id' => $category->id,
            'municipality_id' => $municipality->id,
            'latitude' => 43.12,
            'longitude' => 12.40,
            'type' => 'new_poi',
            'tag_ids' => [$tag->id],
            'is_free' => 'yes',
        ]);

        $this->actingAs($admin)->post(route('admin.contributions.approve', Contribution::query()->firstOrFail()));

        $poi = Poi::query()->where('name', 'Terrazza')->firstOrFail();
        $this->assertTrue($poi->is_free);
        $this->assertTrue($poi->tags()->where('tags.id', $tag->id)->exists());
    }

    private function category(): Category
    {
        return Category::query()->create([
            'slug' => 'panorami',
            'name' => 'Panorami',
            'color' => '#2E7D32',
            'sort_order' => 1,
            'is_active' => true,
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
