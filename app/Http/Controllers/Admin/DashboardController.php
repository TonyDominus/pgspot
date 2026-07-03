<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SponsorshipPlacement;
use App\Enums\SponsorshipType;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Poi;
use App\Models\Sponsorship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $activeSponsorships = Sponsorship::query()->currentlyActive()->count();
        $totalRevenue = Sponsorship::query()->sum('amount_paid');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'pois_published' => Poi::query()->published()->count(),
                'pois_pending' => Poi::query()->where('status', 'pending')->count(),
                'contributions_pending' => Contribution::query()->pending()->count(),
                'events_published' => Event::query()->published()->count(),
                'sponsorships_active' => $activeSponsorships,
                'sponsorships_total' => Sponsorship::query()->count(),
                'sponsorships_revenue' => $totalRevenue,
            ],
        ]);
    }
}
