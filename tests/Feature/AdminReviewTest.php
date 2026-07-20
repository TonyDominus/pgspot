<?php

namespace Tests\Feature;

use App\Models\Review;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    public function test_admin_can_delete_review_and_poi_rating_is_updated(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $poi = $this->createPublishedPoi();

        Review::query()->create([
            'user_id' => $user->id,
            'poi_id' => $poi->id,
            'rating' => 5,
            'comment' => 'Bellissimo',
        ]);

        $poi->update(['rating' => 5, 'review_count' => 1]);

        $review = Review::query()->first();

        $response = $this->actingAs($admin)->delete(route('admin.reviews.destroy', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        $poi->refresh();
        $this->assertSame(0, $poi->review_count);
        $this->assertSame('0.00', $poi->rating);
    }

    public function test_regular_user_cannot_access_review_moderation(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('admin.reviews.index'))->assertForbidden();
    }
}
