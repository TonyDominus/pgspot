<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Poi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ItineraryController extends Controller
{
    public function index(): Response
    {
        $itineraries = Itinerary::query()->orderBy('sort_order')->paginate(20);

        return Inertia::render('Admin/Itineraries/Index', [
            'itineraries' => $itineraries,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Itineraries/Form', [
            'itinerary' => null,
            'pois' => Poi::query()->published()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Itinerary::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['title']).'-'.Str::random(4),
        ]);

        return redirect()->route('admin.itineraries.index')->with('success', 'Itinerario creato.');
    }

    public function edit(Itinerary $itinerary): Response
    {
        return Inertia::render('Admin/Itineraries/Form', [
            'itinerary' => $itinerary,
            'pois' => Poi::query()->published()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, Itinerary $itinerary): RedirectResponse
    {
        $itinerary->update($this->validated($request));

        return redirect()->route('admin.itineraries.index')->with('success', 'Itinerario aggiornato.');
    }

    public function destroy(Itinerary $itinerary): RedirectResponse
    {
        $itinerary->delete();

        return redirect()->route('admin.itineraries.index')->with('success', 'Itinerario eliminato.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'duration' => 'nullable|string|max:50',
            'poi_ids' => 'nullable|array',
            'poi_ids.*' => 'integer|exists:pois,id',
            'is_published' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_published'] = $request->boolean('is_published', true);
        $validated['poi_ids'] = array_values($validated['poi_ids'] ?? []);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
