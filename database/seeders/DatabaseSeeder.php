<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\PoiStatus;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Event;
use App\Models\Poi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoPassword = env('SEED_DEMO_PASSWORD', 'password');

        $superAdmin = User::query()->updateOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'superadmin@pgspot.local')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', $demoPassword)),
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@pgspot.local')],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make(env('ADMIN_PASSWORD', $demoPassword)),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => env('USER_EMAIL', 'user@pgspot.local')],
            [
                'name' => 'Utente Demo',
                'password' => Hash::make(env('USER_PASSWORD', $demoPassword)),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ],
        );

        AppSetting::setValue('app.tagline', 'La mappa collaborativa di Perugia');
        AppSetting::setValue('app.city', 'Perugia');
        AppSetting::setValue('app.default_center', ['lat' => 43.1107, 'lng' => 12.3908, 'zoom' => 14]);

        $categories = [
            ['slug' => 'panorama', 'name' => 'Panorami', 'icon' => 'mountain', 'color' => '#2563EB', 'sort_order' => 1],
            ['slug' => 'restroom', 'name' => 'Servizi igienici', 'icon' => 'restroom', 'color' => '#059669', 'sort_order' => 2],
            ['slug' => 'food', 'name' => 'Dove mangiare', 'icon' => 'utensils', 'color' => '#EA580C', 'sort_order' => 3],
            ['slug' => 'parking', 'name' => 'Parcheggi', 'icon' => 'parking', 'color' => '#7C3AED', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }

        $panorama = Category::query()->where('slug', 'panorama')->first();
        $restroom = Category::query()->where('slug', 'restroom')->first();

        $samplePois = [
            [
                'name' => 'Piazza IV Novembre',
                'slug' => 'piazza-iv-novembre',
                'description' => 'Il cuore del centro storico con vista sulla cattedrale e sulla fontana Maggiore.',
                'latitude' => 43.1120,
                'longitude' => 12.3888,
                'address' => 'Piazza IV Novembre, Perugia',
                'categories' => [$panorama->id],
            ],
            [
                'name' => 'Porta Sole',
                'slug' => 'porta-sole',
                'description' => 'Belvedere panoramico sul cassero e sulla valle umbra.',
                'latitude' => 43.1145,
                'longitude' => 12.3865,
                'address' => 'Via del Cassero, Perugia',
                'categories' => [$panorama->id],
            ],
            [
                'name' => 'Rocca Paolina',
                'slug' => 'rocca-paolina',
                'description' => 'Fortilizio sotterraneo con punti panoramici sul centro storico.',
                'latitude' => 43.1109,
                'longitude' => 12.3901,
                'address' => 'Porta Marzia, Perugia',
                'categories' => [$panorama->id],
            ],
            [
                'name' => 'Stazione FS Perugia',
                'slug' => 'wc-stazione-fs',
                'description' => 'Servizi igienici in stazione, generalmente aperti negli orari dei treni.',
                'latitude' => 43.0612,
                'longitude' => 12.4135,
                'address' => 'Piazza Vittorio Veneto, Perugia',
                'categories' => [$restroom->id],
                'attributes' => ['free' => false, 'accessible' => true],
            ],
        ];

        foreach ($samplePois as $data) {
            $categoryIds = $data['categories'];
            unset($data['categories']);

            $poi = Poi::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    ...$data,
                    'status' => PoiStatus::Published,
                    'created_by' => $superAdmin->id,
                    'approved_by' => $superAdmin->id,
                    'approved_at' => now(),
                ],
            );

            $poi->categories()->sync($categoryIds);
        }

        Event::query()->updateOrCreate(
            ['slug' => 'benvenuto-pgspot'],
            [
                'title' => 'Benvenuto su PG Spot',
                'description' => 'Scopri panorami, servizi ed eventi di Perugia. Registrati per contribuire alla mappa!',
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'is_featured' => true,
                'status' => EventStatus::Published,
                'created_by' => $superAdmin->id,
            ],
        );
    }
}
