<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Support\SiteFeatures;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    public function test_events_page_is_available_when_feature_enabled(): void
    {
        $this->setEventsPublic(true);
        $this->createPublishedEvent(['title' => 'Mercato in piazza']);

        $response = $this->get('/eventi');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('events', 1)
            ->where('events.0.title', 'Mercato in piazza'));
    }

    public function test_events_page_returns_not_found_when_feature_disabled(): void
    {
        AppSetting::setValue('features.events_public', false);

        $this->assertFalse(SiteFeatures::eventsPublicEnabled());

        $this->get('/eventi')->assertNotFound();
    }
}
