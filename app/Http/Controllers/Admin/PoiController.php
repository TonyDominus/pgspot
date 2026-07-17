<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PoiStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Poi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PoiController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';

        $allowed = ['name', 'status', 'rating', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowed)) {
            $sort = 'name';
        }

        $pois = Poi::query()
            ->with(['categories:id,name,color', 'creator:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Pois/Index', [
            'pois' => $pois,
            'filters' => $request->only(['q', 'status', 'sort', 'dir']),
            'statuses' => collect(PoiStatus::cases())->map(fn ($s) => $s->value),
        ]);
    }

    public function edit(Poi $poi): Response
    {
        $poi->load('categories:id');

        return Inertia::render('Admin/Pois/Form', [
            'poi' => $poi,
            'categories' => Category::query()->active()->get(['id', 'name', 'slug']),
            'statuses' => collect(PoiStatus::cases())->map(fn ($s) => $s->value),
        ]);
    }

    public function update(Request $request, Poi $poi): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:'.implode(',', array_column(PoiStatus::cases(), 'value')),
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $poi->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        $poi->categories()->sync($validated['category_ids'] ?? []);

        return redirect()->route('admin.pois.index')->with('success', 'POI aggiornato.');
    }

    public function destroy(Poi $poi): RedirectResponse
    {
        $poi->delete();

        return redirect()->route('admin.pois.index')->with('success', 'POI eliminato.');
    }
}
