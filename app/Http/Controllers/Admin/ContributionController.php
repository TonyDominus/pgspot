<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Enums\PoiStatus;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Poi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function index(Request $request): Response
    {
        $contributions = Contribution::query()
            ->with(['user:id,name,email', 'poi:id,name,slug'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Contributions/Index', [
            'contributions' => $contributions,
            'filters' => $request->only('status'),
            'pendingCount' => Contribution::query()->pending()->count(),
        ]);
    }

    public function approve(Request $request, Contribution $contribution): RedirectResponse
    {
        if ($contribution->status !== ContributionStatus::Pending) {
            return back()->with('error', 'Contributo già elaborato.');
        }

        $payload = $contribution->payload;

        if ($contribution->type === ContributionType::NewPoi) {
            $poi = Poi::query()->create([
                'name' => $payload['name'],
                'slug' => Str::slug($payload['name']).'-'.Str::random(4),
                'description' => $payload['description'] ?? null,
                'latitude' => $payload['latitude'],
                'longitude' => $payload['longitude'],
                'status' => PoiStatus::Published,
                'created_by' => $contribution->user_id,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            if (! empty($payload['category_id'])) {
                $poi->categories()->sync([$payload['category_id']]);
            }
        }

        $contribution->update([
            'status' => ContributionStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Contributo approvato.');
    }

    public function reject(Request $request, Contribution $contribution): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $contribution->update([
            'status' => ContributionStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Contributo rifiutato.');
    }
}
