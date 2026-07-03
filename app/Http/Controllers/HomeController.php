<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Event;
use App\Services\PoiListingService;
use App\Services\SponsorshipService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private PoiListingService $listing,
        private SponsorshipService $sponsorships,
    ) {}

    public function index(Request $request): Response
    {
        $featuredEvents = Event::query()
            ->published()
            ->featured()
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'description', 'starts_at', 'image']);

        return Inertia::render('Home', [
            ...$this->listing->getPageData($request),
            'mapCenter' => AppSetting::getValue('app.default_center', ['lat' => 43.1107, 'lng' => 12.3908, 'zoom' => 14]),
            'featuredEvents' => $featuredEvents,
            'sponsorships' => $this->sponsorships->activeForPlacement('home_sheet'),
            'featuredSponsorships' => $this->sponsorships->activeForPlacement('home_list'),
            'canContribute' => (bool) $request->user(),
        ]);
    }

    public function filters(Request $request): Response
    {
        return Inertia::render('Filters', [
            ...$this->listing->getPageData($request),
            'characteristicTags' => [
                'Vista città', 'Tramonto', 'Alba', 'Accessibile', 'Con parcheggio',
                'Gratuito', 'Vista lago', 'Romantico', 'Foto',
            ],
        ]);
    }

    public function favorites(Request $request): Response
    {
        $user = $request->user();

        $pois = $user
            ? $user->favoritePois()->with('categories:id,slug,name,color,icon')->get()
            : collect();

        return Inertia::render('Favorites', [
            'pois' => $pois,
            'mapCenter' => AppSetting::getValue('app.default_center'),
        ]);
    }

    public function routes(): Response
    {
        return Inertia::render('Routes', [
            'routes' => [
                [
                    'title' => 'Perugia in 2 ore',
                    'description' => 'Panorami imperdibili del centro storico',
                    'stops' => 5,
                    'duration' => '2h',
                ],
                [
                    'title' => 'Tramonto sul centro',
                    'description' => 'I migliori punti per il golden hour',
                    'stops' => 4,
                    'duration' => '1.5h',
                ],
            ],
        ]);
    }
}
