<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Poi;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'pois_published' => Poi::query()->published()->count(),
                'pois_pending' => Poi::query()->where('status', 'pending')->count(),
                'contributions_pending' => Contribution::query()->pending()->count(),
                'events_published' => Event::query()->published()->count(),
            ],
        ]);
    }
}
