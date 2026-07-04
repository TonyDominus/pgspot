<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\PoiStatus;
use App\Enums\SponsorshipPlacement;
use App\Enums\SponsorshipType;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Event;
use App\Models\Poi;
use App\Models\Sponsorship;
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
        AppSetting::setValue('site.paypal', ['url' => 'https://paypal.me/pgspot']);
        AppSetting::setValue('site.social', [
            'instagram' => 'https://instagram.com/pgspot',
            'facebook' => 'https://facebook.com/pgspot',
        ]);
        AppSetting::setValue('site.contact', ['email' => 'info@pgspot.it']);

        $categories = [
            ['slug' => 'panorami', 'name' => 'Panorami', 'icon' => 'panorama', 'color' => '#2E7D32', 'sort_order' => 1],
            ['slug' => 'bagni', 'name' => 'Bagni', 'icon' => 'restroom', 'color' => '#00ACC1', 'sort_order' => 2],
            ['slug' => 'fontanelle', 'name' => 'Fontanelle', 'icon' => 'fountain', 'color' => '#1E88E5', 'sort_order' => 3],
            ['slug' => 'parcheggi', 'name' => 'Parcheggi', 'icon' => 'parking', 'color' => '#FFB300', 'sort_order' => 4],
            ['slug' => 'instagram-spot', 'name' => 'Instagram Spot', 'icon' => 'camera', 'color' => '#8E24AA', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }

        Category::query()
            ->whereNotIn('slug', collect($categories)->pluck('slug'))
            ->update(['is_active' => false]);

        $cats = Category::query()->whereIn('slug', collect($categories)->pluck('slug'))->pluck('id', 'slug');

        $samplePois = [
            [
                'name' => 'Belvedere di Porta Sole',
                'slug' => 'belvedere-porta-sole',
                'description' => 'Vista spettacolare su tutta Perugia e la valle umbra. Ideale al tramonto.',
                'latitude' => 43.1145,
                'longitude' => 12.3865,
                'address' => 'Via del Cassero, Perugia',
                'rating' => 4.8,
                'categories' => ['panorami'],
                'attributes' => ['tags' => ['Vista città', 'Tramonto', 'Accessibile'], 'free' => true, 'sunset' => true, 'city_view' => true],
            ],
            [
                'name' => 'Piazza IV Novembre',
                'slug' => 'piazza-iv-novembre',
                'description' => 'Il cuore del centro storico con vista sulla cattedrale e sulla fontana Maggiore.',
                'latitude' => 43.1120,
                'longitude' => 12.3888,
                'address' => 'Piazza IV Novembre, Perugia',
                'rating' => 4.9,
                'categories' => ['panorami', 'instagram-spot'],
                'attributes' => ['tags' => ['Vista città', 'Foto'], 'free' => true],
            ],
            [
                'name' => 'Rocca Paolina',
                'slug' => 'rocca-paolina',
                'description' => 'Fortilizio sotterraneo con punti panoramici sul centro storico.',
                'latitude' => 43.1109,
                'longitude' => 12.3901,
                'address' => 'Porta Marzia, Perugia',
                'rating' => 4.6,
                'categories' => ['panorami'],
                'attributes' => ['tags' => ['Vista città', 'Accessibile'], 'free' => false],
            ],
            [
                'name' => 'Giardini del Frontone',
                'slug' => 'giardini-frontone',
                'description' => 'Ampio parco con vista sulla città, perfetto per una pausa panoramica.',
                'latitude' => 43.1088,
                'longitude' => 12.3842,
                'address' => 'Viale Indipendenza, Perugia',
                'rating' => 4.5,
                'categories' => ['panorami', 'instagram-spot'],
                'attributes' => ['tags' => ['Vista città', 'Romantico', 'Foto'], 'free' => true],
            ],
            [
                'name' => 'WC Stazione FS',
                'slug' => 'wc-stazione-fs',
                'description' => 'Servizi igienici in stazione, generalmente aperti negli orari dei treni.',
                'latitude' => 43.0612,
                'longitude' => 12.4135,
                'address' => 'Piazza Vittorio Veneto, Perugia',
                'rating' => 3.5,
                'categories' => ['bagni'],
                'attributes' => ['tags' => ['Accessibile'], 'free' => false, 'accessible' => true],
            ],
            [
                'name' => 'WC Piazza IV Novembre',
                'slug' => 'wc-piazza-iv-novembre',
                'description' => 'Servizi pubblici nel sottopasso, comodi per il centro storico.',
                'latitude' => 43.1118,
                'longitude' => 12.3890,
                'address' => 'Piazza IV Novembre, Perugia',
                'rating' => 3.2,
                'categories' => ['bagni'],
                'attributes' => ['tags' => ['Gratuito'], 'free' => true],
            ],
            [
                'name' => 'Fontanella Piazza Matteotti',
                'slug' => 'fontanella-matteotti',
                'description' => 'Acqua potabile fresca, punto comodo nel centro.',
                'latitude' => 43.1095,
                'longitude' => 12.3875,
                'address' => 'Piazza Matteotti, Perugia',
                'rating' => 4.2,
                'categories' => ['fontanelle'],
                'attributes' => ['tags' => ['Gratuito'], 'free' => true],
            ],
            [
                'name' => 'Parcheggio Piazza Multa',
                'slug' => 'parcheggio-piazza-multa',
                'description' => 'Parcheggio coperto vicino al centro storico.',
                'latitude' => 43.1075,
                'longitude' => 12.3920,
                'address' => 'Piazza Multa, Perugia',
                'rating' => 3.8,
                'categories' => ['parcheggi'],
                'attributes' => ['tags' => ['Con parcheggio'], 'free' => false],
            ],
            [
                'name' => 'Scalinata di Sant\'Ercolano',
                'slug' => 'scalianta-ercolano',
                'description' => 'Scalinata iconica perfetta per scatti Instagram con vista sul centro.',
                'latitude' => 43.1112,
                'longitude' => 12.3915,
                'address' => 'Via Sant\'Ercolano, Perugia',
                'rating' => 4.7,
                'categories' => ['instagram-spot'],
                'attributes' => ['tags' => ['Foto', 'Vista città'], 'free' => true],
            ],
        ];

        foreach ($samplePois as $data) {
            $categorySlugs = $data['categories'];
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

            $poi->categories()->sync(
                collect($categorySlugs)->map(fn ($slug) => $cats[$slug])->all(),
            );
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

        $portaSole = Poi::query()->where('slug', 'belvedere-porta-sole')->first();

        if ($portaSole) {
            Sponsorship::query()->updateOrCreate(
                ['title' => 'Demo Caffè Panorama'],
                [
                    'description' => 'Terrazza panoramica con vista su Perugia',
                    'type' => SponsorshipType::Card,
                    'partner_name' => 'Caffè Panorama',
                    'amount_paid' => 150.00,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonths(2),
                    'is_active' => true,
                    'poi_id' => $portaSole->id,
                    'placement' => SponsorshipPlacement::HomeSheet,
                    'created_by' => $superAdmin->id,
                ],
            );
        }

        $poiIdsBySlug = Poi::query()->pluck('id', 'slug');

        \App\Models\Itinerary::query()->updateOrCreate(
            ['slug' => 'perugia-2-ore'],
            [
                'title' => 'Perugia in 2 ore',
                'description' => 'Panorami imperdibili del centro storico: dalla Rocca Paolina al Belvedere di Porta Sole.',
                'duration' => '2h',
                'poi_ids' => array_values(array_filter([
                    $poiIdsBySlug['rocca-paolina'] ?? null,
                    $poiIdsBySlug['piazza-iv-novembre'] ?? null,
                    $poiIdsBySlug['scalianta-ercolano'] ?? null,
                    $poiIdsBySlug['belvedere-porta-sole'] ?? null,
                    $poiIdsBySlug['giardini-frontone'] ?? null,
                ])),
                'sort_order' => 1,
            ],
        );

        \App\Models\Itinerary::query()->updateOrCreate(
            ['slug' => 'tramonto-centro'],
            [
                'title' => 'Tramonto sul centro',
                'description' => 'I migliori punti per il golden hour con vista sulla città.',
                'duration' => '1.5h',
                'poi_ids' => array_values(array_filter([
                    $poiIdsBySlug['giardini-frontone'] ?? null,
                    $poiIdsBySlug['belvedere-porta-sole'] ?? null,
                    $poiIdsBySlug['piazza-iv-novembre'] ?? null,
                    $poiIdsBySlug['scalianta-ercolano'] ?? null,
                ])),
                'sort_order' => 2,
            ],
        );
    }
}
