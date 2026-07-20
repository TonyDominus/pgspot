<?php

namespace Tests\Feature;

use App\Models\Review;
use Tests\TestCase;

class PoiReviewTest extends TestCase
{
    public function test_verified_user_can_submit_review(): void
    {
        $user = $this->createUser();
        $poi = $this->createPublishedPoi();

        $response = $this->actingAs($user)->post(route('poi.reviews.store', $poi->slug), [
            'rating' => 4,
            'comment' => 'Ottimo panorama',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'poi_id' => $poi->id,
            'rating' => 4,
        ]);

        $poi->refresh();
        $this->assertSame(1, $poi->review_count);
    }

    public function test_unverified_user_cannot_submit_review(): void
    {
        $user = $this->createUser(['email_verified_at' => null]);
        $poi = $this->createPublishedPoi();

        $this->actingAs($user)->post(route('poi.reviews.store', $poi->slug), [
            'rating' => 4,
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_submission_is_throttled(): void
    {
        $user = $this->createUser();
        $poi = $this->createPublishedPoi();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('poi.reviews.store', $poi->slug), [
                'rating' => 4,
            ])->assertRedirect();
        }

        $this->actingAs($user)->post(route('poi.reviews.store', $poi->slug), [
            'rating' => 3,
        ])->assertStatus(429);
    }
}
