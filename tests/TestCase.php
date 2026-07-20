<?php

namespace Tests;

use App\Enums\EventStatus;
use App\Enums\PoiStatus;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Poi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    protected function createSuperAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    protected function createPublishedPoi(array $attributes = []): Poi
    {
        return Poi::query()->create(array_merge([
            'name' => 'Panorama Test',
            'slug' => 'panorama-test-'.uniqid(),
            'description' => 'Descrizione test',
            'latitude' => 43.1107,
            'longitude' => 12.3908,
            'status' => PoiStatus::Published,
            'rating' => 0,
            'review_count' => 0,
        ], $attributes));
    }

    protected function createPublishedEvent(array $attributes = []): Event
    {
        return Event::query()->create(array_merge([
            'title' => 'Evento Test',
            'slug' => 'evento-test-'.uniqid(),
            'description' => 'Descrizione evento',
            'starts_at' => now()->addDay(),
            'status' => EventStatus::Published,
            'is_featured' => false,
        ], $attributes));
    }

    protected function setEventsPublic(bool $enabled = true): void
    {
        AppSetting::setValue('features.events_public', $enabled);
    }
}
