<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Category;
use App\Models\Contribution;
use App\Services\PoiPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function __construct(private PoiPhotoService $photos) {}

    public function create(): Response
    {
        return Inertia::render('Contribute/Create', [
            'categories' => Category::query()->active()->get(['id', 'slug', 'name', 'color', 'icon']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'new_poi');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
            'type' => 'nullable|in:new_poi,report',
            'photo' => 'required_if:type,new_poi|nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'extra_photos' => 'nullable|array|max:5',
            'extra_photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $payload = collect($validated)->except(['photo', 'extra_photos'])->all();

        if ($type === 'new_poi' && $request->hasFile('photo')) {
            $payload['photo_path'] = $this->photos->storePendingContribution($request->file('photo'));
        }

        if ($type === 'new_poi' && $request->hasFile('extra_photos')) {
            $payload['extra_photo_paths'] = collect($request->file('extra_photos'))
                ->map(fn ($file) => $this->photos->storePendingContribution($file))
                ->all();
        }

        Contribution::query()->create([
            'user_id' => $request->user()->id,
            'type' => ContributionType::from($type),
            'status' => ContributionStatus::Pending,
            'payload' => $payload,
        ]);

        return redirect()->route('home')->with('success', 'Proposta inviata! Verrà revisionata dal team.');
    }
}
