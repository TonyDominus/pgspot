<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItineraryStatus;
use App\Enums\PoiStatus;
use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Poi;
use App\Services\ItineraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItineraryController extends Controller
{
    public function __construct(private ItineraryService $itineraries) {}

    public function index(Request $request): Response
    {
        $itineraries = Itinerary::query()
            ->with(['pois.municipality.province.region'])
            ->withCount('pois')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $itineraries->getCollection()->transform(function (Itinerary $itinerary) {
            $itinerary->stop_count = $itinerary->pois_count;
            $itinerary->territory = $this->itineraries->territoryLabel($itinerary);
            $itinerary->has_unpublished_stops = $itinerary->pois->contains(
                fn (Poi $poi) => $poi->status !== PoiStatus::Published
            );

            return $itinerary;
        });

        return Inertia::render('Admin/Itineraries/Index', [
            'itineraries' => $itineraries,
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Itineraries/Form', [
            'itinerary' => null,
            'stops' => [],
            'statuses' => collect(ItineraryStatus::cases())->map->value,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $stops = $validated['stops'] ?? [];
        unset($validated['stops'], $validated['cover']);

        $itinerary = Itinerary::query()->create($validated);
        $this->storeCover($request, $itinerary);
        $this->itineraries->syncStops($itinerary, $stops);

        if ($itinerary->status === ItineraryStatus::Published) {
            $errors = $this->itineraries->publishErrors($itinerary->fresh('pois'));
            if ($errors !== []) {
                $itinerary->update(['status' => ItineraryStatus::Draft]);

                return redirect()
                    ->route('admin.itineraries.edit', $itinerary)
                    ->with('error', implode(' ', $errors));
            }
        }

        return redirect()->route('admin.itineraries.index')->with('success', 'Itinerario creato.');
    }

    public function edit(Itinerary $itinerary): Response
    {
        $itinerary->load(['pois.primaryCategory:id,name,slug', 'pois.municipality:id,name', 'pois.photos' => fn ($photos) => $photos->where('is_primary', true)]);

        return Inertia::render('Admin/Itineraries/Form', [
            'itinerary' => $itinerary,
            'stops' => $itinerary->pois->map(fn (Poi $poi) => [
                'id' => $poi->id,
                'name' => $poi->name,
                'slug' => $poi->slug,
                'status' => $poi->status->value,
                'municipality' => $poi->municipality?->name,
                'primary_category' => $poi->primaryCategory?->name,
                'note' => $poi->pivot->note,
                'unpublished' => $poi->status !== PoiStatus::Published,
            ])->values(),
            'statuses' => collect(ItineraryStatus::cases())->map->value,
            'cover_url' => $itinerary->cover_path ? Storage::disk('public')->url($itinerary->cover_path) : null,
            'has_unpublished_stops' => $itinerary->pois->contains(fn (Poi $poi) => $poi->status !== PoiStatus::Published),
        ]);
    }

    public function update(Request $request, Itinerary $itinerary): RedirectResponse
    {
        $validated = $this->validated($request, $itinerary);
        $stops = $validated['stops'] ?? [];
        unset($validated['stops'], $validated['cover']);

        $itinerary->fill($validated);
        $this->itineraries->syncStops($itinerary, $stops);
        $itinerary->load('pois');

        if ($itinerary->status === ItineraryStatus::Published) {
            $errors = $this->itineraries->publishErrors($itinerary);
            if ($errors !== []) {
                return back()->withErrors(['status' => implode(' ', $errors)])->withInput();
            }
        }

        $itinerary->save();
        $this->storeCover($request, $itinerary);

        return redirect()->route('admin.itineraries.index')->with('success', 'Itinerario aggiornato.');
    }

    public function destroy(Itinerary $itinerary): RedirectResponse
    {
        if ($itinerary->cover_path) {
            Storage::disk('public')->delete($itinerary->cover_path);
        }
        $itinerary->delete();

        return redirect()->route('admin.itineraries.index')->with('success', 'Itinerario eliminato.');
    }

    public function searchPois(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        if (mb_strlen($term) < 2) {
            return response()->json(['pois' => []]);
        }

        $like = '%'.$term.'%';
        $pois = Poi::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhereHas('municipality', fn ($municipality) => $municipality->where('name', 'like', $like))
                    ->orWhereHas('primaryCategory', fn ($category) => $category->where('name', 'like', $like));
            })
            ->whereIn('status', [PoiStatus::Published, PoiStatus::Draft])
            ->with([
                'primaryCategory:id,name,slug',
                'municipality:id,name',
                'photos' => fn ($photos) => $photos->where('is_primary', true),
            ])
            ->orderByRaw('case when status = ? then 0 else 1 end', [PoiStatus::Published->value])
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'status', 'primary_category_id', 'municipality_id']);

        return response()->json([
            'pois' => $pois->map(fn (Poi $poi) => [
                'id' => $poi->id,
                'name' => $poi->name,
                'slug' => $poi->slug,
                'status' => $poi->status->value,
                'municipality' => $poi->municipality?->name,
                'primary_category' => $poi->primaryCategory?->name,
                'primary_photo_url' => $poi->append('primary_photo_url')->primary_photo_url,
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Itinerary $itinerary = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('itineraries', 'slug')->ignore($itinerary?->id),
            ],
            'excerpt' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'duration' => 'nullable|string|max:50',
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:10080',
            'estimated_distance_km' => 'nullable|numeric|min:0|max:9999',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'status' => ['required', Rule::enum(ItineraryStatus::class)],
            'sort_order' => 'nullable|integer|min:0',
            'cover' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'stops' => 'nullable|array',
            'stops.*.poi_id' => 'required|integer|exists:pois,id',
            'stops.*.note' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']).($itinerary ? '' : '-'.Str::random(4));
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['status'] = ItineraryStatus::from($validated['status']);

        return $validated;
    }

    private function storeCover(Request $request, Itinerary $itinerary): void
    {
        if (! $request->hasFile('cover')) {
            return;
        }
        if ($itinerary->cover_path) {
            Storage::disk('public')->delete($itinerary->cover_path);
        }
        $itinerary->update([
            'cover_path' => $request->file('cover')->store('itineraries/'.$itinerary->id, 'public'),
        ]);
    }
}
