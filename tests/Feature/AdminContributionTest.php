<?php

namespace Tests\Feature;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Contribution;
use Tests\TestCase;

class AdminContributionTest extends TestCase
{
    public function test_admin_can_view_contributions_index(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        Contribution::query()->create([
            'user_id' => $user->id,
            'type' => ContributionType::NewPoi,
            'payload' => ['name' => 'Nuovo luogo', 'latitude' => 43.11, 'longitude' => 12.39],
            'status' => ContributionStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.contributions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Contributions/Index')
            ->has('contributions.data', 1));
    }

    public function test_regular_user_cannot_access_contributions_moderation(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('admin.contributions.index'))->assertForbidden();
    }
}
