<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PoiStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Poi;
use App\Models\PoiPhoto;
use App\Models\Region;
use App\Models\Tag;
use App\Notifications\PoiUpdatedNotification;
use App\Services\PoiPhotoService;
use App\Support\TerritoryChain;
use App\Support\TerritoryDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PoiController extends Controller
{
    public function __construct(private PoiPhotoService $photos) {}

    public function index(Request $request): Response
    {
        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';

        $allowed = ['name', 'status', 'rating', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowed)) {
            $sort = 'name';
        }

        $pois = Poi::query()
            ->with(['categories:id,name,color', 'creator:id,name', 'photos', 'municipality:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        $pois->getCollection()->each->append('primary_photo_url');

        return Inertia::render('Admin/Pois/Index', [
            'pois' => $pois,
            'filters' => $request->only(['q', 'status', 'sort', 'dir']),
            'statuses' => collect(PoiStatus::cases())->map(fn ($s) => $s->value),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pois/Form', $this->formProps());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePoi($request);

        $poi = Poi::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'municipality_id' => $validated['municipality_id'],
            'status' => $validated['status'],
            'created_by' => $request->user()->id,
            'is_free' => $validated['is_free'],
            'accessibility' => $validated['accessibility'],
            'parking' => $validated['parking'],
            'opening_hours' => $validated['opening_hours'],
            'price' => $validated['price'],
            'website' => $validated['website'],
            'phone' => $validated['phone'],
            'source_type' => $validated['source_type'],
            'last_verified_at' => $validated['last_verified_at'],
        ]);

        $this->syncTaxonomy($poi, $validated);

        return redirect()
            ->route('admin.pois.edit', $poi)
            ->with('success', 'POI creato. Puoi aggiungere le foto.');
    }

    public function edit(Poi $poi): Response
    {
        $poi->load(['categories:id,name,slug', 'tags:id,name,slug', 'photos', 'municipality.province']);

        return Inertia::render('Admin/Pois/Form', $this->formProps($poi->append('primary_photo_url')));
    }

    public function update(Request $request, Poi $poi): RedirectResponse
    {
        $validated = $this->validatePoi($request);

        $poi->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'municipality_id' => $validated['municipality_id'],
            'status' => $validated['status'],
            'is_free' => $validated['is_free'],
            'accessibility' => $validated['accessibility'],
            'parking' => $validated['parking'],
            'opening_hours' => $validated['opening_hours'],
            'price' => $validated['price'],
            'website' => $validated['website'],
            'phone' => $validated['phone'],
            'source_type' => $validated['source_type'],
            'last_verified_at' => $validated['last_verified_at'],
        ]);

        $this->syncTaxonomy($poi, $validated);

        $poi->load('creator');
        if ($poi->creator && $poi->creator->id !== $request->user()->id && $poi->creator->wantsPoiUpdateNotifications()) {
            $poi->creator->notify(new PoiUpdatedNotification($poi));
        }

        return redirect()->route('admin.pois.index')->with('success', 'POI aggiornato.');
    }

    public function destroy(Poi $poi): RedirectResponse
    {
        $photos = $poi->photos()->get();
        foreach ($photos as $photo) {
            $this->photos->delete($photo);
        }

        $poi->delete();

        return redirect()->route('admin.pois.index')->with('success', 'POI eliminato.');
    }

    public function storePhoto(Request $request, Poi $poi): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'is_primary' => 'boolean',
        ]);

        $this->photos->storeForPoi(
            $poi,
            $request->file('photo'),
            $request->boolean('is_primary'),
            $request->user(),
        );

        return back()->with('success', 'Foto caricata.');
    }

    public function destroyPhoto(Poi $poi, PoiPhoto $photo): RedirectResponse
    {
        abort_unless($photo->poi_id === $poi->id, 404);

        $this->photos->delete($photo);

        return back()->with('success', 'Foto eliminata.');
    }

    public function setPrimaryPhoto(Poi $poi, PoiPhoto $photo): RedirectResponse
    {
        abort_unless($photo->poi_id === $poi->id, 404);

        $this->photos->setPrimary($photo);

        return back()->with('success', 'Foto principale aggiornata.');
    }

    /** @return array<string, mixed> */
    private function formProps(?Poi $poi = null): array
    {
        $defaults = TerritoryDefaults::get();

        $currentCategoryIds = $poi?->categories?->pluck('id') ?? collect();
        $categories = Category::query()
            ->where(function ($query) use ($currentCategoryIds) {
                $query->where(fn ($active) => $active->where('is_active', true)->where('slug', '!=', 'instagram-spot'));
                if ($currentCategoryIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentCategoryIds);
                }
            })
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $currentTagIds = $poi?->relationLoaded('tags') ? $poi->tags->pluck('id') : collect();
        $tags = Tag::query()
            ->where(function ($query) use ($currentTagIds) {
                $query->where('is_active', true);
                if ($currentTagIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentTagIds);
                }
            })
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'is_active']);

        return [
            'poi' => $poi,
            'categories' => $categories,
            'tags' => $tags,
            'statuses' => collect(PoiStatus::cases())->map(fn ($s) => $s->value),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'territory' => [
                'region_id' => $poi?->municipality?->province?->region_id ?? $defaults['region_id'],
                'province_id' => $poi?->municipality?->province_id ?? $defaults['province_id'],
                'municipality_id' => $poi?->municipality_id ?? $defaults['municipality_id'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function validatePoi(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:'.implode(',', array_column(PoiStatus::cases(), 'value')),
            'primary_category_id' => 'nullable|integer|exists:categories,id',
            'secondary_category_ids' => 'array',
            'secondary_category_ids.*' => 'integer|exists:categories,id',
            'category_ids' => 'array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'is_free' => 'nullable|in:0,1',
            'accessibility' => 'nullable|in:unknown,yes,partial,no',
            'parking' => 'nullable|in:unknown,none,nearby,on_site,paid',
            'opening_hours' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40',
            'source_type' => 'nullable|in:editorial,community,osm',
            'last_verified_at' => 'nullable|date',
            'region_id' => 'required|integer|exists:regions,id',
            'province_id' => 'required|integer|exists:provinces,id',
            'municipality_id' => 'required|integer|exists:municipalities,id',
        ], [
            'municipality_id.required' => 'Seleziona un comune.',
            'region_id.required' => 'Seleziona una regione.',
            'province_id.required' => 'Seleziona una provincia.',
        ]);

        $poi = $request->route('poi');
        $keepingCurrent = $poi instanceof Poi
            && (int) $poi->municipality_id === (int) $validated['municipality_id'];

        $chainError = TerritoryChain::message(
            (int) $validated['region_id'],
            (int) $validated['province_id'],
            (int) $validated['municipality_id'],
            requireActive: ! $keepingCurrent,
        );
        if ($chainError) {
            throw ValidationException::withMessages($chainError);
        }

        $currentIds = $poi instanceof Poi ? $poi->categories()->pluck('categories.id') : collect();
        $allowedCategories = Category::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'instagram-spot')
            ->pluck('id')
            ->merge($currentIds);
        $requestedCategories = collect($validated['secondary_category_ids'] ?? $validated['category_ids'] ?? [])
            ->map(fn ($id) => (int) $id);
        if (! empty($validated['primary_category_id'])) {
            $requestedCategories->push((int) $validated['primary_category_id']);
        }
        if ($requestedCategories->unique()->diff($allowedCategories)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'primary_category_id' => 'La categoria non è disponibile per i nuovi luoghi.',
            ]);
        }

        if (array_key_exists('tag_ids', $validated)) {
            $currentTags = $poi instanceof Poi ? $poi->tags()->pluck('tags.id') : collect();
            $allowedTags = Tag::query()->where('is_active', true)->pluck('id')->merge($currentTags);
            $invalidTags = collect($validated['tag_ids'])->map(fn ($id) => (int) $id)->diff($allowedTags);
            if ($invalidTags->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'tag_ids' => 'Una caratteristica non è selezionabile.',
                ]);
            }
        }

        $validated['is_free'] = array_key_exists('is_free', $validated)
            ? match ($validated['is_free']) {
                '1', 1, true => true,
                '0', 0, false => false,
                default => null,
            }
        : ($poi instanceof Poi ? $poi->is_free : null);
        $validated['accessibility'] = array_key_exists('accessibility', $validated)
            ? ($validated['accessibility'] ?: 'unknown')
            : ($poi instanceof Poi ? $poi->accessibility : 'unknown');
        $validated['parking'] = array_key_exists('parking', $validated)
            ? ($validated['parking'] ?: 'unknown')
            : ($poi instanceof Poi ? $poi->parking : 'unknown');
        $validated['price'] = array_key_exists('price', $validated)
            ? $validated['price']
            : ($poi instanceof Poi ? $poi->price : null);
        $validated['website'] = array_key_exists('website', $validated)
            ? $validated['website']
            : ($poi instanceof Poi ? $poi->website : null);
        $validated['phone'] = array_key_exists('phone', $validated)
            ? $validated['phone']
            : ($poi instanceof Poi ? $poi->phone : null);
        $validated['source_type'] = array_key_exists('source_type', $validated)
            ? $validated['source_type']
            : ($poi instanceof Poi ? $poi->source_type : null);
        $validated['last_verified_at'] = array_key_exists('last_verified_at', $validated)
            ? $validated['last_verified_at']
            : ($poi instanceof Poi ? $poi->last_verified_at : null);
        if (array_key_exists('opening_hours', $validated)) {
            $hours = trim((string) ($validated['opening_hours'] ?? ''));
            $validated['opening_hours'] = $hours === '' ? null : ['text' => $hours];
        } else {
            $validated['opening_hours'] = $poi instanceof Poi ? $poi->opening_hours : null;
        }

        return $validated;
    }

    /** @param  array<string, mixed>  $validated */
    private function syncTaxonomy(Poi $poi, array $validated): void
    {
        $hasCategories = array_key_exists('primary_category_id', $validated)
            || array_key_exists('secondary_category_ids', $validated)
            || array_key_exists('category_ids', $validated);

        if ($hasCategories) {
            $primary = isset($validated['primary_category_id']) ? (int) $validated['primary_category_id'] : null;
            $secondary = $validated['secondary_category_ids'] ?? $validated['category_ids'] ?? [];
            $poi->syncPlaceCategories($primary ?: null, $secondary);
        }

        if (array_key_exists('tag_ids', $validated)) {
            $poi->tags()->sync($validated['tag_ids']);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'luogo';
        $slug = $base.'-'.Str::lower(Str::random(4));

        while (Poi::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return Str::limit($slug, 255, '');
    }
}
