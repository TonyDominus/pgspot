<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Category;
use App\Models\Contribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contribute/Create', [
            'categories' => Category::query()->active()->get(['id', 'slug', 'name', 'color', 'icon']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
            'type' => 'nullable|in:new_poi,report',
        ]);

        Contribution::query()->create([
            'user_id' => $request->user()->id,
            'type' => ContributionType::from($validated['type'] ?? 'new_poi'),
            'status' => ContributionStatus::Pending,
            'payload' => $validated,
        ]);

        return redirect()->route('home')->with('success', 'Proposta inviata! Verrà revisionata dal team.');
    }
}
